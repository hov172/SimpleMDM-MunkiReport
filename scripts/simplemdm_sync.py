#!/usr/bin/env python3
"""
SimpleMDM Sync Script for MunkiReport

This script fetches device data from the SimpleMDM API and submits it to
MunkiReport's SimpleMDM module processor endpoint.

Configuration:
    Set the following environment variables or pass as arguments:
    - SIMPLEMDM_API_KEY: Your SimpleMDM API key
    - MUNKIREPORT_URL: Your MunkiReport base URL (e.g., https://munkireport.example.com)
    - MUNKIREPORT_TOKEN: (Optional) MunkiReport API token if required

Usage:
    python3 simplemdm_sync.py
    python3 simplemdm_sync.py --api-key YOUR_KEY --munkireport-url https://mr.example.com

Recommended: Run as a cron job every 15-30 minutes on the MunkiReport server.
    */15 * * * * /usr/bin/python3 /path/to/simplemdm_sync.py
"""

import argparse
import json
import logging
import os
import sys
import time
import urllib.request
import urllib.error
import urllib.parse
import base64
import ssl
from datetime import datetime, timezone

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger('simplemdm_sync')
REQUEST_TIMEOUT = 30
REQUEST_RETRIES = 3
REQUEST_BACKOFF_SECONDS = 2
REQUEST_METRICS = {
    'api_requests': 0,
    'api_errors': 0,
    'rate_limit_hits': 0,
}
ENDPOINT_SUPPORT_CACHE = {}

SIMPLEMDM_API_BASE = 'https://a.simplemdm.com/api/v1'
# Keep these lists aligned to documented SimpleMDM GET endpoints to avoid
# probing unsupported routes and inflating sync error telemetry.
RESOURCE_ENDPOINTS = [
    'device_groups',
    'assignment_groups',
    'profiles',
    'apps',
    'custom_attributes',
    'scripts',
    'enrollments',
]
SUBRESOURCE_MAP = {
    'apps': ['installs', 'managed_configs'],
}


def get_config():
    """Parse command line arguments and environment variables."""
    parser = argparse.ArgumentParser(description='Sync SimpleMDM data to MunkiReport')
    parser.add_argument(
        '--api-key',
        default=os.environ.get('SIMPLEMDM_API_KEY', ''),
        help='SimpleMDM API key (or set SIMPLEMDM_API_KEY env var)'
    )
    parser.add_argument(
        '--munkireport-url',
        default=os.environ.get('MUNKIREPORT_URL', ''),
        help='MunkiReport base URL (or set MUNKIREPORT_URL env var)'
    )
    parser.add_argument(
        '--munkireport-token',
        default=os.environ.get('MUNKIREPORT_TOKEN', ''),
        help='MunkiReport API token (or set MUNKIREPORT_TOKEN env var)'
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Fetch from SimpleMDM but do not submit to MunkiReport'
    )
    parser.add_argument(
        '--verbose',
        action='store_true',
        help='Enable verbose logging'
    )
    parser.add_argument(
        '--max-parent-resources',
        type=int,
        default=0,
        help='Limit deep nested sync to first N parent resources per endpoint (0 = all)'
    )
    parser.add_argument(
        '--delta',
        action='store_true',
        help='Attempt delta sync using last cursor (if API supports it)'
    )
    parser.add_argument(
        '--last-sync-cursor',
        default=os.environ.get('SIMPLEMDM_LAST_SYNC_CURSOR', ''),
        help='Override last sync cursor timestamp for delta sync'
    )
    parser.add_argument(
        '--sync-commands',
        action='store_true',
        help='Fetch and submit command status records'
    )
    parser.add_argument(
        '--sync-device-subresources',
        action='store_true',
        help='Fetch per-device subresources (profiles, installed_apps, users)'
    )
    parser.add_argument(
        '--device-subresource-limit',
        type=int,
        default=0,
        help='Limit per-device deep sync to first N devices (0 = all)'
    )
    parser.add_argument(
        '--commands-limit',
        type=int,
        default=250,
        help='Max command records to fetch when --sync-commands is enabled'
    )
    parser.add_argument(
        '--respect-schedule',
        action='store_true',
        help='Only run when admin scheduling is enabled and interval is due'
    )
    parser.add_argument(
        '--force-run',
        action='store_true',
        help='Run now even when --respect-schedule is set'
    )
    parser.add_argument(
        '--sync-interval-minutes',
        type=int,
        default=-1,
        help='Override schedule interval minutes when --respect-schedule is used (0 = use admin setting)'
    )

    args = parser.parse_args()

    if args.verbose:
        logger.setLevel(logging.DEBUG)

    config_data = {}
    if args.munkireport_url:
        if not args.api_key:
            logger.info('API key not found in environment. Attempting to fetch from MunkiReport...')
        try:
            # We call the get_config endpoint of the simplemdm module
            config_url = f"{args.munkireport_url.rstrip('/')}/module/simplemdm/get_config"
            req = urllib.request.Request(config_url)
            if args.munkireport_token:
                req.add_header('X-API-Token', args.munkireport_token)
            
            ctx = ssl.create_default_context()
            with urllib.request.urlopen(req, context=ctx) as response:
                config_data = json.loads(response.read().decode())
                if not args.api_key:
                    args.api_key = config_data.get('api_key', '')
                if not args.delta and str(config_data.get('sync_delta_enabled', '0')) == '1':
                    args.delta = True
                if not args.last_sync_cursor:
                    args.last_sync_cursor = str(config_data.get('last_sync_cursor', '') or '')
                if not args.sync_commands and str(config_data.get('sync_commands_enabled', '0')) == '1':
                    args.sync_commands = True
                if not args.sync_device_subresources and str(config_data.get('sync_device_subresources_enabled', '0')) == '1':
                    args.sync_device_subresources = True
                if args.device_subresource_limit == 0:
                    try:
                        cfg_limit = int(config_data.get('device_subresource_limit', '0') or 0)
                        args.device_subresource_limit = max(0, cfg_limit)
                    except Exception:
                        args.device_subresource_limit = 0
                if args.api_key:
                    logger.info('Successfully retrieved API key from MunkiReport.')
        except Exception as e:
            logger.debug(f'Failed to fetch config from MunkiReport: {e}')

    args.schedule_enabled = str(config_data.get('enable_scheduled_sync', '0')) == '1'
    args.schedule_last_sync_time = str(config_data.get('last_sync_time', '') or '')
    args.sync_request_state = str(config_data.get('sync_request_state', 'idle') or 'idle')
    if args.sync_interval_minutes <= 0:
        try:
            args.sync_interval_minutes = int(config_data.get('sync_interval_minutes', '15') or 15)
        except Exception:
            args.sync_interval_minutes = 15
    if args.sync_interval_minutes < 1:
        args.sync_interval_minutes = 1

    if not args.api_key:
        logger.error('SimpleMDM API key is required. Set SIMPLEMDM_API_KEY, use --api-key, or configure it in the MunkiReport UI.')
        sys.exit(1)

    if not args.munkireport_url and not args.dry_run:
        logger.error('MunkiReport URL is required. Set MUNKIREPORT_URL or use --munkireport-url')
        sys.exit(1)

    return args


def parse_last_sync_time(value):
    """Parse configured last_sync_time value into UTC datetime if possible."""
    raw = str(value or '').strip()
    if not raw:
        return None

    # Stored format is often "<iso_timestamp> - ...".
    token = raw.split(' - ', 1)[0].strip()
    if token.endswith('Z'):
        token = token[:-1] + '+00:00'

    try:
        dt = datetime.fromisoformat(token)
    except Exception:
        try:
            dt = datetime.strptime(token, '%Y-%m-%d %H:%M:%S')
            dt = dt.replace(tzinfo=timezone.utc)
        except Exception:
            return None

    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(timezone.utc)


def should_run_now(config):
    """Decide if scheduled sync should run now."""
    if config.force_run:
        return True, 'force-run enabled'

    if str(getattr(config, 'sync_request_state', '')).lower() == 'queued':
        return True, 'admin sync request is queued'

    if not config.schedule_enabled:
        return False, 'admin schedule is disabled'

    last_sync_dt = parse_last_sync_time(config.schedule_last_sync_time)
    if last_sync_dt is None:
        return True, 'no valid last_sync_time found'

    now = datetime.now(timezone.utc)
    elapsed_seconds = (now - last_sync_dt).total_seconds()
    interval_seconds = int(config.sync_interval_minutes) * 60
    if elapsed_seconds >= interval_seconds:
        return True, f'due (elapsed {int(elapsed_seconds)}s >= interval {interval_seconds}s)'
    return False, f'not due yet (elapsed {int(elapsed_seconds)}s < interval {interval_seconds}s)'


def simplemdm_request(endpoint, api_key, silent_statuses=None):
    """Make an authenticated request to the SimpleMDM API."""
    url = f"{SIMPLEMDM_API_BASE}/{endpoint}"
    logger.debug(f"Requesting: {url}")
    silent_statuses = set(silent_statuses or [])

    # Basic auth: API key as username, empty password
    credentials = base64.b64encode(f"{api_key}:".encode()).decode()

    req = urllib.request.Request(url)
    req.add_header('Authorization', f'Basic {credentials}')
    req.add_header('Accept', 'application/json')

    # Create SSL context
    ctx = ssl.create_default_context()
    for attempt in range(1, REQUEST_RETRIES + 1):
        try:
            REQUEST_METRICS['api_requests'] += 1
            response = urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT)
            return json.loads(response.read().decode())
        except urllib.error.HTTPError as e:
            is_silent = e.code in silent_statuses
            if not is_silent:
                REQUEST_METRICS['api_errors'] += 1
            if e.code not in silent_statuses:
                logger.error(f"HTTP Error {e.code}: {e.reason} for {url}")
            if e.code == 429:
                REQUEST_METRICS['rate_limit_hits'] += 1
            if e.code == 401:
                logger.error("Authentication failed. Check your API key.")
            retryable = e.code in (408, 429, 500, 502, 503, 504)
            if retryable and attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            raise
        except urllib.error.URLError as e:
            REQUEST_METRICS['api_errors'] += 1
            logger.error(f"URL Error: {e.reason} for {url}")
            if attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            raise


def _append_since(endpoint, since_cursor):
    if not since_cursor:
        return endpoint
    joiner = '&' if '?' in endpoint else '?'
    return f'{endpoint}{joiner}updated_at_gt={urllib.parse.quote(str(since_cursor))}'


def fetch_all_devices(api_key, since_cursor=''):
    """Fetch all devices from SimpleMDM, handling pagination."""
    all_devices = []
    starting_after = None
    page = 0

    while True:
        page += 1
        endpoint = 'devices?limit=100'
        if starting_after:
            endpoint += f'&starting_after={starting_after}'
        request_endpoint = _append_since(endpoint, since_cursor)

        logger.info(f"Fetching devices page {page}...")

        try:
            response = simplemdm_request(request_endpoint, api_key)
        except urllib.error.HTTPError as e:
            if since_cursor and e.code in (400, 404):
                logger.warning('Delta filter not supported for devices endpoint; falling back to full fetch.')
                since_cursor = ''
                response = simplemdm_request(endpoint, api_key)
            else:
                logger.error(f"Failed to fetch devices: {e}")
                break
        except Exception as e:
            logger.error(f"Failed to fetch devices: {e}")
            break

        data = response.get('data', [])
        if not data:
            break

        all_devices.extend(data)
        starting_after = data[-1]['id']

        logger.info(f"  Fetched {len(data)} devices (total: {len(all_devices)})")

        # Check if there are more pages
        if not response.get('has_more', False):
            break

    return all_devices


def fetch_all_resources(endpoint, api_key, since_cursor=''):
    """Fetch all records from a SimpleMDM collection endpoint with pagination."""
    records = []
    starting_after = None
    unavailable = False

    while True:
        url = f'{endpoint}?limit=100'
        if starting_after:
            url += f'&starting_after={starting_after}'
        request_url = _append_since(url, since_cursor)

        try:
            response = simplemdm_request(request_url, api_key)
        except urllib.error.HTTPError as e:
            if since_cursor and e.code in (400, 404):
                logger.debug(f"Delta filter unsupported for {endpoint}, retrying full fetch")
                since_cursor = ''
                try:
                    response = simplemdm_request(url, api_key)
                except Exception as inner:
                    logger.warning(f"Failed to fetch {endpoint}: {inner}")
                    break
                else:
                    data = response.get('data', [])
                    if isinstance(data, dict):
                        data = [data]
                    if not data:
                        break
                    records.extend(data)
                    if not response.get('has_more', False):
                        break
                    last = data[-1]
                    starting_after = last.get('id') if isinstance(last, dict) else None
                    if not starting_after:
                        break
                    continue
            if e.code == 404:
                unavailable = True
                logger.debug(f"Endpoint not found (404): {endpoint}")
                break
            logger.warning(f"Failed to fetch {endpoint}: HTTP {e.code}")
            break
        except Exception as e:
            logger.warning(f"Failed to fetch {endpoint}: {e}")
            break

        data = response.get('data', [])
        if isinstance(data, dict):
            data = [data]
        if not data:
            break

        records.extend(data)
        if not response.get('has_more', False):
            break

        last = data[-1]
        starting_after = last.get('id') if isinstance(last, dict) else None
        if not starting_after:
            break

    if unavailable:
        return records, True

    logger.info(f"Fetched {len(records)} resources from {endpoint}")
    return records, False


def probe_collection_endpoint(endpoint, api_key):
    """Probe whether an endpoint is available in this tenant/API version."""
    cache_key = f'probe:{endpoint}'
    if cache_key in ENDPOINT_SUPPORT_CACHE:
        return ENDPOINT_SUPPORT_CACHE[cache_key]
    probe = endpoint if '?' in endpoint else f'{endpoint}?limit=1'
    try:
        simplemdm_request(probe, api_key, silent_statuses={404})
        ENDPOINT_SUPPORT_CACHE[cache_key] = True
        return True
    except urllib.error.HTTPError as e:
        ENDPOINT_SUPPORT_CACHE[cache_key] = False if e.code == 404 else False
        return ENDPOINT_SUPPORT_CACHE[cache_key]
    except Exception:
        ENDPOINT_SUPPORT_CACHE[cache_key] = False
        return False


def probe_nested_endpoint(endpoint, api_key):
    """Probe nested endpoint support without noisy 404 logging."""
    return probe_collection_endpoint(endpoint, api_key)


def fetch_device_groups(api_key):
    """Fetch SimpleMDM device groups and map IDs to names."""
    groups = {}
    starting_after = None

    while True:
        endpoint = 'device_groups?limit=100'
        if starting_after:
            endpoint += f'&starting_after={starting_after}'

        try:
            response = simplemdm_request(endpoint, api_key)
        except Exception as e:
            logger.warning(f"Failed to fetch device groups: {e}")
            break

        data = response.get('data', [])
        if not data:
            break

        for group in data:
            group_id = str(group.get('id'))
            group_name = group.get('attributes', {}).get('name', f'Group {group_id}')
            groups[group_id] = group_name

        if not response.get('has_more', False):
            break

        starting_after = data[-1].get('id')
        if not starting_after:
            break

    return groups


def fetch_assignment_groups(api_key):
    """Fetch SimpleMDM assignment groups and map IDs to names."""
    groups = {}
    starting_after = None

    while True:
        endpoint = 'assignment_groups?limit=100'
        if starting_after:
            endpoint += f'&starting_after={starting_after}'

        try:
            response = simplemdm_request(endpoint, api_key)
        except Exception as e:
            logger.warning(f"Failed to fetch assignment groups: {e}")
            break

        data = response.get('data', [])
        if not data:
            break

        for group in data:
            group_id = str(group.get('id'))
            group_name = group.get('attributes', {}).get('name', f'Group {group_id}')
            groups[group_id] = group_name

        if not response.get('has_more', False):
            break

        starting_after = data[-1].get('id')
        if not starting_after:
            break

    return groups


def resolve_assignment_group_name(group_id, api_key, assignment_groups):
    """Resolve one assignment group ID by API and cache the result."""
    if not group_id:
        return None

    group_id = str(group_id)
    if group_id in assignment_groups:
        return assignment_groups[group_id]

    try:
        response = simplemdm_request(f'assignment_groups/{group_id}', api_key)
        data = response.get('data', {})
        name = data.get('attributes', {}).get('name')
        if name:
            assignment_groups[group_id] = name
        return name
    except Exception:
        return None


def enrich_relationships(relationships, device_groups=None, assignment_groups=None, api_key=''):
    """Add display names to relationship entries where possible."""
    if not isinstance(relationships, dict):
        return {}, None

    device_groups = device_groups or {}
    assignment_groups = assignment_groups or {}
    rel = json.loads(json.dumps(relationships))
    assignment_group_name = None

    device_group_data = rel.get('device_group', {}).get('data')
    if isinstance(device_group_data, dict):
        dg_id = device_group_data.get('id')
        dg_name = device_groups.get(str(dg_id))
        if dg_name:
            device_group_data['name'] = dg_name
            assignment_group_name = dg_name

    groups_data = rel.get('groups', {}).get('data')
    if isinstance(groups_data, list):
        for item in groups_data:
            if not isinstance(item, dict):
                continue
            gid = item.get('id')
            if not gid:
                continue
            name = assignment_groups.get(str(gid))
            if not name and api_key:
                name = resolve_assignment_group_name(gid, api_key, assignment_groups)
            if name:
                item['name'] = name

    return rel, assignment_group_name


def transform_device(device_data, device_groups=None, assignment_groups=None, api_key=''):
    """Transform a SimpleMDM device API response into a flat record for MunkiReport."""
    attrs = device_data.get('attributes', {})
    relationships = device_data.get('relationships', attrs.get('relationships', {}))
    relationships_enriched, assignment_group_name = enrich_relationships(
        relationships, device_groups, assignment_groups, api_key
    )

    # Extract firewall status
    firewall = attrs.get('firewall', {})
    firewall_enabled = firewall.get('enabled') if isinstance(firewall, dict) else None

    # Extract SIP status
    sip_enabled = attrs.get('system_integrity_protection_enabled')

    record = {
        'serial_number': attrs.get('serial_number', ''),
        'simplemdm_id': device_data.get('id'),
        'device_name': attrs.get('device_name', attrs.get('name', '')),
        'status': attrs.get('status', ''),
        'enrolled_at': attrs.get('enrolled_at', ''),
        'last_seen_at': attrs.get('last_seen_at', ''),
        'last_seen_ip': attrs.get('last_seen_ip', ''),
        'model_name': attrs.get('model_name', ''),
        'os_version': attrs.get('os_version', ''),
        'build_version': attrs.get('build_version', ''),
        'is_supervised': attrs.get('is_supervised'),
        'is_dep_enrollment': attrs.get('is_dep_enrollment'),
        'dep_enrolled': attrs.get('dep_enrolled'),
        'dep_assigned': attrs.get('dep_assigned'),
        'filevault_enabled': attrs.get('filevault_enabled'),
        'firewall_enabled': firewall_enabled,
        'sip_enabled': sip_enabled,
        'remote_desktop_enabled': attrs.get('remote_desktop_enabled'),
        'activation_lock_enabled': attrs.get('is_activation_lock_enabled'),
        'passcode_compliant': attrs.get('passcode_compliant'),
        'device_capacity': attrs.get('device_capacity'),
        'available_device_capacity': attrs.get('available_device_capacity'),
        'battery_level': attrs.get('battery_level', ''),
        'assignment_group': assignment_group_name,
        'unique_identifier': attrs.get('unique_identifier'),
        'imei': attrs.get('imei'),
        'meid': attrs.get('meid'),
        'iccid': attrs.get('iccid'),
        'phone_number': attrs.get('phone_number'),
        'bluetooth_mac': attrs.get('bluetooth_mac'),
        'wifi_mac': attrs.get('wifi_mac'),
        'current_carrier_network': attrs.get('current_carrier_network'),
        'personal_hotspot_enabled': attrs.get('personal_hotspot_enabled'),
        'cellular_technology': attrs.get('cellular_technology'),
        'modem_firmware_version': attrs.get('modem_firmware_version'),
        'custom_attributes': attrs.get('custom_attributes', {}),
        # Keep full API payloads so all fields are available in MunkiReport.
        'attributes_json': attrs,
        'relationships_json': relationships_enriched,
    }

    return record


def transform_resource(resource, source_endpoint):
    """Flatten a non-device SimpleMDM object for generic storage."""
    if not isinstance(resource, dict):
        return None

    resource_type = resource.get('type') or source_endpoint
    resource_id = resource.get('id')
    if resource_id is None:
        return None

    attrs = resource.get('attributes', {})
    rels = resource.get('relationships', {})
    name = None
    if isinstance(attrs, dict):
        name = attrs.get('name') or attrs.get('display_name') or attrs.get('title')

    return {
        'resource_type': str(resource_type),
        'resource_id': str(resource_id),
        'source_endpoint': source_endpoint,
        'name': name or '',
        'attributes_json': attrs if isinstance(attrs, dict) else {},
        'relationships_json': rels if isinstance(rels, dict) else {},
        'data_json': resource,
        'synced_at': datetime.now(timezone.utc).isoformat(),
    }


def _inject_device_id(command, device_id):
    """Ensure a command payload carries a device_id for downstream normalization."""
    if not isinstance(command, dict):
        return command
    attrs = command.get('attributes')
    if not isinstance(attrs, dict):
        attrs = {}
        command['attributes'] = attrs
    if not attrs.get('device_id'):
        attrs['device_id'] = str(device_id)
    return command


def fetch_recent_commands(api_key, max_records=250, device_ids=None):
    """
    Fetch command status records.

    Strategy:
    1) Try tenant-wide collection endpoint `/commands`.
    2) Fallback to per-device endpoint `/devices/{id}/commands`.
    """
    endpoint = 'commands'
    if probe_collection_endpoint(endpoint, api_key):
        commands, unavailable = fetch_all_resources(endpoint, api_key)
        if unavailable:
            commands = []
        if max_records > 0:
            commands = commands[:max_records]
        logger.info(f"Fetched {len(commands)} command records from /commands")
        return commands

    device_ids = [str(d).strip() for d in (device_ids or []) if str(d).strip() != '']
    if not device_ids:
        logger.info('Commands endpoint not available and no device IDs supplied; skipping command sync.')
        return []

    logger.info('Global /commands endpoint not available; falling back to /devices/{id}/commands')
    seen = set()
    commands = []
    for device_id in device_ids:
        nested_endpoint = f'devices/{device_id}/commands'
        rows, unavailable = fetch_all_resources(nested_endpoint, api_key)
        if unavailable:
            continue
        for row in rows:
            row = _inject_device_id(row, device_id)
            cmd_id = ''
            if isinstance(row, dict):
                cmd_id = str(row.get('id') or '')
                attrs = row.get('attributes', {})
                if isinstance(attrs, dict):
                    cmd_id = str(attrs.get('uuid') or attrs.get('command_uuid') or cmd_id)
            dedupe_key = f'{device_id}:{cmd_id}'
            if cmd_id and dedupe_key in seen:
                continue
            if cmd_id:
                seen.add(dedupe_key)
            commands.append(row)
            if max_records > 0 and len(commands) >= max_records:
                logger.info(f"Fetched {len(commands)} command records from per-device endpoints")
                return commands

    logger.info(f"Fetched {len(commands)} command records from per-device endpoints")
    return commands


def transform_command(command):
    """Normalize a command object into module command schema."""
    if not isinstance(command, dict):
        return None

    attrs = command.get('attributes', {})
    rels = command.get('relationships', {})
    device_rel = rels.get('device', {}).get('data') if isinstance(rels, dict) else {}
    resource = command.get('id')
    command_uuid = attrs.get('uuid') or attrs.get('command_uuid') or command.get('id')

    return {
        'command_uuid': str(command_uuid) if command_uuid is not None else '',
        'command_type': attrs.get('command_type') or attrs.get('request_type') or command.get('type') or '',
        'status': attrs.get('status') or attrs.get('state') or 'unknown',
        'device_id': str(device_rel.get('id')) if isinstance(device_rel, dict) and device_rel.get('id') is not None else str(attrs.get('device_id', '')),
        'serial_number': attrs.get('serial_number') or '',
        'resource_id': str(resource) if resource is not None else '',
        'error_message': attrs.get('error') or attrs.get('error_message') or '',
        'issued_at': attrs.get('created_at') or attrs.get('issued_at') or '',
        'completed_at': attrs.get('completed_at') or '',
        'updated_at': attrs.get('updated_at') or datetime.now(timezone.utc).isoformat(),
    }


def submit_to_munkireport(records, munkireport_url, api_key, token=''):
    """Submit device records to MunkiReport's SimpleMDM processor."""
    url = f"{munkireport_url.rstrip('/')}/module/simplemdm/index?op=ingest"

    data = json.dumps(records).encode('utf-8')

    req = urllib.request.Request(url, data=data, method='POST')
    req.add_header('Content-Type', 'application/json')
    req.add_header('X-SIMPLEMDM-API-KEY', api_key)

    if token:
        req.add_header('Authorization', f'Bearer {token}')

    ctx = ssl.create_default_context()
    for attempt in range(1, REQUEST_RETRIES + 1):
        try:
            response = urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT)
            result = response.read().decode()
            logger.info(f"Successfully submitted {len(records)} records to MunkiReport")
            logger.debug(f"Response: {result}")
            return True
        except urllib.error.HTTPError as e:
            logger.error(f"Failed to submit to MunkiReport: HTTP {e.code} {e.reason}")
            retryable = e.code in (408, 429, 500, 502, 503, 504)
            if retryable and attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False
        except urllib.error.URLError as e:
            logger.error(f"Failed to connect to MunkiReport: {e.reason}")
            if attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False


def submit_resources_to_munkireport(records, munkireport_url, api_key, token=''):
    """Submit generic resource records to MunkiReport."""
    url = f"{munkireport_url.rstrip('/')}/module/simplemdm/index?op=ingest_resources"
    data = json.dumps(records).encode('utf-8')

    req = urllib.request.Request(url, data=data, method='POST')
    req.add_header('Content-Type', 'application/json')
    req.add_header('X-SIMPLEMDM-API-KEY', api_key)
    if token:
        req.add_header('Authorization', f'Bearer {token}')

    ctx = ssl.create_default_context()
    for attempt in range(1, REQUEST_RETRIES + 1):
        try:
            response = urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT)
            result = response.read().decode()
            logger.info(f"Successfully submitted {len(records)} resource records")
            logger.debug(f"Resource response: {result}")
            return True
        except urllib.error.HTTPError as e:
            logger.error(f"Failed to submit resources: HTTP {e.code} {e.reason}")
            retryable = e.code in (408, 429, 500, 502, 503, 504)
            if retryable and attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False
        except urllib.error.URLError as e:
            logger.error(f"Failed to connect for resource submit: {e.reason}")
            if attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False

def submit_commands_to_munkireport(records, munkireport_url, api_key, token=''):
    """Submit command records to MunkiReport."""
    url = f"{munkireport_url.rstrip('/')}/module/simplemdm/index?op=ingest_commands"
    data = json.dumps(records).encode('utf-8')

    req = urllib.request.Request(url, data=data, method='POST')
    req.add_header('Content-Type', 'application/json')
    req.add_header('X-SIMPLEMDM-API-KEY', api_key)
    if token:
        req.add_header('Authorization', f'Bearer {token}')

    ctx = ssl.create_default_context()
    for attempt in range(1, REQUEST_RETRIES + 1):
        try:
            urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT)
            logger.info(f"Successfully submitted {len(records)} command records")
            return True
        except urllib.error.HTTPError as e:
            logger.error(f"Failed to submit commands: HTTP {e.code} {e.reason}")
            retryable = e.code in (408, 429, 500, 502, 503, 504)
            if retryable and attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False
        except urllib.error.URLError as e:
            logger.error(f"Failed to connect for command submit: {e.reason}")
            if attempt < REQUEST_RETRIES:
                time.sleep(REQUEST_BACKOFF_SECONDS * attempt)
                continue
            return False


def update_sync_status(munkireport_url, api_key, status, message, token='', extra=None):
    """Update sync status values in the module config table."""
    url = f"{munkireport_url.rstrip('/')}/module/simplemdm/index?op=update_sync_status"
    payload = {
        'last_sync_status': status,
        'last_sync_time': message,
    }
    if isinstance(extra, dict):
        for k, v in extra.items():
            payload[str(k)] = str(v)
    data = urllib.parse.urlencode(payload).encode()

    req = urllib.request.Request(url, data=data, method='POST')
    req.add_header('X-SIMPLEMDM-API-KEY', api_key)
    req.add_header('Content-Type', 'application/x-www-form-urlencoded')
    if token:
        req.add_header('Authorization', f'Bearer {token}')

    try:
        ctx = ssl.create_default_context()
        urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT)
        logger.debug("Updated sync status in MunkiReport.")
    except Exception as e:
        logger.debug(f"Failed to report sync status: {e}")


def begin_sync_run(munkireport_url, api_key, token=''):
    """Claim the sync slot before starting expensive work."""
    url = f"{munkireport_url.rstrip('/')}/module/simplemdm/index?op=begin_sync_run"
    req = urllib.request.Request(url, data=b'', method='POST')
    req.add_header('X-SIMPLEMDM-API-KEY', api_key)
    if token:
        req.add_header('Authorization', f'Bearer {token}')

    try:
        ctx = ssl.create_default_context()
        with urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT) as response:
            payload = json.loads(response.read().decode() or '{}')
            return payload.get('status') == 'success', payload
    except urllib.error.HTTPError as e:
        if e.code == 409:
            return False, {'status': 'busy'}
        logger.debug(f"Failed to claim sync run: HTTP {e.code} {e.reason}")
    except Exception as e:
        logger.debug(f"Failed to claim sync run: {e}")
    return False, {'status': 'error'}


def main():
    """Main sync routine."""
    config = get_config()

    if config.respect_schedule and not config.dry_run:
        run_now, reason = should_run_now(config)
        if not run_now:
            logger.info(f"Skipping sync (--respect-schedule): {reason}")
            return
        logger.info(f"Schedule check passed: {reason}")

    if not config.dry_run:
        claimed, claim_result = begin_sync_run(
            config.munkireport_url,
            config.api_key,
            config.munkireport_token
        )
        if not claimed:
            if claim_result.get('status') == 'busy':
                logger.info('Another sync is already running; skipping this run.')
                return
            logger.error('Unable to claim sync run state in MunkiReport.')
            sys.exit(1)

        update_sync_status(
            config.munkireport_url,
            config.api_key,
            'Running',
            f"{datetime.now(timezone.utc).isoformat()} - sync started",
            config.munkireport_token,
            extra={'sync_request_state': 'running'}
        )

    start = time.time()
    logger.info("Starting SimpleMDM sync...")
    since_cursor = config.last_sync_cursor if config.delta and config.last_sync_cursor else ''
    scope = 'delta' if since_cursor else 'full'

    # Fetch group maps for name lookup
    logger.info("Fetching device groups...")
    device_groups = fetch_device_groups(config.api_key)
    logger.info(f"Found {len(device_groups)} device groups")

    logger.info("Fetching assignment groups...")
    assignment_groups = fetch_assignment_groups(config.api_key)
    logger.info(f"Found {len(assignment_groups)} assignment groups")

    # Fetch all devices
    devices = fetch_all_devices(config.api_key, since_cursor)
    logger.info(f"Fetched {len(devices)} total devices from SimpleMDM")

    if not devices:
        logger.warning("No devices found in SimpleMDM")
        if not config.dry_run:
            update_sync_status(
                config.munkireport_url,
                config.api_key,
                'Success',
                f"{datetime.now(timezone.utc).isoformat()} - 0 devices, 0 resources synced",
                config.munkireport_token,
                extra={
                    'sync_request_state': 'idle',
                    'sync_last_scope': scope,
                    'sync_last_delta_mode': '1' if config.delta else '0',
                    'last_sync_cursor': datetime.now(timezone.utc).isoformat(),
                }
            )
        return

    # Transform device data
    records = []
    skipped = 0
    for device in devices:
        record = transform_device(device, device_groups, assignment_groups, config.api_key)
        if record.get('serial_number'):
            records.append(record)
        else:
            skipped += 1
            logger.debug(f"Skipping device {device.get('id')} - no serial number")

    logger.info(f"Transformed {len(records)} records ({skipped} skipped - no serial)")

    if config.dry_run:
        logger.info("DRY RUN - Not submitting to MunkiReport")
        for record in records[:5]:
            logger.info(f"  {record['serial_number']}: {record['device_name']} ({record['status']})")
        if len(records) > 5:
            logger.info(f"  ... and {len(records) - 5} more")
        return

    # Submit to MunkiReport in batches of 50
    batch_size = 50
    for i in range(0, len(records), batch_size):
        batch = records[i:i + batch_size]
        logger.info(f"Submitting batch {i // batch_size + 1} ({len(batch)} records)...")
        success = submit_to_munkireport(batch, config.munkireport_url, config.api_key, config.munkireport_token)
        if not success:
            logger.error("Aborting sync due to submission failure")
            update_sync_status(
                config.munkireport_url,
                config.api_key,
                'Failed',
                f"{datetime.now(timezone.utc).isoformat()} - submission failure",
                config.munkireport_token,
                extra={
                    'sync_request_state': 'idle',
                    'sync_last_duration_ms': int((time.time() - start) * 1000),
                    'sync_last_api_requests': REQUEST_METRICS['api_requests'],
                    'sync_last_api_errors': REQUEST_METRICS['api_errors'],
                    'sync_last_rate_limit_hits': REQUEST_METRICS['rate_limit_hits'],
                    'sync_last_delta_mode': '1' if config.delta else '0',
                    'sync_last_scope': scope,
                }
            )
            sys.exit(1)

    resource_records = []
    fetched_by_endpoint = {}
    for endpoint in RESOURCE_ENDPOINTS:
        if not probe_collection_endpoint(endpoint, config.api_key):
            logger.info(f"Endpoint not available in this tenant/API: {endpoint}")
            continue
        items, unavailable = fetch_all_resources(endpoint, config.api_key, since_cursor)
        if unavailable:
            logger.info(f"Endpoint not available in this tenant/API: {endpoint}")
            continue
        fetched_by_endpoint[endpoint] = items
        for item in items:
            transformed = transform_resource(item, endpoint)
            if transformed:
                resource_records.append(transformed)

    # Deep sync nested resources (apps/profiles/groups/enrollments children) when available.
    for parent_endpoint, subpaths in SUBRESOURCE_MAP.items():
        parents = fetched_by_endpoint.get(parent_endpoint, [])
        if not parents:
            continue

        for subpath in subpaths:
            nested_supported = None
            for idx, parent in enumerate(parents):
                if config.max_parent_resources > 0 and idx >= config.max_parent_resources:
                    break

                parent_id = parent.get('id')
                if parent_id is None:
                    continue
                nested_endpoint = f'{parent_endpoint}/{parent_id}/{subpath}'
                if nested_supported is None and not probe_nested_endpoint(nested_endpoint, config.api_key):
                    nested_supported = False
                    logger.info(f"Nested endpoint not available: {parent_endpoint}/{{id}}/{subpath}")
                    break
                items, unavailable = fetch_all_resources(nested_endpoint, config.api_key, since_cursor)
                if unavailable:
                    # If the first parent returns 404, treat the nested route as unsupported globally.
                    if nested_supported is None:
                        nested_supported = False
                        logger.info(f"Nested endpoint not available: {parent_endpoint}/{{id}}/{subpath}")
                        break
                    continue

                nested_supported = True
                for item in items:
                    transformed = transform_resource(item, nested_endpoint)
                    if transformed:
                        resource_records.append(transformed)

    # Optional deep sync for per-device child resources.
    if config.sync_device_subresources:
        device_children = ['profiles', 'installed_apps', 'users']
        device_source = records
        if config.device_subresource_limit > 0:
            device_source = device_source[:config.device_subresource_limit]

        logger.info(
            f"Deep syncing device subresources for {len(device_source)} devices "
            f"({', '.join(device_children)})"
        )

        for device in device_source:
            device_id = device.get('simplemdm_id')
            if device_id in (None, ''):
                continue
            for child in device_children:
                nested_endpoint = f'devices/{device_id}/{child}'
                cache_key = f'device-child:{child}'
                if cache_key in ENDPOINT_SUPPORT_CACHE and not ENDPOINT_SUPPORT_CACHE[cache_key]:
                    continue
                if cache_key not in ENDPOINT_SUPPORT_CACHE:
                    ENDPOINT_SUPPORT_CACHE[cache_key] = probe_nested_endpoint(nested_endpoint, config.api_key)
                    if not ENDPOINT_SUPPORT_CACHE[cache_key]:
                        logger.info(f"Nested endpoint not available: devices/{{id}}/{child}")
                        continue
                items, unavailable = fetch_all_resources(nested_endpoint, config.api_key, since_cursor)
                if unavailable:
                    ENDPOINT_SUPPORT_CACHE[cache_key] = False
                    logger.info(f"Nested endpoint not available: devices/{{id}}/{child}")
                    continue
                for item in items:
                    transformed = transform_resource(item, nested_endpoint)
                    if transformed:
                        resource_records.append(transformed)

    if resource_records:
        logger.info(f"Submitting {len(resource_records)} non-device resources...")
        resource_batch_size = 100
        for i in range(0, len(resource_records), resource_batch_size):
            batch = resource_records[i:i + resource_batch_size]
            ok = submit_resources_to_munkireport(
                batch, config.munkireport_url, config.api_key, config.munkireport_token
            )
            if not ok:
                logger.error("Aborting sync due to resource submission failure")
                update_sync_status(
                    config.munkireport_url,
                    config.api_key,
                    'Failed',
                    f"{datetime.now(timezone.utc).isoformat()} - resource submission failure",
                    config.munkireport_token,
                    extra={
                        'sync_request_state': 'idle',
                        'sync_last_duration_ms': int((time.time() - start) * 1000),
                        'sync_last_api_requests': REQUEST_METRICS['api_requests'],
                        'sync_last_api_errors': REQUEST_METRICS['api_errors'],
                        'sync_last_rate_limit_hits': REQUEST_METRICS['rate_limit_hits'],
                        'sync_last_delta_mode': '1' if config.delta else '0',
                        'sync_last_scope': scope,
                    }
                )
                sys.exit(1)

    command_records = []
    if config.sync_commands:
        command_device_ids = [r.get('simplemdm_id') for r in records if r.get('simplemdm_id')]
        raw_commands = fetch_recent_commands(
            config.api_key,
            config.commands_limit,
            command_device_ids
        )
        for command in raw_commands:
            transformed = transform_command(command)
            if transformed and transformed.get('command_uuid'):
                command_records.append(transformed)
        if command_records:
            logger.info(f"Submitting {len(command_records)} command records...")
            ok = submit_commands_to_munkireport(
                command_records,
                config.munkireport_url,
                config.api_key,
                config.munkireport_token
            )
            if not ok:
                logger.error("Command submission failed; continuing with sync status reporting.")

    logger.info("SimpleMDM sync completed successfully!")

    duration_ms = int((time.time() - start) * 1000)

    update_sync_status(
        config.munkireport_url,
        config.api_key,
        'Success',
        f"{datetime.now(timezone.utc).isoformat()} - {len(records)} devices, {len(resource_records)} resources, {len(command_records)} commands synced",
        config.munkireport_token,
        extra={
            'sync_request_state': 'idle',
            'sync_last_duration_ms': duration_ms,
            'sync_last_api_requests': REQUEST_METRICS['api_requests'],
            'sync_last_api_errors': REQUEST_METRICS['api_errors'],
            'sync_last_rate_limit_hits': REQUEST_METRICS['rate_limit_hits'],
            'sync_last_delta_mode': '1' if config.delta else '0',
            'sync_last_scope': scope,
            'last_sync_cursor': datetime.now(timezone.utc).isoformat(),
        }
    )


if __name__ == '__main__':
    main()
