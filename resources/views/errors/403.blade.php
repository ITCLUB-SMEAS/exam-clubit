<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
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

        <!-- Pixel Art SVG (Gembok Terkunci) -->
        <div class="mb-8 animate-float inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Badan Gembok (Kuning/Emas) -->
                <rect x="2" y="6" width="10" height="8" fill="#ffcc00"/>
                <!-- Lubang Kunci (Hitam) -->
                <rect x="6" y="9" width="2" height="3" fill="#1a1b26"/>
                <!-- Shackle/Leher Gembok (Abu-abu/Putih) Kiri -->
                <rect x="3" y="3" width="2" height="3" fill="#e0e0e0"/>
                <!-- Shackle Kanan -->
                <rect x="9" y="3" width="2" height="3" fill="#e0e0e0"/>
                <!-- Shackle Atas -->
                <rect x="3" y="1" width="8" height="2" fill="#e0e0e0"/>
                <!-- Shadow Detail -->
                <rect x="2" y="13" width="10" height="1" fill="#b38f00"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-403 tracking-widest text-pixel-red-warning">
            403
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-yellow">
            <span>ACCESS: DENIED</span>
            <span>SEC: HIGH</span>
            <span>KEY: MISSING</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Akses Dilarang! Area Terbatas.
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Maaf, Anda tidak memiliki 'Golden Key' untuk membuka level ini. Penjaga pixel sedang berpatroli, harap segera tinggalkan area ini.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Home (Primary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-red-warning translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🛡️</span> Ke Zona Aman
                </button>
            </a>

            <!-- Button Back (Secondary) -->
            <button onclick="history.back()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#00e5ff] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Mundur
                </button>
            </button>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.SEC // 0x000F3
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-red-warning animate-pulse-fast">
        UNAUTHORIZED ACCESS DETECTED
    </div>

</body>
</html>
