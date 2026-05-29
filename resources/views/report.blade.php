@extends('layouts.main')

@section('title', 'Report an Item')

@section('content')
<div class="bg-white min-vh-100 pb-5">
    <div class="text-white py-4 text-center shadow-sm mb-4" style="background: #0d6efd; border-bottom: 5px solid #ffc107;">
        <h2 class="fw-bold text-uppercase mb-1">Report an Item</h2>
        <p class="opacity-100 fw-bold small mb-0">Help the RUPP community stay connected.</p>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-md-8 col-lg-6">
                <div class="card shadow-lg border border-3 border-dark p-2" style="border-radius: 25px;">
                    <div class="card-body">
                        <form method="post" action="{{ route('report.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Item Name</label>
                                <input type="text" name="title" value="{{ old('title') }}"
                                       class="form-control border-2 border-dark rounded-3 @error('title') is-invalid @enderror"
                                       placeholder="Ex: Blue Wallet, Student ID" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" class="form-select border-2 border-dark rounded-3">
                                        <option value="lost" @selected(old('status', 'lost') === 'lost')>Lost Item</option>
                                        <option value="found" @selected(old('status') === 'found')>Found Item</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">Date & Time</label>
                                    <input type="datetime-local" name="created_at"
                                           value="{{ old('created_at', now()->format('Y-m-d\TH:i')) }}"
                                           class="form-control border-2 border-dark rounded-3" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" name="location" value="{{ old('location') }}"
                                       class="form-control border-2 border-dark rounded-3" placeholder="Ex: Building A" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Info</label>
                                <input type="text" name="contact_info" value="{{ old('contact_info') }}"
                                       class="form-control border-2 border-dark rounded-3" placeholder="Telegram or Phone number" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description (Optional)</label>
                                <textarea name="description" rows="2" class="form-control border-2 border-dark rounded-3"
                                          placeholder="Color, brand, or unique marks...">{{ old('description') }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Attach Photo</label>
                                <input type="file" name="image" accept="image/*" class="form-control border-2 border-dark rounded-3">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-3 shadow-sm rounded-pill border-3 border-dark">
                                SUBMIT REPORT
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
