# Git & Commit Rules

> **Platform**: UEW Innovation Hub Enterprise

1. Write descriptive, conventional commit messages:
   - `feat: <description>` — new features or capabilities
   - `fix: <description>` — bug fixes and corrections
   - `refactor: <description>` — code restructuring with no functional change
   - `test: <description>` — adding or modifying automated tests
   - `docs: <description>` — documentation updates
   - `style: <description>` — formatting, missing semicolons, etc.
   - `chore: <description>` — tooling, dependencies, build configuration
   - `migration: <description>` — database schema changes
2. Keep commits atomic, cohesive, and focused on a single logical change. Do not bundle unrelated changes together.
3. Never commit secrets, credentials, API keys, private certificates, or `.env` files.
4. Ensure `.gitignore` properly excludes `vendor/`, `node_modules/`, `public/build/`, `public/hot`, `storage/*.key`, `.env`, and local environment files.
5. Do not commit unnecessary temporary files, editor configs, or system files (`.DS_Store`, `Thumbs.db`, `*.swp`).
6. Verify that tests pass and no linter errors exist prior to creating commits or opening pull requests.
7. Legacy procedural PHP files should remain in history but be clearly superseded by the new Laravel architecture in the working tree.
