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
    public function clients()
    {
        return view('dashboard.clients');
    }
    public function domains()
    {
        return view('dashboard.domains');
    }


    public function sites()
    {
        return view('dashboard.sites');
    }

    public function subscriptions()
    {
        return view('dashboard.subscriptions');
    }
    public function plans()
    {
        return view('dashboard.plans');
    }
}
