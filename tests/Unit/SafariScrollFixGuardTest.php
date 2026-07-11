<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tripwire guard for the Safari scroll fixes in
 * views/simplemdm_widget_modern_assets.php.
 *
 * There is no JS test framework for view files, and the fix has already been
 * silently regressed once: commit b36af45 centralized the wheel handling and
 * dropped the passive elastic-bounce clamp (part of the 2026-07-10 shake fix,
 * restored in c647ffa). Safari widget scrolling only works when ALL of these
 * components are present together — see the two Safari postmortems in
 * docs/DEVELOPER_GUIDE.md before touching any of them.
 *
 * If an assertion here fails, do NOT weaken the test: you have removed or
 * renamed part of a load-bearing fix. Read the postmortems first.
 */
final class SafariScrollFixGuardTest extends TestCase
{
    private static string $src;

    public static function setUpBeforeClass(): void
    {
        self::$src = (string) file_get_contents(
            __DIR__ . '/../../views/simplemdm_widget_modern_assets.php'
        );
    }

    public function testWheelHandlerPresent(): void
    {
        // JS-driven wheel scrolling: Safari won't natively wheel-scroll
        // overflow:auto sub-scrollers for phase-less input (plain mice, KVMs).
        $this->assertStringContainsString(
            "addEventListener('wheel'",
            self::$src,
            'bindWheelScroll wheel listener missing — Safari sub-scrollers will not wheel-scroll (see DEVELOPER_GUIDE Safari sub-scroller wheel postmortem).'
        );
        $this->assertStringContainsString(
            '{ passive: false }',
            self::$src,
            'Wheel listener must be non-passive so it can preventDefault applied deltas.'
        );
        $this->assertStringContainsString(
            'data-simplemdm-wheel-scroll',
            self::$src,
            'Wheel-binding marker attribute missing — rebind guards and QA checks depend on it.'
        );
    }

    public function testElasticBounceClampPresent(): void
    {
        // Passive scroll clamp: trackpad GESTURE input bypasses the wheel
        // handler; without this clamp Safari rubber-bands sub-scrollers past
        // their bounds and widgets visibly shake at scroll edges. Dropped once
        // by b36af45, restored by c647ffa. It must stay passive: consuming
        // gesture events breaks Safari click-through on widget controls.
        $this->assertStringContainsString(
            "addEventListener('scroll'",
            self::$src,
            'Passive elastic-bounce clamp missing — Safari trackpad scrolling will shake widgets at scroll edges (regression of c647ffa; see DEVELOPER_GUIDE).'
        );
        $this->assertStringContainsString(
            '{ passive: true }',
            self::$src,
            'Bounce clamp must be passive — preventDefault on gesture events breaks Safari click-through.'
        );

        $wheelPos = strpos(self::$src, "addEventListener('wheel'");
        $clampPos = strpos(self::$src, "addEventListener('scroll'");
        $this->assertNotFalse($wheelPos);
        $this->assertNotFalse($clampPos);
        $this->assertGreaterThan(
            $wheelPos,
            $clampPos,
            'The bounce clamp must live inside bindWheelScroll (after the wheel listener) so every bound scroller gets it.'
        );
    }

    public function testBindWheelScrollExportedForDynamicViews(): void
    {
        // The device page binds its dynamically rendered finding-data
        // disclosures through this export.
        $this->assertStringContainsString(
            'window.simplemdmBindWheelScroll',
            self::$src,
            'simplemdmBindWheelScroll export missing — device-page finding disclosures lose the Safari wheel fix.'
        );
    }

    public function testAutoMarkedListsGetWheelBinding(): void
    {
        // markScrollableSimplemdmLists() turns any >12-item widget list into a
        // Safari sub-scroller; each one needs the wheel+clamp binding or the
        // widget won't wheel-scroll (mice) and will bounce-shake (trackpads)
        // in Safari. Regressed in the wild before being wired here.
        $this->assertStringContainsString(
            "window.simplemdmBindWheelScroll(w.querySelector('.list-group'))",
            self::$src,
            'markScrollableSimplemdmLists no longer binds the lists it marks scrollable — auto-scrollable widgets lose the Safari wheel/bounce fix.'
        );
        $this->assertStringContainsString(
            ".simplemdm-modern-widget.simplemdm-list-scroll .list-group')",
            self::$src,
            'bindKnownScrollers no longer sweeps auto-marked scrollable lists.'
        );
    }

    public function testCollapsibleWidgetsBindTheirOwnScrollers(): void
    {
        // The group and resource-types widgets manage their own collapsed
        // sub-scrollers via jQuery .css(overflowY: 'auto') — they are on
        // markScrollableSimplemdmLists()'s exclusion list precisely because
        // of that, so the central auto-binding never reaches them. Each must
        // bind its scroll body itself or Safari users get no wheel scrolling
        // and uncorrected elastic bounce in those widgets.
        foreach (
            [
                'simplemdm_group_widget.php',
                'simplemdm_resource_types_widget.php',
            ] as $view
        ) {
            $src = (string) file_get_contents(__DIR__ . '/../../views/' . $view);
            $this->assertStringContainsString(
                'window.simplemdmBindWheelScroll(',
                $src,
                $view . ' no longer binds its collapsed sub-scroller — Safari scrolling breaks in that widget (see DEVELOPER_GUIDE Safari postmortems).'
            );
        }
    }

    public function testResizeLoopGatePresent(): void
    {
        // Synthetic-resize feedback loop fix: applyLayoutMode must never
        // dispatch resize unconditionally from a path a resize listener
        // re-schedules (widgets shook ~120x/sec in Safari before this gate).
        $this->assertStringContainsString(
            'selfResizeDispatch',
            self::$src,
            'selfResizeDispatch re-entrancy gate missing — the Safari resize feedback loop (scroll-shake postmortem) will return.'
        );
    }

    public function testHoverLiftSuppressedInScrollableLists(): void
    {
        // Hover-lift replaying on rows sliding under a stationary cursor reads
        // as the list shaking while it scrolls.
        $this->assertStringContainsString(
            '.simplemdm-modern-widget.simplemdm-list-scroll .list-group-item',
            self::$src,
            'Hover-lift suppression selector for scrollable lists missing — rows will "shake" as they scroll under the cursor.'
        );
    }

    public function testNoNewOverscrollBehavior(): void
    {
        // overscroll-behavior: none on a sub-scroller freezes Safari wheel
        // scrolling entirely. Exactly one occurrence is allowed: the
        // pre-existing `contain` on .simplemdm-section-body.simplemdm-collapsed.
        // If you add another, you are almost certainly reintroducing the bug —
        // read the DEVELOPER_GUIDE postmortem first, then update this count
        // and document why.
        $this->assertSame(
            1,
            substr_count(self::$src, 'overscroll-behavior'),
            'Unexpected overscroll-behavior occurrence count in widget assets — new overscroll-behavior rules freeze Safari sub-scrollers (see DEVELOPER_GUIDE).'
        );
    }
}
