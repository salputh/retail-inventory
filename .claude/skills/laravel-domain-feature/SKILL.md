---
name: laravel-domain-feature
description: Scaffold a new feature inside a domain module - migration, model, action, policy, form request, Livewire component, routes and Pest tests - following this project's conventions. Use when adding a new entity, document type or screen.
argument-hint: "[Domain] [FeatureName]"
model: sonnet
effort: medium
---

Build feature `$1` in domain `$0`.

## Order of work
1. **Migration** in `database/migrations/`: `bigint` identity PK, `location_id` FK if transactional, `numeric` for qty/money, `timestamptz`, FKs with explicit `on delete`, indexes for every filter used in lists. Must roll back cleanly.
2. **Model** in `app/Domain/$0/Models/`: casts for numerics (as string/decimal, never float), `LocationScope` global scope when it has `location_id`.
3. **Action(s)** in `app/Domain/$0/Actions/`: one public `handle()` method, wrapped in `DB::transaction`. Stock changes go through `PostStockMovement` only.
4. **Form Request** for validation; **Policy** for authorization (role + location).
5. **Livewire component** in `app/Http/Livewire/$0/`: thin, calls the Action, no queries beyond list/read. View uses Bootstrap markup and existing Blade components (`x-card`, `x-modal`, `x-table`); modals open/close through Alpine (`x-data`), not raw `data-bs-toggle` inside morphed DOM; set `$paginationTheme = 'bootstrap'` for paginated lists.
6. **Audit**: log create/update/approve via activitylog.
7. **Tests** in `tests/Feature/$0/`: one test per acceptance criterion of the matching US-1.xx in `docs/PRD-retail-inventory.md`, plus validation failure, wrong-location user gets 404, and role without permission gets 403 (per the RBAC matrix §4.3).
8. Run `pint`, `phpstan`, `php artisan test`. Fix until green.

## Done when
All steps above exist, tests pass, and `inventory:verify` still passes if the feature touches stock.
