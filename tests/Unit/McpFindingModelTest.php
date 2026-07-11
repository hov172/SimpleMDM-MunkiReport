<?php

use PHPUnit\Framework\TestCase;

final class McpFindingModelTest extends TestCase
{
    public function testComputeFingerprintIsStableForSameInputs(): void
    {
        $a = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $b = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $this->assertSame($a, $b);
    }

    public function testComputeFingerprintDiffersByCategory(): void
    {
        $withCategory = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $withoutCategory = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol');
        $this->assertNotSame($withCategory, $withoutCategory);
    }

    public function testComputeFingerprintIsCaseInsensitive(): void
    {
        $lower = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'c02abc123', 'os_eol', 'os');
        $upper = Simplemdm_mcp_finding_model::computeFingerprint('SOFA_AUDIT', 'C02ABC123', 'OS_EOL', 'OS');
        $this->assertSame($lower, $upper);
    }

    public function testNormalizeFindingRejectsMissingFindingType(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['message' => 'hi'], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingRejectsMissingMessage(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'os_eol'], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingRejectsEmptyStrings(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => '  ', 'message' => ' '], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingDefaultsSeverityToInfo(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm'], 65536);
        $this->assertSame('info', $result['severity']);
    }

    public function testNormalizeFindingRejectsInvalidSeverityFallsBackToInfo(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'severity' => 'critical'], 65536);
        $this->assertSame('info', $result['severity']);
    }

    public function testNormalizeFindingLowercasesSeverity(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'severity' => 'DANGER'], 65536);
        $this->assertSame('danger', $result['severity']);
    }

    public function testNormalizeFindingTruncatesSerialNumberTo64Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'serial_number' => str_repeat('A', 100),
        ], 65536);
        $this->assertSame(64, strlen($result['serial_number']));
    }

    public function testNormalizeFindingTruncatesFindingTypeTo128Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => str_repeat('x', 200), 'message' => 'm',
        ], 65536);
        $this->assertSame(128, strlen($result['finding_type']));
    }

    public function testNormalizeFindingEmptyCategoryBecomesNull(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'category' => '  '], 65536);
        $this->assertNull($result['category']);
    }

    public function testNormalizeFindingTruncatesMessageTo1000Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => str_repeat('m', 1500),
        ], 65536);
        $this->assertSame(1000, strlen($result['message']));
    }

    public function testNormalizeFindingEncodesArrayDataAsJson(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => ['cves_behind' => 3],
        ], 65536);
        $this->assertSame(json_encode(['cves_behind' => 3]), $result['data']);
    }

    public function testNormalizeFindingKeepsStringDataAsIs(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => 'raw-string',
        ], 65536);
        $this->assertSame('raw-string', $result['data']);
    }

    public function testNormalizeFindingTruncatesDataToMetadataMaxBytes(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => str_repeat('d', 200),
        ], 100);
        $this->assertSame(100, strlen($result['data']));
    }

    public function testNormalizeFindingNullOrEmptyDataBecomesEmptyString(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'data' => null], 65536);
        $this->assertSame('', $result['data']);
    }

    private function fakeExisting($status, $occurrenceCount)
    {
        $row = new Simplemdm_mcp_finding_model();
        $row->status = $status;
        $row->occurrence_count = $occurrenceCount;
        return $row;
    }

    private function fakeNormalized(array $overrides = [])
    {
        return array_merge([
            'serial_number' => 'C02ABC123',
            'category'      => 'OS',
            'severity'      => 'danger',
            'message'       => 'OS end-of-life',
            'data'          => '',
        ], $overrides);
    }

    public function testComputeUpsertUpdateOpenFindingCountsAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_OPEN, 3);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('updated', $result['kind']);
        $this->assertSame(4, $result['update']['occurrence_count']);
        $this->assertArrayNotHasKey('status', $result['update']);
    }

    public function testComputeUpsertUpdateResolvedFindingReopens(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 5);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('reopened', $result['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $result['update']['status']);
        $this->assertNull($result['update']['resolved_at']);
    }

    public function testComputeUpsertUpdateSuppressedFindingDoesNotCountAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 2);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('unchanged', $result['kind']);
        $this->assertArrayNotHasKey('status', $result['update']);
    }

    public function testComputeUpsertUpdateIgnoredFindingDoesNotCountAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_IGNORED, 1);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('unchanged', $result['kind']);
    }

    public function testComputeUpsertUpdateRefreshesFieldsRegardlessOfStatus(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 2);
        $normalized = $this->fakeNormalized(['severity' => 'warning', 'message' => 'new message']);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $normalized, 'scan_2', '2026-07-10T00:00:00+00:00');
        $this->assertSame('warning', $result['update']['severity']);
        $this->assertSame('new message', $result['update']['message']);
        $this->assertSame('scan_2', $result['update']['scan_id']);
        $this->assertSame('2026-07-10T00:00:00+00:00', $result['update']['last_seen_at']);
    }

    public function testParseFindingIdsFromIdsArray(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [1, 2, 3]]);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testParseFindingIdsFromSingleId(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['id' => 5]);
        $this->assertSame([5], $result);
    }

    public function testParseFindingIdsDedupes(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [1, 1, 2, 2, 3]]);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testParseFindingIdsDropsNonPositiveAndNonNumeric(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [0, -1, 'abc', 4]]);
        $this->assertSame([4], $result);
    }

    public function testParseFindingIdsEmptyWhenNeitherIdNorIdsPresent(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds([]);
        $this->assertSame([], $result);
    }

    public function testBuildStatusUpdateResolvedSetsResolvedAt(): void
    {
        $result = Simplemdm_mcp_finding_model::buildStatusUpdate(Simplemdm_mcp_finding_model::STATUS_RESOLVED);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_RESOLVED, $result['status']);
        $this->assertNotNull($result['resolved_at']);
    }

    public function testBuildStatusUpdateNonResolvedClearsResolvedAt(): void
    {
        $result = Simplemdm_mcp_finding_model::buildStatusUpdate(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED, $result['status']);
        $this->assertNull($result['resolved_at']);
    }

    public function testParseMultiValueParamSingleValue(): void
    {
        $this->assertSame(['danger'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger'));
    }

    public function testParseMultiValueParamMultipleValues(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger,warning'));
    }

    public function testParseMultiValueParamTrimsWhitespace(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam(' danger , warning '));
    }

    public function testParseMultiValueParamFiltersEmptyEntries(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger,,warning,'));
    }

    public function testParseMultiValueParamEmptyStringReturnsEmptyArray(): void
    {
        $this->assertSame([], Simplemdm_mcp_finding_model::parseMultiValueParam(''));
    }

    public function testSummarizeFindingsDangerWins(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 2, 'warning' => 9, 'info' => 1], 1);
        $this->assertSame('danger', $s['type']);
        $this->assertSame('SimpleMDM MCP: 2 danger findings require immediate attention.', $s['message']);
    }

    public function testSummarizeFindingsWarningsAtThreshold(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 18, 'info' => 0], 10);
        $this->assertSame('warning', $s['type']);
        $this->assertSame('SimpleMDM MCP: 18 warnings detected across the fleet.', $s['message']);
    }

    public function testSummarizeFindingsWarningsBelowThresholdFallsToInfo(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 3, 'info' => 2], 10);
        $this->assertSame('info', $s['type']);
        $this->assertSame('SimpleMDM MCP: informational findings available (3 warnings below threshold, 2 info).', $s['message']);
    }

    public function testSummarizeFindingsNothingOpenReturnsNull(): void
    {
        $this->assertNull(Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 0, 'info' => 0], 1));
    }

    public function testSummarizeFindingsSingularDanger(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 1, 'warning' => 0, 'info' => 0], 1);
        $this->assertSame('SimpleMDM MCP: 1 danger finding requires immediate attention.', $s['message']);
    }
}
