<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RickTech | Kioskos Inteligentes de Impresión</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <style>
        :root {
            --bg-color: #0b0f19;
            --text-color: #f8fafc;
            --accent-primary: #8b5cf6;
            --accent-secondary: #3b82f6;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Dynamic Background */
        .background-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 15% 50%, rgba(139, 92, 246, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(59, 130, 246, 0.15), transparent 25%);
        }

        .blob {
            position: absolute;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite alternate;
        }

        .blob-1 {
            background: var(--accent-primary);
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            border-radius: 50%;
        }

        .blob-2 {
            background: var(--accent-secondary);
            width: 500px;
            height: 500px;
            bottom: -200px;
            right: -100px;
            border-radius: 50%;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(100px, 50px) scale(1.1); }
            100% { transform: translate(-50px, 100px) scale(0.9); }
        }

        /* Navbar */
        nav {
            padding: 2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(to right, #a78bfa, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .login-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 600;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 2rem;
            margin-top: -5vh;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(3rem, 6vw, 5.5rem);
            line-height: 1.1;
            font-weight: 900;
            margin-bottom: 1.5rem;
            letter-spacing: -0.03em;
        }

        h1 span {
            background: linear-gradient(135deg, #a855f7 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            color: #94a3b8;
            max-width: 600px;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        /* Upload Card (Glassmorphism) */
        .upload-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 100%;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .upload-card:hover {
            transform: translateY(-10px);
        }

        .upload-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .upload-icon svg {
            width: 40px;
            height: 40px;
            color: #a78bfa;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #a855f7 0%, #3b82f6 100%);
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -10px rgba(168, 85, 247, 0.6);
        }

        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px -10px rgba(59, 130, 246, 0.8);
        }

        .features {
            display: flex;
            gap: 2rem;
            margin-top: 4rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .feature-item svg {
            width: 20px;
            height: 20px;
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .upload-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <nav>
        <a href="#" class="logo">RickTech.</a>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/admin') }}" class="login-btn">Panel Admin</a>
            @else
                <a href="{{ route('login') }}" class="login-btn">Acceder</a>
            @endauth
        @endif
    </nav>

    <main class="hero">
        <h1>Imprime sin <span>filas</span> ni apps.</h1>
        <p class="subtitle">Sube tu PDF, configúralo desde tu celular y recoge tus hojas impresas al instante en nuestro kiosko con ayuda de Inteligencia Artificial.</p>

        <div class="upload-card">
            <div class="upload-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
            </div>
            <!-- Redireccionamos a la vista donde está el componente KioskoUpload (Livewire) -->
            <a href="{{ url('/subir') }}" class="cta-button">Subir mi PDF ahora</a>
            <p style="margin-top: 1rem; color: #94a3b8; font-size: 0.9rem;">Totalmente seguro y encriptado.</p>
        </div>

        <div class="features">
            <div class="feature-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                IA que atiende 24/7
            </div>
            <div class="feature-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Paga por WhatsApp
            </div>
            <div class="feature-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Sin instalar nada
            </div>
        </div>
    </main>

</body>
</html>
