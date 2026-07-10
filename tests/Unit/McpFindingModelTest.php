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
}
