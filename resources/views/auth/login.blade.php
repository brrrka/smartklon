<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SmartKlon Mart</title>
    <meta name="description" content="Masuk ke Sistem Manajemen Stok SmartKlon Mart">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="login-body">

<div class="login-container">
    {{-- Left Panel: Branding --}}
    <div class="login-brand-panel">
        <div class="login-brand-content">
            <div class="login-brand-icon">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none">
                    <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="white" stroke-width="1.5"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="white" stroke-width="1.5"/>
                    <circle cx="12" cy="14" r="2" fill="white"/>
                    <path d="M12 16v2" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <h1 class="login-brand-name">SMARTKLON MART</h1>
            <p class="login-brand-tagline">Sistem Smart Kelontong</p>
        </div>
        <div class="login-brand-decoration">
            <div class="deco-circle deco-circle--1"></div>
            <div class="deco-circle deco-circle--2"></div>
            <div class="deco-circle deco-circle--3"></div>
        </div>
    </div>

    {{-- Right Panel: Form --}}
    <div class="login-form-panel">
        <div class="login-form-wrapper">
            <div class="login-form-header">
                <div class="login-form-avatar">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h2 class="login-form-title">Selamat Datang</h2>
                <p class="login-form-subtitle">Masuk untuk melanjutkan ke dashboard</p>
            </div>

            @if($errors->any())
                <div class="login-alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="login-form">
                @csrf

                <div class="form-group" id="group-email">
                    <label class="form-label" for="email">Email</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input {{ $errors->has('email') ? 'form-input--error' : '' }}"
                            value="{{ old('email') }}"
                            placeholder="admin@smartklon.com"
                            autocomplete="email"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group" id="group-password">
                    <label class="form-label" for="password">Password</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="form-input-toggle" id="toggle-password" aria-label="Tampilkan password">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" id="eye-icon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group form-group--inline">
                    <label class="form-checkbox" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="form-checkbox-mark"></span>
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-login" id="btn-login">
                    <span class="btn-login-text">Masuk</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <polyline points="10 17 15 12 10 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>

            <p class="login-footer-text">
                SmartKlon Mart &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    const toggleBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
        });
    }

    // Submit loading state
    const loginForm = document.getElementById('login-form');
    const btnLogin = document.getElementById('btn-login');
    if (loginForm) {
        loginForm.addEventListener('submit', () => {
            btnLogin.disabled = true;
            btnLogin.querySelector('.btn-login-text').textContent = 'Memproses...';
        });
    }
</script>
</body>
</html>
