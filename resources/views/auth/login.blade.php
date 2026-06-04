<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - SIGEF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: #0a1628;
            background-image: url('/images/login-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        }

        .brand-title {
            color: #ffffff;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            margin-top: 1rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }

        .brand-subtitle {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 400;
            margin-top: 0.5rem;
            text-align: center;
            line-height: 1.4;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .login-form {
            width: 100%;
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 4px;
            color: #333;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input::placeholder {
            color: #6b7280;
        }

        .form-input:focus {
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(4, 28, 79, 0.3);
        }

        .form-input.error {
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5);
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #1a48ab;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .submit-btn:hover {
            background: #225fe3;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(34, 95, 227, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #fca5a5;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .error-message svg {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: #ef4444;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #041B4E;
            cursor: pointer;
        }

        .checkbox-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .footer-text {
            text-align: center;
            margin-top: 2.5rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
        }

        /* Loading state */
        .loading .submit-btn {
            pointer-events: none;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
        }

        .loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 0 1rem;
            }

            .brand-title {
                font-size: 2rem;
            }

            .brand-subtitle {
                font-size: 1.25rem;
            }

            .logo {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="/images/logo-policia.png" alt="Polícia Nacional de Angola" class="logo">
            <h1 class="brand-title">SIGEF</h1>
            <p class="brand-subtitle">Sistema Integrado de<br>Gestão Formativa</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            
            <div class="form-group">
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input @error('email') error @enderror" 
                    value="{{ old('email') }}"
                    placeholder="Usuário"
                    autocomplete="email"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input @error('password') error @enderror" 
                    placeholder="Senha"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" class="checkbox" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" class="checkbox-label">Manter-me ligado</label>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <span class="spinner"></span>
                <span>Entrar</span>
            </button>
        </form>

        <p class="footer-text">
            &copy; {{ date('Y') }} SIGEF - Polícia Nacional de Angola
        </p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const form = this;
            const btn = document.getElementById('submitBtn');
            
            // Add loading state
            form.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
