<?php

namespace App\Http\Controllers;

use App\Models\TimeSheet;
use Illuminate\Http\Request;
use View;

class RunController extends Controller
{
        /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', TimeSheet::class);
        
        return view('app.run.index', compact('rooms', 'search'));
    }
}
