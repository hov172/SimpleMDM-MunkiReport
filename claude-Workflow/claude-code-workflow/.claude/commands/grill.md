---
description: Adversarially review the current changes before opening a PR
---
Review the staged and unstaged changes in this repo like a skeptical staff engineer.

Current diff:
!`git diff HEAD`

For each change:
1. Challenge whether it actually solves the root cause
2. Look for edge cases, off-by-ones, null dereferences, and race conditions
3. Ask "Is there a more elegant solution?"
4. Don't approve until the implementation is genuinely solid

Don't make a PR until I pass your review.
