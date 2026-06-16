<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemClaim;
use App\Services\ClaimDataService;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index(Request $request, ClaimDataService $claims)
    {
        return response()->json([
            'data' => $claims->filtered([
                'type' => $request->query('type', 'all'),
                'status' => $request->query('status'),
                'category' => $request->query('category', 'all'),
                'search' => $request->query('search'),
                'sort' => $request->query('sort', 'desc'),
            ]),
        ]);
    }

    public function store(Request $request, ClaimDataService $claims)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'claimant_name' => ['nullable', 'string', 'max:255'],
            'contact_info' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'ownership_proof' => ['nullable', 'string', 'max:1000'],
            'verification_answer' => ['nullable', 'string', 'max:1000'],
            'proof_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $item = Item::findOrFail($validated['item_id']);
        if ((int) $item->user_id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot claim your own report.'], 422);
        }

        if (ItemClaim::query()
            ->where('item_id', $item->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists()) {
            return response()->json(['message' => 'You already submitted a claim for this item.'], 422);
        }

        $ownershipProof = $validated['ownership_proof']
            ?? $validated['message']
            ?? $validated['verification_answer']
            ?? null;

        if ($item->status === 'found' && blank($ownershipProof)) {
            return response()->json(['message' => 'Ownership proof is required.'], 422);
        }

        $validated['ownership_proof'] = $ownershipProof;
        $claim = $claims->create($item, $validated, $request->user()->id, $request->file('proof_image'));

        return response()->json([
            'message' => $item->status === 'found'
                ? 'Claim submitted successfully.'
                : 'Found report submitted successfully.',
            'data' => $claim,
        ], 201);
    }

    public function show(string $id)
    {
        $claim = ItemClaim::with('item')->find($id);

        if (! $claim) {
            return response()->json(['error' => 'Claim not found'], 404);
        }

        $userId = request()->user()?->id;
        abort_unless(
            $userId && (
                (int) $claim->user_id === (int) $userId
                || (int) $claim->item?->user_id === (int) $userId
            ),
            403
        );

        return response()->json(['data' => $claim->toDisplayArray()]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $claim = ItemClaim::with('item')->find($id);

        if (! $claim) {
            return response()->json(['error' => 'Claim not found'], 404);
        }

        $canReview = $claim->item && (int) $claim->item->user_id === (int) $request->user()->id;
        abort_unless($canReview, 403);

        $claims = app(ClaimDataService::class);
        $data = $claims->review($claim, $validated['status'], $request->user()->id);

        return response()->json([
            'message' => 'Claim status updated successfully',
            'data' => $data,
        ]);
    }

    public function destroy(Request $request, string $id, ClaimDataService $claims)
    {
        $claim = ItemClaim::with('item')->find($id);

        if (! $claim) {
            return response()->json(['error' => 'Claim not found'], 404);
        }

        $userId = (int) $request->user()->id;
        $canDelete = (int) $claim->user_id === $userId
            || (int) $claim->item?->user_id === $userId;
        abort_unless($canDelete, 403);

        if (! $claims->delete($id)) {
            return response()->json(['error' => 'Claim not found'], 404);
        }

        return response()->json(['message' => 'Claim deleted successfully']);
    }
}
