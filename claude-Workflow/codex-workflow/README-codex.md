# Claude Code Workflow Kit — Codex Edition

> The same 16-step workflow, adapted for OpenAI Codex CLI.
> For the Claude Code version, see `claude-workflow.zip`.

**27 Skills · 2 Hooks (experimental) · 16-Step Workflow**
**Web · iOS/macOS · Android · Windows · Cross-platform**

---

## What's Different from the Claude Code Version

| Feature | Claude Code | Codex |
|---|---|---|
| Instructions file | `CLAUDE.md` | `AGENTS.md` |
| Personal overrides | `CLAUDE.local.md` | `AGENTS.override.md` |
| Global preferences | `~/.claude/CLAUDE.md` | `~/.codex/AGENTS.md` |
| Config format | `.claude/settings.json` | `~/.codex/config.toml` |
| Skills location | `.claude/skills/` | `.codex/skills/` |
| Invoke a skill | `/skill-name` | `$skill-name` |
| Commands | `.claude/commands/` | Converted to skills in `.codex/skills/` |
| Hooks | 6 events (stable) | 2 events (experimental) |
| Auto-memory | `/memory` (built-in) | Not available |
| `/cost`, `/compact` | Available | Not available |

### Hook Gaps

The most important gap. Claude Code hooks enforce rules at 100%. Codex hooks are experimental with only 2 events currently. These Claude Code hooks have **no Codex equivalent yet**:

- `protect-files.sh` (PreToolUse) — blocks writes to .env, certificates, lock files
- `scan-secrets.sh` (PreToolUse) — catches hardcoded secrets before they're written
- `block-dangerous-commands.sh` (PreToolUse) — blocks push to main, DROP TABLE, rm -rf
- `format-on-save.sh` (PostToolUse) — auto-runs formatter after every edit
- `anti-rationalization.sh` (Stop) — blocks premature stops

**Mitigation**: These are covered in AGENTS.md as behavioral rules (~70% effective). The turn-complete hook logs rationalization phrases to `.codex/logs/turns.log` for manual review. Full hook parity is expected as Codex's hook system matures.

---

## 5-Step Setup

### 1. Install Codex CLI
```bash
npm install -g @openai/codex
codex  # sign in with ChatGPT account or API key
```

### 2. Set up global preferences (once, all projects)
```bash
cp global-AGENTS.md.example ~/.codex/AGENTS.md
# Edit ~/.codex/AGENTS.md — fill in your name, role, shortcuts
```

### 3. Set up Codex config
```bash
cp .codex/config.toml.example ~/.codex/config.toml
# Edit ~/.codex/config.toml — uncomment MCP servers you use, set model
```

### 4. Set up project overrides
```bash
cp AGENTS.override.md.example AGENTS.override.md
# Edit AGENTS.override.md — local env, sandbox URLs, sprint context
```

### 5. Enable hooks (experimental) and install skills
```bash
# Enable hooks in config.toml:
# [features]
# hooks = true

# Copy hooks.json into the right place:
cp .codex/hooks.json.example .codex/hooks.json

# Make hook scripts executable:
chmod +x .codex/hooks/session-context.sh .codex/hooks/turn-complete.sh

# Install Superpowers for managed skill updates (optional):
git clone https://github.com/obra/superpowers.git ~/.codex/superpowers
mkdir -p ~/.agents/skills
ln -s ~/.codex/superpowers/skills ~/.agents/skills/superpowers

# Run setup to customize for your stack:
# Tell Codex: "$setupdotcodex"
```

### GitHub Action (Step 15 — Compounding Engineering)
Add `OPENAI_API_KEY` to GitHub repo → Settings → Secrets → Actions.
Tag `@codex` in any PR comment → Codex acts automatically.

---

## Skills Reference

All 27 skills are in `.codex/skills/`. Invoke with `$skill-name`.

### Design & Planning
| Skill | When to use |
|---|---|
| `$pre-build` | Before any build — data mapping, edge cases, security, production thinking |
| `$research-first` | Before any new app/feature — find what already exists |
| `$prd` | Generate Product Requirements Document |
| `$brainstorming` | Design approval HARD-GATE — required before any implementation |
| `$writing-plans` | Turn approved design into bite-sized tasks in tasks/todo.md |

### Development
| Skill | When to use |
|---|---|
| `$debug-fix` | Fix any bug — reproduce, investigate, fix root cause, verify |
| `$hotfix` | Emergency production fix — minimal change, fast PR |
| `$refactor` | Safe refactor with tests as safety net |
| `$tdd` | Strict test-driven development |
| `$test-writer` | Write comprehensive tests for new/changed code |
| `$screenshot-fix` | Screenshot Survival Loop — paste error, get fix |
| `$fix-issue` | Fix a GitHub issue by number or description |

### Review & Quality
| Skill | When to use |
|---|---|
| `$pr-review` | Full review before any PR — dispatches specialist subagents |
| `$grill` | Adversarial pre-PR challenge — harder than pr-review |
| `$techdebt` | Kill debt before shipping — run at end of every session |
| `$requesting-code-review` | Send a focused code-review request to a subagent |
| `$receiving-code-review` | Anti-sycophancy protocol when acting on review feedback |
| `$verification-before-completion` | Prove it works before marking done |

### Git & Shipping
| Skill | When to use |
|---|---|
| `$ship` | Full commit-push-PR workflow with confirmation |
| `$finishing-a-development-branch` | Complete a branch — 4 options: merge, PR, keep, discard |
| `$dispatching-parallel-agents` | Multiple independent failures — parallel subagent fix |

### Debugging
| Skill | When to use |
|---|---|
| `$systematic-debugging` | 4-phase root cause: reproduce, isolate, hypothesize, verify |
| `$context-dump` | Pull recent activity summary from git and connected tools |

### Platform
| Skill | When to use |
|---|---|
| `$swiftui-pro` | SwiftUI code review — iOS 26, Swift 6.2, Paul Hudson |
| `$app-store-submission-auditor` | iOS App Store pre-submission audit (auto-triggers on 20+ phrases) |

### Meta
| Skill | When to use |
|---|---|
| `$setupdotcodex` | Customize .codex/ for this project's actual stack |
| `$explain` | Explain any file or function with diagrams |
| `$writing-skills` | Create new skills following best practices |

---

## The 16-Step Workflow

```
1   IDEA          → Even vague is enough to start
1b  PRE-BUILD     → $pre-build — before any code
2   RESEARCH      → $research-first — find what exists
3   BRAINSTORM    → $brainstorming — HARD-GATE, no code until approved
4   PRD           → $prd — blueprint before building
5   PLAN          → $writing-plans — tasks to tasks/todo.md
6   WORKTREES     → git worktree add for parallel sessions
7   SECRETS       → .env with all API keys from PRD
8   BUILD         → subagent per task via $dispatching-parallel-agents
9   TEST          → run server, test every feature
10  SCREENSHOT    → $screenshot-fix — bug → fix → repeat
11  CODE REVIEW   → $pr-review — specialist subagents
12  TECHDEBT      → $techdebt — before every ship
13  SHIP          → $ship — commit → push → PR
14  DEPLOY        → Railway + secrets in Variables
15  COMPOUND      → Update AGENTS.md after every PR — lessons compound
16  ITERATE       → Back to step 1, AGENTS.md is smarter now
```

---

## File Structure

```
.
├── AGENTS.md                           # Project rules (committed)
├── AGENTS.override.md                  # Personal overrides (gitignored)
├── AGENTS.override.md.example          # Template for the above
├── global-AGENTS.md.example            # Copy to ~/.codex/AGENTS.md
├── .gitignore
├── tasks/
│   ├── todo.md                         # Task log
│   └── lessons.md                      # Lessons learned
├── docs/
│   ├── specs/                          # Design specs from $brainstorming
│   └── plans/                          # PRDs from $prd
├── .github/workflows/codex-review.yml  # @codex in PR comments
└── .codex/
    ├── config.toml.example             # Copy to ~/.codex/config.toml
    ├── hooks.json.example              # Copy to .codex/hooks.json (experimental)
    ├── hooks/
    │   ├── session-context.sh          # userpromptsubmit: git context injection
    │   └── turn-complete.sh            # agent-turn-complete: rationalization log
    └── skills/ (27 folders)
        ├── pre-build/                  # Pre-build checklist
        ├── research-first/             # Research before building
        ├── prd/                        # Product requirements
        ├── brainstorming/              # Design HARD-GATE
        ├── writing-plans/              # Task planning
        ├── debug-fix/                  # Bug fixing
        ├── hotfix/                     # Emergency fix
        ├── refactor/                   # Safe refactoring
        ├── tdd/                        # Test-driven development
        ├── test-writer/                # Test coverage
        ├── screenshot-fix/             # Screenshot loop
        ├── fix-issue/                  # Fix GitHub issue
        ├── pr-review/                  # Full PR review
        ├── grill/                      # Adversarial review
        ├── techdebt/                   # Debt scanning
        ├── requesting-code-review/     # Send review to subagent
        ├── receiving-code-review/      # Anti-sycophancy
        ├── verification-before-completion/ # Prove it works
        ├── ship/                       # Commit-push-PR
        ├── finishing-a-development-branch/ # Branch completion
        ├── dispatching-parallel-agents/    # Parallel agents
        ├── systematic-debugging/       # Root cause
        ├── context-dump/               # Session orientation
        ├── swiftui-pro/                # SwiftUI review (Paul Hudson)
        ├── app-store-submission-auditor/   # iOS App Store audit
        ├── setupdotcodex/              # Stack customization
        ├── explain/                    # Code explanation
        └── writing-skills/             # Create new skills
```

---

## Comparison: What Each Version Covers

| Capability | Claude Code | Codex |
|---|---|---|
| 16-step workflow | ✅ Full | ✅ Full |
| All 20+ skills | ✅ | ✅ (27 incl. commands-as-skills) |
| Git worktrees | ✅ | ✅ |
| MCP tools | ✅ | ✅ |
| GitHub Action | ✅ | ✅ |
| Session context injection | ✅ SessionStart hook | ⚠️ userpromptsubmit (fires every prompt) |
| File protection | ✅ PreToolUse hook | ❌ Not yet |
| Secret scanning | ✅ PreToolUse hook | ❌ Not yet |
| Dangerous command blocking | ✅ PreToolUse hook | ❌ Not yet |
| Auto-formatting | ✅ PostToolUse hook | ❌ Not yet |
| Anti-rationalization (blocking) | ✅ Stop hook | ⚠️ Logs only |
| Pre-compact context save | ✅ PreCompact hook | ❌ Not yet |
| Auto-memory (`/memory`) | ✅ | ❌ Not available |
| `/cost`, `/compact`, `/clear` | ✅ | ❌ Not available |
| SwiftUI Pro skill | ✅ | ✅ |
| App Store Auditor | ✅ | ✅ |

*Hook parity expected as Codex's hook system matures from experimental status.*

---

**Sources:** Boris Cherny (Creator of Claude Code) · Superpowers (obra/superpowers) · dotclaude · Trail of Bits · HumanLayer · Doctor AI Workshop (Alan Knox) · Paul Hudson (SwiftUI Pro)
