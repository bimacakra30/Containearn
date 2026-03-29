@if(session('success'))
<div class="glass rounded-2xl px-5 py-4 border-l-4 border-emerald-400 text-emerald-700 text-sm font-medium">
    {{ session('success') }}
</div>
@endif