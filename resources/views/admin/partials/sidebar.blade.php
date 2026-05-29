<aside class="bg-dark text-white position-fixed top-0 start-0 h-100 shadow" style="width: 260px; z-index: 1000;">
    <div class="p-4 border-bottom border-secondary">
        <h5 class="fw-bold text-uppercase mb-0">Lost & Found</h5>
        <small class="text-secondary">Admin Panel</small>
    </div>
    <nav class="nav flex-column p-3 gap-2">
        <a class="nav-link text-white fw-bold active bg-primary rounded-3 px-3 py-2" href="{{ route('admin.dashboard') }}">
            Dashboard
        </a>
        <a class="nav-link text-white-50 px-3 py-2" href="{{ route('board.index') }}">Community Board</a>
        <a class="nav-link text-white-50 px-3 py-2" href="{{ route('home') }}">Homepage</a>
    </nav>
    <div class="position-absolute bottom-0 w-100 p-3 border-top border-secondary">
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100 fw-bold">Logout</button>
        </form>
    </div>
</aside>
