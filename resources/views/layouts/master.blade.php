<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Containearn Interactive' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Space Grotesk"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(900px 500px at 8% -10%, #bfdbfe 0, transparent 60%),
                radial-gradient(700px 420px at 100% 10%, #c7d2fe 0, transparent 55%),
                radial-gradient(600px 400px at 50% 110%, #dbeafe 0, transparent 60%),
                #f0f6ff;
        }

        .glass {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(8px);
        }

        .fade-in {
            animation: fade-in 0.6s ease-out both;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body>
    @yield('content')
    @vite('resources/js/app.js')
</body>

</html>