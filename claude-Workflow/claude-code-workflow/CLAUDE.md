# CLAUDE.md
> Team instructions — committed to git. Personal overrides → CLAUDE.local.md (gitignored).
> Global preferences (all projects) → ~/.claude/CLAUDE.md
> Run `/setupdotclaude` after cloning to customize .claude/ for this stack.
> Keep this file under 200 lines. Beyond that, adherence drops. Move detail to .claude/rules/.

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

**Always:** Run tests before PR · Find root causes (no band-aids) · Minimal code impact · Give Claude a verification path

**Never:** Push to `main`/`production` · Hardcode secrets · Modify generated files (`*.gen.ts`, `*.generated.*`) · Keep pushing when sideways — re-plan

**Hard rules live in `.claude/settings.json`** (deterministic). Soft guidelines live here.

---

## WORKFLOW (16 Steps — Every Project, No Exceptions)

```
1  IDEA          → Even vague is enough to start
1b PRE-BUILD      → /pre-build — data mapping, edge cases, security, production thinking BEFORE any code
2  RESEARCH      → /research-first — find what exists before building anything
3  BRAINSTORM    → Design approval HARD-GATE — no code until approved
4  PRD           → /prd — goals, features, stack, data model, API keys needed
5  PLAN          → /writing-plans — bite-sized tasks with verification steps → tasks/todo.md
6  WORKTREES     → git worktree add for isolated parallel sessions
7  SECRETS       → Create .env, add all API keys from PRD before first line of code
8  BUILD         → Subagent per task, two-stage review (spec compliance → code quality)
9  TEST          → Run locally — test every feature, every edge case
10 SCREENSHOT    → Bug → screenshot → paste → "fix this" → test → repeat
11 CODE REVIEW   → /pr-review or @code-reviewer @security-reviewer @performance-reviewer
12 TECHDEBT      → /techdebt — kill duplicated/dead/complex code before shipping
13 SHIP          → /ship — commit → push → PR with description
14 DEPLOY        → GitHub → Railway → add secrets to Variables → generate public URL
15 COMPOUND      → Tag @claude on PR → Claude updates CLAUDE.md with lessons → future sessions smarter
16 ITERATE       → Back to step 1 for next feature — loop forever
```

**Key rules:**
- **Plan Mode** — Shift+Tab twice for any 3+ step task. Check in before coding.
- **Goes sideways** → STOP. Re-plan. Never keep pushing.
- **Plan annotation cycle** — Claude drafts `plan.md`, you add `> NOTE:` inline, send back with **"address all notes, don't implement yet"** (without this phrase Claude skips the plan and codes).
- **After any correction** → update `tasks/lessons.md`. Say: "Update CLAUDE.md so you don't make that mistake again."
- **Never mark done** without proving it works. "Would a staff engineer approve this?"
- **Screenshot Survival Loop** — Bug → screenshot → paste → "fix this" → test → repeat. This IS the process.

### 🚫 Anti-Learning-Purgatory
Ship before it's perfect. Get it in front of real users. Iterate based on actual feedback — not imagined requirements.
Signs of purgatory: "it's not ready yet" · adding features before getting users · haven't shown it to anyone
Cure: ship today, iterate tomorrow.

### 🔬 Research Before Building
Run `/research-first` before every new app or major feature. Find what already exists — GitHub repos, libraries, APIs, CLIs. Stand on the shoulders of giants. Never build from scratch what already exists.

### ❌ Anti-Sycophancy (receiving code review)
- **Never say:** "You're absolutely right!" · "Great point!" · "Excellent feedback!"
- **Instead:** restate the technical requirement, verify against codebase, push back with technical reasoning if wrong
- Verify before implementing. Ask before assuming. Technical correctness over social comfort.
- If feedback is unclear: STOP — ask for clarification before implementing anything

### 🚧 HARD-GATE before implementation
Claude MUST NOT write code, scaffold, or take implementation action until:
1. Brainstorming is complete and design is approved (use `brainstorming` skill)
2. A written plan exists (use `writing-plans` skill)
This applies to EVERY request regardless of perceived simplicity.

---

## PLATFORM

### 🌐 Web
`bun install` · `bun run dev` · `bun run typecheck` · `bun run test -- -t "name"` · `bun run lint` · `bun run format`
CSS: _fill in_ · State: _fill in_ · API: _fill in_

### 🍎 iOS / macOS
`xcodebuild -scheme <S> -destination '...'` · `swiftlint` · `swift-format --in-place -r .`
Min target: _fill in_ · UI: _fill in_ · Arch: _fill in_
Conventions: Views→`/Views` · VMs→`/ViewModels` · No logic in View body · `async/await` only

**App Store Auditor** (`.claude/skills/app-store-submission-auditor/`) — auto-triggers on "audit my app", "ready to submit", "got rejected", "TestFlight" + 15 more phrases.

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

```bash
/cost        # check token usage after every major task
/memory      # browse Claude's auto-written session notes
```

At 50% context → `/compact "preserve modified files and current test status"`
Switching tasks → `/clear`
Edit this file mid-session → `@CLAUDE.md` to force re-read

Don't `@`-file large docs — use paths: "see `docs/api.md`"
**Dump anything in a folder** — documents, spreadsheets, PDFs, transcripts, CSVs — open the folder in VS Code and Claude can summarize, transform, or build an app from it
MCP configs >20k tokens eat your working context. Keep them lean.
When compacting: preserve modified file list, current test status, active task.

---

## CODE STYLE

TypeScript: `type` over `interface` · Never `enum` → string literal unions
Comments: WHY not WHAT · Markers: `TODO(name): desc (#issue)` · `FIXME` · `HACK` · `NOTE`
No dead code (git has history) · No magic numbers (named constants only)

---

## ECOSYSTEM ADDONS
Optional: `claude-mem` (AI memory) · `ECC` (maximalist 119-skill system)
See `docs/ecosystem.md` for install commands and details.
Hook profile: `export ECC_HOOK_PROFILE=strict` — adds active PR to session context (no addon needed).

**UI UX Pro Max** (included — 7 skills, 54k stars, MIT): `/ui-ux-pro-max` · `/design-system` · `/design` · `/brand` · `/ui-styling` · `/banner-design` · `/slides`
Auto-triggers on any UI/UX request. Requires Python 3. Priority rules: Accessibility (CRITICAL) → Touch (CRITICAL) → Performance → Style → Layout → Typography → Animation → Forms → Navigation → Charts.

---

## COMPOUNDING ENGINEERING

```
# PR comment → Claude commits to CLAUDE.md automatically:
@claude add to CLAUDE.md: never use enum, always prefer string literal unions
```
Install: `/install-github-action` — or use `.github/workflows/claude-review.yml` (included).

---

## PR CHECKLIST

- [ ] Tests pass · Lint clean · Build succeeds (debug + release)
- [ ] No secrets · No orphaned TODOs · Root cause fixed (not band-aid)
- [ ] "Staff engineer approval" test passed
- [ ] CLAUDE.md + tasks/lessons.md updated if needed
- [ ] PR description: what / why / how to test · Ticket linked

---

## LESSONS LEARNED
> Full log in `tasks/lessons.md`. High-signal ones promoted here.

| Date | Lesson |
|---|---|
| — | Never `enum` — string literal unions only |
| — | "address all notes, don't implement yet" prevents plan-skipping |
| — | `/compact` at 50% context — don't wait for auto-compaction |
| — | `@CLAUDE.md` forces re-read if edited mid-session |
| — | research.md before planning — garbage in, garbage out |
| — | _add yours_ |

---
*Last updated: — · By: —*
