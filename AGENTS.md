# Agent instructions

Use this project's `CONTEXT.md` for domain terms and boundaries. Follow `docs/agents/domain.md` when consulting context and ADRs. Treat `.env` files and local credentials as sensitive; use examples or placeholders, never secret values.

## Agent skills

### Issue tracker

Issues and specs are tracked in this repository's GitHub Issues. Use the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Use `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, and `wontfix` for the five triage roles. See `docs/agents/triage-labels.md`.

### Domain docs

This is a single-context repository; read `CONTEXT.md` and relevant ADRs in `docs/adr/`. See `docs/agents/domain.md`.
