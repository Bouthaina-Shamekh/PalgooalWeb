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
    public function feedbacks()
    {
        return view('dashboard.feedbacks');
    }
    public function general_settings()
    {
        return view('dashboard.general-setting');
    }
    public function portfolios()
    {
        return view('dashboard.portfolios');
    }
}
