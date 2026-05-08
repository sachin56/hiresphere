<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'HireSphere')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

{{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
<nav class="sidebar" id="sidebar">

    <div class="sidebar-brand d-flex align-items-center gap-2">
        <div class="d-flex align-items-center justify-content-center rounded-2"
             style="width:36px;height:36px;background:rgba(255,255,255,.2);flex-shrink:0">
            <span class="text-white fw-bold">H</span>
        </div>
        <span class="text-white fw-semibold">HireSphere</span>
    </div>

    <div class="sidebar-nav nav flex-column flex-grow-1 py-2 overflow-auto">

        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('interviewers.index') }}"
           class="nav-link {{ request()->routeIs('interviewers.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Interviewers
        </a>

        <a href="{{ route('bookings.index') }}"
           class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Bookings
        </a>

        <a href="{{ route('submissions.index') }}"
           class="nav-link {{ request()->routeIs('submissions.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Submissions
        </a>

        <a href="{{ route('evaluations.index') }}"
           class="nav-link {{ request()->routeIs('evaluations.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Evaluations
        </a>

        <a href="{{ route('messages.index') }}"
           class="nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Messages
        </a>

        @if($authUser->isInterviewer())
        <a href="{{ route('availability.index') }}"
           class="nav-link {{ request()->routeIs('availability.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Availability
        </a>
        @endif

    </div>

    <div class="p-3" style="border-top:1px solid rgba(255,255,255,.08)">
        <a href="{{ route('profile.edit') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                 style="width:34px;height:34px;background:rgba(255,255,255,.2);font-size:.8rem;flex-shrink:0">
                {{ strtoupper(substr($authUser->name, 0, 2)) }}
            </div>
            <div class="overflow-hidden">
                <p class="mb-0 text-white small fw-semibold text-truncate">{{ $authUser->name }}</p>
                <p class="mb-0 text-truncate" style="color:rgba(255,255,255,.5);font-size:.72rem">{{ ucfirst($authUser->role) }}</p>
            </div>
        </a>
    </div>

</nav>

{{-- Backdrop (mobile) --}}
<button id="sidebarBackdrop" class="d-lg-none border-0 p-0"
        aria-label="Close sidebar"
        style="display:none!important;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:999;cursor:default"
        onclick="closeSidebar()"></button>

{{-- ── Main ─────────────────────────────────────────────────────────────── --}}
<div class="main-content d-flex flex-column">

    <header class="bg-white border-bottom sticky-top">
        <div class="d-flex align-items-center px-3 px-lg-4" style="height:60px">

            <button class="btn btn-sm btn-outline-secondary me-3 d-lg-none"
                    onclick="toggleSidebar()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h6 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h6>

            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2"
                            data-bs-toggle="dropdown">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                             style="width:28px;height:28px;background:#4f46e5;font-size:.7rem;flex-shrink:0">
                            {{ strtoupper(substr($authUser->name, 0, 2)) }}
                        </div>
                        <span class="d-none d-sm-inline small">{{ $authUser->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item small" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item small text-danger">Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    @if(session('success') || session('error'))
    <div class="px-3 px-lg-4 pt-3">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-0">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-0">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>
    @endif

    <main class="flex-grow-1 p-3 p-lg-4">
        @yield('content')
    </main>

</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('show');
    backdrop.style.setProperty('display', sidebar.classList.contains('show') ? 'block' : 'none', 'important');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('show');
    document.getElementById('sidebarBackdrop').style.setProperty('display', 'none', 'important');
}
</script>

</body>
</html>
