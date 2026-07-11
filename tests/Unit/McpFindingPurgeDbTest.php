<?php

use PHPUnit\Framework\TestCase;

final class McpFindingPurgeDbTest extends TestCase
{
    protected function setUp(): void
    {
        // Each test gets a clean table -- bootstrap.php's connection persists
        // across the whole process, so truncate rather than re-migrate.
        Simplemdm_mcp_finding_model::query()->delete();
    }

    /**
     * Seeds one finding row directly. $daysAgo controls last_seen_at
     * (null = leave last_seen_at NULL, for pre-lifecycle-row coverage).
     */
    private function seedRow($status, $daysAgo, $reportedDaysAgo = null, $suffix = '')
    {
        $lastSeen = $daysAgo === null ? null : gmdate('c', time() - $daysAgo * 86400);
        $reported = gmdate('c', time() - ($reportedDaysAgo ?? ($daysAgo ?? 0)) * 86400);

        return Simplemdm_mcp_finding_model::create([
            'serial_number'    => 'SER' . $status . $suffix,
            'category'         => 'Test',
            'source'           => 'mcp_test',
            'finding_type'     => 'retention_probe_' . $status . $suffix,
            'fingerprint'      => sha1($status . ($daysAgo ?? 'null') . $suffix),
            'severity'         => 'warning',
            'status'           => $status,
            'occurrence_count' => 1,
            'scan_id'          => 'scan_retention_test',
            'message'          => 'retention probe',
            'data'             => null,
            'reported_at'      => $reported,
            'first_seen_at'    => $reported,
            'last_seen_at'     => $lastSeen,
            'resolved_at'      => $status === Simplemdm_mcp_finding_model::STATUS_RESOLVED ? $reported : null,
        ]);
    }

    public function testPurgesResolvedRowOlderThanWindow(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 31);

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(1, $deleted);
        $this->assertSame(0, Simplemdm_mcp_finding_model::count());
    }

    public function testKeepsResolvedRowInsideWindow(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 29);

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(0, $deleted);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testNeverPurgesActiveRowsRegardlessOfAge(): void
    {
        foreach (Simplemdm_mcp_finding_model::ACTIVE_STATUSES as $i => $status) {
            $this->seedRow($status, 400, null, (string) $i);
        }

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(0, $deleted);
        $this->assertSame(
            count(Simplemdm_mcp_finding_model::ACTIVE_STATUSES),
            Simplemdm_mcp_finding_model::count()
        );
    }

    public function testPurgesStaleSuppressedAndIgnoredRows(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 31);
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_IGNORED, 31);
        // A suppressed finding still being observed keeps a fresh last_seen_at
        // (computeUpsertUpdate bumps it on every push) and must survive.
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 0, null, 'fresh');

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(2, $deleted);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
        $this->assertSame(
            Simplemdm_mcp_finding_model::STATUS_SUPPRESSED,
            Simplemdm_mcp_finding_model::first()->status
        );
    }

    public function testZeroAndNegativeRetentionAreNoOps(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 400);

        $this->assertSame(0, Simplemdm_mcp_finding_model::purgeExpired(0, gmdate('c')));
        $this->assertSame(0, Simplemdm_mcp_finding_model::purgeExpired(-5, gmdate('c')));
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testNullLastSeenFallsBackToReportedAt(): void
    {
        // Pre-lifecycle rows can have NULL last_seen_at; reported_at decides.
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, null, 31, 'old');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, null, 5, 'new');

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(1, $deleted);
        $remaining = Simplemdm_mcp_finding_model::first();
        $this->assertSame('retention_probe_resolvednew', $remaining->finding_type);
    }

    public function testReturnsExactDeletedCount(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 31, null, 'a');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 32, null, 'b');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_IGNORED, 33, null, 'c');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 1, null, 'keep');

        $this->assertSame(3, Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c')));
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }
}
