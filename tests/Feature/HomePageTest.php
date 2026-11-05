<?php

use App\Http\Livewire\Demo\Welcome;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('menampilkan halaman awal', function () {
    get('/')->assertOk()->assertSee('Retail Inventory Platform');
});

it('merender ulang komponen Livewire', function () {
    Livewire::test(Welcome::class)
        ->assertSet('clicks', 0)
        ->call('increment')
        ->assertSet('clicks', 1);
});
