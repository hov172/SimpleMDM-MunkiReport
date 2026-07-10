---
name: context-dump
description: Pull recent activity summary: recent commits, diff summary, open tickets, open PRs. Best for starting a session after time away.
---

Assemble a context dump of recent activity to orient this session. Pull from available tools:

- **Git**: last 10 commits on this branch (`git log --oneline -10`)
- **Git**: current diff summary (`git diff --stat HEAD`)
- **Slack** (if MCP connected): threads mentioning this project from the last 7 days
- **Linear/Jira** (if MCP connected): open tickets assigned to me
- **GitHub** (if MCP connected): open PRs, recent comments, failing CI

Current git activity:


Summarize what's been happening and what needs attention. Format as:
## What changed recently
## Open work items
## Blockers or needs attention
