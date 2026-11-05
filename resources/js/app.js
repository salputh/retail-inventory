import * as bootstrap from 'bootstrap';

// Alpine dibundel Livewire (jangan di-import lagi). Widget JS Bootstrap
// dikendalikan Alpine lewat window.bootstrap; dilarang data-bs-toggle di
// elemen yang di-morph Livewire (Architecture §6).
window.bootstrap = bootstrap;
