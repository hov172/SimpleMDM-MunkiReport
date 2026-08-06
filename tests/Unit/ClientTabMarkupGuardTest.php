<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tripwire for the client tab's root element.
 *
 * MunkiReport core renders module tabs already wrapped in
 * <div class="tab-pane" id="{client_tabs key}"> — see the $tab_list loop in
 * app/views/client/client_detail.php. views/simplemdm_tab.php used to repeat
 * both the id and the class on its own root div, which put a duplicate
 * `simplemdm-tab` id and a nested .tab-pane into every client detail page.
 *
 * The view now hooks .simplemdm-tab-root instead, so it neither duplicates
 * core's markup nor depends on core's wrapper id.
 */
final class ClientTabMarkupGuardTest extends TestCase
{
    private static string $src;

    public static function setUpBeforeClass(): void
    {
        self::$src = (string) file_get_contents(
            __DIR__ . '/../../views/simplemdm_tab.php'
        );
    }

    public function testTabViewDoesNotRedeclareCoresPaneId(): void
    {
        // Only the explanatory comment may mention the bare id; no attribute
        // may set it. Strip HTML comments before asserting.
        $markup = (string) preg_replace('/<!--.*?-->/s', '', self::$src);

        $this->assertDoesNotMatchRegularExpression(
            '/\bid\s*=\s*(["\'])simplemdm-tab\1/',
            $markup,
            'views/simplemdm_tab.php re-declares id="simplemdm-tab", which MunkiReport core already puts on the wrapper it renders this view into — that creates a duplicate DOM id on every client detail page.'
        );
    }

    public function testTabViewDoesNotNestAnotherTabPane(): void
    {
        $markup = (string) preg_replace('/<!--.*?-->/s', '', self::$src);

        // Match tab-pane only as a whole class token — the view's own
        // .simplemdm-tab-panel class contains it as a substring.
        $this->assertDoesNotMatchRegularExpression(
            '/class\s*=\s*(["\'])[^"\']*\btab-pane\b[^"\']*\1/',
            $markup,
            'views/simplemdm_tab.php declares its own .tab-pane; core already wraps this view in one, so this nests a second pane inside the first.'
        );
    }

    public function testTabViewUsesItsOwnRootHook(): void
    {
        // Styles and delegated handlers hang off this class. If it disappears,
        // the tab silently loses its panel styling and its findings handlers.
        $this->assertStringContainsString(
            'class="simplemdm-tab-root"',
            self::$src,
            'The .simplemdm-tab-root wrapper is gone — tab styling and the delegated MCP findings handlers both hook it.'
        );
        $this->assertMatchesRegularExpression(
            '/\$\(\s*(["\'])\.simplemdm-tab-root\1\s*\)\s*\.on\(/',
            self::$src,
            'The tab no longer delegates its click handlers from .simplemdm-tab-root — handlers must stay scoped to the tab, since the standalone device page binds the same selectors at document level.'
        );
    }
}
