<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password — Computing Society</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen font-sans flex items-center justify-center p-4">

<div class="w-full max-w-sm space-y-6">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#12141c] border border-slate-800 mx-auto mb-4">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society" class="w-10 h-10 object-contain">
        </div>
        <h1 class="text-xl font-bold text-white font-brand-display">Set New Password</h1>
        <p class="text-sm text-slate-400 mt-1">Computing Society</p>
    </div>

    @if(session('force_change') || auth()->user()->must_change_password)
    <div class="rounded-xl border border-amber-500/30 bg-amber-950/20 px-4 py-3">
        <p class="text-amber-300 text-sm font-semibold">🔐 You must set a new password before continuing.</p>
        <p class="text-amber-400/70 text-xs mt-1">Your account was created with a temporary password.</p>
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl border border-red-500/30 bg-red-950/20 px-4 py-3 space-y-1">
        @foreach($errors->all() as $error)
        <p class="text-red-400 text-sm">{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('student.password.update') }}" class="card space-y-4">
        @csrf

        <div>
            <label for="current_password" class="label text-xs">Current Password</label>
            <input type="password" id="current_password" name="current_password" required
                   class="input w-full bg-slate-950"
                   placeholder="Your temporary password">
        </div>

        <div>
            <label for="password" class="label text-xs">New Password</label>
            <input type="password" id="password" name="password" required
                   class="input w-full bg-slate-950"
                   placeholder="Min. 8 chars, upper, lower, number">
            <p class="text-xs text-slate-500 mt-1">Minimum 8 characters with uppercase, lowercase, and number.</p>
        </div>

        <div>
            <label for="password_confirmation" class="label text-xs">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   class="input w-full bg-slate-950"
                   placeholder="Repeat new password">
        </div>

        <button type="submit" class="btn-primary w-full py-3">
            Set New Password
        </button>
    </form>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-600 hover:text-red-400 transition-colors">
                Sign out instead
            </button>
        </form>
    </div>
</div>

</body>
</html>
