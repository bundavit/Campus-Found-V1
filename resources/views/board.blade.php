@extends('layouts.main')

@section('title', 'Community Board')

@section('content')
<div class="min-vh-100 bg-white pb-5">
    <div class="text-white shadow-sm mb-4" style="background: #0d6efd; border-bottom: 5px solid #ffc107;">
        <div class="container py-5 text-center">
            <h1 class="display-5 fw-bold text-uppercase">RUPP Community Board</h1>
            <p class="lead fw-bold mb-0">Find your belongings within the RUPP community.</p>
        </div>
    </div>

    <div class="container">
        <form method="get" action="{{ route('board.index') }}" class="row g-2 mb-4 justify-content-center px-2">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <div class="col-12 col-lg-8">
                <div class="input-group input-group-lg shadow-sm border border-2 border-dark rounded">
                    <span class="input-group-text bg-white border-0">🔍</span>
                    <input type="text" name="search" value="{{ $search }}" class="form-control border-0"
                           placeholder="Search name or location...">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Search</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-3 justify-content-center mb-4 px-2">
            @foreach(['all' => '#212529', 'lost' => '#dc3545', 'found' => '#198754'] as $type => $color)
                @php
                    $active = $filter === $type;
                    $query = array_filter(['status' => $type !== 'all' ? $type : null, 'search' => $search ?: null, 'date' => $date ?: null, 'sort' => $sort]);
                @endphp
                <a href="{{ route('board.index', $query) }}"
                   class="btn btn-lg px-4 py-2 fw-bold text-uppercase"
                   style="border-radius: 12px; min-width: 115px; border: 3px solid {{ $color }};
                          background-color: {{ $active ? $color : '#fff' }};
                          color: {{ $active ? '#fff' : $color }};">
                    {{ $type }}
                </a>
            @endforeach
        </div>

        <form method="get" class="row g-2 mb-5 justify-content-center">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <div class="col-auto">
                <input type="date" name="date" value="{{ $date }}" class="form-control border-2 border-dark" onchange="this.form.submit()">
            </div>
            <div class="col-auto">
                <select name="sort" class="form-select border-2 border-dark" onchange="this.form.submit()">
                    <option value="desc" @selected($sort === 'desc')>Newest first</option>
                    <option value="asc" @selected($sort === 'asc')>Oldest first</option>
                </select>
            </div>
        </form>

        <div class="row g-3 g-md-4 justify-content-center px-2">
            @forelse($items as $item)
                @php $modalId = 'board-item-' . $item['id']; @endphp
                <div class="col-6 col-md-4 col-lg-3 d-flex justify-content-center">
                    @include('partials.item-card', ['item' => $item, 'modalId' => $modalId])
                </div>
                @include('partials.item-modal', ['item' => $item, 'id' => $modalId])
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted fw-bold">No items match your filters.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
