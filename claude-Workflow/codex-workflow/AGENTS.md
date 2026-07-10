# AGENTS.md
> Codex instructions — committed to git. Personal overrides → AGENTS.override.md (gitignored).
> Global preferences (all projects) → ~/.codex/AGENTS.md
> Run $setupdotcodex after cloning to customize .codex/ for this stack.
> Keep this file under 200 lines. Beyond that, adherence drops. Move detail to sub-AGENTS.md files.

---

## PROJECT

| | |
|---|---|
| **Name** | _fill in_ |
| **Platform** | ☐ Web ☐ iOS/macOS ☐ Android ☐ Windows ☐ Cross-platform |
| **Stack** | _fill in_ |
| **Package manager** | _fill in_ |
| **Phase** | ☐ Greenfield ☐ Active Dev ☐ Beta ☐ Production |
| **Repo / CI** | _fill in_ |

**Architecture** (2–4 sentences — WHY, not file listings):
_fill in_

**Key decisions** (WHY non-obvious choices were made):
_fill in_

**Domain terms**: _fill in_ — or see `docs/glossary.md`

**Where to find things**: Architecture → `docs/architecture.md` · API → `docs/api.md` · Tasks → `tasks/todo.md` · Lessons → `tasks/lessons.md`

---

## COMMANDS

```bash
build:     _fill in_
test:      _fill in_      # full suite
test-one:  _fill in_      # single file/name
lint:      _fill in_
format:    _fill in_
typecheck: _fill in_
dev:       _fill in_
pre-pr:    _fill in_      # run all of the above
```

---

## RULES

**Always:** Run tests before PR · Find root causes (no band-aids) · Minimal code impact · Give Codex a verification path

**Never:** Push to `main`/`production` · Hardcode secrets · Modify generated files (`*.gen.ts`, `*.generated.*`) · Keep pushing when sideways — re-plan

---

## WORKFLOW (16 Steps — Every Project, No Exceptions)

```
1   IDEA          → Even vague is enough to start
1b  PRE-BUILD     → $pre-build — data mapping, edge cases, security, production thinking
2   RESEARCH      → $research-first — find what exists before building anything
3   BRAINSTORM    → $brainstorming — design approval HARD-GATE — no code until approved
4   PRD           → $prd — goals, features, stack, data model, API keys needed
5   PLAN          → $writing-plans — bite-sized tasks → tasks/todo.md. Check in before building.
6   WORKTREES     → git worktree add for isolated parallel sessions
7   SECRETS       → Create .env, add all API keys from PRD before first build
8   BUILD         → $dispatching-parallel-agents or sequential — subagent per task
9   TEST          → Run locally — test every feature, every edge case
10  SCREENSHOT    → Bug → screenshot → paste → "fix this" → $screenshot-fix → repeat
11  CODE REVIEW   → $pr-review — @code-reviewer @security-reviewer @performance-reviewer
12  TECHDEBT      → $techdebt — kill debt before shipping
13  SHIP          → $ship — commit → push → PR
14  DEPLOY        → GitHub → Railway → secrets in Variables → public URL
15  COMPOUND      → Update AGENTS.md with lessons from every PR review
16  ITERATE       → Back to step 1 for next feature — loop forever
```

**Key rules:**
- **Plan first** — always use $brainstorming + $writing-plans before coding
- **Goes sideways** → STOP. Re-plan. Never keep pushing.
- **Plan annotation cycle** — Codex drafts `plan.md`, you add `> NOTE:` inline, send back with **"address all notes, don't implement yet"**
- **After any correction** → update `tasks/lessons.md`. Say: "Update AGENTS.md so you don't make that mistake again."
- **Never mark done** without proving it works. "Would a staff engineer approve this?"
- **Screenshot Survival Loop** — Bug → screenshot → paste → "fix this" → test → repeat.

### 🚫 Anti-Learning-Purgatory
Ship before it's perfect. Get it in front of real users. Iterate based on actual feedback.
Signs of purgatory: "it's not ready yet" · adding features before getting users · haven't shown it to anyone

### 🔬 Research Before Building
Run `$research-first` before every new app or major feature. Never build from scratch what already exists.

### ❌ Anti-Sycophancy (receiving code review)
- **Never say:** "You're absolutely right!" · "Great point!" · "Excellent feedback!"
- **Instead:** restate the technical requirement, verify against codebase, push back with technical reasoning if wrong
- If feedback is unclear: STOP — ask for clarification before implementing anything

### 🚧 HARD-GATE before implementation
Codex MUST NOT write code, scaffold, or take implementation action until:
1. Brainstorming is complete and design is approved (`$brainstorming`)
2. A written plan exists (`$writing-plans`)

---

## PLATFORM

### 🌐 Web
`bun install` · `bun run dev` · `bun run typecheck` · `bun run test -- -t "name"` · `bun run lint` · `bun run format`
CSS: _fill in_ · State: _fill in_ · API: _fill in_

### 🍎 iOS / macOS
`xcodebuild -scheme <S> -destination '...'` · `swiftlint` · `swift-format --in-place -r .`
Min target: _fill in_ · UI: _fill in_ · Arch: _fill in_
Conventions: Views→`/Views` · VMs→`/ViewModels` · No logic in View body · `async/await` only

**App Store Auditor** — auto-triggers on "audit my app", "ready to submit", "got rejected", "TestFlight" + 15 more.
**SwiftUI Pro** — run `$swiftui-pro` after any SwiftUI code review. Targets iOS 26 + Swift 6.2. By Paul Hudson.

### 🤖 Android
`./gradlew assembleDebug` · `./gradlew test` · `./gradlew lint` · `ktlint --format`
Min SDK: _fill in_ · UI: _fill in_ · Arch: _fill in_

### 🪟 Windows
`dotnet build` · `dotnet test` · `dotnet format` · `dotnet publish -c Release`
Framework: _fill in_ · Arch: _fill in_

### 🌍 Cross-platform
MAUI: `dotnet build -f net9.0-ios` · Flutter: `flutter build` · RN: `npx react-native run-ios`

---

## CONTEXT MANAGEMENT

At ~50% context → tell Codex: "Summarize progress, preserve: modified files, current test status, active task"
Switching tasks → start a new session
Edit this file mid-session → paste relevant section into your next prompt

Don't paste large docs directly — use paths: "see `docs/api.md`"
**Dump anything in a folder** — documents, PDFs, spreadsheets — Codex can read and work on all of it
MCP configs with >20k tokens eat your working context. Keep them lean.

---

## CODE STYLE

TypeScript: `type` over `interface` · Never `enum` → string literal unions
Comments: WHY not WHAT · Markers: `TODO(name): desc (#issue)` · `FIXME` · `HACK` · `NOTE`
No dead code (git has history) · No magic numbers (named constants only)

---

## COMPOUNDING ENGINEERING

After every PR review, update AGENTS.md with lessons learned:
```
# In a PR comment or during session:
"Add to AGENTS.md: never use enum, always prefer string literal unions"
```
See `.github/workflows/codex-review.yml` — tag @codex in PR comments for automated review.

---

## PR CHECKLIST

- [ ] Tests pass · Lint clean · Build succeeds (debug + release)
- [ ] No secrets · No orphaned TODOs · Root cause fixed (not band-aid)
- [ ] "Staff engineer approval" test passed
- [ ] AGENTS.md + tasks/lessons.md updated if needed
- [ ] PR description: what / why / how to test · Ticket linked

---

## LESSONS LEARNED
> Full log in `tasks/lessons.md`. High-signal ones promoted here.

| Date | Lesson |
|---|---|
| — | Never `enum` — string literal unions only |
| — | "address all notes, don't implement yet" prevents plan-skipping |
| — | $pre-build before every build — data mapping catches hidden assumptions |
| — | research.md before planning — garbage in, garbage out |
| — | _add yours_ |

---
*Last updated: — · By: —*
