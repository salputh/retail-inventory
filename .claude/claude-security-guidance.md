# Security guidance for this repo (retail inventory)

- Every query on transactional tables must be scoped to the user's assigned locations (global scope or explicit where).
- Authorization through Laravel Policies on every controller action and Livewire method that mutates data.
- POS API tokens are per source, ability-scoped (`pos:write`), revocable and rate limited.
- Never build SQL with string concatenation; use the query builder or bound parameters, including in raw reporting queries.
- Imported CSV/XLSX content is untrusted: escape it in Blade, guard against CSV formula injection (`=`, `+`, `-`, `@`) in exports.
- Approval actions must check the approver is not the same user who created the document.
- No secrets, client data or real store data in the repository.
