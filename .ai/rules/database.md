# Database Rules

> **Platform**: UEW Innovation Hub Enterprise

1. Every schema change **MUST** be accompanied by a dedicated Laravel migration. Never alter schema directly in a GUI or CLI without a migration file.
2. Migrations must be non-destructive and backward compatible where possible. Never drop tables or columns in production without an additive deprecation phase.
3. Every foreign key must have an explicit constraint with intentional referential behavior (`onDelete('cascade')`, `onDelete('restrict')`, or `onDelete('set null')`).
4. Always index foreign keys, status columns, date columns, slugs, and composite filter keys (`status, start_date`).
5. Never store relational structures as loose JSON unless preserving raw third-party payloads.
6. Beneficiary counts, grant amounts, and financial fields must use `unsignedInteger` or `decimal(12, 2)` respectively. Floats are strictly prohibited for countable or monetary data.
7. Wrap all multi-model or multi-table mutations inside `DB::transaction(function() { ... })` to ensure atomic consistency.
8. Avoid N+1 query performance problems. Always eager-load relationships (`with(['organizations', 'creator'])`) in controllers before passing data to views.
9. Slugs must have unique indexes and be auto-generated from titles/names using `Str::slug()`.
10. Soft deletes should be utilized for primary institutional records (projects, publications) to prevent accidental data loss and to support audit requirements.
