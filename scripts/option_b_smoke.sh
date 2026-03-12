#!/bin/sh
set -eu

APP_ROOT="${1:-/var/munkireport}"
SERIAL="${2:-C02C72ANLVDL}"
SECRET="${3:-phase2_client_secret}"

DB_PATH="${APP_ROOT}/app/db/db.sqlite"
INGEST_URL="http://127.0.0.1/index.php?/module/simplemdm/index&op=ingest_client_facts"

php -r '
$db = new PDO("sqlite:'"${DB_PATH}"'");
$updates = [
    "client_reporter_enabled" => "1",
    "client_reporter_secret" => "'"${SECRET}"'",
    "client_reporter_history_enabled" => "1",
];
foreach ($updates as $name => $value) {
    $stmt = $db->prepare("UPDATE simplemdm_config SET value = :value, updated_at = datetime(\"now\") WHERE name = :name");
    $stmt->execute(["value" => $value, "name" => $name]);
}
echo "config_updated\n";
'

HTTP_CODE="$(curl -s -o /tmp/simplemdm_option_b_ingest.json -w '%{http_code}' \
  -X POST "${INGEST_URL}" \
  -H 'Content-Type: application/json' \
  -H "X-SIMPLEMDM-CLIENT-SECRET: ${SECRET}" \
  -d "{\"serial_number\":\"${SERIAL}\",\"reported_at\":\"2026-03-12T12:00:00Z\",\"client_version\":\"1.0.0\",\"facts\":{\"mdm_profile_present\":true,\"console_user\":\"jdoe\",\"uptime_seconds\":86400,\"munki_last_run_result\":\"success\",\"local_filevault_enabled\":true,\"not_allowed\":\"x\"}}")"

echo "http_code=${HTTP_CODE}"
echo "ingest_body:"
cat /tmp/simplemdm_option_b_ingest.json 2>/dev/null || true
echo

php -r '
$db = new PDO("sqlite:'"${DB_PATH}"'");
$serial = "'"${SERIAL}"'";
$stmt = $db->prepare("SELECT fact_key, COALESCE(fact_value_string, CAST(fact_value_int AS TEXT), CAST(fact_value_bool AS TEXT), fact_value_json) AS value, reported_at FROM simplemdm_client_fact WHERE serial_number = :serial ORDER BY fact_key");
$stmt->execute(["serial" => $serial]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "current_facts:\n";
echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM simplemdm_client_fact_history WHERE serial_number = :serial");
$stmt->execute(["serial" => $serial]);
echo "history_count=" . (int) $stmt->fetchColumn() . "\n";
'
