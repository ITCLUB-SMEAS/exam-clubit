<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>504 - Gateway Timeout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Import Font 'Press Start 2P' untuk nuansa 8-bit yang autentik -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'pixel': ['"Press Start 2P"', 'cursive'],
                    },
                    colors: {
                        pixel: {
                            bg: '#1a1b26',       /* Gelap malam */
                            primary: '#d946ef',  /* Fuchsia Neon - Time Void */
                            accent: '#22d3ee',   /* Cyan - Kontras */
                            sand: '#fcd34d',     /* Warna Pasir */
                            glass: '#a5f3fc',    /* Warna Kaca */
                            wood: '#78350f',     /* Warna Kayu Frame */
                            white: '#ffffff',
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'spin-slow': 'spin 3s linear infinite', /* Putaran jam pasir */
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'drop': 'drop 1s infinite',
                    },
                    keyframes: {
                        'drop': {
                            '0%': { transform: 'translateY(0)', opacity: '1' },
                            '100%': { transform: 'translateY(10px)', opacity: '0' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* CSS Tambahan untuk efek Scanline CRT dan Glitch */
        body {
            background-color: #1a1b26;
            overflow-x: hidden;
        }

        /* Garis-garis halus layar TV lama */
        .scanlines {
            background: linear-gradient(
                to bottom,
                rgba(255,255,255,0),
                rgba(255,255,255,0) 50%,
                rgba(0,0,0,0.2) 50%,
                rgba(0,0,0,0.2)
            );
            background-size: 100% 4px;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 50;
        }

        /* Efek Glitch pada teks 504 */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #d946ef, -3px -3px 0px #22d3ee;
        }

        /* Membuat grid latar belakang */
        .bg-grid {
            background-image:
                linear-gradient(rgba(217, 70, 239, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(217, 70, 239, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Butiran pasir jatuh */
        .sand-particle {
            animation: drop 0.8s linear infinite;
        }
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-white relative">

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
                <rect x="6" y="6" width="2" height="2" fill="#fcd34d" class="sand-particle"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
            504
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-primary">
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
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">⌛</span> Reset Waktu
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#22d3ee] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Kembali
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.TIME // ERR_TIMEOUT
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-pulse-slow">
        CONNECTION TERMINATED
    </div>

</body>
</html>
