@extends('layouts.main')

@section('title', 'My Dashboard')

@section('content')
<div class="cf-page cf-account-dashboard">
    <section class="cf-board-hero">
        <div class="cf-container">
            <h1>My Dashboard</h1>
            <p>Manage your reports, claims, and account information.</p>
        </div>
    </section>

    <div class="cf-container cf-account-shell">
        <nav class="cf-account-tabs" aria-label="Account sections">
            <a href="#overview">Overview</a>
            <a href="#my-reports">My Reports</a>
            <a href="#my-claims">My Claims</a>
            <a href="#profile">Profile</a>
        </nav>

        <section id="overview" class="cf-account-section">
            <div class="cf-account-stats">
                <div><i class="bi bi-card-list"></i><strong>{{ $stats['reports'] }}</strong><span>Total Reports</span></div>
                <div><i class="bi bi-broadcast"></i><strong>{{ $stats['active_reports'] }}</strong><span>Active Reports</span></div>
                <div><i class="bi bi-check2-circle"></i><strong>{{ $stats['claims'] }}</strong><span>Submitted Claims</span></div>
                <div><i class="bi bi-box2-heart"></i><strong>{{ $stats['recovered'] }}</strong><span>Approved Claims</span></div>
            </div>
        </section>

        <section id="my-reports" class="cf-account-section">
            <div class="cf-account-heading">
                <div><h2>My Reports</h2><p>Items you reported to the campus community.</p></div>
                <a href="{{ route('report.create') }}" class="cf-btn cf-nav-report"><i class="bi bi-plus-lg"></i> New Report</a>
            </div>
            <form method="get" action="{{ route('account.show') }}#my-reports" class="cf-history-filter">
                <label><span>Report status</span><select name="report_status" onchange="this.form.submit()">
                    <option value="all" @selected($reportStatus === 'all')>All reports</option>
                    <option value="lost" @selected($reportStatus === 'lost')>Lost</option>
                    <option value="found" @selected($reportStatus === 'found')>Found</option>
                </select></label>
                <input type="hidden" name="claim_status" value="{{ $claimStatus }}">
            </form>
            <div class="cf-account-list">
                @forelse($reports as $report)
                    <article class="cf-account-row">
                        <div class="cf-account-row-main">
                            <span class="cf-status cf-status-{{ $report->status }}">{{ ucfirst($report->status) }}</span>
                            <div>
                                <h3>{{ $report->title }}</h3>
                                <p><i class="bi bi-geo-alt"></i> {{ $report->location }} · {{ $report->reported_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="cf-account-row-meta">
                            <span>{{ $report->claims_count }} responses</span>
                            @if($report->pending_claims_count > 0)<strong>{{ $report->pending_claims_count }} pending</strong>@endif
                            @if($report->moderation_status === 'hidden')<strong class="text-danger">Hidden by admin</strong>@endif
                        </div>
                        <div class="cf-account-row-actions">
                            <a href="{{ route('report.edit', $report) }}" class="cf-btn cf-btn-outline"><i class="bi bi-pencil"></i> Edit</a>
                            <form method="post" action="{{ route('report.destroy', $report) }}" onsubmit="return confirm('Delete this report permanently?')">
                                @csrf @method('DELETE')
                                <button class="cf-btn cf-btn-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="cf-empty-state"><h3>No reports yet.</h3><p>Create your first lost or found report.</p></div>
                @endforelse
            </div>
            {{ $reports->withQueryString()->fragment('my-reports')->links() }}
        </section>

        <section id="my-claims" class="cf-account-section">
            <div class="cf-account-heading">
                <div><h2>My Claims</h2><p>Track the responses you submitted.</p></div>
                <a href="{{ route('board.index') }}" class="cf-btn cf-btn-primary">Browse Items</a>
            </div>
            <form method="get" action="{{ route('account.show') }}#my-claims" class="cf-history-filter">
                <label><span>Claim status</span><select name="claim_status" onchange="this.form.submit()">
                    <option value="all" @selected($claimStatus === 'all')>All claims</option>
                    <option value="pending" @selected($claimStatus === 'pending')>Pending</option>
                    <option value="approved" @selected($claimStatus === 'approved')>Approved</option>
                    <option value="rejected" @selected($claimStatus === 'rejected')>Rejected</option>
                </select></label>
                <input type="hidden" name="report_status" value="{{ $reportStatus }}">
            </form>
            <div class="cf-account-list">
                @forelse($claims as $claim)
                    <article class="cf-account-row">
                        <div class="cf-account-row-main">
                            <span class="cf-request-badge cf-request-{{ $claim->status }}">{{ ucfirst($claim->status) }}</span>
                            <div>
                                <h3>{{ $claim->item?->title ?? 'Removed item' }}</h3>
                                <p>{{ $claim->type === 'claim' ? 'Ownership claim' : 'Found-item response' }} · {{ $claim->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="cf-account-message">{{ $claim->message ?: 'No proof provided.' }}</div>
                        @if($claim->dispute_status === 'open')
                            <div class="cf-account-message"><strong>Dispute under review:</strong> {{ $claim->dispute_reason }}</div>
                        @elseif(in_array($claim->status, ['approved', 'rejected'], true) && $claim->dispute_status === 'none')
                            <details class="cf-dispute-form">
                                <summary>Request admin review</summary>
                                <form method="post" action="{{ route('claims.dispute', $claim) }}">
                                    @csrf
                                    <textarea name="reason" rows="3" required placeholder="Explain why this decision should be reviewed."></textarea>
                                    <button class="cf-btn cf-btn-outline" type="submit">Submit Dispute</button>
                                </form>
                            </details>
                        @endif
                    </article>
                @empty
                    <div class="cf-empty-state"><h3>No claims yet.</h3><p>Browse the board to find a matching item.</p></div>
                @endforelse
            </div>
            {{ $claims->withQueryString()->fragment('my-claims')->links() }}
        </section>

        <section id="profile" class="cf-account-section">
            <div class="cf-account-heading"><div><h2>Profile</h2><p>Keep your contact and student information current.</p></div></div>
            <div class="cf-account-settings">
                <form method="post" action="{{ route('account.profile.update') }}" class="cf-page-form">
                    @csrf @method('PUT')
                    <h3>Personal Information</h3>
                    <label><span>Name</span><input name="name" value="{{ old('name', $user->name) }}" required>@error('name', 'profile')<small class="cf-error">{{ $message }}</small>@enderror</label>
                    <label><span>Email</span><input type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email', 'profile')<small class="cf-error">{{ $message }}</small>@enderror</label>
                    <div class="cf-form-grid">
                        <label><span>Phone</span><input name="phone" value="{{ old('phone', $user->phone) }}"></label>
                        <label><span>Student ID</span><input name="student_id" value="{{ old('student_id', $user->student_id) }}"></label>
                    </div>
                    <button class="cf-btn cf-btn-primary" type="submit">Save Profile</button>
                </form>

                <form method="post" action="{{ route('account.password.update') }}" class="cf-page-form">
                    @csrf @method('PUT')
                    <h3>Change Password</h3>
                    <label><span>Current Password</span><input type="password" name="current_password" required>@error('current_password', 'password')<small class="cf-error">{{ $message }}</small>@enderror</label>
                    <label><span>New Password</span><input type="password" name="password" required>@error('password', 'password')<small class="cf-error">{{ $message }}</small>@enderror</label>
                    <label><span>Confirm New Password</span><input type="password" name="password_confirmation" required></label>
                    <button class="cf-btn cf-btn-primary" type="submit">Update Password</button>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
