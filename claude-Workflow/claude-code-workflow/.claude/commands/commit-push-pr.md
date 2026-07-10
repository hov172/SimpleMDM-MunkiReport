---
description: Stage changes, commit, push, and open a PR with confirmation at each step
---
Run the /ship skill to stage, commit, push, and open a PR.

Current git context:
- Status: !`git status --short`
- Branch: !`git branch --show-current`
- Last commit: !`git log --oneline -1`
