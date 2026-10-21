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

        <div class="min-h-screen flex flex-col justify-start sm:justify-center items-center px-4 py-8 sm:py-12 relative z-10 overflow-y-auto">
            <div class="mb-4 sm:mb-6">
                <a href="/" aria-label="COMSOC Home">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-[#172033]/90 border border-[#334155] p-2.5 shadow-xl enter-logo backdrop-blur-sm flex items-center justify-center hover:border-[#8F3033] transition">
                        <x-application-logo class="w-full h-full" />
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md lg:max-w-lg card shadow-2xl border border-[#334155] bg-[#172033]/95 backdrop-blur-md enter-card p-6 sm:p-8 my-auto sm:my-0">
                {{ $slot }}
            </div>

            <x-public-branding-footer />
        </div>
    </body>
</html>
