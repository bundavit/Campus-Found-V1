<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemClaim;
use App\Models\User;
use App\Services\EmailCodeService;
use App\Services\ItemDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'claims as pending_claims_count' => fn ($query) => $query->where('status', 'pending'),
        ])->get();
        $allClaims = ItemClaim::where('user_id', $user->id)->get();
        $latestActivity = collect()
            ->merge(
                $allReports->map(fn (Item $report) => [
                    'kind' => 'report',
                    'title' => $report->title,
                    'summary' => ucfirst($report->status).' report in '.$report->location,
                    'time' => $report->reported_at ?? $report->created_at,
                    'state' => $report->moderation_status === 'hidden'
                        ? 'hidden'
                        : ($report->approved_claims_count > 0 ? 'resolved' : ($report->pending_claims_count > 0 ? 'attention' : 'open')),
                    'label' => $report->moderation_status === 'hidden'
                        ? 'Hidden by admin'
                        : ($report->approved_claims_count > 0 ? 'Recovered' : ($report->pending_claims_count > 0 ? 'Needs review' : 'Active')),
                ])
            )
            ->merge(
                $allClaims->map(fn (ItemClaim $claim) => [
                    'kind' => 'claim',
                    'title' => $claim->item?->title ?? 'Removed item',
                    'summary' => ($claim->type === 'claim' ? 'Ownership claim' : 'Found-item response').' for '.($claim->item?->location ?? 'unknown location'),
                    'time' => $claim->created_at,
                    'state' => $claim->dispute_status === 'open'
                        ? 'attention'
                        : match ($claim->status) {
                            'approved' => 'resolved',
                            'rejected' => 'closed',
                            default => 'open',
                        },
                    'label' => $claim->dispute_status === 'open'
                        ? 'Dispute open'
                        : ucfirst($claim->status ?? 'pending'),
                ])
            )
            ->sortByDesc('time')
            ->take(6)
            ->values();

        return view('account.dashboard', [
            'user' => $user,
            'reports' => $reports,
            'claims' => $claims,
            'stats' => [
                'reports' => $allReports->count(),
                'active_reports' => $allReports->where('approved_claims_count', 0)->count(),
                'claims' => $allClaims->count(),
                'recovered' => $allClaims->where('status', 'approved')->count(),
                'pending_reviews' => $allReports->sum('pending_claims_count'),
                'open_disputes' => $allClaims->where('dispute_status', 'open')->count(),
            ],
            'latestActivity' => $latestActivity,
            'reportStatus' => $reportStatus,
            'claimStatus' => $claimStatus,
        ]);
    }

    public function updateProfile(Request $request, EmailCodeService $codes)
    {
        $user = $request->user();
        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'student_id' => ['nullable', 'string', 'max:60'],
        ]);

        $emailChanged = $validated['email'] !== $user->email;
        $user->update($validated);

        if ($emailChanged && ! $user->isAdmin()) {
            $user->forceFill(['email_verified_at' => null])->save();
            $codes->send($user, EmailCodeService::VERIFY_EMAIL);

            return redirect()
                ->route('verification.notice')
                ->with('success', 'Profile updated. Verify your new email to continue.');
        }

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

    public function destroy(Request $request, ItemDataService $items)
    {
        $request->validateWithBag('deleteAccount', [
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $user = $request->user();
        $userId = $user->id;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($userId, $items) {
            Item::query()
                ->where('user_id', $userId)
                ->pluck('id')
                ->each(fn ($itemId) => $items->delete((string) $itemId));

            ItemClaim::query()
                ->where('user_id', $userId)
                ->get()
                ->each(function (ItemClaim $claim) {
                    if ($claim->proof_image_path) {
                        Storage::disk('public')->delete($claim->proof_image_path);
                    }

                    $claim->delete();
                });

            $user = User::query()->find($userId);

            if ($user) {
                $user->tokens()->delete();
                $user->delete();
            }
        });

        return redirect()->route('home')->with('success', 'Your account has been deleted.');
    }
}
