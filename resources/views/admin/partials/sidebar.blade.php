<aside class="main-sidebar sidebar-light-primary elevation-4 d-flex flex-column bg-gradient-sidebar">
    <div class="p-3">
        <!-- Brand Logo فقط -->
        <div class="text-center mb-4">
            <img src="{{ asset('dist/assets/img/logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 60px;">
        </div>

        <!-- Navigation Menu -->
        <nav class="nav flex-column flex-grow-1">
            <a class="nav-link mb-1 {{ request()->routeIs('admin.home') ? 'active' : '' }}" 
               href="{{ route('admin.home') }}">
                <i class="bi bi-house-door me-2"></i> Dashboard
            </a>
            <a class="nav-link mb-1 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
               href="{{ route('admin.users.index') }}">
                <i class="bi bi-file-text me-2"></i> Police Accounts
            </a>
        </nav>

        <!-- Logout في أسفل الـ Sidebar -->
        <div class="mt-auto pt-3">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>
</aside>

<style>
/* Sidebar Gradient */
.bg-gradient-sidebar {
    background: linear-gradient(180deg, #1e3c72, #2a5298);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: #fff;
}
.nav-link {
    color: #e0e0e0;
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
}
.nav-link i {
    font-size: 1.1rem;
}
.nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
}
.nav-link.active {
    background: #ff6a00;
    color: #fff;
    font-weight: bold;
}
.btn-outline-light {
    border-color: #fff;
    color: #fff;
}
.btn-outline-light:hover {
    background-color: #ffd700;
    color: #1e3c72;
    border-color: #ffd700;
}
</style>
