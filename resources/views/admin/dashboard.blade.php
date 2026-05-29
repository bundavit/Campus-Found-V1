<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard — Lost & Found</title>
    <link href="{{ asset('assets/bootstrap-5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
    @include('admin.partials.sidebar')

    <div class="flex-grow-1" style="margin-left: 260px;">
        <div class="p-4 min-vh-100">
            @if (session('success'))
                <div class="alert alert-success border-2 border-dark fw-bold">{{ session('success') }}</div>
            @endif

            <div class="text-center mb-5">
                <h1 class="fw-bold text-dark display-5">Management Dashboard</h1>
                <p class="text-muted">Securely manage and monitor all reported items.</p>

                <div class="row g-3 mt-2 px-md-5">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-4">
                            <small class="text-uppercase opacity-75">Total Reports</small>
                            <h3 class="fw-bold m-0">{{ $totalItems }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-4">
                            <small class="text-uppercase opacity-75">Lost Items</small>
                            <h3 class="fw-bold m-0">{{ $lostItems }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-4">
                            <small class="text-uppercase opacity-75">Found Items</small>
                            <h3 class="fw-bold m-0">{{ $foundItems }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <form method="get" action="{{ route('admin.dashboard') }}" class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control w-auto flex-grow-1 border-0 bg-light py-2"
                           placeholder="Search by title or location...">
                    <select name="sort" class="form-select w-auto border-0 bg-light py-2" onchange="this.form.submit()">
                        <option value="desc" @selected($sort === 'desc')>Newest First</option>
                        <option value="asc" @selected($sort === 'asc')>Oldest First</option>
                    </select>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Apply</button>
                </div>
            </form>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4 py-3">Item Details</th>
                                <th>Status</th>
                                <th>Date Reported</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary text-uppercase">{{ $item['title'] }}</div>
                                        <small class="text-muted">📍 {{ $item['location'] }}</small>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-2 {{ $item['status'] === 'lost' ? 'bg-danger' : 'bg-success' }}">
                                            {{ strtoupper($item['status']) }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ \Illuminate\Support\Carbon::parse($item['created_at'])->toFormattedDateString() }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                data-bs-toggle="modal" data-bs-target="#admin-item-{{ $item['id'] }}">
                                            View
                                        </button>
                                        <form method="post" action="{{ route('admin.items.destroy', $item['id']) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this report forever?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-bold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted fw-bold">No reports yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @foreach($items as $item)
        <div class="modal fade" id="admin-item-{{ $item['id'] }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border border-3 border-dark" style="border-radius: 28px; overflow: hidden;">
                    <div class="modal-body p-0">
                        <div class="bg-dark d-flex align-items-center justify-content-center" style="height: 300px;">
                            <img src="{{ $item['image_url'] ?: 'https://via.placeholder.com/400x250?text=No+Image' }}"
                                 class="h-100 mw-100" style="object-fit: contain;" alt="">
                        </div>
                        <div class="p-4">
                            <h2 class="fw-bold text-uppercase h4">{{ $item['title'] }}</h2>
                            <p class="text-muted fw-bold">📍 {{ $item['location'] }}</p>
                            <p class="bg-light p-3 rounded-3 border border-2 border-dark">{{ $item['description'] ?: 'No description.' }}</p>
                            <p class="fw-bold">Contact: {{ $item['contact_info'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script src="{{ asset('assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
