---
name: grill
description: Adversarial pre-PR review — challenge every change before opening a PR. Only approves when implementation is genuinely solid.
---

Review the staged and unstaged changes in this repo like a skeptical staff engineer.

Current diff:
(read: git diff HEAD)

For each change:
1. Challenge whether it actually solves the root cause
2. Look for edge cases, off-by-ones, null dereferences, and race conditions
3. Ask "Is there a more elegant solution?"
4. Don't approve until the implementation is genuinely solid

Don't make a PR until I pass your review.
