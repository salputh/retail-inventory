---
name: pos-import
description: Rules for the POS integration pipeline - CSV/XLSX upload, REST push endpoint, SKU mapping, idempotency and discrepancy detection. Use when working on app/Domain/Pos or the /api/v1/pos endpoints.
paths: app/Domain/Pos/**, routes/api.php, tests/Feature/Pos/**
---

## Pipeline
1. Receive file or payload → create `pos_import_batches` row (`pending`), store raw payload in `JSONB`, store file SHA-256.
2. Reject a file whose hash already exists for the same source (return the existing batch id).
3. Queue job `ProcessPosBatch` (Horizon). Stream rows; never load a whole file into memory.
4. Per row: map `external_sku` → variant via `barcodes`, then `product_variants.sku`, then `sku_mappings`.
5. Insert `pos_transactions` with `UNIQUE (pos_source_id, location_id, external_id)`; on conflict skip and count as duplicate.
6. Post valid lines through `PostStockMovement` (`sale` / `customer_return`).
7. Problems go to `pos_discrepancies`, never silently dropped.
8. Finish batch: totals for rows, posted, duplicates, discrepancies, failed.

## Discrepancy types
`unmapped_sku`, `duplicate`, `negative_stock`, `price_mismatch` (beyond configured %), `future_or_closed_hours`, `sequence_gap`.

## API endpoint rules
- Sanctum token per POS source with ability `pos:write`; rate limited.
- Accept batches; respond 202 with batch id; processing is async.
- Re-sending the same payload must be safe (idempotent).

## Tests to keep
Same file twice, same transaction twice via API, unmapped SKU, malformed row, 10k-row file under memory limit.
