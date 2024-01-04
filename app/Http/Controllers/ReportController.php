<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
        /**
    * Display the specified resource.
    */
    public function index()
    {
        

        return view('app.reports.index');//->with('user', $user);
    }


            /**
    * Display the specified resource.
    */
    public function minus()
    {
        
        

        return view('app.reports.printable');//->with('user', $user);
    }
}
