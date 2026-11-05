<?php

namespace App\Http\Livewire\Demo;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Halaman contoh T0.04: membuktikan modal Bootstrap (via Alpine) tetap
 * berfungsi setelah komponen Livewire re-render. Dihapus/diganti di T1.06.
 */
class Welcome extends Component
{
    public int $clicks = 0;

    public function increment(): void
    {
        $this->clicks++;
    }

    public function render(): View
    {
        return view('livewire.demo.welcome')->layout('layouts::app', ['title' => 'Retail Inventory']);
    }
}
