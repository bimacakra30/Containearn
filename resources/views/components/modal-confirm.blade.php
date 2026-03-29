<div x-show="{{ $show }}" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">

    <div class="glass rounded-2xl p-6 w-full max-w-sm mx-4 shadow-xl">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                ✔
            </div>
            <h3 class="font-display text-lg text-slate-900">{{ $title }}</h3>
        </div>

        <p class="text-sm text-slate-500 mb-5">{{ $message }}</p>

        <div class="flex gap-3 justify-end">
            <button @click="{{ $show }} = false"
                class="rounded-xl border px-4 py-2 text-sm">
                Cancel
            </button>

            <button @click="{{ $show }} = false; {{ $action }}"
                class="rounded-xl bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">
                Yes
            </button>
        </div>
    </div>
</div>