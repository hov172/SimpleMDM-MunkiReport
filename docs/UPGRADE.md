# Upgrade Guide

This guide covers safe upgrade procedures for this module in Hosted/VM and Docker environments.

## 1) Upgrade Principles

1. Always back up before upgrade.
2. Pull/update code first, then run migrations.
3. Do not modify historical migration files.
4. Validate sync + UI after every upgrade.

## 2) Pre-Upgrade Checklist

1. Capture current commit:

```bash
git rev-parse --short HEAD
```

2. Back up database (and app state according to your MunkiReport deployment standard).
3. Record current module settings from `Admin -> SimpleMDM Settings` (or DB snapshot).
4. Pause high-risk operations during upgrade window (optional but recommended).

## 3) Hosted / VM Upgrade Runbook

Assumes you are in MunkiReport repo root.

1. Update module code:

```bash
cd local/modules/simplemdm
git pull
cd ../../..
```

2. Run migrations:

```bash
php please migrate
```

3. Restart app/web stack if your environment requires it.
4. Run one manual sync:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'http://127.0.0.1' \
  --respect-schedule \
  --force-run \
  --verbose
```

5. Verify:
   - `Admin -> SimpleMDM Settings` loads
   - `reports/simplemdm` renders
   - device/resource listings return data

## 4) Docker Upgrade Runbook

Assumes you are in MunkiReport repo root.

1. Update module code:

```bash
cd local/modules/simplemdm
git pull
cd ../../..
```

2. Rebuild/restart containers:

```bash
docker compose up -d --build
```

3. Run migrations in container:

```bash
docker compose exec munkireport php please migrate
```

4. Run one manual sync from host:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'http://localhost:8888' \
  --respect-schedule \
  --force-run \
  --verbose
```

5. Verify:
   - `http://localhost:8888/reports/simplemdm`
   - `http://localhost:8888/show/listing/simplemdm/simplemdm`
   - `http://localhost:8888/show/listing/simplemdm/simplemdm_resources`

## 5) Post-Upgrade Verification

1. Config endpoint health:
   - Open admin page; ensure no save/load errors.
2. Sync health:
   - `last_sync_status = success`
   - `last_sync_time` updated recently
3. Data health:
   - counts non-zero where expected
   - trend/compliance/command widgets render without JS/API errors
4. Device detail page:
   - opens for known serial
   - actions panel loads
   - connected resources/subresources show data

## 6) Rollback Strategy

If upgrade fails:

1. Revert module code:

```bash
cd local/modules/simplemdm
git checkout <previous-known-good-commit>
cd ../../..
```

2. If needed, restore database backup taken pre-upgrade.
3. Restart app/container runtime.
4. Re-run verification checks.

Notes:
- Some migrations may be additive and not trivially reversible without DB restore.
- For production, keep tested DB restore runbooks ready before any upgrade.

## 7) Common Upgrade Failures

1. `php please migrate` fails:
   - Confirm DB connectivity and permissions.
2. UI loads but admin menu missing:
   - Restart app and refresh metadata cache.
3. Sync errors after upgrade:
   - Verify `api_key` still present.
   - Check headers used by sync runner.
4. Docker command failures:
   - Confirm compose service name (`munkireport`) and container status (`docker compose ps`).
