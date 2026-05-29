<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>SEDYCO | Plataforma Inteligente</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        *{
            font-family: 'Inter', sans-serif;
        }
        html{
            scroll-behavior: smooth;
        }

        body{
            overflow-x: hidden;
            overflow-y: auto;
            background: #f8fafc;
            min-height: 100vh;
        }

        .bg-grid{
            background-image:
                linear-gradient(rgba(15, 157, 138, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 157, 138, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .glass{
            background: rgba(255,255,255,0.55);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.6);
        }

        .floating{
            animation: floating 6s ease-in-out infinite;
        }

        .floating2{
            animation: floating2 8s ease-in-out infinite;
        }

        .floating3{
            animation: floating3 10s ease-in-out infinite;
        }

        @keyframes floating{
            0%{ transform: translateY(0px);}
            50%{ transform: translateY(-15px);}
            100%{ transform: translateY(0px);}
        }

        @keyframes floating2{
            0%{ transform: translateY(0px);}
            50%{ transform: translateY(12px);}
            100%{ transform: translateY(0px);}
        }

        @keyframes floating3{
            0%{ transform: translateY(0px);}
            50%{ transform: translateY(-10px);}
            100%{ transform: translateY(0px);}
        }

        .hero-glow{
            position: absolute;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(15,157,138,0.18) 0%, rgba(15,157,138,0) 70%);
            filter: blur(40px);
            z-index: 0;
        }

        .btn-enter{
            transition: all .35s ease;
        }

        .btn-enter:hover{
            transform: translateY(-3px);
            box-shadow:
                0 20px 40px rgba(15,157,138,.25),
                0 0 30px rgba(15,157,138,.15);
        }

        .logo-shadow{
            filter: drop-shadow(0 20px 40px rgba(15,157,138,.25));
        }

        .card-hover{
            transition: all .35s ease;
        }

        .card-hover:hover{
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="bg-grid">

<!-- Ambient Glow -->
<div class="hero-glow top-[-200px] right-[-100px]"></div>
<div class="hero-glow bottom-[-250px] left-[-100px]"></div>

<section class="relative w-full min-h-screen overflow-x-hidden">

    <!-- NAV -->
    <nav class="relative z-20 flex items-center justify-between px-10 lg:px-20 py-8">

        <div class="flex items-center gap-4">
            <img
                src="img/netinnfodeRound.png"
                class="w-14 h-14 object-contain"
            >

            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    SEDYCO
                </h2>

                <p class="text-sm text-slate-500">
                    Human Platform
                </p>
            </div>
        </div>

        <div class="hidden md:flex items-center gap-10 text-sm text-slate-600">
            <a href="#" class="hover:text-emerald-600 transition">Inicio</a>
            <a href="/dashboard" class="hover:text-emerald-600 transition">Plataforma</a>
            <!--
            <a href="#" class="hover:text-emerald-600 transition">Inicio</a>
            <a href="#" class="hover:text-emerald-600 transition">Plataforma</a>
            <a href="#" class="hover:text-emerald-600 transition">Seguridad</a>
            <a href="#" class="hover:text-emerald-600 transition">Contacto</a>
            -->
        </div>

    </nav>

    <!-- HERO -->
    <div class="relative z-10 grid lg:grid-cols-2 min-h-[85vh]">

        <!-- LEFT -->
        <div class="flex items-center px-10 lg:px-20 py-20">

            <div class="max-w-2xl">

                <!-- Badge -->
                <div class="glass inline-flex items-center gap-3 px-5 py-3 rounded-full mb-8 shadow-sm">

                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>

                    <span class="text-sm font-medium text-slate-700">
                            Plataforma Inteligente de Desarrollo Organizacional
                        </span>
                </div>

                <!-- Title -->
                <h1 class="text-5xl lg:text-7xl font-black leading-tight text-slate-900">

                    Transformando talento en

                    <span class="text-emerald-600 relative">

                            decisiones inteligentes.

                            <div class="absolute bottom-1 left-0 w-full h-3 bg-emerald-100 -z-10 rounded-full"></div>

                        </span>

                </h1>

                <!-- Subtitle -->
                <p class="mt-8 text-xl leading-relaxed text-slate-600 max-w-xl">

                    Centraliza evaluaciones psicométricas, desempeño,
                    NOM-035 y desarrollo organizacional en una sola
                    plataforma moderna, segura y escalable.

                </p>

                <!-- Buttons -->
                <div class="mt-12 flex flex-wrap gap-5">

                    <a href="/dashboard"
                       class="btn-enter bg-emerald-600 hover:bg-emerald-700 text-white px-9 py-5 rounded-2xl font-semibold text-lg shadow-lg">

                        Acceder a la Plataforma

                    </a>

                    <button class="glass px-8 py-5 rounded-2xl font-semibold text-slate-700 card-hover">

                        Ver funcionalidades

                    </button>

                </div>

                <!-- Stats -->
                <div class="mt-16 grid grid-cols-3 gap-6 max-w-xl">

                    <div class="glass rounded-2xl p-5 card-hover">
                        <h3 class="text-3xl font-black text-slate-900">15+</h3>
                        <p class="text-sm text-slate-500 mt-1">Módulos</p>
                    </div>

                    <div class="glass rounded-2xl p-5 card-hover">
                        <h3 class="text-3xl font-black text-slate-900">100%</h3>
                        <p class="text-sm text-slate-500 mt-1">Cloud</p>
                    </div>

                    <div class="glass rounded-2xl p-5 card-hover">
                        <h3 class="text-3xl font-black text-slate-900">24/7</h3>
                        <p class="text-sm text-slate-500 mt-1">Disponibilidad</p>
                    </div>

                </div>

            </div>

        </div>

        <!-- RIGHT -->
        <div class="relative hidden lg:flex items-center justify-center">

            <!-- Floating Cards -->

            <div class="absolute top-28 left-16 glass px-6 py-4 rounded-2xl shadow-xl floating">
                <div class="text-sm text-slate-500">Módulo</div>
                <div class="font-bold text-slate-900 mt-1">NOM-035</div>
            </div>

            <div class="absolute top-52 right-16 glass px-6 py-4 rounded-2xl shadow-xl floating2">
                <div class="text-sm text-slate-500">Analytics</div>
                <div class="font-bold text-slate-900 mt-1">Indicadores</div>
            </div>

            <div class="absolute bottom-40 left-10 glass px-6 py-4 rounded-2xl shadow-xl floating3">
                <div class="text-sm text-slate-500">Evaluaciones</div>
                <div class="font-bold text-slate-900 mt-1">Psicometría</div>
            </div>

            <div class="absolute bottom-24 right-20 glass px-6 py-4 rounded-2xl shadow-xl floating">
                <div class="text-sm text-slate-500">IA</div>
                <div class="font-bold text-slate-900 mt-1">Talento Inteligente</div>
            </div>

            <!-- Main Logo Container -->
            <div class="relative flex items-center justify-center">

                <!-- Glow -->
                <div class="absolute w-[500px] h-[500px] bg-emerald-400/20 rounded-full blur-3xl"></div>

                <!-- Circle -->
                <div class="relative glass w-[420px] h-[420px] rounded-full flex items-center justify-center shadow-2xl border border-white/50">

                    <div class="absolute inset-6 rounded-full border border-emerald-100"></div>

                    <img
                        src="img/optraLogo.png"
                        class="w-52 logo-shadow floating"
                    >

                </div>

            </div>

        </div>

    </div>

    <!-- Bottom Blur -->
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-white to-transparent"></div>

</section>

</body>
</html>
