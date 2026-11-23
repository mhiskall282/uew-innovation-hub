# Database Schema Change Workflow

> **Platform**: UEW Innovation Hub Enterprise

---

## 1. Impact Analysis
1. Inspect existing schema and models in `app/Models/` and `database/migrations/`.
2. Determine if the change will affect active production records or existing relationships.
3. Verify that the change is additive (new nullable columns or columns with defaults).

---

## 2. Generate Migration
```bash
php artisan make:migration add_field_to_table_name --table=table_name
```
- Define `up()` and reverse `down()` methods.
- Define explicit foreign key constraints and index names.
- Never write destructive commands (`dropColumn`, `dropTable`) on production tables without a prior staged deprecation.

---

## 3. Synchronize Domain Models & Factories
1. Add new attributes to Model `$fillable` array.
2. Add attribute type casting to Model `$casts` array.
3. Update Model Factory definition (`database/factories/`) with realistic test attributes.
4. Update any relevant database seeders (`database/seeders/`).

---

## 4. Verify Both Directions
Run migration and rollback locally to confirm clean reversibility:
```bash
php artisan migrate
php artisan migrate:rollback --step=1
php artisan migrate
```

---

## 5. Verify Application & Tests
- Run `php artisan test` to ensure existing feature and unit tests pass with the new schema.
- Update `.ai/database.md` if core entity relationships changed.
- Update `.ai/changelog.md` under the `### Database` section.
