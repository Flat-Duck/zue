<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class NavigationBuilderController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('manage-navigation');

        return view('app.navigation-builder.index');
    }
}
