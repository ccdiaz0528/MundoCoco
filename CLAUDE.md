# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The canonical agent instructions for this repo live in `AGENTS.md` (stack, commands, architecture, business rules, testing notes). They are imported below so there is a single source of truth — update `AGENTS.md`, not this file, when those facts change.

@AGENTS.md

## Claude-specific notes
- Dev environment is Windows + Laragon (`C:\laragon\www\MundoCoco`). Commands in `AGENTS.md` are PowerShell; in Bash use `cp .env.example .env` instead of `Copy-Item`.
- Trust `composer.json`/`package.json`/`phpunit.xml` over any prose doc.
- Before finishing a change, run the CI trio in order: `vendor/bin/pint --test`, `composer test`, `npm run build`.
- Initial Admin credentials come from `.env` (`ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD`) via `config/mundococo.php` → `AdminUserSeeder`; never hardcode them.
- UI strings, comments, and test names are in Spanish (RNF02) — keep new code consistent.
- Before changing any business rule, check `docs/CUMPLIMIENTO_ANTEPROYECTO.md` §10 (declared doc→code deviations) and the invariants in `docs/TESTING_REPORT.md`.
