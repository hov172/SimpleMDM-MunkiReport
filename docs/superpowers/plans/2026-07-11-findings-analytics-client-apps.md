# Findings Analytics Client-App Cards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Surface the module's new findings analytics — the 30-day findings timeline and the top-devices-by-risk ranking — as dashboard cards in both the iOS/macOS app (ReportSimpleMDM) and the Android app (ReportSimpleMDMAndroid), following each app's established MCP Findings card pattern exactly.

**Architecture:** Pure read-only client work: each app gains two decodable payload models, two `fetchOptionalModuleRoute` calls against the module's token-readable routes, two `DashboardWidget` enum cases, and two dashboard panels copied from the sibling MCP Findings card idiom. No server changes; the routes already exist on the module's local `main` (`get_mcp_finding_timeline`, `top_devices` in `get_mcp_finding_stats`). Older servers without those routes return 404 → `fetchOptionalModuleRoute` yields nil → the cards simply don't render (this graceful absence is a requirement, not an accident).

**Tech Stack:** iOS — SwiftUI + Swift Charts (already imported elsewhere; deployment targets iOS 17.6 / macOS 14.6, no availability guards needed), XCTest. Android — Jetpack Compose + kotlinx.serialization (lenient Json config), hand-drawn `Canvas` chart (no chart library exists in the app), plain JUnit.

## Global Constraints

- **Repo paths:** iOS = `<ReportSimpleMDM-repo>`; Android = `<ReportSimpleMDMAndroid-repo>` (NOTE: the Android repo is NOT under `GitHub/`). Both are on `main`, clean, one commit ahead of origin (the MCP Findings card work, unpushed).
- Create branch `findings-analytics-cards` in each repo before its first task; commit style `feat: ...` (match `git log -5` in each repo).
- **Server data contracts (verbatim, from the module):**
  - `GET module/simplemdm/get_mcp_finding_timeline?days=30` → `{"labels": ["YYYY-MM-DD", ...], "new": [int, ...], "resolved": [int, ...]}` (arrays same length as labels; route is in `$token_read_actions`; returns 403 when mcp findings disabled, 404 on older servers — both must hide the card).
  - `GET module/simplemdm/get_mcp_finding_stats` → object containing (among other keys the apps must ignore) `"top_devices": [{"serial_number": "...", "score": int, "danger": int, "warning": int, "info": int}, ...]` — max 10, sorted score desc; score = 3·danger + 2·warning + 1·info.
- **Widget default policy:** iOS default set is `Set(DashboardWidget.allCases)` → new cases are default-enabled for fresh installs, but existing users with a persisted subset will NOT auto-gain them (same caveat as `mcpFindings`; acceptable, do not migrate). Android module widgets are opt-in: do NOT add the new constants to `DashboardWidget.defaultSet`.
- **iOS test noise:** 4 pre-existing failures on clean HEAD (`SettingsTests.testApplyConnectionSettingsDoesNotIncrementRevisionForEquivalentConfiguration`, `SettingsTests.testApplyConnectionSettingsIncrementsRevisionOnceForChangedConfiguration`, `SyncSessionTrackerTests.testBeginRequestCategorizesDeviceRequests`, `SyncSessionTrackerTests.testFinalizeReturnsNilWhenNoRequestsOccurred`). Run new tests with `-only-testing:`; a full-suite run may show exactly those 4 red and no more.
- Xcode 15+ `fileSystemSynchronizedGroups` — new Swift test files are auto-discovered; NO `project.pbxproj` edits.
- Android: explicit per-class imports (no wildcards) in all touched files.
- Copy strings verbatim where specified: card titles "Findings Timeline" / "Top Devices by Findings"; empty states "No findings activity in the last 30 days." / "No active findings.".
- **Out of scope (deliberate, do not add):** surfacing the fleet findings summary *event* in the apps (off-by-default server-side and redundant — the apps' MCP Findings card already shows live severity totals; the event's home is the web Events UI); any use of the `finding_type` filter (the apps have no findings browser); driving lifecycle actions (acknowledge/resolve/…) from mobile; version bumps, releases, or pushes (user-requested only).
- This sandbox sweeps `/tmp` aggressively — redirect long build/test output to a gitignored file inside the respective repo and read it back.

---

## Slice A — iOS (ReportSimpleMDM)

### Task 1: iOS payload models + decode tests (TDD)

**Files:**
- Modify: `ReportSimpleMDM/Models.swift` (append after `McpFindingsPayload`, ~line 1756; extend `ModuleDashboardData` ~line 1758)
- Create: `ReportSimpleMDMTests/McpAnalyticsTests.swift`

**Interfaces:**
- Consumes: nothing new (mirrors `McpFindingsPayload` decode-tolerance conventions).
- Produces: `McpTimelinePayload` (`labels: [String]`, `newCounts: [Int]`, `resolvedCounts: [Int]`, `hasActivity: Bool`), `McpTopDevice` (`serial_number: String`, `score/danger/warning/info: Int`, `Identifiable` by serial), `McpFindingStatsPayload` (`top_devices: [McpTopDevice]`), and two new `ModuleDashboardData` fields `mcpTimeline: McpTimelinePayload?` / `mcpFindingStats: McpFindingStatsPayload?` (both defaulted `nil` in the init so `.empty` keeps compiling). Consumed by Tasks 2–3.

- [ ] **Step 0: Create the branch**

```bash
cd <ReportSimpleMDM-repo>
git checkout -b findings-analytics-cards
```

- [ ] **Step 1: Write the failing tests** — create `ReportSimpleMDMTests/McpAnalyticsTests.swift`:

```swift
import XCTest
@testable import ReportSimpleMDM

final class McpAnalyticsTests: XCTestCase {
    func testDecodesTimelinePayloadWithKeyMapping() throws {
        let json = """
        {"labels": ["2026-07-09", "2026-07-10", "2026-07-11"], "new": [1, 183, 0], "resolved": [0, 7, 2]}
        """.data(using: .utf8)!

        let payload = try JSONDecoder().decode(McpTimelinePayload.self, from: json)
        XCTAssertEqual(payload.labels.count, 3)
        XCTAssertEqual(payload.newCounts, [1, 183, 0])
        XCTAssertEqual(payload.resolvedCounts, [0, 7, 2])
        XCTAssertTrue(payload.hasActivity)
    }

    func testTimelineToleratesMissingArraysAndReportsNoActivity() throws {
        let payload = try JSONDecoder().decode(
            McpTimelinePayload.self,
            from: #"{"labels": []}"#.data(using: .utf8)!
        )
        XCTAssertTrue(payload.labels.isEmpty)
        XCTAssertTrue(payload.newCounts.isEmpty)
        XCTAssertTrue(payload.resolvedCounts.isEmpty)
        XCTAssertFalse(payload.hasActivity)
    }

    func testDecodesTopDevicesIgnoringOtherStatsKeys() throws {
        let json = """
        {
            "total": 190,
            "by_severity": {"danger": 1, "warning": 182, "info": 7},
            "by_category": {"Health": 182},
            "top_devices": [
                {"serial_number": "BBB", "score": 6, "danger": 2, "warning": 0, "info": 0},
                {"serial_number": "AAA", "score": 3, "danger": 0, "warning": 1, "info": 1}
            ]
        }
        """.data(using: .utf8)!

        let payload = try JSONDecoder().decode(McpFindingStatsPayload.self, from: json)
        XCTAssertEqual(payload.top_devices.count, 2)
        XCTAssertEqual(payload.top_devices.first?.serial_number, "BBB")
        XCTAssertEqual(payload.top_devices.first?.score, 6)
        XCTAssertEqual(payload.top_devices.first?.danger, 2)
    }

    func testTopDevicesToleratesNumericStringsAndMissingList() throws {
        let withStrings = try JSONDecoder().decode(
            McpFindingStatsPayload.self,
            from: #"{"top_devices": [{"serial_number": "CCC", "score": "4", "danger": "1", "warning": "0", "info": "1"}]}"#.data(using: .utf8)!
        )
        XCTAssertEqual(withStrings.top_devices.first?.score, 4)
        XCTAssertEqual(withStrings.top_devices.first?.danger, 1)

        let missing = try JSONDecoder().decode(
            McpFindingStatsPayload.self,
            from: #"{"total": 0}"#.data(using: .utf8)!
        )
        XCTAssertTrue(missing.top_devices.isEmpty)
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd <ReportSimpleMDM-repo> && xcodebuild -project ReportSimpleMDM.xcodeproj -scheme ReportSimpleMDM -destination 'platform=macOS' -only-testing:ReportSimpleMDMTests/McpAnalyticsTests test -quiet 2>&1 | tail -20`
Expected: BUILD FAILS — `cannot find 'McpTimelinePayload' in scope` (compile error is the RED state here since the types don't exist).

- [ ] **Step 3: Implement the models** — append to `ReportSimpleMDM/Models.swift` directly after `McpFindingsPayload` (~line 1756):

```swift
struct McpTimelinePayload: Decodable, Hashable, Sendable {
    let labels: [String]
    let newCounts: [Int]
    let resolvedCounts: [Int]

    private enum CodingKeys: String, CodingKey, Sendable {
        case labels
        case newCounts = "new"
        case resolvedCounts = "resolved"
    }

    nonisolated init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        labels = try container.decodeIfPresent([String].self, forKey: .labels) ?? []
        newCounts = try container.decodeIfPresent([Int].self, forKey: .newCounts) ?? []
        resolvedCounts = try container.decodeIfPresent([Int].self, forKey: .resolvedCounts) ?? []
    }

    nonisolated var hasActivity: Bool {
        newCounts.contains { $0 > 0 } || resolvedCounts.contains { $0 > 0 }
    }
}

struct McpTopDevice: Decodable, Hashable, Identifiable, Sendable {
    let serial_number: String
    let score: Int
    let danger: Int
    let warning: Int
    let info: Int

    nonisolated var id: String { serial_number }

    private enum CodingKeys: String, CodingKey, Sendable {
        case serial_number, score, danger, warning, info
    }

    nonisolated init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        serial_number = try container.decodeIfPresent(String.self, forKey: .serial_number) ?? ""
        // Stats rows come from the module's database driver, which can
        // serialize numerics as strings, so accept both forms.
        func flexibleInt(_ key: CodingKeys) -> Int {
            if let intValue = try? container.decode(Int.self, forKey: key) { return intValue }
            if let stringValue = try? container.decode(String.self, forKey: key) { return Int(stringValue) ?? 0 }
            return 0
        }
        score = flexibleInt(.score)
        danger = flexibleInt(.danger)
        warning = flexibleInt(.warning)
        info = flexibleInt(.info)
    }
}

struct McpFindingStatsPayload: Decodable, Hashable, Sendable {
    let top_devices: [McpTopDevice]

    private enum CodingKeys: String, CodingKey, Sendable {
        case top_devices
    }

    nonisolated init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        top_devices = try container.decodeIfPresent([McpTopDevice].self, forKey: .top_devices) ?? []
    }
}
```

Then extend `ModuleDashboardData` (~line 1758) the same way `mcpFindings` was added — append two stored properties after `mcpFindings`:

```swift
    let mcpTimeline: McpTimelinePayload?
    let mcpFindingStats: McpFindingStatsPayload?
```

append two defaulted parameters at the end of the init signature:

```swift
        mcpTimeline: McpTimelinePayload? = nil,
        mcpFindingStats: McpFindingStatsPayload? = nil
```

and two assignments at the end of the init body:

```swift
        self.mcpTimeline = mcpTimeline
        self.mcpFindingStats = mcpFindingStats
```

(`.empty` needs no edit — it relies on the default arguments, same as `mcpFindings`.)

- [ ] **Step 4: Run to pass**

Run: same command as Step 2.
Expected: `** TEST SUCCEEDED **` (4 tests in McpAnalyticsTests).

- [ ] **Step 5: Commit**

```bash
git add ReportSimpleMDM/Models.swift ReportSimpleMDMTests/McpAnalyticsTests.swift
git commit -m "feat: MCP analytics payload models (timeline, top devices)"
```

### Task 2: iOS Findings Timeline card

**Files:**
- Modify: `ReportSimpleMDM/SimpleMDMService.swift` (`loadModuleDashboardData()`, ~line 2307)
- Modify: `ReportSimpleMDM/Settings.swift` (`DashboardWidget`, lines 29–84)
- Modify: `ReportSimpleMDM/Dashboard/DashboardView.swift` (`munkiReportInsightsSection` ~line 313, `configurationBackedModuleDataAvailable` ~line 1378, new panel struct near `McpFindingsPanel` ~line 1998)

**Interfaces:**
- Consumes: Task 1's `McpTimelinePayload` (`labels`/`newCounts`/`resolvedCounts`/`hasActivity`) and `ModuleDashboardData.mcpTimeline`.
- Produces: `DashboardWidget.mcpTimeline` case; `McpTimelinePanel` view; timeline fetch wired into the dashboard load. Task 3 adds its case/panel alongside these.

- [ ] **Step 1: Wire the fetch.** In `loadModuleDashboardData()`, after the `async let mcpFindings ...` line add:

```swift
        async let mcpTimeline = self.fetchOptionalModuleRoute("get_mcp_finding_timeline?days=30") as McpTimelinePayload?
```

and in the `ModuleDashboardData(...)` construction after `mcpFindings: await mcpFindings` add:

```swift
            mcpTimeline: await mcpTimeline,
```

- [ ] **Step 2: Add the enum case.** In `Settings.swift` `DashboardWidget`, add case `mcpTimeline` after `mcpFindings`, and extend all three switches (they are exhaustive — the compiler enforces this):

```swift
    case mcpTimeline
```
```swift
        case .mcpTimeline: return "Findings Timeline"
```
```swift
        case .mcpTimeline: return "New vs resolved MCP findings over the last 30 days."
```
and add `.mcpTimeline` to the `requiresModuleData` true-list:
```swift
        case .syncHealth, .compliance, .supplementalOverview, .appleCare, .mcpFindings, .mcpTimeline:
            return true
```

- [ ] **Step 3: Add the panel.** In `DashboardView.swift`: add `import Charts` at the top (alongside the existing imports — the file does not import it yet; `EnrollmentStatusView.swift`/`DeviceActivityView.swift` show the established Charts usage). Then add below `McpFindingsPanel`:

```swift
private struct McpTimelinePanel: View {
    let payload: McpTimelinePayload

    private struct TimelinePoint: Identifiable, Hashable {
        let label: String
        let series: String
        let count: Int
        var id: String { "\(series)-\(label)" }
    }

    private var points: [TimelinePoint] {
        payload.labels.enumerated().flatMap { index, label -> [TimelinePoint] in
            let day = String(label.suffix(5)) // "YYYY-MM-DD" -> "MM-DD"
            return [
                TimelinePoint(label: day, series: "New",
                              count: index < payload.newCounts.count ? payload.newCounts[index] : 0),
                TimelinePoint(label: day, series: "Resolved",
                              count: index < payload.resolvedCounts.count ? payload.resolvedCounts[index] : 0)
            ]
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Findings Timeline")
                .font(.title2.weight(.semibold))

            if payload.hasActivity {
                Chart(points) { point in
                    LineMark(
                        x: .value("Day", point.label),
                        y: .value("Count", point.count)
                    )
                    .foregroundStyle(by: .value("Series", point.series))
                }
                .chartForegroundStyleScale(["New": AppColors.warning, "Resolved": AppColors.mint])
                .chartXAxis {
                    AxisMarks(values: .automatic(desiredCount: 5))
                }
                .frame(height: 180)
            } else {
                Text("No findings activity in the last 30 days.")
                    .foregroundStyle(.secondary)
            }
        }
        .padding(20)
        .glassmorphic()
    }
}
```

- [ ] **Step 4: Render + availability.** In `munkiReportInsightsSection`, after the `McpFindingsPanel` `if` block, add:

```swift
            if settings.showsDashboardWidget(.mcpTimeline), let timeline = moduleData.mcpTimeline {
                McpTimelinePanel(payload: timeline)
            }
```

In `configurationBackedModuleDataAvailable`, append to the OR chain:

```swift
        moduleDashboardData.mcpTimeline != nil
```

- [ ] **Step 5: Build + focused tests**

Run: `xcodebuild -project ReportSimpleMDM.xcodeproj -scheme ReportSimpleMDM -destination 'platform=macOS' build -quiet 2>&1 | tail -5`
Expected: `** BUILD SUCCEEDED **`
Run: the Task 1 test command again.
Expected: `** TEST SUCCEEDED **`

- [ ] **Step 6: Commit**

```bash
git add ReportSimpleMDM/SimpleMDMService.swift ReportSimpleMDM/Settings.swift ReportSimpleMDM/Dashboard/DashboardView.swift
git commit -m "feat: Findings Timeline card in MunkiReport Insights dashboard section"
```

### Task 3: iOS Top Devices card + suite verification

**Files:**
- Modify: `ReportSimpleMDM/SimpleMDMService.swift`, `ReportSimpleMDM/Settings.swift`, `ReportSimpleMDM/Dashboard/DashboardView.swift` (same locations as Task 2)

**Interfaces:**
- Consumes: Task 1's `McpFindingStatsPayload`/`McpTopDevice`, Task 2's edited switch arms (extend, don't duplicate).
- Produces: `DashboardWidget.mcpTopDevices` case; `McpTopDevicesPanel` view; stats fetch wired in.

- [ ] **Step 1: Wire the fetch.** In `loadModuleDashboardData()`, after the `async let mcpTimeline ...` line:

```swift
        async let mcpFindingStats = self.fetchOptionalModuleRoute("get_mcp_finding_stats") as McpFindingStatsPayload?
```

and in the constructor after `mcpTimeline: await mcpTimeline,`:

```swift
            mcpFindingStats: await mcpFindingStats
```

- [ ] **Step 2: Add the enum case** (same four spots as Task 2):

```swift
    case mcpTopDevices
```
```swift
        case .mcpTopDevices: return "Top Devices by Findings"
```
```swift
        case .mcpTopDevices: return "Devices ranked by weighted active-finding risk."
```
```swift
        case .syncHealth, .compliance, .supplementalOverview, .appleCare, .mcpFindings, .mcpTimeline, .mcpTopDevices:
            return true
```

- [ ] **Step 3: Add the panel** below `McpTimelinePanel`:

```swift
private struct McpTopDevicesPanel: View {
    let payload: McpFindingStatsPayload

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Top Devices by Findings")
                .font(.title2.weight(.semibold))

            if payload.top_devices.isEmpty {
                Text("No active findings.")
                    .foregroundStyle(.secondary)
            } else {
                VStack(alignment: .leading, spacing: 12) {
                    ForEach(Array(payload.top_devices.enumerated()), id: \.element.id) { index, device in
                        deviceRow(rank: index + 1, device: device)
                        if device.id != payload.top_devices.last?.id {
                            Divider()
                        }
                    }
                }
            }
        }
        .padding(20)
        .glassmorphic()
    }

    private func deviceRow(rank: Int, device: McpTopDevice) -> some View {
        HStack(spacing: 10) {
            Text("#\(rank)")
                .font(.caption.weight(.bold))
                .foregroundStyle(.secondary)
                .frame(width: 28, alignment: .leading)
            Text(device.serial_number)
                .font(.callout.weight(.medium))
                .lineLimit(1)
            Spacer()
            if device.danger > 0 { countBadge(device.danger, AppColors.danger) }
            if device.warning > 0 { countBadge(device.warning, AppColors.warning) }
            if device.info > 0 { countBadge(device.info, AppColors.accent) }
            Text("\(device.score)")
                .font(.headline)
        }
    }

    private func countBadge(_ count: Int, _ tint: Color) -> some View {
        Text("\(count)")
            .font(.caption2.weight(.bold))
            .padding(.horizontal, 6)
            .padding(.vertical, 2)
            .background(tint.opacity(0.25), in: Capsule())
            .foregroundStyle(tint)
    }
}
```

- [ ] **Step 4: Render + availability.** After the `McpTimelinePanel` `if` block:

```swift
            if settings.showsDashboardWidget(.mcpTopDevices), let stats = moduleData.mcpFindingStats {
                McpTopDevicesPanel(payload: stats)
            }
```

In `configurationBackedModuleDataAvailable`, append `moduleDashboardData.mcpFindingStats != nil` to the OR chain.

- [ ] **Step 5: Full verification**

Run: `xcodebuild -project ReportSimpleMDM.xcodeproj -scheme ReportSimpleMDM -destination 'platform=macOS' build -quiet 2>&1 | tail -5` → `** BUILD SUCCEEDED **`
Run: full suite `xcodebuild -project ReportSimpleMDM.xcodeproj -scheme ReportSimpleMDM -destination 'platform=macOS' test -quiet 2>&1 | tail -30` (redirect to a gitignored log file in-repo and read it back — /tmp is swept).
Expected: only the 4 pre-existing failures listed in Global Constraints; McpAnalyticsTests and McpFindingsTests all green.

- [ ] **Step 6: Commit**

```bash
git add ReportSimpleMDM/SimpleMDMService.swift ReportSimpleMDM/Settings.swift ReportSimpleMDM/Dashboard/DashboardView.swift
git commit -m "feat: Top Devices by Findings card in MunkiReport Insights dashboard section"
```

---

## Slice B — Android (ReportSimpleMDMAndroid)

### Task 4: Android payload models + decode tests (TDD)

**Files:**
- Modify: `app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/model/DashboardModels.kt` (append after `McpFindingsPayload` ~line 264; extend `ModuleDashboardData` ~line 291)
- Create: `app/src/test/java/com/AyalaSolutions/reportsimplemdmandroid/model/McpAnalyticsPayloadTest.kt`

**Interfaces:**
- Produces: `McpTimelinePayload` (`labels`, `newCounts` ← JSON `new`, `resolvedCounts` ← JSON `resolved`, `hasActivity`), `McpTopDevice`, `McpFindingStatsPayload` (`top_devices`), plus `ModuleDashboardData.mcpTimeline`/`.mcpFindingStats` fields and their `hasAnyData` checks. Consumed by Tasks 5–6.

- [ ] **Step 0: Create the branch**

```bash
cd <ReportSimpleMDMAndroid-repo>
git checkout -b findings-analytics-cards
```

- [ ] **Step 1: Write the failing tests** — create `McpAnalyticsPayloadTest.kt`:

```kotlin
package com.AyalaSolutions.reportsimplemdmandroid.model

import kotlinx.serialization.json.Json
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class McpAnalyticsPayloadTest {

    // Mirrors SimpleMDMService's lenient module-route decoder configuration.
    private val json = Json {
        ignoreUnknownKeys = true
        coerceInputValues = true
        explicitNulls = false
        isLenient = true
    }

    @Test
    fun `decodes timeline payload with key mapping`() {
        val payload = json.decodeFromString<McpTimelinePayload>(
            """{"labels": ["2026-07-09", "2026-07-10", "2026-07-11"], "new": [1, 183, 0], "resolved": [0, 7, 2]}"""
        )

        assertEquals(3, payload.labels.size)
        assertEquals(listOf(1, 183, 0), payload.newCounts)
        assertEquals(listOf(0, 7, 2), payload.resolvedCounts)
        assertTrue(payload.hasActivity)
    }

    @Test
    fun `timeline tolerates missing arrays and reports no activity`() {
        val payload = json.decodeFromString<McpTimelinePayload>("""{"labels": []}""")
        assertTrue(payload.labels.isEmpty())
        assertTrue(payload.newCounts.isEmpty())
        assertTrue(payload.resolvedCounts.isEmpty())
        assertFalse(payload.hasActivity)
    }

    @Test
    fun `decodes top devices ignoring other stats keys`() {
        val payload = json.decodeFromString<McpFindingStatsPayload>(
            """
            {
              "total": 190,
              "by_severity": {"danger": 1, "warning": 182, "info": 7},
              "by_category": {"Health": 182},
              "top_devices": [
                {"serial_number": "BBB", "score": 6, "danger": 2, "warning": 0, "info": 0},
                {"serial_number": "AAA", "score": 3, "danger": 0, "warning": 1, "info": 1}
              ]
            }
            """.trimIndent()
        )

        assertEquals(2, payload.top_devices.size)
        assertEquals("BBB", payload.top_devices.first().serial_number)
        assertEquals(6, payload.top_devices.first().score)
        assertEquals(2, payload.top_devices.first().danger)
    }

    @Test
    fun `top devices tolerates missing list`() {
        val payload = json.decodeFromString<McpFindingStatsPayload>("""{"total": 0}""")
        assertTrue(payload.top_devices.isEmpty())
    }
}
```

- [ ] **Step 2: Run to fail**

Run: `cd <ReportSimpleMDMAndroid-repo> && ./gradlew :app:testDebugUnitTest --tests 'com.AyalaSolutions.reportsimplemdmandroid.model.McpAnalyticsPayloadTest' 2>&1 | tail -15`
Expected: compilation failure — `Unresolved reference: McpTimelinePayload` (RED = compile error since the types don't exist).

- [ ] **Step 3: Implement the models** — append to `DashboardModels.kt` after `McpFindingsPayload` (~line 264). Add `import kotlinx.serialization.SerialName` to the file's imports (explicit per-class style):

```kotlin
@Serializable
data class McpTimelinePayload(
    val labels: List<String> = emptyList(),
    @SerialName("new") val newCounts: List<Int> = emptyList(),
    @SerialName("resolved") val resolvedCounts: List<Int> = emptyList()
) {
    val hasActivity: Boolean get() = newCounts.any { it > 0 } || resolvedCounts.any { it > 0 }
}

@Serializable
data class McpTopDevice(
    val serial_number: String = "",
    val score: Int = 0,
    val danger: Int = 0,
    val warning: Int = 0,
    val info: Int = 0
)

@Serializable
data class McpFindingStatsPayload(
    val top_devices: List<McpTopDevice> = emptyList()
)
```

Extend `ModuleDashboardData` (~line 291): add after `mcpFindings`:

```kotlin
    val mcpTimeline: McpTimelinePayload? = null,
    val mcpFindingStats: McpFindingStatsPayload? = null
```

and extend `hasAnyData`'s OR chain:

```kotlin
            mcpFindings != null ||
            mcpTimeline != null ||
            mcpFindingStats != null
```

- [ ] **Step 4: Run to pass**

Run: same command as Step 2.
Expected: `BUILD SUCCESSFUL`, 4 tests passing.

- [ ] **Step 5: Commit**

```bash
git add app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/model/DashboardModels.kt app/src/test/java/com/AyalaSolutions/reportsimplemdmandroid/model/McpAnalyticsPayloadTest.kt
git commit -m "feat: MCP analytics payload models (timeline, top devices)"
```

### Task 5: Android Findings Timeline card

**Files:**
- Modify: `app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/SimpleMDMService.kt` (`loadModuleDashboardData()`, ~line 1108)
- Modify: `app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/AppState.kt` (`DashboardWidget`, lines 130–202)
- Modify: `app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/ui/dashboard/DashboardScreen.kt` (render block ~lines 328–345; new composables near `McpFindingsPanel` ~line 970)

**Interfaces:**
- Consumes: Task 4's `McpTimelinePayload` and `ModuleDashboardData.mcpTimeline`.
- Produces: `DashboardWidget.MCP_TIMELINE` (opt-in — NOT in `defaultSet`); `McpTimelinePanel` composable.

- [ ] **Step 1: Wire the fetch.** In `loadModuleDashboardData()` add after the `mcpFindings = ...` argument:

```kotlin
        mcpTimeline = fetchOptionalModuleRoute<McpTimelinePayload>("get_mcp_finding_timeline?days=30"),
```

Add `import com.AyalaSolutions.reportsimplemdmandroid.model.McpTimelinePayload` to the file's imports.

- [ ] **Step 2: Add the enum constant.** In `AppState.kt` `DashboardWidget`, after `MCP_FINDINGS(...)` add:

```kotlin
    MCP_TIMELINE(
        title = "Findings Timeline",
        description = "New vs resolved MCP findings over the last 30 days.",
        requiresModuleData = true
    ),
```

(Do NOT add it to `defaultSet` — module widgets are opt-in.)

- [ ] **Step 3: Add the composables.** In `DashboardScreen.kt`, below `mcpSeverityColor`, add (new imports, explicit per-class: `androidx.compose.foundation.shape.CircleShape`, `androidx.compose.ui.graphics.Path`, `androidx.compose.ui.graphics.StrokeCap`, `androidx.compose.ui.graphics.drawscope.Stroke`, `com.AyalaSolutions.reportsimplemdmandroid.model.McpTimelinePayload` — check which are already imported first; `Canvas`, `background`, `Box`, `Spacer`, `Arrangement` are already in use in this file):

```kotlin
@Composable
private fun McpTimelinePanel(payload: McpTimelinePayload) {
    Panel("Findings Timeline") {
        if (!payload.hasActivity) {
            Text("No findings activity in the last 30 days.", color = AppColors.secondaryText)
        } else {
            McpTimelineChart(payload)
            Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                McpTimelineLegendDot("New", AppColors.warning)
                McpTimelineLegendDot("Resolved", AppColors.mint)
            }
        }
    }
}

@Composable
private fun McpTimelineChart(payload: McpTimelinePayload) {
    val maxCount = maxOf(payload.newCounts.maxOrNull() ?: 0, payload.resolvedCounts.maxOrNull() ?: 0, 1)
    Canvas(modifier = Modifier.fillMaxWidth().height(160.dp)) {
        val pointCount = payload.labels.size
        if (pointCount < 2) return@Canvas
        val stepX = size.width / (pointCount - 1).toFloat()
        fun yFor(value: Int): Float = size.height - (value.toFloat() / maxCount.toFloat()) * size.height
        fun drawSeries(values: List<Int>, color: Color) {
            val path = Path()
            for (i in 0 until pointCount) {
                val x = i * stepX
                val y = yFor(values.getOrElse(i) { 0 })
                if (i == 0) path.moveTo(x, y) else path.lineTo(x, y)
            }
            drawPath(path, color = color, style = Stroke(width = 2.dp.toPx(), cap = StrokeCap.Round))
        }
        drawSeries(payload.newCounts, AppColors.warning)
        drawSeries(payload.resolvedCounts, AppColors.mint)
    }
}

@Composable
private fun McpTimelineLegendDot(label: String, color: Color) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Box(modifier = Modifier.size(8.dp).background(color, CircleShape))
        Spacer(modifier = Modifier.width(6.dp))
        Text(label, color = AppColors.secondaryText, style = MaterialTheme.typography.labelSmall)
    }
}
```

- [ ] **Step 4: Render gate.** In the dashboard LazyColumn's `moduleData.hasAnyData` block, after the `MCP_FINDINGS` `if`:

```kotlin
    if (DashboardWidget.MCP_TIMELINE in widgets && moduleData.mcpTimeline != null) {
        item { McpTimelinePanel(moduleData.mcpTimeline) }
    }
```

- [ ] **Step 5: Compile + tests**

Run: `./gradlew :app:compileDebugKotlin 2>&1 | tail -5` → `BUILD SUCCESSFUL`
Run: `./gradlew :app:testDebugUnitTest 2>&1 | tail -5` → `BUILD SUCCESSFUL`

- [ ] **Step 6: Commit**

```bash
git add app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/SimpleMDMService.kt app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/AppState.kt app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/ui/dashboard/DashboardScreen.kt
git commit -m "feat: Findings Timeline card in MunkiReport Insights dashboard section"
```

### Task 6: Android Top Devices card + suite verification

**Files:**
- Modify: same three files as Task 5 (same locations).

**Interfaces:**
- Consumes: Task 4's `McpFindingStatsPayload`/`McpTopDevice`; Task 5's render-block position.
- Produces: `DashboardWidget.MCP_TOP_DEVICES` (opt-in); `McpTopDevicesPanel` composable.

- [ ] **Step 1: Wire the fetch.** In `loadModuleDashboardData()` after the `mcpTimeline = ...` argument:

```kotlin
        mcpFindingStats = fetchOptionalModuleRoute<McpFindingStatsPayload>("get_mcp_finding_stats")
```

Add `import com.AyalaSolutions.reportsimplemdmandroid.model.McpFindingStatsPayload`.

- [ ] **Step 2: Add the enum constant** after `MCP_TIMELINE(...)`:

```kotlin
    MCP_TOP_DEVICES(
        title = "Top Devices by Findings",
        description = "Devices ranked by weighted active-finding risk.",
        requiresModuleData = true
    );
```

(Note the `;` moves from the previous last constant to this one.) Do NOT add to `defaultSet`.

- [ ] **Step 3: Add the composables** below the timeline composables (imports: `com.AyalaSolutions.reportsimplemdmandroid.model.McpFindingStatsPayload`, `com.AyalaSolutions.reportsimplemdmandroid.model.McpTopDevice`, `androidx.compose.foundation.shape.RoundedCornerShape` if not present):

```kotlin
@Composable
private fun McpTopDevicesPanel(payload: McpFindingStatsPayload) {
    Panel("Top Devices by Findings") {
        if (payload.top_devices.isEmpty()) {
            Text("No active findings.", color = AppColors.secondaryText)
        } else {
            payload.top_devices.forEachIndexed { index, device ->
                McpTopDeviceRow(rank = index + 1, device = device, showDivider = index < payload.top_devices.lastIndex)
            }
        }
    }
}

@Composable
private fun McpTopDeviceRow(rank: Int, device: McpTopDevice, showDivider: Boolean) {
    Column {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(
                "#$rank",
                color = AppColors.secondaryText,
                style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.Bold),
                modifier = Modifier.width(32.dp)
            )
            Text(
                device.serial_number,
                color = Color.White,
                modifier = Modifier.weight(1f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            if (device.danger > 0) McpSeverityCountBadge(device.danger, AppColors.danger)
            if (device.warning > 0) McpSeverityCountBadge(device.warning, AppColors.warning)
            if (device.info > 0) McpSeverityCountBadge(device.info, AppColors.accent)
            Spacer(modifier = Modifier.width(8.dp))
            Text(device.score.toString(), color = Color.White, style = MaterialTheme.typography.titleSmall)
        }
        if (showDivider) HorizontalDivider(color = AppColors.divider, modifier = Modifier.padding(top = 12.dp))
    }
}

@Composable
private fun McpSeverityCountBadge(count: Int, tint: Color) {
    Text(
        count.toString(),
        color = tint,
        style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.Bold),
        modifier = Modifier
            .padding(start = 6.dp)
            .background(tint.copy(alpha = 0.22f), RoundedCornerShape(8.dp))
            .padding(horizontal = 6.dp, vertical = 2.dp)
    )
}
```

- [ ] **Step 4: Render gate** after the `MCP_TIMELINE` block:

```kotlin
    if (DashboardWidget.MCP_TOP_DEVICES in widgets && moduleData.mcpFindingStats != null) {
        item { McpTopDevicesPanel(moduleData.mcpFindingStats) }
    }
```

- [ ] **Step 5: Full verification**

Run: `./gradlew :app:compileDebugKotlin 2>&1 | tail -5` → `BUILD SUCCESSFUL`
Run: `./gradlew :app:testDebugUnitTest 2>&1 | tail -5` → `BUILD SUCCESSFUL` (all tests, incl. McpFindingsPayloadTest + McpAnalyticsPayloadTest)
Run: `./gradlew :app:assembleDebug 2>&1 | tail -5` → `BUILD SUCCESSFUL`

- [ ] **Step 6: Commit**

```bash
git add app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/SimpleMDMService.kt app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/app/AppState.kt app/src/main/java/com/AyalaSolutions/reportsimplemdmandroid/ui/dashboard/DashboardScreen.kt
git commit -m "feat: Top Devices by Findings card in MunkiReport Insights dashboard section"
```

---

## Self-Review Notes

- **Spec coverage:** timeline route → Tasks 2 (iOS) / 5 (Android); `top_devices` → Tasks 3 / 6; decode contracts + tolerance → Tasks 1 / 4. Events-summary surfacing and `finding_type` usage are explicitly out of scope (Global Constraints) with rationale; lifecycle actions from mobile likewise.
- **Type consistency:** `McpTimelinePayload.newCounts/resolvedCounts/hasActivity` and `McpFindingStatsPayload.top_devices`/`McpTopDevice` names are identical across Tasks 1↔2↔3 and 4↔5↔6; `ModuleDashboardData` field names `mcpTimeline`/`mcpFindingStats` used consistently in service wiring and render gates on both platforms.
- **Judgment calls:** iOS timeline uses Swift Charts (already a dependency-free framework import used elsewhere in the app); Android draws with `Canvas` because no chart library exists and adding one for a single card violates YAGNI. Android cards are opt-in (matching every sibling module widget); iOS cards are default-on for fresh installs only (the `allCases` default) — existing users toggle them on in Dashboard settings, same as `mcpFindings`. `hasActivity` gates the chart, not the card: an all-zero timeline still renders the card with its empty-state line, matching the web widget's wording.
- **Merge/branch:** each repo's work is independently shippable; the two repos have no cross-dependency (both consume the already-merged module API). Note both repos also carry the unpushed MCP Findings card commit — this branch stacks on it.
