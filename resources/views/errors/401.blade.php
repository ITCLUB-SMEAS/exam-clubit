<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
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

        <!-- Pixel Art SVG (Spy / Incognito Agent) -->
        <div class="mb-8 animate-float inline-block">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Topi (Ungu Gelap) -->
                <rect x="4" y="1" width="6" height="2" fill="#d600ff"/>
                <rect x="2" y="3" width="10" height="1" fill="#d600ff"/>

                <!-- Wajah (Bayangan/Hitam) -->
                <rect x="3" y="4" width="8" height="7" fill="#1a1b26"/>

                <!-- Kacamata Hitam (Agen) -->
                <rect x="3" y="5" width="3" height="2" fill="#e0e0e0"/>
                <rect x="8" y="5" width="3" height="2" fill="#e0e0e0"/>
                <rect x="6" y="6" width="2" height="1" fill="#e0e0e0"/>

                <!-- Kerah Jas (Ungu) -->
                <rect x="2" y="11" width="10" height="3" fill="#d600ff"/>
                <path d="M5 11 L7 13 L9 11" stroke="#1a1b26" stroke-width="1"/>

                <!-- Tanda Tanya (?) di Samping -->
                <rect x="11" y="1" width="2" height="2" fill="#00e5ff"/>
                <rect x="12" y="3" width="1" height="1" fill="#00e5ff"/>
                <rect x="11" y="4" width="1" height="1" fill="#00e5ff"/>
                <rect x="11" y="6" width="1" height="1" fill="#00e5ff"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-401 tracking-widest text-pixel-violet">
            401
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-yellow">
            <span>ID: UNKNOWN</span>
            <span>AUTH: FALSE</span>
            <span>TOKEN: NULL</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Siapa Anda? Identitas Tidak Dikenal.
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Sistem pertahanan pixel tidak mengenali wajah Anda. Anda harus Login (Insert Coin) terlebih dahulu untuk mengakses level rahasia ini.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Login (Primary) -->
            <a href="/login" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-violet translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔑</span> Login / Masuk
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
        SYS.AUTH // 0x000F1
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-violet animate-pulse-fast">
        PLEASE LOGIN TO CONTINUE
    </div>

</body>
</html>
