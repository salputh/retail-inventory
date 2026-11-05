---
name: stock-reconciliation
description: Rules for stock count sessions, variance calculation, exception thresholds and the approval flow before count adjustments are posted. Use when working on app/Domain/Reconciliation or count/variance reports.
paths: app/Domain/Reconciliation/**, tests/Feature/Reconciliation/**
---

## State machine
`draft → counting → submitted → under_review → approved → posted`, or `cancelled` from any state before `posted`.
Enforce transitions in one place (an enum + guard method). Only Store Manager can approve; approver must differ from the counter.

## Expected quantity
- Snapshot `expected_qty` per line when the session moves to `counting`.
- At submit, adjust: expected = snapshot + SUM(movements for that SKU/location with occurred_at between snapshot and counted_at).
- Store both snapshot and adjusted expected for audit.

## Variance and exceptions
- `variance_qty = counted_qty - expected_qty` (generated column).
- `variance_value = variance_qty * avg_cost`.
- Exception if any configured threshold is exceeded: abs qty, abs %, abs value (per location, from `reconciliation_settings`).
- Exceptions can be accepted, sent to recount, or annotated. Recount creates a new count line; keep the old one.

## Posting
On approve → post all non-zero variances as `count_adjustment` via `PostStockMovement` in a single transaction, link movements to the session, set `posted`.

## Tests to keep
Sales during counting do not create false variance; threshold edge values; approver = counter is rejected; posting is atomic (one failing line rolls back all).
