<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>502 - Bad Gateway</title>
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
                            primary: '#ef4444',  /* Signal Red - Broken Connection */
                            accent: '#fbbf24',   /* Spark Yellow - Listrik/Percikan */
                            cable: '#64748b',    /* Abu-abu kabel */
                            white: '#ffffff',
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'flash': 'flash 0.2s infinite alternate',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        'flash': {
                            '0%': { opacity: '1' },
                            '100%': { opacity: '0.3' }
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

        /* Efek Glitch pada teks 502 */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #ef4444, -3px -3px 0px #fbbf24;
        }

        /* Membuat grid latar belakang */
        .bg-grid {
            background-image:
                linear-gradient(rgba(239, 68, 68, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(239, 68, 68, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Efek Percikan Listrik (Sparks) */
        .spark {
            animation: flash 0.1s infinite alternate;
        }
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-white relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Broken Cable / Disconnected Plug) -->
        <div class="mb-8 inline-block relative">
            <svg width="160" height="100" viewBox="0 0 16 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Kabel Kiri (Abu-abu) -->
                <rect x="0" y="4" width="6" height="2" fill="#64748b"/>
                <!-- Ujung Tembaga Kiri -->
                <rect x="6" y="4" width="1" height="2" fill="#b45309"/>

                <!-- Kabel Kanan (Abu-abu) -->
                <rect x="10" y="4" width="6" height="2" fill="#64748b"/>
                <!-- Ujung Tembaga Kanan -->
                <rect x="9" y="4" width="1" height="2" fill="#b45309"/>

                <!-- Percikan Listrik (Sparks) - Kuning/Putih -->
                <g class="spark">
                    <!-- Tengah Atas -->
                    <rect x="7" y="2" width="2" height="1" fill="#fbbf24"/>
                    <rect x="8" y="1" width="1" height="1" fill="#ffffff"/>
                    <!-- Tengah Bawah -->
                    <rect x="7" y="7" width="1" height="1" fill="#fbbf24"/>
                    <rect x="8" y="8" width="1" height="1" fill="#ffffff"/>
                    <!-- Tengah -->
                    <rect x="8" y="5" width="1" height="1" fill="#ffffff"/>
                </g>
            </svg>

            <!-- Tambahan efek visual "Zzzt" -->
            <div class="absolute -top-4 right-1/2 translate-x-1/2 text-xs text-pixel-accent spark font-sans font-bold">Zzzt!</div>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
            502
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-primary">
            <span>SIGNAL: LOST</span>
            <span>GATEWAY: BAD</span>
            <span>PING: 999ms</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Jalur Terputus!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Gateway menerima respons yang tidak valid dari server hulu. Kabel server mungkin tersandung atau digigit monster pixel.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Retry (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔌</span> Sambung Ulang
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-cable translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-white border-2 border-pixel-white px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#ffffff] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Menu Utama
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.NET // ERR_BAD_GATEWAY
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-pulse-slow">
        CONNECTION ATTEMPT FAILED
    </div>

</body>
</html>
