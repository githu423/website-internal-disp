<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout untuk halaman tamu (login, halaman rapat publik).
 */
class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // ===== LAYOUT TANPA SIDEBAR =====
        return view('layouts.guest');
    }
}
