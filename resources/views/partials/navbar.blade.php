<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm py-2 px-3"
     style="background: #0d6efd; border-bottom: 5px solid #ffc107;">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand fw-bold fs-3 text-uppercase text-white" href="{{ route('home') }}" style="letter-spacing: 2px;">
            LOST <span style="color: #ffc107;">& FOUND</span>
        </a>
        <button class="navbar-toggler border-2 border-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center gap-3 mt-3 mt-lg-0">
                <a href="{{ route('board.index') }}"
                   class="btn btn-outline-light fw-bold px-4 rounded-pill shadow border border-2 border-dark text-uppercase {{ request()->routeIs('board.index') ? 'border-warning' : '' }}"
                   style="background-color: white; color: #000;">
                    Board
                </a>
                <a href="{{ route('report.create') }}"
                   class="btn btn-warning fw-bold px-4 rounded-pill shadow border border-2 border-dark text-uppercase">
                    + Report Item
                </a>
                @if (session('is_admin'))
                    <a href="{{ route('admin.dashboard') }}"
                       class="btn btn-dark fw-bold px-3 rounded-pill text-uppercase border border-2 border-warning {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       style="font-size: 0.8rem;">
                        Dashboard
                    </a>
                    <form method="post" action="{{ route('admin.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-outline-light btn-sm fw-bold px-3 rounded-pill text-uppercase border border-2 border-dark"
                                style="font-size: 0.75rem;">
                            Logout
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</nav>
