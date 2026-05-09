<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Containearn Interactive' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    x-data="shellLayout()"
    x-init="init()"
    @keydown.window.escape="closeMobileSidebar()"
    :data-sidebar-mobile="mobileSidebarOpen ? 'open' : 'closed'"
    class="font-sans antialiased">
    <div class="page-loader" aria-hidden="true">
        <div class="page-loader-bar"></div>
    </div>
    @yield('content')
    @stack('scripts')
</body>

</html>
