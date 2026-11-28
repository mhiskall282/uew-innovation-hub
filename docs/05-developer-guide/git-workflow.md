# Git Workflow

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [branching-strategy](branching-strategy.md), [development-workflow](development-workflow.md), `../09-deployment-and-operations/ci-cd-pipeline.md`, `../09-deployment-and-operations/rollback-procedures.md`

What the repository history shows about how changes reach `main`, the commit message style in use, and what to do before you push. Items marked "Recommended, not implemented" are suggestions and are not enforced by the repository.

## What the history shows

`git log --oneline` lists 14 commits, all on one branch, `main` (also `origin/main`). There are no merge commits and no other branches on the remote. The history is linear. Two author names appear in it.

```text
9f3bdee Security: remove web-shell string from a test fixture (Defender alert); add repository security scan
50a83df Gallery: hide photos whose files were lost instead of showing broken images
c41cf32 Fix header overflow at 1024px after adding Gallery to the menu
30ec5f6 Gallery, least-privilege admin controls, UEW-branded e-mails with hosted images
f41748b Security: update Symfony components for CVE-...
a5bbeb9 Audit hardening, browser tests, accessibility and mobile fixes, Gmail SMTP verified
87df882 Secure demo accounts: rotate default passwords to SEED_PASSWORD on boot; ...
df331f4 CI: audit composer.lock directly; checkout v5
...
01e8701 init
```

Observed practice:

- Work is committed directly to `main`.
- `git push` to `main` triggers CI (`.github/workflows/ci.yml`: `on: push: branches: [main]`, also `pull_request` and `workflow_dispatch`).
- Render deploys `main` automatically (`autoDeploy: true` in `render.yaml`). `ci.yml` notes that "Wait for CI" or branch protection should be switched on in GitHub so a failing build never ships. Whether that is switched on in the GitHub settings cannot be seen from the repository.

> **Requires confirmation:** GitHub branch protection and Render's "Wait for CI" setting. Check the repository settings on GitHub and the service settings on Render.

Because a push to `main` can deploy, run the checks in [development-workflow](development-workflow.md) before you push.

## Commit messages

Actual style: one line, imperative or descriptive, often starting with an area word and a colon (`Security:`, `CI:`) or the feature name (`Gallery:`). Examples: `Fix header overflow at 1024px after adding Gallery to the menu`, `CI: audit composer.lock directly; checkout v5`.

`.ai/rules/git.md` asks for conventional prefixes: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `style:`, `chore:`, `migration:`. The history does not follow that rule. For new commits, follow the written rule (it is easy to read and to scan), and keep it in this shape:

```text
<type>: <what changed and why, under about 72 characters>
```

Examples: `fix: hide gallery photos whose files are missing`, `migration: add programme deadline index`, `docs: add developer guide`.

Rules from `.ai/rules/git.md` that apply:

1. Keep commits small and about one thing.
2. Never commit secrets, `.env` files or credentials.
3. Run the tests and the style check before committing.
4. Do not commit temporary files, editor folders or OS files.

## Before you commit

```powershell
vendor\bin\pint --test      # style check (use vendor\bin\pint to fix)
php artisan test            # PHPUnit
git status                  # look for .env, *.sqlite, test-results/, playwright-report/
```

```bash
vendor/bin/pint --test
php artisan test
git status
```

`.gitignore` already excludes `.env`, `.env.*` (except `.env.example`), `vendor/`, `node_modules/`, `public/build`, `public/hot`, `database/database.sqlite`, `database/e2e.sqlite`, `test-results/`, `playwright-report/`, and `storage/*.ps1`.

> **Warning:** A tester password was committed in git history (commit `87df882`). Do not copy it and do not reuse it anywhere. If you find another secret in history, tell the project owner. Rewriting shared history needs the owner's agreement.

## Line endings on Windows

`.gitattributes` sets `* text=auto eol=lf`, and `.editorconfig` sets `end_of_line = lf`. Git on Windows may print "LF will be replaced by CRLF" warnings if `core.autocrlf` is true. They are harmless with these settings, but do not commit CRLF files.

## Undoing mistakes

| Situation | Safe action |
| --- | --- |
| Bad commit not yet pushed | `git commit --amend`, or `git reset --soft HEAD~1` to keep the changes |
| Bad commit already on `main` | `git revert <sha>` and push the revert (a new commit, history stays intact) |
| Production broken after a deploy | Revert on `main` (Render redeploys), or redeploy a previous build from the Render dashboard. See `../09-deployment-and-operations/rollback-procedures.md` |

> **Warning:** Do not use `git push --force` on `main`. It can break the deployment history and other people's clones.

## Recommended, not implemented

- Work on short-lived branches and open pull requests, so CI runs before code reaches `main` (see [branching-strategy](branching-strategy.md)).
- Turn on GitHub branch protection for `main`: require the CI jobs "Code style", "Tests (sqlite)", "Tests (pgsql)" and "Front-end build" to pass.
- Add a pull request template. `.ai/templates/pull-request.md` exists and can be copied into `.github/`.
- Add a commit message check (for example commitlint) once the team agrees on the prefixes.
