# CLAUDE.md - Project Guidelines

## Core Philosophy
You are working with a senior systems architect on production software. Approach problems like a detective: form theories, collect evidence, then solve based on proof.

## Information Gathering
- When uncertain about facts, current information, or technical details, use web search to verify
- When a problem involves a specific API or library, ask for additional context or documentation rather than making assumptions
- The user likely has more relevant documentation or context - always ask when needed

## Design Principles

1. **Don't overengineer**: Simple beats complex
2. **Plan, then act**: Evaluate alternatives with the user before choosing a path
3. **No fallbacks**: One correct path, no alternatives
4. **One way**: Single processing paths, avoid high cyclomatic complexity
5. **Clarity over compatibility**: Clear code beats clever solutions
6. **Throw errors**: Fail fast when preconditions aren't met
7. **Separation of concerns**: Single responsibility per function/module/file
8. **Surgical changes**: Prefer minimal, focused fixes. Suggest larger refactoring when sensible but don't default to it
9. **Evidence-based debugging**: Start with a failing test, then fix
10. **Fix root causes**: Address underlying issues, not symptoms
11. **Collaborative process**: Work with user to identify most efficient solution

Quality comes from breadth of consideration, not just thoroughness. Better to explore the full landscape of possibilities once than iterate through narrow solutions multiple times.

## Code Quality Standards

### Comments
- Comments explain WHY, not HOW (the code shows how)
- No comments about previous versions - source control handles history
- Keep comments current with the code they describe

### Change Management
- Check for ripple effects: assumptions, usage, tests, build tooling, READMEs, CI
- Update all associated and referencing components to prevent drift
- Complete the entire task - don't implement 2 of 10 items and leave others for later

### Refactoring Guidelines
- Some code duplication is acceptable
- When the same logic appears in 3+ places, refactor to maintain simplicity

### Error Handling
- Fail early and loudly
- Never "work around" broken assumptions or preconditions
- Clear error messages that identify the specific problem

## CI/CD Guidelines
- **NEVER make empty commits to trigger CI** - Empty commits pollute git history
- To trigger pipelines: Use the Github CLI
- Be mindful that pipelines consume runner resources

## Working Approach
- This is production software - implement complete solutions
- Include all associated changes: tests, documentation, configuration
- Don't write examples or demonstrations - write production code
- Leverage the user's expertise as a senior architect