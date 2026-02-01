<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <link href="{{ asset('build/assets/errors.css') }}" rel="stylesheet">
    <!-- Import Font 'Press Start 2P' untuk nuansa 8-bit yang autentik -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #1a1b26;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="error-body h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid-red selection-pixel relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Burning Server) -->
        <div class="mb-8 inline-block relative animate-shake-hard">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Asap (Abu-abu) -->
                <rect x="3" y="0" width="2" height="2" fill="#4b5563" class="smoke-particle" style="animation-delay: 0.2s"/>
                <rect x="8" y="1" width="2" height="1" fill="#4b5563" class="smoke-particle" style="animation-delay: 0.5s"/>

                <!-- Api Utama (Orange/Kuning) -->
                <rect x="2" y="3" width="10" height="11" fill="#f97316"/> <!-- Dasar Api Orange -->
                <rect x="3" y="4" width="8" height="9" fill="#facc15"/> <!-- Inti Api Kuning -->
                <rect x="4" y="6" width="6" height="6" fill="#ffffff"/> <!-- Inti Panas Putih -->

                <!-- Server Hangus (Hitam/Gelap di tengah api) -->
                <rect x="3" y="5" width="8" height="8" fill="#1f2937"/>

                <!-- Retakan/Kerusakan Server (Merah) -->
                <rect x="4" y="6" width="2" height="1" fill="#dc2626"/>
                <rect x="7" y="9" width="3" height="1" fill="#dc2626"/>
                <rect x="5" y="11" width="1" height="1" fill="#dc2626"/>

                <!-- Lidah Api Liar (Animasi via CSS flicker) -->
                <rect x="1" y="4" width="1" height="2" fill="#f97316" class="animate-flicker"/>
                <rect x="12" y="5" width="1" height="3" fill="#f97316" class="animate-flicker"/>
                <rect x="5" y="2" width="1" height="2" fill="#facc15" class="animate-flicker"/>
                <rect x="9" y="3" width="1" height="2" fill="#facc15" class="animate-flicker"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-500 tracking-widest text-pixel-danger">
            500
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-danger">
            <span>SYS: CRITICAL</span>
            <span>ERR: FATAL</span>
            <span>REBOOT: FAIL</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Server Meledak!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Terjadi kesalahan internal yang fatal. Tim teknisi pixel kami sedang menyiram air ke server room. Silakan kembali lagi nanti.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Retry (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-danger translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔥</span> Coba Refresh
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-yellow translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-yellow border-2 border-pixel-yellow px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#facc15] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Evakuasi (Home)
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.ERR // INTERNAL_SERVER_ERROR
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-danger animate-flicker">
        SYSTEM FAILURE DETECTED
    </div>

</body>
</html>
