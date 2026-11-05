---
name: inventory-ledger
description: Rules for any code that changes stock - stock_movements ledger, stock_levels cache, reversals, locking. Use whenever writing or reviewing receipts, sales, returns, transfers, adjustments, count adjustments, or anything touching on_hand.
paths: app/Domain/Inventory/**, app/Domain/Pos/**, app/Domain/Reconciliation/**, database/migrations/**
---

## The ledger is the source of truth
- `stock_movements` is append-only. A Postgres trigger rejects UPDATE/DELETE. Never write a migration or query that bypasses it.
- `stock_levels.on_hand` is a cache. It is only written inside `PostStockMovement`, in the same DB transaction as the movement row.
- Corrections are new `reversal` movements pointing at `reversed_movement_id`. Never edit a posted movement.

## PostStockMovement contract
1. Open a DB transaction.
2. `SELECT ... FOR UPDATE` the `stock_levels` row for (location_id, product_variant_id); create it if missing.
3. Insert the movement with signed `quantity` (CHECK quantity <> 0), `balance_after`, `reference_type/reference_id`, `occurred_at`, `posted_by`.
4. Update `on_hand`, `avg_cost` (weighted average), `last_movement_id`.
5. Commit. Dispatch events after commit only (`afterCommit`).

Multi-line documents (receipt, transfer, count) post all lines in one transaction. Lock rows in a stable order (by product_variant_id) to avoid deadlocks.

## Movement types and sign
opening_balance (+, once per location, from an approved opening balance document; see PRD §8), receipt (+), sale (−), customer_return (+), supplier_return (−), transfer_out (−), transfer_in (+), adjustment (±), count_adjustment (±), reversal (opposite of original).

## Negative stock
Do not block a POS sale that drives stock negative. Post it and raise a `negative_stock` discrepancy. Manual documents (adjustment, transfer_out) must reject negative results.

## Every change to stock code must
- Add or update a Pest test that asserts `inventory:verify` passes afterwards.
- Include a concurrency test when locking logic changes (two parallel postings on one SKU).
- Keep point-in-time balance computable: `SUM(quantity) WHERE occurred_at <= :t`.
