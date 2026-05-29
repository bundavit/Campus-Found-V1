<?php

namespace App\Http\Controllers;

use App\Services\ItemDataService;

class HomeController extends Controller
{
    public function __invoke(ItemDataService $items)
    {
        return view('home', [
            'recentItems' => $items->recent(4),
        ]);
    }
}
