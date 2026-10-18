<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#F8FAFC] antialiased bg-[#0B1120] min-h-screen relative overflow-x-hidden selection:bg-[#560E0F] selection:text-white">
        <x-public-auth-bg />

        <div class="min-h-screen flex flex-col sm:justify-center items-center p-4 py-8 relative z-10">
            <div class="mb-4">
                <a href="/">
                    <div class="w-16 h-16 rounded-2xl bg-[#172033]/90 border border-[#334155] p-2 shadow-xl enter-logo backdrop-blur-sm flex items-center justify-center">
                        <x-application-logo class="w-full h-full" />
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md card shadow-2xl border border-[#334155] bg-[#172033]/95 backdrop-blur-md enter-card">
                {{ $slot }}
            </div>

            <x-public-branding-footer />
        </div>
    </body>
</html>
