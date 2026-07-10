<?php

use PHPUnit\Framework\TestCase;

final class McpFindingUpsertDbTest extends TestCase
{
    protected function setUp(): void
    {
        // Each test gets a clean table -- bootstrap.php's connection persists
        // across the whole process, so truncate rather than re-migrate.
        Simplemdm_mcp_finding_model::query()->delete();
    }

    /**
     * Mirrors ingest_mcp_findings' single-finding orchestration: normalize,
     * look up by (source, fingerprint), then either computeUpsertUpdate()+save
     * or create(). Returns the row and the 'kind' (inserted/updated/reopened/
     * unchanged) so tests can assert on both.
     */
    private function upsertOne($source, array $rawFinding, $scanId, $now)
    {
        $metadataMaxBytes = 65536;
        $normalized = Simplemdm_mcp_finding_model::normalizeFinding($rawFinding, $metadataMaxBytes);
        $this->assertNotNull($normalized, 'test fixture finding must be valid');

        $fingerprint = Simplemdm_mcp_finding_model::computeFingerprint(
            $source, $normalized['serial_number'], $normalized['finding_type'], $normalized['category']
        );

        $existing = Simplemdm_mcp_finding_model::where('source', $source)
            ->where('fingerprint', $fingerprint)->first();

        if ($existing) {
            $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $normalized, $scanId, $now);
            $existing->fill($result['update']);
            $existing->save();
            return ['row' => $existing, 'kind' => $result['kind']];
        }

        $row = Simplemdm_mcp_finding_model::create([
            'serial_number'    => $normalized['serial_number'],
            'category'         => $normalized['category'],
            'source'           => $source,
            'finding_type'     => $normalized['finding_type'],
            'fingerprint'      => $fingerprint,
            'severity'         => $normalized['severity'],
            'status'           => Simplemdm_mcp_finding_model::STATUS_OPEN,
            'occurrence_count' => 1,
            'scan_id'          => $scanId,
            'message'          => $normalized['message'],
            'data'             => $normalized['data'],
            'reported_at'      => $now,
            'first_seen_at'    => $now,
            'last_seen_at'     => $now,
            'resolved_at'      => null,
        ]);
        return ['row' => $row, 'kind' => 'inserted'];
    }

    public function testFirstPushCreatesOpenRowWithOccurrenceCountOne(): void
    {
        $result = $this->upsertOne('sofa_audit', [
            'finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS',
        ], 'scan_1', '2026-07-10T00:00:00+00:00');

        $this->assertSame('inserted', $result['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $result['row']->status);
        $this->assertSame(1, $result['row']->occurrence_count);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testRepeatPushUpdatesSameRowNotDuplicate(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $second = $this->upsertOne('sofa_audit', $finding, 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('updated', $second['kind']);
        $this->assertSame($first['row']->id, $second['row']->id);
        $this->assertSame(2, $second['row']->occurrence_count);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testResolvedFindingReopensOnRepush(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $first['row']->fill(['status' => Simplemdm_mcp_finding_model::STATUS_RESOLVED, 'resolved_at' => '2026-07-10T00:30:00+00:00']);
        $first['row']->save();

        $second = $this->upsertOne('sofa_audit', $finding, 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('reopened', $second['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $second['row']->status);
        $this->assertNull($second['row']->resolved_at);
    }

    public function testSuppressedFindingRefreshesFieldsButStaysSuppressed(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'old message', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $first['row']->fill(['status' => Simplemdm_mcp_finding_model::STATUS_SUPPRESSED]);
        $first['row']->save();

        $second = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'new message', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('unchanged', $second['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, $second['row']->status);
        $this->assertSame('new message', $second['row']->message);
    }

    public function testDifferingOnlyByCategoryProducesTwoDistinctRows(): void
    {
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'Compliance'], 'scan_1', '2026-07-10T00:00:00+00:00');

        $this->assertSame(2, Simplemdm_mcp_finding_model::count());
    }

    public function testCategorylessFindingFingerprintsSameAsEmptyCategory(): void
    {
        $withoutCategory = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $expectedFingerprint = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02X', 'os_eol', '');
        $this->assertSame($expectedFingerprint, $withoutCategory['row']->fingerprint);
    }

    public function testReplacePushAutoResolvesUntouchedActiveRows(): void
    {
        // Simulates a full scan: two findings pushed in scan_1, only one re-pushed in scan_2.
        $stale = $this->upsertOne('sofa_audit', ['finding_type' => 'filevault_disabled', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'FileVault'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $touched = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_1', '2026-07-10T00:00:00+00:00');

        // Re-push only $touched's fingerprint for scan_2, then mirror ingest_mcp_findings'
        // auto-resolve step: mark every active row for this source NOT in touchedIds as resolved.
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_2', '2026-07-10T01:00:00+00:00');
        $touchedIds = [$touched['row']->id];

        Simplemdm_mcp_finding_model::where('source', 'sofa_audit')
            ->whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
            ->whereNotIn('id', $touchedIds)
            ->update(['status' => Simplemdm_mcp_finding_model::STATUS_RESOLVED, 'resolved_at' => '2026-07-10T01:00:00+00:00']);

        $stale['row']->refresh();
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_RESOLVED, $stale['row']->status);
    }
}
