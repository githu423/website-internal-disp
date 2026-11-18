<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout aplikasi untuk halaman yang memakai sidebar.
 */
class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // ===== LAYOUT UTAMA APLIKASI =====
        return view('layouts.app');
    }
}
