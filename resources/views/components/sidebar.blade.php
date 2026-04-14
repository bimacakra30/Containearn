@php
$role = auth()->user()->role;
$menus = config("sidebar.$role") ?? [];
@endphp

<aside class="glass rounded-2xl p-5 sticky top-6 h-fit fade-in">
    <div>
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Containearn</p>
        <p class="font-display text-lg text-slate-900">INTERACTIVE</p>
    </div>

    <nav class="mt-6 space-y-2 text-sm">

        @foreach($menus as $menu)
        @php
        $isActive = false;
        foreach ($menu['active'] as $activeRoute) {
        if (request()->routeIs($activeRoute)) {
        $isActive = true;
        break;
        }
        }
        @endphp

        <a href="{{ route($menu['route']) }}"
            class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ $isActive
            ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold'
            : 'border-slate-200 text-slate-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700' }}">
            {{ $menu['label'] }}
        </a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-slate-700 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 transition text-sm">
                Logout
            </button>
        </form>

    </nav>
</aside>
