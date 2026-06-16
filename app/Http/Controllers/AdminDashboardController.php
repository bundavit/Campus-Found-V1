<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemClaim;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ClaimDataService;
use App\Services\ItemDataService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request, ItemDataService $items, ClaimDataService $claims)
    {
        $section = $request->query('section', 'items');
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'desc');
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $claimFilter = $request->query('claim_status', 'all');
        $reviewStatus = $request->query('review_status', 'all');

        $allItems = $items->filtered([
            'include_claimed' => true,
            'status' => $status,
            'category' => $category,
            'search' => $search,
            'sort' => $sort,
        ]);

        $allClaims = $claims->filtered([
            'type' => $claimFilter,
            'status' => $reviewStatus,
            'category' => $category,
            'search' => $search,
            'sort' => $sort,
        ]);

        $claimStats = $claims->filtered([]);
        $users = User::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'users_page');
        $auditLogs = AuditLog::query()->latest()->paginate(20, ['*'], 'audit_page');

        return view('admin.dashboard', [
            'section' => $section,
            'items' => $allItems,
            'claims' => $allClaims,
            'search' => $search,
            'sort' => $sort,
            'status' => $status,
            'category' => $category,
            'categories' => config('lostfound.categories'),
            'claimFilter' => $claimFilter,
            'reviewStatus' => $reviewStatus,
            'totalItems' => count($allItems),
            'lostItems' => collect($allItems)->where('status', 'lost')->count(),
            'foundItems' => collect($allItems)->where('status', 'found')->count(),
            'totalClaims' => count($claimStats),
            'ownershipClaims' => collect($claimStats)->where('type', 'claim')->count(),
            'pendingClaims' => collect($claimStats)->where('status', 'pending')->count(),
            'users' => $users,
            'auditLogs' => $auditLogs,
            'totalUsers' => User::count(),
            'openDisputes' => ItemClaim::where('dispute_status', 'open')->count(),
        ]);
    }

    public function destroy(string $id, ItemDataService $items, AuditService $audit)
    {
        $item = Item::find($id);
        if ($item) {
            $audit->record('item.deleted', $item);
        }
        $items->delete($id);

        return redirect()
            ->back()
            ->with('success', 'Report deleted.');
    }

    public function destroyClaim(string $id, ClaimDataService $claims, AuditService $audit)
    {
        $claim = ItemClaim::find($id);
        if ($claim) {
            $audit->record('claim.deleted', $claim);
        }
        $claims->delete($id);

        return redirect()
            ->back()
            ->with('success', 'Claim removed.');
    }

    public function reviewClaim(Request $request, ItemClaim $claim, ClaimDataService $claims, AuditService $audit)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $claims->review($claim, $validated['status'], 0);
        $audit->record('claim.reviewed', $claim, $validated['status']);

        return redirect()->back()->with('success', 'Claim '.$validated['status'].'.');
    }

    public function updateUser(Request $request, User $user, AuditService $audit)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,admin,super_admin'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $user->update($validated);
        $audit->record('user.updated', $user, "role={$validated['role']}; status={$validated['status']}");

        return back()->with('success', 'User access updated.');
    }

    public function moderateItem(Request $request, Item $item, AuditService $audit)
    {
        $validated = $request->validate([
            'moderation_status' => ['required', 'in:active,hidden'],
            'reason' => ['nullable', 'required_if:moderation_status,hidden', 'string', 'max:1000'],
        ]);

        $item->update([
            'moderation_status' => $validated['moderation_status'],
            'moderation_reason' => $validated['reason'] ?? null,
        ]);
        $audit->record('item.moderated', $item, $validated['reason'] ?? $validated['moderation_status']);

        return back()->with('success', 'Report moderation updated.');
    }

    public function resolveDispute(Request $request, ItemClaim $claim, AuditService $audit)
    {
        $validated = $request->validate([
            'dispute_status' => ['required', 'in:resolved,dismissed'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $claim->update($validated);
        $audit->record('claim.dispute_resolved', $claim, json_encode($validated));

        return back()->with('success', 'Claim dispute resolved.');
    }
}
