<?php

namespace App\Http\Controllers;

use App\Services\ItemDataService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request, ItemDataService $items)
    {
        $all = $items->filtered([
            'search' => $request->query('search'),
            'sort' => $request->query('sort', 'desc'),
        ]);

        return view('admin.dashboard', [
            'items' => $all,
            'search' => $request->query('search', ''),
            'sort' => $request->query('sort', 'desc'),
            'totalItems' => count($all),
            'lostItems' => collect($all)->where('status', 'lost')->count(),
            'foundItems' => collect($all)->where('status', 'found')->count(),
        ]);
    }

    public function destroy(string $id, ItemDataService $items)
    {
        $items->delete($id);

        return redirect()
            ->back()
            ->with('success', 'Report deleted.');
    }
}
