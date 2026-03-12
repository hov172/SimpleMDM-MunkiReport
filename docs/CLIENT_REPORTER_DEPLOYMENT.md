# Client Reporter Deployment Guide

This guide explains how to deploy and use the included Option B example clients.

Use this guide when you want to send client-reported facts from a Mac into the SimpleMDM MunkiReport module.

## 1) What Option B Does

Option B sends narrow client-reported facts into this module's ingest endpoint:

- endpoint:
  - `POST /module/simplemdm/index?op=ingest_client_facts`
- purpose:
  - add device-local facts to the module's supplemental data layer
- examples:
  - current console user
  - local uptime
  - local FileVault state

Use Option B when:

- the fact comes from the Mac itself
- no other loaded MunkiReport module already owns that fact
- you want lightweight supplemental context instead of building a full new module

Do not use Option B for:

- large inventories
- authoritative SimpleMDM API state
- facts already cleanly owned by another module unless you are intentionally doing drift detection

## 2) Included Example Files

- shared-secret shell reporter:
  - [simplemdm_client_reporter_example.sh](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/simplemdm_client_reporter_example.sh)
- hardened Python reporter:
  - [simplemdm_client_reporter_hardened.py](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/simplemdm_client_reporter_hardened.py)
- installer helper:
  - [install_client_reporter.sh](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/install_client_reporter.sh)
- backend Option A validation helper:
  - [option_a_backend_check.php](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/option_a_backend_check.php)
- `launchd` example:
  - [com.googlecode.munkireport-simplemdm-client-reporter.plist.example](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/com.googlecode.munkireport-simplemdm-client-reporter.plist.example)
- Munki postflight wrapper:
  - [postflight_simplemdm_client_reporter_example.sh](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/postflight_simplemdm_client_reporter_example.sh)

## 3) Before You Deploy

In `Admin -> SimpleMDM Settings`:

1. enable `Client Reporter Ingestion`
2. set `Client Reporter Secret`
3. keep the allowlist narrow
4. decide whether you want the original shared-secret flow or the hardened flow

Before deploying, review the admin `Client Reporter Requirements` panel. It shows:

- whether shared-secret-only is still valid
- which headers are required right now
- whether HMAC, nonce replay protection, device tokens, or trusted proxy rules are active
- the current IP allowlist and trusted proxy settings
- a copyable request summary aligned to the saved settings

If using the hardened flow, also configure:

- `Require HMAC-signed requests`
- `Require timestamp + nonce replay protection`
- `Require per-device client tokens`
- optional proxy or IP rules

## 4) Shared-Secret Deployment

This is the simplest path.

Fastest install path:

```bash
sudo ./scripts/install_client_reporter.sh \
  --mode basic \
  --munkireport-url "https://munkireport.example.com" \
  --client-secret "replace-me" \
  --install-launchd
```

Server-side requirements:

- `Client Reporter Ingestion` enabled
- `Client Reporter Secret` set
- hardening options left off unless you explicitly want them

Client-side requirements:

- `/usr/bin/curl`
- `/usr/bin/python3`
- `system_profiler`
- `fdesetup`

Environment variables used by [simplemdm_client_reporter_example.sh](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/simplemdm_client_reporter_example.sh):

- required:
  - `MUNKIREPORT_URL`
  - `SIMPLEMDM_CLIENT_SECRET`
- optional:
  - `SIMPLEMDM_CLIENT_SERIAL`
  - `SIMPLEMDM_CLIENT_SOURCE`
  - `SIMPLEMDM_CLIENT_VERSION`
  - `CURL_BIN`

Example manual run:

```bash
chmod +x simplemdm_client_reporter_example.sh

MUNKIREPORT_URL="https://munkireport.example.com" \
SIMPLEMDM_CLIENT_SECRET="replace-me" \
./simplemdm_client_reporter_example.sh
```

What it sends:

- `console_user`
- `uptime_seconds`
- `local_filevault_enabled`

## 5) Hardened Deployment

Use the hardened Python example when the admin page enables one or more of:

- HMAC signing
- timestamp + nonce replay protection
- per-device client tokens

Fastest install path:

```bash
sudo ./scripts/install_client_reporter.sh \
  --mode hardened \
  --munkireport-url "https://munkireport.example.com" \
  --client-secret "replace-me" \
  --device-token "device-token-for-this-serial" \
  --install-launchd
```

Server-side requirements:

- `Client Reporter Ingestion` enabled
- `Client Reporter Secret` set
- if enabled:
  - HMAC and replay controls configured
  - device tokens provisioned for the correct serial
  - proxy/IP rules configured correctly

Environment variables used by [simplemdm_client_reporter_hardened.py](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/simplemdm_client_reporter_hardened.py):

- required:
  - `MUNKIREPORT_URL`
  - `SIMPLEMDM_CLIENT_SECRET`
  - `SIMPLEMDM_DEVICE_TOKEN`
- optional:
  - `SIMPLEMDM_CLIENT_SERIAL`
  - `SIMPLEMDM_CLIENT_SOURCE`
  - `SIMPLEMDM_CLIENT_VERSION`
  - `SIMPLEMDM_TIMEOUT_SECONDS`

Example manual run:

```bash
chmod +x simplemdm_client_reporter_hardened.py

MUNKIREPORT_URL="https://munkireport.example.com" \
SIMPLEMDM_CLIENT_SECRET="replace-me" \
SIMPLEMDM_DEVICE_TOKEN="device-token-for-this-serial" \
./simplemdm_client_reporter_hardened.py
```

What it adds automatically:

- `X-SIMPLEMDM-CLIENT-TIMESTAMP`
- `X-SIMPLEMDM-CLIENT-NONCE`
- `X-SIMPLEMDM-CLIENT-SIGNATURE`
- `X-SIMPLEMDM-CLIENT-TOKEN`

## 6) Launchd Deployment

Use [com.googlecode.munkireport-simplemdm-client-reporter.plist.example](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/com.googlecode.munkireport-simplemdm-client-reporter.plist.example) as a template.

Recommended pattern:

1. copy the reporter script to a stable path such as:
   - `/usr/local/munki/simplemdm_client_reporter_example.sh`
2. copy the plist to:
   - `/Library/LaunchDaemons/com.googlecode.munkireport-simplemdm-client-reporter.plist`
3. edit:
   - `MUNKIREPORT_URL`
   - `SIMPLEMDM_CLIENT_SECRET`
   - `ProgramArguments`
4. load it:

```bash
sudo launchctl bootstrap system /Library/LaunchDaemons/com.googlecode.munkireport-simplemdm-client-reporter.plist
sudo launchctl enable system/com.googlecode.munkireport-simplemdm-client-reporter
```

5. confirm logs:
   - `/var/log/simplemdm_client_reporter.log`

For a hardened deployment, adjust the plist to call the Python reporter and include:

- `SIMPLEMDM_DEVICE_TOKEN`
- any custom version/source values you want

Or use the installer helper to generate and place the plist for you.

## 7) Munki Postflight Deployment

Use [postflight_simplemdm_client_reporter_example.sh](/Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/postflight_simplemdm_client_reporter_example.sh) when you want reporting to happen after Munki-managed software activity.

Recommended pattern:

1. copy the base reporter to:
   - `/usr/local/munki/simplemdm_client_reporter_example.sh`
2. copy the wrapper to a postflight location such as:
   - `/usr/local/munki/postflight.d/postflight_simplemdm_client_reporter_example.sh`
3. edit the wrapper values:
   - `MUNKIREPORT_URL`
   - `SIMPLEMDM_CLIENT_SECRET`
   - `REPORTER_PATH`
4. make both scripts executable

Example:

```bash
chmod +x /usr/local/munki/simplemdm_client_reporter_example.sh
chmod +x /usr/local/munki/postflight.d/postflight_simplemdm_client_reporter_example.sh
```

Behavior:

- Munki postflight runs the wrapper
- the wrapper calls the reporter
- reporter output is appended to `/var/log/simplemdm_client_reporter.log`
- wrapper exits safely even if reporting fails

Or use the installer helper with:

```bash
sudo ./scripts/install_client_reporter.sh \
  --mode basic \
  --munkireport-url "https://munkireport.example.com" \
  --client-secret "replace-me" \
  --install-postflight
```

## 8) Device Token Provisioning

If `Require per-device client tokens` is enabled:

1. open `Admin -> SimpleMDM Settings`
2. go to `Supplemental And Client Reporter Settings`
3. in `Device Token Provisioning (JSON, write-only)`, submit either:

Object form:

```json
{
  "C02ABC123": "token-one",
  "C02DEF456": "token-two"
}
```

Array form:

```json
[
  {
    "serial_number": "C02ABC123",
    "label": "lab-mac",
    "token": "token-one",
    "enabled": true
  }
]
```

Important:

- raw tokens are not returned after save
- only token metadata is shown back in the UI
- clearing the textarea does not delete existing tokens
- submit `[]` if you want to clear all stored device tokens

## 9) Header Requirements

Original flow:

- `X-SIMPLEMDM-CLIENT-SECRET`

Hardened flow, when enabled:

- `X-SIMPLEMDM-CLIENT-SECRET`
- `X-SIMPLEMDM-CLIENT-TIMESTAMP`
- `X-SIMPLEMDM-CLIENT-NONCE`
- `X-SIMPLEMDM-CLIENT-SIGNATURE`
- `X-SIMPLEMDM-CLIENT-TOKEN`

Proxy-aware flow, when enabled:

- send requests through a trusted proxy
- ensure `X-Forwarded-For` or `X-Real-IP` is passed from that proxy

## 10) Verification

After deployment:

1. run the client once manually
2. confirm HTTP success
3. open the device page in the SimpleMDM module
4. confirm `Client Reporter` appears in supplemental data
5. confirm the expected facts render

If using hardening:

1. confirm a valid request succeeds
2. confirm a replayed nonce is rejected
3. confirm an invalid device token is rejected
4. confirm a request from a disallowed IP or without the trusted proxy path is rejected

## 11) Troubleshooting

Common issues:

- `401 Unauthorized`
  - wrong `SIMPLEMDM_CLIENT_SECRET`
  - missing HMAC/device token headers when hardening is enabled
- `403`
  - client reporter ingestion disabled
  - client IP not allowed
  - trusted proxy required but request did not arrive through a trusted proxy
- `409`
  - nonce replay detected
  - token or nonce table missing because migrations were not run
- no data on device page
  - serial number mismatch
  - fact keys not in allowlist
  - posted facts accepted but not among the fields your script actually sends

Recommended debug order:

1. verify the admin settings
2. verify the serial number on the Mac
3. test with one manual run before automating with `launchd` or Munki
4. if using hardening, temporarily confirm the basic shared-secret flow first, then enable hardening one layer at a time
