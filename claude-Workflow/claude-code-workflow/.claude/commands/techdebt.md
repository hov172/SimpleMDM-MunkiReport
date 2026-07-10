---
description: Find and eliminate technical debt — duplicated code, dead code, over-complexity
---
Scan the codebase for technical debt. Focus on:

1. **Duplicated code** — identical or near-identical logic in multiple places → extract to shared function
2. **Dead code** — functions, variables, imports never referenced → delete (git has history)
3. **Over-complexity** — functions >30 lines, nesting >3 levels, >3 parameters → simplify
4. **Magic numbers/strings** — inline literals that should be named constants
5. **TODOs and FIXMEs** — list them all with file:line, assess which are stale

Current file structure:
!`find . -type f -name "*.ts" -o -name "*.swift" -o -name "*.kt" -o -name "*.cs" | grep -v node_modules | grep -v .git | head -50`

Report findings grouped by category. For each: file:line, what it is, proposed fix.
Start with the highest-impact items (most duplicated, most complex).
After reporting, ask which ones to fix now.
