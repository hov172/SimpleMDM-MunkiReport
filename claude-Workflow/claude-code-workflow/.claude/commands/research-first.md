---
description: Research what already exists before building — GitHub repos, libraries, APIs, CLIs. Run this before every new build.
argument-hint: "[describe your app idea]"
---

I want to build: $ARGUMENTS

Before writing a single line of code, research what already exists:

1. **GitHub repositories** that do something similar — look for active repos with recent commits
2. **Python libraries or npm packages** that handle core functionality
3. **CLI tools** that could be wrapped or extended
4. **APIs and services** I can connect to instead of building from scratch
5. **Existing templates or boilerplates** that give a head start

Current project context:
!`ls -la`
!`cat package.json 2>/dev/null || cat requirements.txt 2>/dev/null || cat Cargo.toml 2>/dev/null || echo "No manifest found"`

**Goal:** Stand on the shoulders of giants. Find the best starting points so we don't build from scratch.

Summarize:
- What exists and how mature/active it is
- Which approach you recommend and why
- What we'd still need to build ourselves
- Estimated time savings vs. building from scratch

After research is complete, offer to create a PRD based on findings.
