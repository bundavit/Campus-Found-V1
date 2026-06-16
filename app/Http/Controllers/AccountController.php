<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $reportStatus = $request->query('report_status', 'all');
        $claimStatus = $request->query('claim_status', 'all');

        $reportsQuery = Item::query()
            ->where('user_id', $user->id)
            ->withCount([
                'claims',
                'claims as pending_claims_count' => fn ($query) => $query->where('status', 'pending'),
                'claims as approved_claims_count' => fn ($query) => $query->where('status', 'approved'),
            ]);
        if (in_array($reportStatus, ['lost', 'found'], true)) {
            $reportsQuery->where('status', $reportStatus);
        }
        $reports = $reportsQuery->orderByDesc('reported_at')->paginate(8, ['*'], 'reports_page');

        $claimsQuery = ItemClaim::query()
            ->where('user_id', $user->id)
            ->with('item');
        if (in_array($claimStatus, ['pending', 'approved', 'rejected'], true)) {
            $claimsQuery->where('status', $claimStatus);
        }
        $claims = $claimsQuery->orderByDesc('created_at')->paginate(8, ['*'], 'claims_page');

        $allReports = Item::where('user_id', $user->id)->withCount([
            'claims as approved_claims_count' => fn ($query) => $query->where('status', 'approved'),
        ])->get();
        $allClaims = ItemClaim::where('user_id', $user->id)->get();

        return view('account.dashboard', [
            'user' => $user,
            'reports' => $reports,
            'claims' => $claims,
            'stats' => [
                'reports' => $allReports->count(),
                'active_reports' => $allReports->where('approved_claims_count', 0)->count(),
                'claims' => $allClaims->count(),
                'recovered' => $allClaims->where('status', 'approved')->count(),
            ],
            'reportStatus' => $reportStatus,
            'claimStatus' => $claimStatus,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'student_id' => ['nullable', 'string', 'max:60'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
