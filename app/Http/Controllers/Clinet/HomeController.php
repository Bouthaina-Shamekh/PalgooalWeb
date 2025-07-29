<?php

namespace App\Http\Controllers\Clinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('client.index');
    }

    public function updateClient()
    {
        return view('client.updateclinet');
    }

    public function domainNameSearch()
    {
        return view('client.domain-name-search');
    }
}
