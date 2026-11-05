<div>
    <h1 class="h3 mb-3">Retail Inventory Platform</h1>
    <p class="text-body-secondary">Kerangka project aktif. Halaman ini hanya contoh (T0.04).</p>

    <div class="card" style="max-width: 32rem">
        <div class="card-body">
            <p class="mb-3">Klik: <strong dusk="clicks">{{ $clicks }}</strong></p>
            <button type="button" class="btn btn-outline-primary" wire:click="increment">Re-render Livewire</button>
        </div>
    </div>

    {{-- Modal Bootstrap dikendalikan Alpine; wire:ignore agar tidak di-morph. --}}
    <div x-data="{ modal: null }" x-init="modal = bootstrap.Modal.getOrCreateInstance($refs.modal)" class="mt-3">
        <button type="button" class="btn btn-primary" @click="modal.show()">Buka modal</button>

        <div class="modal fade" tabindex="-1" x-ref="modal" wire:ignore.self>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5">Modal contoh</h2>
                        <button type="button" class="btn-close" aria-label="Tutup" @click="modal.hide()"></button>
                    </div>
                    <div class="modal-body">Modal ini tetap bekerja setelah komponen re-render.</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="modal.hide()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
