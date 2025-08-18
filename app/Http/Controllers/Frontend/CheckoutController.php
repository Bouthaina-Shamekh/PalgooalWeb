<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public function index($template_id)
    {
        return view('tamplate.checkout', compact('template_id'));
    }
}
