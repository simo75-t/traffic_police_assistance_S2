<aside class="main-sidebar sidebar-dashboard elevation-4 d-flex flex-column bg-dashboard">
    <div class="p-3 d-flex flex-column flex-grow-1">
        <div class="text-center mb-4">
            <h5 class="mb-0 text-white fw-bold">Admin Dashboard</h5>
            <small class="text-white-50">Control center</small>
        </div>

        <nav class="nav flex-column flex-grow-1 mt-2">
            <a class="nav-link mb-2 {{ request()->routeIs('admin.home') ? 'active' : '' }}"
               href="{{ route('admin.home') }}">
                <i class="bi bi-house-door me-2"></i> Dashboard
            </a>

            <a class="nav-link mb-2 {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
               href="{{ route('admin.users.index') }}">
                <i class="bi bi-person-badge me-2"></i> Police Accounts
            </a>

            <div class="nav-section-title text-white-50 mt-3 mb-1 ms-1" style="font-size: 0.75rem;">
                Violations Management
            </div>

            <a class="nav-link mb-2 {{ request()->routeIs('admin.violationTypes.*') ? 'active' : '' }}"
               href="{{ route('admin.violationTypes.index') }}">
                <i class="bi bi-exclamation-triangle me-2"></i> Violation Types
            </a>
        </nav>

        <div class="mt-auto pt-3">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-logout w-100 text-center">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </button>
            </form>
        </div>
    </div>
</aside>

<style>
.bg-dashboard {
    background: linear-gradient(180deg, #10243d, #1a5d87);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: #fff;
    padding-top: 1rem;
}

.sidebar-dashboard {
    border-top-right-radius: 24px;
    border-bottom-right-radius: 24px;
    overflow: hidden;
}

.nav-section-title {
    letter-spacing: 1px;
    text-transform: uppercase;
}

.nav-link {
    color: #e2e6ea;
    transition: all 0.3s ease;
    border-radius: 0.8rem;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    font-weight: 600;
}

.nav-link i {
    font-size: 1.1rem;
}

.nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
}

.nav-link.active {
    background: linear-gradient(135deg, #efd28a, #d7a93c);
    color: #10243d;
    font-weight: 800;
}

.btn-logout {
    background-color: #f8f9fc;
    color: #10243d;
    font-weight: 700;
    border-radius: 0.8rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    background-color: #e2e6ea;
    color: #10243d;
}
</style>
