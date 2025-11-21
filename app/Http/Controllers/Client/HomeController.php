<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function subscriptions()
    {
        $client = Client::find(Auth::guard('client')->user()->id);
        $subscriptions = Subscription::with(['client', 'plan'])->where('client_id',$client->id)->latest()->paginate(20);
        return view('client.subscriptions',compact('subscriptions'));
    }

    public function invoices()
    {
        $client = Client::find(Auth::guard('client')->user()->id);
        $invoices = Invoice::with(['client', 'items'])->where('client_id',$client->id)->latest()->paginate(20);
        return view('client.invoices',compact('invoices'));
    }
}
