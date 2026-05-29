<?php

namespace App\Http\Controllers;

use App\Services\ItemDataService;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index(Request $request, ItemDataService $items)
    {
        return view('board', [
            'items' => $items->filtered([
                'status' => $request->query('status', 'all'),
                'search' => $request->query('search'),
                'date' => $request->query('date'),
                'sort' => $request->query('sort', 'desc'),
            ]),
            'filter' => $request->query('status', 'all'),
            'search' => $request->query('search', ''),
            'date' => $request->query('date', ''),
            'sort' => $request->query('sort', 'desc'),
        ]);
    }
}
