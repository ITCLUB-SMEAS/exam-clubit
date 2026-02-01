<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Too Many Requests</title>
    <link href="{{ asset('build/assets/errors.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #1a1b26;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="error-body h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection-pixel relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Stopwatch / Timer) -->
        <div class="mb-8 animate-shake inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Frame Luar Stopwatch (Orange) -->
                <path d="M5 0H9V2H5V0Z" fill="#e0e0e0"/> <!-- Tombol Atas -->
                <rect x="2" y="3" width="10" height="10" rx="2" fill="#ff7700"/> <!-- Body Kotak -->

                <!-- Layar Dalam (Hitam) -->
                <rect x="3" y="4" width="8" height="8" fill="#1a1b26"/>

                <!-- Jarum Jam (Putih) -->
                <rect x="6" y="6" width="2" height="2" fill="#e0e0e0"/> <!-- Pusat -->
                <rect x="7" y="5" width="1" height="1" fill="#e0e0e0"/>
                <rect x="8" y="4" width="1" height="1" fill="#e0e0e0"/>

                <!-- Indikator "Panas" / Keringat (Kuning) -->
                <rect x="1" y="5" width="1" height="2" fill="#ffff00"/>
                <rect x="12" y="5" width="1" height="2" fill="#ffff00"/>
                <rect x="0" y="4" width="1" height="1" fill="#ffff00"/>
                <rect x="13" y="4" width="1" height="1" fill="#ffff00"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-429 tracking-widest text-pixel-orange">
            429
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-pure-yellow">
            <span>CPU: OVERLOAD</span>
            <span>SPEED: LIMIT</span>
            <span>WAIT: 60s</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Whoa! Terlalu Cepat, Kawan.
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Server kami sedang kepanasan karena request Anda yang secepat kilat (Spamming). Silakan "Cooldown" sejenak sebelum mencoba lagi.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Refresh (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-orange translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔄</span> Coba Lagi (Reload)
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-mint translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-mint border-2 border-pixel-mint px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#00ff9d] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Ke Halaman Utama
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.HEAT // 99%
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-orange animate-pulse-slow">
        COOLDOWN INITIATED...
    </div>

</body>
</html>
