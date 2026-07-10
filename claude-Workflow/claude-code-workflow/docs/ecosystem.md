# Ecosystem Addons

> Optional tools that extend the Claude Code Workflow Kit. Install any or all — each adds something distinct. None conflict with the kit.

---

## claude-mem — AI-Compressed Persistent Memory

**github.com/thedotmack/claude-mem · 42k stars · AGPL-3.0**

Automatically captures everything Claude does during coding sessions, compresses it with AI (using Claude's agent-sdk), and injects relevant context back into future sessions. Solves the amnesia problem — you stop re-explaining your project every time you start a new chat.

**What it adds on top of the kit's built-in memory:**

| Kit's memory system | claude-mem |
|---|---|
| MEMORY.md (Claude writes notes manually) | Automatic capture of every tool call |
| tasks/lessons.md (you write corrections) | AI-compressed semantic observations |
| Hierarchical CLAUDE.md loading | SQLite + vector search across all sessions |
| Text-based retrieval | Progressive disclosure with token cost visibility |
| No web interface | Web viewer at http://localhost:37777 |

**Key features:**
- Progressive disclosure — injects high-level context first, deeper history on demand (~2,250 token savings vs MCP)
- Chroma vector database — hybrid semantic + keyword search
- `<private>` tags — exclude sensitive content from storage
- Citations — reference past decisions with `claude-mem://` URIs
- Endless Mode (beta) — biomimetic memory for extended sessions

**Install:**
```bash
/plugin marketplace add thedotmack/claude-mem
/plugin install claude-mem
# Restart Claude Code — context from previous sessions appears automatically
```

**Config:** `~/.claude-mem/settings.json` — model, port (default 37777), data directory, log level, context injection settings.

**Note:** Not in npm's install-g — always install via the /plugin commands above.

---

## UI UX Pro Max — Design System Generator (BUNDLED IN KIT)

> **Now included** — all 7 skills are in `.claude/skills/`. No separate install needed.
> Requires Python 3: `python3 --version`



**github.com/nextlevelbuilder/ui-ux-pro-max-skill · 54k stars · MIT**

Forces a real design direction before any code gets written. Without it, Claude builds the same landing page every time: Inter font, purple gradient, grid cards. This skill gives design intelligence — typography, color systems, animations — all intentional before a single component is rendered.

**What it adds on top of the kit's @frontend-designer agent:**

| Kit's @frontend-designer | UI UX Pro Max |
|---|---|
| Design tokens first | AI-powered Design System Generator |
| Picks one design principle | 161 industry-specific reasoning rules |
| Anti-AI-slop aesthetics | 57 UI styles, searchable database |
| Complete runnable code | 95 color palettes, 56 font pairings |
| Dark mode + mobile-first | 99 UX guidelines, 25 chart types |
| | BM25 + regex hybrid search engine |
| | Pre-delivery validation against anti-patterns |

**Supported stacks:** HTML/Tailwind, React, Next.js, Vue, Nuxt.js, Svelte, ShadCN, Flutter, SwiftUI, React Native, Jetpack Compose.

**Design System Generator output example:**
```
TARGET: Serenity Spa
PATTERN: Hero-Centric + Social Proof
Colors: Warm neutrals (#F5F0EB primary, #C9A882 accent)
Typography: Cormorant Garamond / Nunito Sans pairing
Animation: Subtle parallax, opacity transitions only
Anti-patterns: Avoid harsh shadows, avoid stock imagery
```

**Install:**
```bash
/plugin marketplace add nextlevelbuilder/ui-ux-pro-max-skill
/plugin install ui-ux-pro-max@ui-ux-pro-max-skill
# Activates automatically on any UI/UX request
```

**Or via npm CLI:**
```bash
npm install -g uipro-cli
uipro init --ai claude
```

---

## Everything-Claude Code (ECC) — The Maximalist Alternative

**github.com/affaan-m/everything-claude-code · 116k stars · MIT**

The most comprehensive Claude Code configuration system — 28 agents, 119 skills, 60 commands, AgentShield security scanning, and cross-platform support (Claude Code, Codex, Cursor, OpenCode). Selective install means you only get what you need.

**What ECC adds beyond this kit:**

- **Instincts system** — continuous learning with confidence scoring. Automatically extracts patterns from git history and wraps them into skills. `/instinct-import`, `/instinct-export`.
- **AgentShield** — 1,282 security tests, 102 rules. CVE database with 25+ known MCP vulnerabilities. Supply chain verification. `/security-scan` skill.
- **Hook runtime controls** — `ECC_HOOK_PROFILE=minimal|standard|strict` and `ECC_DISABLED_HOOKS=hook1,hook2` for gating hooks without editing files. *(The kit's session-start.sh now supports these same env vars.)*
- **Harness commands** — `/harness-audit`, `/loop-start`, `/loop-status`, `/quality-gate`, `/model-route`.
- **PM2 multi-agent orchestration** — `/pm2`, `/multi-plan`, `/multi-execute`, `/multi-backend`, `/multi-frontend`.
- **ecc.tools GitHub App** — analyzes your git commit history, finds hidden patterns, generates SKILL.md files automatically for things your team already does repeatedly.
- **12 language ecosystems** — C#, Rust, Java, Kotlin, C++, Go, Python, TypeScript, Perl, PyTorch, Nuxt 4, Flutter with dedicated rules and agents.

**Install:**
```bash
/plugin marketplace add affaan-m/everything-claude-code
/plugin install everything-claude-code@everything-claude-code

# Or selectively:
ecc install --profile developer --with lang:typescript --with agent:security-reviewer
```

**Kit vs ECC — when to use which:**

This kit is intentional and curated — under 200 lines of CLAUDE.md, hand-selected sources, carefully integrated. ECC is maximalist — 28 agents, 119 skills, 60 commands, everything. Both follow the same SKILL.md standard and are compatible. Start with this kit to establish the workflow; layer in ECC components selectively as needs grow.

---

## Hook Runtime Controls (available now, no addon needed)

The kit's `session-start.sh` now respects ECC-compatible environment variables:

```bash
# In your ~/.zshrc or ~/.bashrc:
export ECC_HOOK_PROFILE=strict    # minimal | standard (default) | strict
export ECC_DISABLED_HOOKS="warn-large-files,format-on-save"  # comma-separated
```

| Profile | What it injects |
|---|---|
| `minimal` | Branch + last commit only |
| `standard` | Branch, commit, modified count, staged, stashes *(default)* |
| `strict` | All of the above + active PR number and title |
