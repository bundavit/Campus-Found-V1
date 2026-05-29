@props(['item', 'id'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border border-3 border-dark shadow-lg" style="border-radius: 25px; overflow: hidden;">
            <div class="modal-body p-0 text-dark">
                @if(!empty($item['image_url']))
                    <div class="bg-light border-bottom border-2 border-dark d-flex align-items-center justify-content-center" style="height: 400px;">
                        <img src="{{ $item['image_url'] }}" class="w-100 h-100" style="object-fit: contain;" alt="{{ $item['title'] }}">
                    </div>
                @endif
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="fw-bold m-0">{{ $item['title'] }}</h2>
                        <span class="badge px-3 py-2 rounded-pill border border-2 border-dark {{ $item['status'] === 'lost' ? 'bg-danger text-white' : 'bg-success text-white' }}">
                            {{ strtoupper($item['status']) }}
                        </span>
                    </div>
                    <p class="text-muted mb-4 fs-6 fw-bold">📅 {{ \Illuminate\Support\Carbon::parse($item['created_at'])->toFormattedDateString() }}</p>
                    <hr class="border-2 border-dark">
                    <div class="mb-3">
                        <label class="fw-bold text-primary small text-uppercase mb-1 d-block">Location</label>
                        <p class="fs-5 fw-bold mb-0">📍 {{ $item['location'] }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-primary small text-uppercase mb-1 d-block">Description</label>
                        <p class="bg-light p-3 rounded-3 border border-dark mb-0">{{ $item['description'] ?: 'No description provided.' }}</p>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-4 shadow-sm border border-2 border-dark text-center">
                        <label class="fw-bold small text-uppercase mb-1 d-block opacity-75">Contact Details</label>
                        <p class="m-0 fs-4 fw-bold">{{ $item['contact_info'] }}</p>
                    </div>

                    @if (session('is_admin'))
                        <form method="post"
                              action="{{ route('admin.items.destroy', $item['id']) }}"
                              class="mt-3"
                              onsubmit="return confirm('Delete this report permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 rounded-pill border border-2 border-dark">
                                Delete Report
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
