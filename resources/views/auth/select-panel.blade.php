<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Selecionar Painel - SIGEF</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="/favicon.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: #041c4f;
            color: #1e293b;
            position: relative;
        }

        /* Background with blur and opacity */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('/images/login-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.70;
            filter: blur(3px);
            z-index: -2;
        }

        /* Dark overlay */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(4, 28, 79, 0.6) 0%, rgba(4, 28, 79, 0.8) 100%);
            z-index: -1;
        }

        /* Header */
        .header {
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-brand img {
            height: 40px;
        }

        .header-brand-text {
            display: flex;
            flex-direction: column;
        }

        .header-brand-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #041c4f;
            letter-spacing: 0.02em;
        }

        .header-brand-desc {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 400;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-size: 0.875rem;
            color: #374151;
            font-weight: 500;
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-logout:hover {
            background: #b91c1c;
        }

        .btn-logout svg {
            width: 16px;
            height: 16px;
        }

        /* Main */
        .main {
            min-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Welcome Section with Logo */
        .welcome-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .welcome-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.25rem;
        }

        .welcome-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Panels Container */
        .panels-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.25rem;
            max-width: 1100px;
            width: 100%;
        }

        .panel-card {
            width: 250px;
        }

        @media (max-width: 640px) {
            .panel-card {
                width: 100%;
            }
        }

        /* Panel Card */
        .panel-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e5e7eb;
        }

        .panel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .panel-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .panel-icon svg {
            width: 22px;
            height: 22px;
            color: #ffffff;
        }

        .panel-card.admin .panel-icon {
            background: #7c3aed;
        }

        .panel-card.escola .panel-icon {
            background: #2563eb;
        }

        .panel-card.dpq .panel-icon {
            background: #059669;
        }

        .panel-card.comando .panel-icon {
            background: #d97706;
        }

        .panel-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.25rem;
        }

        .panel-card.admin .panel-label {
            color: #7c3aed;
        }

        .panel-card.escola .panel-label {
            color: #2563eb;
        }

        .panel-card.dpq .panel-label {
            color: #059669;
        }

        .panel-card.comando .panel-label {
            color: #d97706;
        }

        .panel-name {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .panel-desc {
            font-size: 0.8rem;
            color: #6b7280;
            line-height: 1.4;
            flex: 1;
        }

        .panel-action {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f3f4f6;
            font-size: 0.8rem;
            font-weight: 500;
            color: #041c4f;
        }

        .panel-action svg {
            width: 14px;
            height: 14px;
            transition: transform 0.15s;
        }

        .panel-card:hover .panel-action svg {
            transform: translateX(3px);
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.2);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .footer-logo img {
            height: 32px;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 0.75rem;
                padding: 1rem;
            }

            .welcome-logo {
                width: 80px;
                height: 80px;
            }

            .welcome-title {
                font-size: 1.25rem;
            }

            .main {
                padding: 1.5rem 1rem;
            }

            .footer {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-brand">
            <img src="/images/logo-sigef.png" alt="SIGEF" onerror="this.style.display='none'">
            <div class="header-brand-text">
                <span class="header-brand-name">SIGEF</span>
                <span class="header-brand-desc">Sistema Integrado de Gestão Formativa</span>
            </div>
        </div>

        <div class="header-user">
            <span class="user-name">{{ $user->name ?? 'Utilizador' }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    Sair
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="welcome-section">
            <div class="welcome-logo">
                <img src="/images/logo-policia.png" alt="Polícia Nacional de Angola">
            </div>
            <h1 class="welcome-title">Bem-vindo, {{ $user->name ?? 'Utilizador' }}</h1>
            <p class="welcome-subtitle">Selecione o painel que deseja aceder</p>
        </div>

        @php
        $panelData = [
        'admin' => [
        'label' => 'Admin',
        'name' => 'Administração',
        'desc' => 'Gestão de utilizadores, permissões e configurações do sistema.',
        ],
        'escola' => [
        'label' => 'Escola',
        'name' => 'Gestão Escolar',
        'desc' => 'Gestão de formações, cursos, turmas e avaliações.',
        ],
        'dpq' => [
        'label' => 'DPQ',
        'name' => 'Pessoal e Quadros',
        'desc' => 'Gestão de candidaturas e processos de admissão.',
        ],
        'comando' => [
        'label' => 'Comando',
        'name' => 'Comandos',
        'desc' => 'Gestão de comandos provinciais e unidades.',
        ],
        ];
        @endphp

        <div class="panels-container">
            @foreach($panels as $panelId => $panel)
            <a href="{{ $panel['url'] }}" class="panel-card {{ $panelId }}">
                <div class="panel-icon">
                    @if($panelId === 'admin')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    @elseif($panelId === 'escola')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                    @elseif($panelId === 'dpq')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    @elseif($panelId === 'comando')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    @endif
                </div>

                <span class="panel-label">{{ $panelData[$panelId]['label'] ?? strtoupper($panelId) }}</span>
                <h2 class="panel-name">{{ $panelData[$panelId]['name'] ?? $panel['name'] }}</h2>
                <p class="panel-desc">{{ $panelData[$panelId]['desc'] ?? '' }}</p>

                <div class="panel-action">
                    Aceder
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <span class="footer-text">&copy; {{ date('Y') }} DTTI-PNA. Polícia Nacional de Angola.</span>
        <div class="footer-logo">
            <img src="/images/logo-policia.png" alt="PNA" onerror="this.style.display='none'">
        </div>
    </footer>
</body>

</html>