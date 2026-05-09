<div x-show="{{ $show }}" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">

    <div class="glass w-full max-w-sm mx-4 p-6 shadow-xl">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
                ⚠
            </div>
            <h3 class="font-display text-lg text-slate-900">{{ $title }}</h3>
        </div>

        <p class="text-sm text-slate-500 mb-5">{{ $message }}</p>

        <div class="flex gap-3 justify-end">
            <button @click="{{ $show }} = false"
                class="btn-secondary px-4 py-2">
                Cancel
            </button>

            <button @click="{{ $show }} = false; {{ $action }}"
                class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">
                Yes, Delete
            </button>
        </div>
    </div>
</div>
