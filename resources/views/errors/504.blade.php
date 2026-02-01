<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>504 - Gateway Timeout</title>
    <link href="{{ asset('build/assets/errors.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #1a1b26;
            overflow-x: hidden;
        }
        .glitch-text-504 {
            text-shadow: 3px 3px 0px #d946ef, -3px -3px 0px #22d3ee;
        }
    </style>
</head>
<body class="error-body h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection-pixel relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Hourglass / Jam Pasir) -->
        <div class="mb-8 inline-block relative">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Bingkai Atas & Bawah (Kayu) -->
                <rect x="2" y="1" width="10" height="1" fill="#78350f"/>
                <rect x="2" y="12" width="10" height="1" fill="#78350f"/>

                <!-- Tiang Penyangga Kiri & Kanan -->
                <rect x="2" y="2" width="1" height="10" fill="#78350f"/>
                <rect x="11" y="2" width="1" height="10" fill="#78350f"/>

                <!-- Kaca Jam Pasir (Biru Muda/Transparan) -->
                <!-- Bagian Atas -->
                <rect x="3" y="2" width="8" height="4" fill="#1a1b26"/> <!-- Kosong (pasir habis) -->
                <rect x="3" y="2" width="1" height="4" fill="#a5f3fc" opacity="0.5"/> <!-- Kaca kiri -->
                <rect x="10" y="2" width="1" height="4" fill="#a5f3fc" opacity="0.5"/> <!-- Kaca kanan -->
                <rect x="4" y="5" width="6" height="1" fill="#a5f3fc" opacity="0.3"/>

                <!-- Leher -->
                <rect x="6" y="6" width="2" height="2" fill="#a5f3fc" opacity="0.5"/>

                <!-- Bagian Bawah (Penuh Pasir) -->
                <rect x="3" y="8" width="8" height="4" fill="#fcd34d"/>
                <rect x="3" y="8" width="1" height="4" fill="#a5f3fc" opacity="0.5"/> <!-- Kaca kiri -->
                <rect x="10" y="8" width="1" height="4" fill="#a5f3fc" opacity="0.5"/> <!-- Kaca kanan -->

                <!-- Sisa Pasir Sedikit di Atas -->
                <rect x="6" y="5" width="2" height="1" fill="#fcd34d"/>

                <!-- Tetesan Pasir (Animasi) -->
                <rect x="6" y="6" width="2" height="2" fill="#fcd34d" class="animate-drop"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text-504 tracking-widest text-pixel-fuchsia">
            504
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-fuchsia">
            <span>TIME: 00:00</span>
            <span>TIMEOUT: TRUE</span>
            <span>WAIT: LONG</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Waktu Habis!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Server hulu terlalu lama merespon. Koneksi terputus karena batas waktu (timeout) telah terlampaui. Silakan coba lagi nanti.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Retry (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-fuchsia translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">⌛</span> Reset Waktu
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-cyan-light translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-cyan-light border-2 border-pixel-cyan-light px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#22d3ee] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Kembali
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.TIME // ERR_TIMEOUT
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-fuchsia animate-pulse-slow">
        CONNECTION TERMINATED
    </div>

</body>
</html>
