<nav class="navbar navbar-expand bg-body fixed-top shadow-sm navbar-modern">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <!-- Logout -->
            <form action="{{ route('admin.logout') }}" method="POST" class="d-inline me-3">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>

            <!-- Sidebar Toggle -->
            <li class="nav-item">
                <a class="nav-link toggle-btn" data-lte-toggle="sidebar" href="#">
                    <i class="bi bi-list"></i>
                </a>
            </li>

            <li class="nav-item d-none d-md-block ms-2">
                <a href="#" class="nav-link">Home</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <!-- Notifications -->
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="bi bi-bell-fill"></i></a>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="{{ asset('dist/assets/img/user2-160x160.jpg') }}" 
                         class="user-image rounded-circle shadow" alt="User Image" />
                    <span class="d-none d-md-inline">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">Logout</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<style>
.navbar-modern {
    background: linear-gradient(90deg, #1e3c72, #2a5298);
    color: #fff;
}
.navbar-modern .nav-link,
.navbar-modern .navbar-brand {
    color: #fff;
}
.navbar-modern .nav-link:hover {
    color: #ffd700;
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
.toggle-btn i {
    font-size: 1.2rem;
    transition: transform 0.3s;
}
.toggle-btn:hover i {
    transform: rotate(90deg);
    color: #ffd700;
}
</style>
