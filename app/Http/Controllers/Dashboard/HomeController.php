<?php

namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;


class HomeController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }
    public function general_settings()
    {
        return view('dashboard.general-setting');
    }
}
