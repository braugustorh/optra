<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Evaluaciones') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>

    @livewireStyles
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-100">

<div class="min-h-screen">
    {{-- Logo / Marca --}}
    <div class="pt-6 pb-2 text-center">
        <h1 class="text-2xl font-bold text-indigo-600 tracking-tight">SEDYCO</h1>
    </div>

    {{-- Slot sin restricción de ancho: cada vista maneja su propio max-w --}}
    <div class="w-full pb-12">
        {{ $slot }}
    </div>
</div>

@livewireScripts
</body>
</html>

