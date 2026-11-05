---
name: demo-seeder
description: Generate or extend sample data for client demos - products, SKUs, 90 days of POS sales and deliberate discrepancy scenarios.
disable-model-invocation: true
argument-hint: "[scenario]"
---

Create or update seeders for scenario: $ARGUMENTS (default: full demo).

## Baseline dataset
1 store location (+1 warehouse location disabled by default), 50 categories, ~2,000 variants with barcodes, 3 POS sources, 90 days of synthetic sales with weekday/weekend and payday peaks, receipts every week.

## Deliberate scenarios (each must be visible in the dashboard)
- 15 unmapped POS SKUs
- 1 duplicated POS file and 5 duplicated API transactions
- 10 SKUs driven negative by sales
- 1 completed stock count with 20 exceptions (mix of shrinkage and over-count)
- 1 pending adjustment waiting for approval

## Rules
- Post every stock change through `PostStockMovement`, never insert stock rows directly.
- Deterministic: seed Faker with a fixed seed.
- After seeding, run `php artisan inventory:verify` and report the result.
- No real brand names, store names or people.
