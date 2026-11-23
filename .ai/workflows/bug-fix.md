# Bug Fix Workflow

> **Platform**: UEW Innovation Hub Enterprise

1. **Reproduce** the issue with clear steps and observed vs expected behavior.
2. **Identify the root cause** — inspect related models, controllers, views, and queries.
3. **Inspect surrounding code** for related fragilities or cascading risks.
4. **Avoid treating symptoms only** — fix the underlying cause, not a surface workaround.
5. **Implement the smallest correct fix** that resolves the issue without introducing side effects.
6. **Add a regression test** where appropriate to prevent recurrence.
7. **Verify** the original bug is resolved and related functionality still works.
8. **Update documentation/state** if the fix changes expected behavior or data flow.

Never rewrite an entire subsystem to fix a localized bug without clear justification.
