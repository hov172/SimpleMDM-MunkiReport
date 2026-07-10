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
}
