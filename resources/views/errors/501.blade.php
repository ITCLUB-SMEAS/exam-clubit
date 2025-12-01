<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>501 - Not Implemented</title>
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
                            primary: '#fbbf24',  /* Construction Yellow - Under Construction */
                            accent: '#3b82f6',   /* Blueprint Blue - Warna teknis/arsitek */
                            white: '#ffffff',
                            black: '#000000',
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'bounce-pixel': 'bounce-pixel 2s infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        'bounce-pixel': {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
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

        /* Efek Glitch pada teks 501 - Efek striping konstruksi */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #fbbf24, -3px -3px 0px #ffffff;
        }

        /* Membuat grid latar belakang ala Blueprint */
        .bg-grid {
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
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-black relative">

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
                <rect x="4" y="9" width="1" height="2" fill="#b45309"/> <!-- Darker Amber for shading -->
                <rect x="5" y="7" width="1" height="2" fill="#b45309"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
            501
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-accent">
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
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🚧</span> Kembali
                </button>
            </button>

            <!-- Button Contact (Secondary) -->
            <a href="#" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#3b82f6] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Lapor Bug
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.BUILD // V.ALPHA
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-pulse-slow">
        UNDER CONSTRUCTION...
    </div>

</body>
</html>
