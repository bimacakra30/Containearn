@php
$role = auth()->user()->role;
$menus = config("sidebar.$role") ?? [];
$menuMeta = [
    'Dashboard' => [
        'icon' => '<path d="M4.75 5.75h6.5v5.5h-6.5zm8 0h6.5v8h-6.5zm-8 7h6.5v6.5h-6.5zm8 2.5h6.5v4h-6.5z" />',
    ],
    'Profile' => [
        'icon' => '<path d="M12 12.25a3.75 3.75 0 1 0-3.75-3.75A3.75 3.75 0 0 0 12 12.25Zm0 2c-3.4 0-6.25 1.73-6.25 4.25V20h12.5v-1.5c0-2.52-2.85-4.25-6.25-4.25Z" />',
    ],
    'Practicum Content' => [
        'icon' => '<path d="M7.25 5.75h9.5a2 2 0 0 1 2 2v8.5a2 2 0 0 1-2 2h-9.5a2 2 0 0 1-2-2v-8.5a2 2 0 0 1 2-2Zm1.5 3h6.5m-6.5 3h6.5m-6.5 3h4.5" />',
    ],
    'Users Management' => [
        'icon' => '<path d="M8.75 11.25a2.5 2.5 0 1 0-2.5-2.5 2.5 2.5 0 0 0 2.5 2.5Zm6.5 0a2.5 2.5 0 1 0-2.5-2.5 2.5 2.5 0 0 0 2.5 2.5ZM5.75 18.5c0-1.91 1.96-3.25 4-3.25s4 1.34 4 3.25V20h-8Zm8 1.5v-1.15c0-1.11-.42-2.05-1.13-2.77.71-.37 1.54-.58 2.38-.58 1.92 0 3.75 1.1 3.75 2.85V20Z" />',
    ],
    'Practicum Contents' => [
        'icon' => '<path d="M6.75 6.25h10.5a1.5 1.5 0 0 1 1.5 1.5v8.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-8.5a1.5 1.5 0 0 1 1.5-1.5Zm2 3h6.5m-6.5 3h6.5m-6.5 3h4" />',
    ],
    'Reports' => [
        'icon' => '<path d="M5.75 18.25V5.75m4.25 12.5v-7.5m4.25 7.5v-10m4 10v-5.5M4.75 18.25h14.5" />',
    ],
    'Monitoring' => [
        'icon' => '<path d="M6 16.75 9.25 13.5l2.25 2.25L17.75 9.5M6.75 5.75h10.5a1.5 1.5 0 0 1 1.5 1.5v9.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-9.5a1.5 1.5 0 0 1 1.5-1.5Z" />',
    ],
];
$roleLabel = ucfirst($role);
$user = auth()->user();
@endphp

<button type="button" @click="toggleSidebar()" class="sidebar-mobile-toggle" aria-label="Toggle sidebar">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 7h16M4 12h16M4 17h16" />
    </svg>
</button>

<div
    x-show="mobileSidebarOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-950/10 xl:hidden"
    @click="closeMobileSidebar()"></div>

<aside
    class="app-sidebar fade-in"
    @mouseenter="openSidebarPeek()"
    @mouseleave="closeSidebarPeek()">
    <div class="sidebar-brand-bar">
        <div class="sidebar-brand-copy min-w-0">
            <h2 class="truncate font-display text-[1.55rem] tracking-[-0.04em] text-slate-950">Containearn Lab</h2>
        </div>

        <div class="sidebar-brand-compact" aria-hidden="true">
            C
        </div>

        <button type="button" @click="toggleSidebar()" class="sidebar-toggle xl:hidden" aria-label="Toggle sidebar">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>

    <div class="flex min-h-0 flex-1 flex-col px-4 py-5">
        <nav class="mt-6 space-y-2">
        @foreach($menus as $menu)
        @php
        $isActive = false;
        foreach ($menu['active'] as $activeRoute) {
        if (request()->routeIs($activeRoute)) {
        $isActive = true;
        break;
        }
        }
        $meta = $menuMeta[$menu['label']] ?? ['icon' => '<path d="M7 12h10M12 7l5 5-5 5" />'];
        @endphp

        <a href="{{ route($menu['route']) }}"
            class="{{ $isActive ? 'nav-item nav-item-active' : 'nav-item' }}">
            <span class="nav-icon">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {!! $meta['icon'] !!}
                </svg>
            </span>
            <span class="sidebar-nav-label block font-semibold">{{ $menu['label'] }}</span>
        </a>
        @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="sidebar-footer mt-auto pt-6">
            @csrf
            <button type="submit"
                class="nav-item nav-item-danger w-full text-left">
                <span class="nav-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9.75 7.75V6.5A1.75 1.75 0 0 1 11.5 4.75h5A1.75 1.75 0 0 1 18.25 6.5v11A1.75 1.75 0 0 1 16.5 19.25h-5A1.75 1.75 0 0 1 9.75 17.5v-1.25" />
                        <path d="M14.25 12H5.75m0 0 2.5-2.5m-2.5 2.5 2.5 2.5" />
                    </svg>
                </span>
                <span>
                    <span class="sidebar-nav-label block font-semibold">Logout</span>
                </span>
            </button>
        </form>
    </div>
</aside>
