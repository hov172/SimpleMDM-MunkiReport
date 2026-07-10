---
name: screenshot-fix
description: The Screenshot Survival Loop — identify root cause from error or screenshot, minimal fix, verify. No clarifying questions.
---

I have an error. Context: {user-provided arguments}

Analyze the screenshot/error I've shared and:

1. **Identify** exactly what broke and why
2. **Fix** it — minimal change, root cause only
3. **Verify** the fix works by running the relevant test or command
4. **Explain** in one sentence what caused it

This is the standard debugging loop:
Bug → Screenshot → Fix → Test → Repeat until green

Don't ask clarifying questions — just fix it. If you genuinely need more context, ask one specific question only.
