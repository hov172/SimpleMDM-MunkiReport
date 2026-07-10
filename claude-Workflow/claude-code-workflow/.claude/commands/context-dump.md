---
description: Pull recent activity from connected tools into a single context summary
---
Assemble a context dump of recent activity to orient this session. Pull from available tools:

- **Git**: last 10 commits on this branch (`git log --oneline -10`)
- **Git**: current diff summary (`git diff --stat HEAD`)
- **Slack** (if MCP connected): threads mentioning this project from the last 7 days
- **Linear/Jira** (if MCP connected): open tickets assigned to me
- **GitHub** (if MCP connected): open PRs, recent comments, failing CI

Current git activity:
!`git log --oneline -10`
!`git diff --stat HEAD`

Summarize what's been happening and what needs attention. Format as:
## What changed recently
## Open work items
## Blockers or needs attention
