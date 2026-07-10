# Setting Up Your Claude Browser Coding Partner

> This is your **advisor** — separate from Claude Code in VS Code (your **builder**).
> Same AI, different jobs. Use both together.

| | Claude (Browser) | Claude Code (VS Code) |
|---|---|---|
| Role | Advisor, planner, troubleshooter | Builder, coder, fixer |
| Can create files | ❌ | ✅ |
| Can install software | ❌ | ✅ |
| Can run your app | ❌ | ✅ |
| Best for | Research, PRDs, debugging help | Everything else |

## Setup (3 minutes, do this once)

1. Go to [claude.ai](https://claude.ai) → click **Projects** → **New Project**
2. Name it: `Dev Partner` (or your project name)
3. Upload your `CLAUDE.md` file into the project
4. Paste this prompt to initialize it:

```
You are my coding partner and technical advisor. I'm building [describe your project].

When I get stuck: I'll send you screenshots. Walk me through fixes step by step.

When something breaks:
1. Ask me for a screenshot of the error
2. Identify the root cause
3. Give me the exact command or change to make in Claude Code

Keep explanations clear. No jargon unless you explain it.
My stack: [fill in your stack]
My goal today: [fill in]
```

5. Keep this browser tab open the entire session.

## The Screenshot Survival Loop

**This solves 90% of all problems:**

```
1. Something breaks
2. Take a screenshot (Mac: Cmd+Shift+4 → Clipboard → Cmd+V to paste)
         (Win:  Win+Shift+S → click notification → copy → Ctrl+V to paste)
3. Paste it here or into Claude Code
4. Describe in plain English: "this button doesn't work" or just "fix this"
5. Claude tells you exactly what to do
6. Test it
7. Repeat
```

This loop IS software development. Every professional developer does this all day.

## Anti-Learning-Purgatory Rule

> Learning purgatory: spending weeks building and perfecting, never shipping.

Signs you're in it:
- "It's not ready yet" (it never will be — ship anyway)
- Adding features instead of getting users
- Haven't shown it to a real person yet

The cure: **Ship today. Iterate based on real feedback.**

