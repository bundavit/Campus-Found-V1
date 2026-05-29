<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Lost & Found') — RUPP</title>
    <link href="{{ asset('assets/bootstrap-5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-white">
    @unless (request()->routeIs('admin.dashboard'))
        @include('partials.navbar')
    @endunless

    @if (session('success'))
        <div class="container mt-3">
            <div class="alert alert-success border-2 border-dark fw-bold mb-0">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger border-2 border-dark fw-bold mb-0">{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')

    <script src="{{ asset('assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
