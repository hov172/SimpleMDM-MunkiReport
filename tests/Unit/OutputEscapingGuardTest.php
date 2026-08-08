<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tripwires for the module's XSS defences.
 *
 * Every view renders SimpleMDM-supplied text — device names, model names,
 * assignment groups, resource names, MCP finding messages. A user who renames
 * their own Mac controls device_name, so unescaped output here means script
 * execution in the browser of any MunkiReport operator who opens the page,
 * including a global admin whose session can reach save_config and run_script.
 *
 * Two rules are enforced:
 *
 *  1. There is exactly one escaper, window.simplemdmEscapeHtml, and it escapes
 *     quotes. The old per-view helper was `$('<div>').text(v).html()`, which
 *     relies on innerHTML serialization and so leaves `"` and `'` intact —
 *     safe in a text node, unsafe inside href="..." or title="...".
 *  2. The two DataTables listings escape every column. DataTables writes cell
 *     content as HTML, so a column with no render callback is a sink.
 */
final class OutputEscapingGuardTest extends TestCase
{
    private const VIEW_DIR = __DIR__ . '/../../views';

    /** @return string[] Absolute paths of every view */
    private static function viewFiles(): array
    {
        return (array) glob(self::VIEW_DIR . '/*.php');
    }

    public function testCanonicalEscaperExistsAndEscapesQuotes(): void
    {
        $assets = (string) file_get_contents(
            self::VIEW_DIR . '/simplemdm_widget_modern_assets.php'
        );

        $this->assertStringContainsString(
            'window.simplemdmEscapeHtml = function',
            $assets,
            'The shared escaper has gone missing; every view delegates to it.'
        );

        foreach (['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'] as $entity) {
            $this->assertStringContainsString(
                "'" . $entity . "'",
                $assets,
                sprintf(
                    'simplemdmEscapeHtml must emit %s. Dropping quote escaping reopens attribute injection in href/title/data-* values.',
                    $entity
                )
            );
        }

        $this->assertStringContainsString(
            'window.simplemdmEscapeUrl = function',
            $assets,
            'simplemdmEscapeUrl guards href attributes against javascript:/data: URLs.'
        );
    }

    public function testNoViewUsesTheQuoteUnsafeEscaper(): void
    {
        $offenders = [];

        foreach (self::viewFiles() as $file) {
            $src = (string) file_get_contents($file);
            // Strip JS line comments so the explanatory note in the assets
            // file describing the old pattern does not trip the guard.
            $code = (string) preg_replace('#^\s*//.*$#m', '', $src);

            if (preg_match('/\$\(\s*[\'"]<\w+>[\'"]\s*\)\s*\.text\(.*?\)\s*\.html\(\)/', $code)) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These views use \$('<div>').text(v).html() to escape. That leaves quotes unescaped, so any value "
            . "placed inside a quoted attribute can break out of it. Use window.simplemdmEscapeHtml instead: "
            . implode(', ', $offenders)
        );
    }

    /**
     * @dataProvider listingViews
     */
    public function testEveryDataTablesColumnIsEscaped(string $view): void
    {
        $src = (string) file_get_contents(self::VIEW_DIR . '/' . $view);

        preg_match_all("/\{\s*data:\s*'([a-z_]+)'\s*\}/i", $src, $matches);

        $this->assertSame(
            [],
            $matches[1],
            sprintf(
                '%s declares DataTables columns with no render callback: %s. DataTables writes cell content as '
                . 'HTML, so each of these renders SimpleMDM-supplied text unescaped. Give them a render that '
                . 'escapes (or $.fn.dataTable.render.text()).',
                $view,
                implode(', ', $matches[1])
            )
        );
    }

    public static function listingViews(): array
    {
        return [
            'device listing'   => ['simplemdm_listing.php'],
            'resource listing' => ['simplemdm_resources_listing.php'],
        ];
    }

    public function testDeviceListingDoesNotInterpolateRawRowData(): void
    {
        $src = (string) file_get_contents(self::VIEW_DIR . '/simplemdm_listing.php');

        // `+ data +` with no escaping wrapper was the original stored-XSS sink
        // on serial_number, device_name and status.
        $this->assertDoesNotMatchRegularExpression(
            "/'\s*\+\s*data\s*\+\s*'/",
            $src,
            'simplemdm_listing.php interpolates a raw DataTables `data` value into HTML again. '
            . 'Wrap it in esc() — device_name is settable by the end user in SimpleMDM.'
        );
    }
}
