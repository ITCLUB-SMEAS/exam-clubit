<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Session Expired</title>
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

        <!-- Pixel Art SVG (Hourglass - Time Expired) -->
        <div class="mb-8 animate-float inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Top Frame -->
                <rect x="2" y="0" width="10" height="2" fill="#e0e0e0"/>
                <!-- Bottom Frame -->
                <rect x="2" y="12" width="10" height="2" fill="#e0e0e0"/>
                <!-- Glass Body -->
                <rect x="3" y="2" width="8" height="10" fill="#334155"/>
                <!-- Sand (Expired - at bottom) -->
                <rect x="4" y="8" width="6" height="3" fill="#ffcc00"/>
                <!-- Center Neck -->
                <rect x="5" y="6" width="4" height="2" fill="#1a1b26"/>
                <!-- Empty Top -->
                <rect x="4" y="3" width="6" height="3" fill="#1a1b26"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-419 tracking-widest text-pixel-red-warning">
            419
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-yellow">
            <span>SESSION: EXPIRED</span>
            <span>TOKEN: INVALID</span>
            <span>TIME: 00:00</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Sesi Habis! Waktu Anda Sudah Berakhir.
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Token keamanan Anda telah kadaluarsa. Ini biasanya terjadi jika Anda terlalu lama tidak aktif. Silakan refresh halaman dan coba lagi.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Refresh (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-red-warning translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔄</span> Refresh Page
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#00e5ff] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Kembali
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.TOKEN // CSRF_EXPIRED
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-red-warning animate-pulse-fast">
        PLEASE REFRESH TO CONTINUE
    </div>

</body>
</html>
