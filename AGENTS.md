# AGENTS.md — nhomnhom

## Project Identity
- Project: nhomnhom
- Stack: TypeScript / JavaScript (React, Next.js, Node.js)
- Purpose: [Tìm kiếm món ăn yêu thích và sau đó chỉ đường GG map dựa theo yêu thích trên web]
- Harness: Codex (VSCode extension) — no hook system, no subagent system like Claude Code. All rules below are instruction-based.

## Core Principles
1. **Research first** — read existing code before writing anything new
2. **Test-driven** — write tests before implementation, prioritize coverage for critical logic
3. **Security-first** — never compromise on security, validate all inputs
4. **Minimal diff** — change only what's necessary, no sweeping rewrites
5. **Plan before execute** — plan complex features before writing code
6. **Fail loud** — surface errors immediately, never swallow exceptions

## Security Guidelines

**Before ANY commit, check:**
- No hardcoded secrets (API keys, passwords, tokens)
- All user input validated
- SQL injection prevention (parameterized queries)
- XSS prevention (sanitized HTML output)
- CSRF protection enabled on relevant forms/APIs
- Error messages don't leak sensitive data

**Secret management:** never hardcode secrets — use environment variables (`.env`, already in `.gitignore`). If a secret is exposed, stop, report immediately, and rotate it before continuing.

## Coding Style

**File organization:** many small files over few large ones. 200-300 lines typical, 500 lines max. Organize by feature/domain, not by file type.

**Naming:** camelCase for functions/variables, PascalCase for components/classes. 1 React component = 1 file.

**Error handling:** handle errors at every level. User-friendly messages in UI, detailed logs server-side. Never silently swallow errors in empty catch blocks.

**Input validation:** validate all input at boundaries (forms, APIs, query params). Fail fast with clear messages.

**Code quality checklist:**
- Functions small (<30 lines), files focused (<300 lines)
- No nesting deeper than 4 levels
- Proper error handling, no hardcoded values
- Clear, readable identifiers

## Testing Requirements

**Test framework:** Jest or Vitest (depending on Next.js version)

Required test types:
1. **Unit tests** — individual functions, utilities, components
2. **Integration tests** — API endpoints, database operations
3. **E2E tests** — critical user flows (if applicable)

**TDD workflow:**
1. Write test first (RED) — test should FAIL
2. Write minimal implementation (GREEN) — test should PASS
3. Refactor (IMPROVE) — verify tests still pass after cleanup

Never merge code without passing tests.

## Memory Rule

After EVERY code change, ALWAYS:
1. Append to `MEMORY.md`
2. Format: `[date] ACTION: what changed | file affected | next step`
3. End response with `"✅ MEMORY.md updated"`

Never skip this step.

## Memory Compression Rule
- `MEMORY.md` max 50 lines
- Over 50 lines → compress SUMMARY section to 1 line per feature
- Tasks completed > 7 days ago → move to `CHANGELOG.md`, remove from `MEMORY.md`
- ACTIVE section: max 5 items

## Development Workflow

1. **Plan** — identify goal, steps, risks, and affected files before coding
2. **TDD** — write tests first, implement, refactor
3. **Review** — self-review code just written, address CRITICAL/HIGH issues first
4. **Capture knowledge in the right place**
   - Personal debugging notes, temporary context → `MEMORY.md`
   - Architecture decisions, API changes → existing project docs, or ask before creating a new file
   - Don't duplicate info already in code comments or other docs
5. **Commit** — conventional commits format, clear description

## Slash Commands

### /plan [task]
1. GOAL: one sentence
2. PHASES: ordered steps with affected files
3. COMPLEXITY: S / M / L
Limit: ≤200 tokens. No implementation at this step. Ask at most 1 question if task is unclear.

### /code-review [scope?]
1. Read diff or specified files
2. Output: ISSUES (CRITICAL / HIGH / MED / LOW) + SUGGESTIONS
Limit: ≤300 tokens. Skip style nitpicks if a linter exists. Security issues always tagged CRITICAL.

### /done
1. Summarize completed work in 1 line
2. Append to `MEMORY.md`
3. Confirm `"✅ MEMORY.md updated"`

## Git Workflow

**Commit format:** `<type>: <description>` — types: feat, fix, refactor, docs, test, chore, perf

**Before pushing:** review `git diff`, ensure tests pass.

## Architecture Patterns

**API response format:** consistent envelope with success indicator, data payload, error message, pagination metadata if needed.

**Repository pattern** (if a data layer exists): decouple data access from business logic via a standard interface (findAll, findById, create, update, delete).

## Performance & Context Management

- Avoid using 80%+ of context window for large refactors or multi-file features — keep buffer for continued smooth work
- Simple tasks (single file edit, docs, small fixes) can tolerate higher context usage

## Forbidden
- Never repeat the user's prompt back verbatim
- Never generate unsolicited boilerplate
- Never hallucinate file paths — verify before referencing
- Never ask more than 1 question per response

## Success Metrics
- Tests pass, reasonable coverage for critical logic
- No obvious security vulnerabilities
- Code is readable and maintainable
- User requirements are met
