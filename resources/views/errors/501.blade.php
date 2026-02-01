<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>501 - Not Implemented</title>
    <link href="{{ asset('build/assets/errors.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #1a1b26;
            overflow-x: hidden;
        }
        /* Blueprint Grid background */
        .bg-grid-blueprint {
            background-image:
                linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        /* Pattern garis peringatan (Construction Tape) */
        .warning-tape {
            background: repeating-linear-gradient(
                45deg,
                #fbbf24,
                #fbbf24 10px,
                #000000 10px,
                #000000 20px
            );
        }
        .glitch-text-501 {
            text-shadow: 3px 3px 0px #fbbf24, -3px -3px 0px #ffffff;
        }
    </style>
</head>
<body class="error-body h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid-blueprint selection-pixel relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Garis Peringatan Atas & Bawah -->
    <div class="absolute top-0 w-full h-4 warning-tape opacity-80 z-20"></div>
    <div class="absolute bottom-0 w-full h-4 warning-tape opacity-80 z-20"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Traffic Cone / Construction) -->
        <div class="mb-8 animate-bounce-pixel inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Base Cone (Hitam/Gelap) -->
                <rect x="2" y="11" width="10" height="2" fill="#1a1b26"/>
                <rect x="1" y="12" width="12" height="1" fill="#fbbf24"/>

                <!-- Badan Cone (Orange/Kuning) -->
                <rect x="3" y="9" width="8" height="2" fill="#fbbf24"/>
                <rect x="4" y="7" width="6" height="2" fill="#fbbf24"/>
                <rect x="5" y="3" width="4" height="2" fill="#fbbf24"/>
                <rect x="6" y="1" width="2" height="2" fill="#fbbf24"/>

                <!-- Stripe Putih (Reflective) -->
                <rect x="5" y="5" width="4" height="2" fill="#ffffff"/>

                <!-- Shadow/Detail -->
                <rect x="4" y="9" width="1" height="2" fill="#b45309"/>
                <rect x="5" y="7" width="1" height="2" fill="#b45309"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-501 tracking-widest text-pixel-construction">
            501
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-blueprint">
            <span>STATUS: BUILDING</span>
            <span>PROGRESS: 0%</span>
            <span>WORKERS: BUSY</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Fitur Belum Siap!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Maaf, arsitek pixel kami belum selesai membangun fitur ini. Jalur ini masih buntu dan belum diimplementasikan di server.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Back (Primary) -->
            <button onclick="history.back()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-construction translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🚧</span> Kembali
                </button>
            </button>

            <!-- Button Contact (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-blueprint translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-blueprint border-2 border-pixel-blueprint px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#3b82f6] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Ke Home
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.BUILD // V.ALPHA
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-construction animate-pulse-slow">
        UNDER CONSTRUCTION...
    </div>

</body>
</html>
