<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Domain Outreach</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
           @import url('https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap');
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #13131a;
            --bg-tertiary: #1a1a24;
            --border-color: #252530;
            --accent-blue: #3b82f6;
        }
        body {
            background: var(--bg-primary);
             font-family: "Urbanist", sans-serif;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float { animation: float 3s ease-in-out infinite; }
        .glow {
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.3);
        }
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8 float">
            <div class="text-6xl mb-4">🌐</div>
            <h1 class="text-4xl font-bold gradient-text mb-2">
                Domain Outreach
            </h1>
            <p class="text-gray-400 text-lg">
                Professional outbound system
            </p>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl p-8 glow"
             style="background: var(--bg-secondary);
                    border: 1px solid var(--border-color);">

            {{-- Error Message --}}
            @if(session('error'))
            <div class="mb-6 p-4 rounded-lg"
                 style="background: rgba(239,68,68,0.1);
                        border-left: 4px solid #ef4444;">
                <p class="text-red-400 text-sm">
                    {{ session('error') }}
                </p>
            </div>
            @endif

            {{-- Success Message --}}
@if(session('success'))
<div class="mb-6 p-4 rounded-lg"
     style="background: rgba(16,185,129,0.1);
            border-left: 4px solid #10b981;">
    <p class="text-green-400 text-sm">
        {{ session('success') }}
    </p>
</div>
@endif

            {{-- Welcome Text --}}
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-white mb-2">
                    Welcome Back
                </h2>
                <p class="text-gray-400">
                    Sign in to manage your domain campaigns
                </p>
            </div>

            {{-- Google Login Button --}}
            <a href="{{ route('auth.google') }}"
               class="flex items-center justify-center gap-4 w-full py-4 px-6
                      rounded-xl font-semibold text-lg transition-all
                      hover:scale-105 active:scale-95"
               style="background: white; color: #1a1a1a;">

                {{-- Google Icon --}}
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>

                Continue with Google
            </a>

            {{-- Info --}}
            <p class="text-center text-gray-500 text-xs mt-6">
                We only access Gmail to send your outbound emails.
                Your data is never shared.
            </p>
        </div>

        {{-- Footer --}}
        <p class="text-center text-gray-600 text-sm mt-6">
            Domain Outreach System v1.0
        </p>
    </div>

</body>
</html>
