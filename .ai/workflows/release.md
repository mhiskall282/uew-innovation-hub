# Release Workflow

> **Platform**: UEW Innovation Hub Enterprise

---

## 1. Pre-Release Checklist
- [ ] All feature branches merged into `main`.
- [ ] Full test suite passes: `php artisan test`.
- [ ] Frontend assets compile cleanly: `npm run build`.
- [ ] Database migrations apply cleanly: `php artisan migrate --pretend`.
- [ ] No `.env` secrets or debug credentials committed.
- [ ] `.ai/state.md` reflects the current system state.
- [ ] `.ai/changelog.md` has the release entry.

---

## 2. Tag & Version
```bash
git tag -a v1.0.0 -m "Release v1.0.0 — Laravel 12 Enterprise Migration"
git push origin main --tags
```

---

## 3. Deploy
Follow the atomic deployment script in `.ai/deployment.md`.

---

## 4. Post-Release Verification
- Verify the application loads without errors.
- Confirm existing project data renders correctly in the dashboard.
- Verify PDF report and publication downloads function.
- Verify Excel export generation works with year filters.
- Inspect audit log to confirm the deployment was recorded.
