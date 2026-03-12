#!/usr/bin/env python3
"""
Example hardened Option B client reporter for macOS.

Required environment variables:
  MUNKIREPORT_URL
  SIMPLEMDM_CLIENT_SECRET
  SIMPLEMDM_DEVICE_TOKEN

Optional environment variables:
  SIMPLEMDM_CLIENT_SERIAL
  SIMPLEMDM_CLIENT_SOURCE
  SIMPLEMDM_CLIENT_VERSION
  SIMPLEMDM_TIMEOUT_SECONDS
"""

from __future__ import annotations

import hashlib
import hmac
import json
import os
import secrets
import subprocess
import sys
import time
import urllib.error
import urllib.request


def getenv_required(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise SystemExit(f"ERROR: {name} is required")
    return value


def run_text(command: list[str]) -> str:
    try:
        return subprocess.check_output(command, text=True, stderr=subprocess.DEVNULL).strip()
    except Exception:
        return ""


def mac_serial() -> str:
    serial = os.environ.get("SIMPLEMDM_CLIENT_SERIAL", "").strip()
    if serial:
        return serial
    output = run_text(["/usr/sbin/system_profiler", "SPHardwareDataType"])
    for line in output.splitlines():
        if "Serial Number" in line:
            return line.split(":", 1)[1].strip()
    return ""


def console_user() -> str:
    return run_text(["/usr/bin/stat", "-f", "%Su", "/dev/console"])


def uptime_seconds() -> int:
    boot = run_text(["/usr/sbin/sysctl", "-n", "kern.boottime"])
    try:
        sec = int(boot.split("sec = ")[1].split(",", 1)[0])
        return max(0, int(time.time()) - sec)
    except Exception:
        return 0


def filevault_enabled() -> bool:
    status = run_text(["/usr/bin/fdesetup", "status"])
    return "FileVault is On" in status


def build_payload() -> str:
    serial = mac_serial()
    if not serial:
        raise SystemExit("ERROR: unable to determine serial number")
    payload = {
        "serial_number": serial,
        "source": os.environ.get("SIMPLEMDM_CLIENT_SOURCE", "client_reporter").strip() or "client_reporter",
        "client_version": os.environ.get("SIMPLEMDM_CLIENT_VERSION", "1.0.0").strip() or "1.0.0",
        "facts": {
            "console_user": console_user(),
            "uptime_seconds": uptime_seconds(),
            "local_filevault_enabled": filevault_enabled(),
        },
    }
    return json.dumps(payload, separators=(",", ":"))


def main() -> int:
    munkireport_url = getenv_required("MUNKIREPORT_URL").rstrip("/")
    client_secret = getenv_required("SIMPLEMDM_CLIENT_SECRET")
    device_token = getenv_required("SIMPLEMDM_DEVICE_TOKEN")
    timeout = int(os.environ.get("SIMPLEMDM_TIMEOUT_SECONDS", "15") or "15")

    body = build_payload().encode("utf-8")
    timestamp = str(int(time.time()))
    nonce = secrets.token_hex(16)
    message = timestamp.encode("utf-8") + b"\n" + nonce.encode("utf-8") + b"\n" + body
    signature = hmac.new(client_secret.encode("utf-8"), message, hashlib.sha256).hexdigest()

    request = urllib.request.Request(
        url=f"{munkireport_url}/index.php?/module/simplemdm/index?op=ingest_client_facts",
        data=body,
        method="POST",
        headers={
            "Content-Type": "application/json",
            "X-SIMPLEMDM-CLIENT-SECRET": client_secret,
            "X-SIMPLEMDM-CLIENT-TIMESTAMP": timestamp,
            "X-SIMPLEMDM-CLIENT-NONCE": nonce,
            "X-SIMPLEMDM-CLIENT-SIGNATURE": signature,
            "X-SIMPLEMDM-CLIENT-TOKEN": device_token,
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            sys.stdout.write(response.read().decode("utf-8"))
            sys.stdout.write("\n")
        return 0
    except urllib.error.HTTPError as err:
        sys.stderr.write(err.read().decode("utf-8"))
        sys.stderr.write("\n")
        return err.code or 1


if __name__ == "__main__":
    raise SystemExit(main())
