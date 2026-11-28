# Onboarding New Developers

> **Audience:** New developers, maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [../05-developer-guide/developer-onboarding.md](../05-developer-guide/developer-onboarding.md), [project-handover.md](project-handover.md), [technical-debt-register.md](technical-debt-register.md)

The full setup and first-week guide is [../05-developer-guide/developer-onboarding.md](../05-developer-guide/developer-onboarding.md). This page only adds what a maintainer should arrange.

## Before day one (maintainer)

- Add the person to the GitHub repository and the Render project, with the lowest access that works.
- Share secrets through a password manager, never by e-mail or chat. Never share `.env`.
- Tell them which environment is production (https://uew-innovation-hub.onrender.com). Local work uses SQLite.

## Reading order

1. `AGENTS.md` and the files under `.ai/` (`ai.md`, `context.md`, `brand.md`, `architecture.md`, `database.md`, `state.md`, `rules/`).
2. [../05-developer-guide/developer-onboarding.md](../05-developer-guide/developer-onboarding.md) and the installation guide.
3. The [technical debt register](technical-debt-register.md).
4. [release-management.md](release-management.md). Pushing to `main` deploys to production.

## First tasks

| Task | Why |
| --- | --- |
| Run the app and the test suite locally | Proves the setup |
| Sign in as each staff role (accounts come from `DemoContentSeeder`, password from `SEED_PASSWORD`) | Learn the permission differences |
| Fix one low-priority item from the register (for example TD-001 or TD-002) | Practise the workflow |

## Rules to remember

- Update `.ai/state.md` and `.ai/changelog.md` after meaningful changes.
- Do not commit secrets or `.env`.
- Follow the workflows in `.ai/workflows/`.
