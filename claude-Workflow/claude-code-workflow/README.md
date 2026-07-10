# Claude Code Workflow Kit

A complete `.claude/` setup based on Boris Cherny's workflow, dotclaude,
Trail of Bits, and Claude's own best-practice documentation.

---

## What's included

```
CLAUDE.md                          # Project rules — commit this, fill in the blanks
CLAUDE.local.md.example            # Personal overrides — gitignored, never committed
global-CLAUDE.md.example           # Universal preferences across all projects
.gitignore                         # Ignores local files that shouldn't be committed
tasks/
  todo.md                          # Claude writes plans here
  lessons.md                       # Claude logs corrections here
.github/
  workflows/claude-review.yml      # @claude in PR comments → auto-updates CLAUDE.md
.claude/
  settings.json                    # Permissions + hooks (committed, shared with team)
  settings.local.json.example      # Personal permission overrides (gitignored)
  .mcp.json.example                # MCP server config (fill in, then commit)
  memory/MEMORY.md                 # Auto-memory index (gitignored, Claude manages)
  commands/                        # Slash commands — invoke with /command-name
    commit-push-pr.md              # Stage → commit → push → PR
    grill.md                       # Adversarial pre-PR review
    techdebt.md                    # Find duplicated/dead/complex code
    context-dump.md                # Pull recent activity from all connected tools
    fix-issue.md                   # Fix a GitHub issue or error message
  agents/                          # Specialist subagents — invoke with @agent-name
    code-reviewer.md
    security-reviewer.md
    performance-reviewer.md
    doc-reviewer.md
    frontend-designer.md
  hooks/                           # Shell scripts — run automatically
    session-start.sh               # Injects git context at session start
    protect-files.sh               # Blocks edits to .env, *.pem, secrets/
    scan-secrets.sh                # Blocks writing AWS keys, tokens, private keys
    warn-large-files.sh            # Blocks writes to node_modules/, dist/, binaries
    block-dangerous-commands.sh    # Blocks push to main, force push, rm -rf, DROP TABLE
    format-on-save.sh              # Auto-formats after every edit (Prettier/Ruff/gofmt/etc)
    pre-compact.sh                 # Saves context before auto-compaction
    anti-rationalization.sh        # Catches Claude declaring victory on incomplete work
  rules/                           # Auto-loaded modular rules (alwaysApply or path-scoped)
    code-quality.md                # Always loaded
    testing.md                     # Always loaded
    security.md                    # Loads on API/auth/route files
    error-handling.md              # Loads on API/service files
    frontend.md                    # Loads on .tsx/.jsx/component files
    database.md                    # Loads on migration files
  skills/                          # Auto-invoked workflows
    app-store-submission-auditor/  # iOS App Store audit — auto-triggers on submission phrases
    debug-fix/                     # /debug-fix
    explain/                       # /explain
    hotfix/                        # /hotfix
    pr-review/                     # /pr-review
    refactor/                      # /refactor
    setupdotclaude/                # /setupdotclaude
    ship/                          # /ship
    tdd/                           # /tdd
    test-writer/                   # /test-writer
```

---

## Setup (5 steps)

### Step 1 — Copy to your project
```bash
cp -r .claude/ your-project/
cp CLAUDE.md your-project/
cp .gitignore your-project/   # or merge with existing
cp -r tasks/ your-project/
cp -r .github/ your-project/
```

### Step 2 — Set up global preferences (once, all projects)
```bash
cp global-CLAUDE.md.example ~/.claude/CLAUDE.md
# Edit ~/.claude/CLAUDE.md — fill in your name, role, shortcuts, package manager
```

### Step 3 — Set up personal project overrides
```bash
cp CLAUDE.local.md.example your-project/CLAUDE.local.md
cp .claude/settings.local.json.example your-project/.claude/settings.local.json
# Edit both — fill in your local env, sandbox URLs, personal allow rules
```

### Step 4 — Configure MCP tools
```bash
cp .claude/.mcp.json.example your-project/.mcp.json
# Edit .mcp.json — add your Slack workspace URL, GitHub token, etc.
# Add ANTHROPIC_API_KEY to your repo secrets for the GitHub Action
```

### Step 5 — Customize for your stack
```bash
cd your-project
claude  # start Claude Code
# Then run:
/setupdotclaude
# Claude scans your project, updates commands/rules/hooks to match your actual stack
```

---

## Fill in CLAUDE.md

Open `CLAUDE.md` and replace every `_fill in_` with your project details:
- Stack, platform, package manager
- Build/test/lint/format commands
- Architecture summary (2–4 sentences — WHY, not file listings)
- Key decisions (why non-obvious choices were made)

Delete every platform section that doesn't apply.

---

## Enable the @claude GitHub Action

1. Go to your repo → Settings → Secrets → Actions
2. Add secret: `ANTHROPIC_API_KEY` (your Anthropic API key)
3. Commit `.github/workflows/claude-review.yml`

Now tag `@claude` in any PR comment and Claude will act on it — often updating CLAUDE.md with lessons learned automatically.

---

## Key commands once running

| Command | What it does |
|---|---|
| `/ship` | Commit → push → open PR with confirmation at each step |
| `/pr-review` | Full review by specialist agents (code, security, performance, docs) |
| `/debug-fix [issue]` | Fix a bug from end to end — reproduce, investigate, fix, verify |
| `/hotfix [issue]` | Emergency production fix — minimal change, fast path to PR |
| `/research-first [idea]` | Research what exists before building — GitHub, libraries, APIs |
| `/prd [description]` | Generate a Product Requirements Document before any build |
| `/screenshot-fix [context]` | Screenshot Survival Loop — paste any error, get a fix |
| `/grill` | Claude adversarially reviews your changes before you open a PR |
| `/techdebt` | Find and eliminate duplicated/dead/complex code |
| `/tdd [feature]` | Strict red → green → refactor TDD loop |
| `/setupdotclaude` | Re-customize .claude/ whenever you add a new dependency or language |
| `/memory` | Browse and edit Claude's auto-written session notes |
| `/cost` | Check live token usage |

---

## The memory system

| File | Written by | Purpose |
|---|---|---|
| `CLAUDE.md` | You + team | Project rules, architecture, commands |
| `CLAUDE.local.md` | You | Personal overrides for this project |
| `~/.claude/CLAUDE.md` | You | Universal preferences across all projects |
| `.claude/memory/MEMORY.md` | Claude | Auto-written notes from sessions (gitignored) |
| `tasks/lessons.md` | Claude + you | Corrections and patterns to avoid repeating |

---


---

## Superpowers plugin (optional but recommended)

This kit and [Superpowers](https://github.com/obra/superpowers) (120k stars) complement each other perfectly. Install both:

```bash
/plugin install superpowers@claude-plugins-official
```

Superpowers adds the full design-first workflow:
- **brainstorming** — Socratic design refinement before any code is written (HARD-GATE)
- **writing-plans** — Detailed bite-sized task plans with exact file paths and verification steps
- **subagent-driven-development** — Fresh subagent per task with two-stage review
- **systematic-debugging** — 4-phase root cause process
- **dispatching-parallel-agents** — Concurrent agent workflow for independent failures
- **finishing-a-development-branch** — Structured merge/PR/keep/discard workflow
- **receiving-code-review** — Anti-sycophancy: verify before implementing, push back if wrong

The skills from Superpowers are also included in this kit's `.claude/skills/` for offline use.

## Sources

- [Boris Cherny's workflow threads](https://x.com/bcherny) (Creator of Claude Code)
- [dotclaude](https://github.com/meleantonio/ChernyCode)
- [Trail of Bits claude-code-config](https://github.com/trailofbits/claude-code-config)
- [HumanLayer — Writing a good CLAUDE.md](https://www.humanlayer.dev/blog/writing-a-good-claude-md)
- [Anthropic Claude Code docs](https://code.claude.com/docs)
- [app-store-submission-auditor](https://github.com/itsncki-design/app-store-submission-auditor)
