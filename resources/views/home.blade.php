@extends('layouts.main')

@section('title', 'Home')

@section('content')
<div class="bg-white min-vh-100 pb-5">
    <section class="text-center text-white shadow-sm mb-5" style="background: #0d6efd; border-bottom: 5px solid #ffc107;">
        <div class="container py-5">
            <h1 class="display-3 fw-bold mb-3 text-uppercase">YOU LOST WE FOUND</h1>
            <p class="lead fw-bold mb-5 mx-auto" style="max-width: 750px;">
                The official digital portal for the RUPP community. Report lost items or browse
                our verified board to find what you've lost.
            </p>
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 pb-4">
                <a href="{{ route('board.index') }}" class="btn btn-light btn-lg px-5 fw-bold rounded-pill border border-2 border-dark shadow">
                    Browse Board
                </a>
                <a href="{{ route('report.create') }}" class="btn btn-warning btn-lg px-5 fw-bold rounded-pill border border-2 border-dark shadow">
                    + Report Item
                </a>
            </div>
        </div>
    </section>

    <section class="container mb-5">
        <div class="row g-4 text-center justify-content-center">
            @foreach([
                ['icon' => '🔍', 'title' => '1. Search', 'text' => 'Check the community board for your missing item.'],
                ['icon' => '📝', 'title' => '2. Report', 'text' => 'Submit a report if you found or lost something.'],
                ['icon' => '🤝', 'title' => '3. Reunited', 'text' => 'Connect with the owner and return the item.'],
            ] as $step)
                <div class="col-md-4 col-sm-10">
                    <div class="p-4 bg-light rounded-4 border border-2 border-dark h-100 shadow-sm">
                        <div class="display-4 mb-3">{{ $step['icon'] }}</div>
                        <h4 class="fw-bold">{{ $step['title'] }}</h4>
                        <p class="text-muted mb-0 fw-semibold">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h2 class="fw-bold m-0 text-dark text-uppercase">Recent Activity</h2>
            <a href="{{ route('board.index') }}" class="btn btn-outline-primary fw-bold rounded-pill border-2">View All →</a>
        </div>
        <div class="row g-3 g-md-4 justify-content-center px-2">
            @forelse($recentItems as $item)
                @php $modalId = 'item-modal-' . $item['id']; @endphp
                <div class="col-6 col-md-3 d-flex justify-content-center">
                    @include('partials.item-card', ['item' => $item, 'modalId' => $modalId])
                </div>
                @include('partials.item-modal', ['item' => $item, 'id' => $modalId])
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted fw-bold">No recent items reported yet.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
