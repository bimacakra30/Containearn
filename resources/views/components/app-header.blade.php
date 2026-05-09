@php
    $role = auth()->user()->role;
    $user = auth()->user();
@endphp

<header class="app-topbar">
    <div class="flex items-center gap-3">
        <button type="button" @click="toggleSidebar()" class="sidebar-toggle hidden xl:inline-flex" aria-label="Toggle sidebar">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>

    <div class="flex items-center gap-3" x-data="{ open: false }">
        <button type="button" class="topbar-icon-button" aria-label="Appearance">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3c0 .27-.01.54-.01.82A7 7 0 0 0 18.18 10.8c.28 0 .55-.01.82-.01Z" />
            </svg>
        </button>

        <span class="hidden text-sm text-slate-600 md:inline">{{ $user->email }}</span>

        <div
            class="relative"
            @mouseenter="open = true"
            @mouseleave="open = false">
            <button
                type="button"
                class="topbar-avatar"
                aria-label="Account menu">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition.opacity
                class="topbar-menu">
                <a href="{{ route($role === 'mahasiswa' ? 'mahasiswa.profile' : 'admin.profile') }}" class="topbar-menu-link">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="topbar-menu-link text-left">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
