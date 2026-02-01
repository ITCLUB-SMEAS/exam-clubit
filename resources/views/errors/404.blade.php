<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Game Over</title>
    <link href="{{ asset('build/assets/errors.css') }}" rel="stylesheet">
    <!-- Import Font 'Press Start 2P' untuk nuansa 8-bit yang autentik -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        /* Additional page-specific styles */
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

        <!-- Pixel Art SVG (Hantu Pacman yang Sedih) -->
        <div class="mb-8 animate-float inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Badan Biru -->
                <path fill-rule="evenodd" clip-rule="evenodd" d="M1 6V13H2V12H3V13H4V12H5V13H6V12H8V13H9V12H10V13H11V12H12V13H13V6C13 2.68629 10.3137 0 7 0C3.68629 0 1 2.68629 1 6Z" fill="#00e5ff"/>
                <!-- Mata Kiri (Putih) -->
                <rect x="3" y="4" width="3" height="3" fill="white"/>
                <!-- Mata Kanan (Putih) -->
                <rect x="8" y="4" width="3" height="3" fill="white"/>
                <!-- Pupil Kiri (Hitam) -->
                <rect x="5" y="5" width="1" height="1" fill="#1a1b26"/>
                <!-- Pupil Kanan (Hitam) -->
                <rect x="10" y="5" width="1" height="1" fill="#1a1b26"/>
                <!-- Mulut Sedih -->
                <rect x="5" y="9" width="4" height="1" fill="#1a1b26"/>
                <rect x="4" y="10" width="1" height="1" fill="#1a1b26"/>
                <rect x="9" y="10" width="1" height="1" fill="#1a1b26"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-404 tracking-widest">
            404
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-yellow">
            <span>SCORE: 0</span>
            <span>WORLD: 4-4</span>
            <span>LIVES: 0</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-pixel-primary mb-6 leading-relaxed uppercase">
            Sistem Gagal! Level Tidak Ditemukan.
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Karakter Anda tersesat di dalam glitch. Halaman yang Anda cari mungkin telah dimakan oleh monster pixel atau dipindahkan ke castle lain.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Home (Primary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">▶</span> Restart Game
                </button>
            </a>

            <!-- Button Back (Secondary) -->
            <button onclick="history.back()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#00e5ff] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Kembali
                </button>
            </button>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.ERR // 0x000F4
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-gray-600 animate-pulse-fast">
        INSERT COIN TO CONTINUE
    </div>

</body>
</html>
