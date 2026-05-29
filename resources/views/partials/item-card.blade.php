@props(['item', 'modalId' => null])

<div class="card h-100 border-2 border-dark shadow-sm overflow-hidden"
     style="border-radius: 15px; cursor: pointer; max-width: 300px; width: 100%;"
     @if($modalId) data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" @endif>
    <div class="position-relative">
        <img src="{{ $item['image_url'] ?: 'https://via.placeholder.com/300x180?text=No+Image' }}"
             style="height: 180px; width: 100%; object-fit: cover;" alt="{{ $item['title'] }}">
        <span class="position-absolute top-0 end-0 m-2 badge rounded-pill border border-1 border-white {{ $item['status'] === 'lost' ? 'bg-danger' : 'bg-success' }}">
            {{ strtoupper($item['status']) }}
        </span>
    </div>
    <div class="card-body p-3">
        <h6 class="fw-bold mb-1 text-truncate">{{ $item['title'] }}</h6>
        <p class="text-muted small mb-2 text-truncate">📍 {{ $item['location'] }}</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-dark">
            <small class="fw-bold text-secondary" style="font-size: 0.7rem;">
                {{ \Illuminate\Support\Carbon::parse($item['created_at'])->diffForHumans() }}
            </small>
            <small class="fw-bold text-primary">Details →</small>
        </div>
    </div>
</div>
