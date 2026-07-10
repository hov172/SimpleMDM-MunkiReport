---
name: setupdotcodex
description: Scan this project and customize all .codex/ config files to match the actual tech stack. Run after cloning. Detects package manager, test framework, linter, architecture, and source directories.
---

Scan this project and customize all .codex/ configuration files to match the actual stack.

## Steps

1. **Detect the stack**
   - Package manager: bun / npm / pnpm / yarn / cargo / pip / gradle / dotnet
   - Language: TypeScript / JavaScript / Python / Swift / Kotlin / C# / Rust / Go
   - Test framework: vitest / jest / pytest / XCTest / JUnit / NUnit
   - Linter/formatter: biome / eslint+prettier / ruff / swiftlint / ktlint
   - Source directories: src/, app/, lib/, Sources/

2. **Update AGENTS.md COMMANDS section**
   - Fill in the actual build, test, lint, format, typecheck, dev commands
   - Remove commands that don't apply to this stack

3. **Update .codex/config.toml.example**
   - Set sandbox_mode based on what the build process needs
   - Uncomment relevant MCP servers

4. **Update .codex/hooks/session-context.sh**
   - Add any stack-specific context injection needed

5. **Report changes**
   - List every file modified and every change made
   - Ask for confirmation before applying each change

## Rules
- Never modify without showing the proposed change and asking to confirm
- Never add tools or linters that aren't already installed in the project
- For monorepos: ask which workspace/package to focus on before scanning
