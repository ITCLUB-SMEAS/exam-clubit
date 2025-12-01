<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
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
                            primary: '#dc2626',  /* Danger Red - Critical Error */
                            accent: '#facc15',   /* Fire Yellow - Api */
                            fire: '#f97316',     /* Fire Orange */
                            smoke: '#4b5563',    /* Asap Abu-abu */
                            server: '#1f2937',   /* Server Hitam */
                            white: '#ffffff',
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'shake-hard': 'shake-hard 0.5s cubic-bezier(.36,.07,.19,.97) both infinite',
                        'flicker': 'flicker 0.1s infinite',
                        'rise': 'rise 2s infinite',
                    },
                    keyframes: {
                        'shake-hard': {
                            '0%, 100%': { transform: 'translate(0, 0)' },
                            '10%': { transform: 'translate(-2px, -2px)' },
                            '20%': { transform: 'translate(2px, 2px)' },
                            '30%': { transform: 'translate(-2px, 2px)' },
                            '40%': { transform: 'translate(2px, -2px)' },
                            '50%': { transform: 'translate(-2px, 0)' },
                            '60%': { transform: 'translate(2px, 0)' },
                        },
                        'flicker': {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' },
                        },
                        'rise': {
                            '0%': { transform: 'translateY(0)', opacity: '1' },
                            '100%': { transform: 'translateY(-20px)', opacity: '0' },
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

        /* Efek Glitch pada teks 500 */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #dc2626, -3px -3px 0px #facc15;
            animation: flicker 2s infinite;
        }

        /* Membuat grid latar belakang */
        .bg-grid {
            background-image:
                linear-gradient(rgba(220, 38, 38, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(220, 38, 38, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Partikel Asap */
        .smoke-particle {
            animation: rise 1.5s linear infinite;
        }
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-white relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Burning Server) -->
        <div class="mb-8 inline-block relative animate-shake-hard">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Asap (Abu-abu) -->
                <rect x="3" y="0" width="2" height="2" fill="#4b5563" class="smoke-particle" style="animation-delay: 0.2s"/>
                <rect x="8" y="1" width="2" height="1" fill="#4b5563" class="smoke-particle" style="animation-delay: 0.5s"/>

                <!-- Api Utama (Orange/Kuning) -->
                <rect x="2" y="3" width="10" height="11" fill="#f97316"/> <!-- Dasar Api Orange -->
                <rect x="3" y="4" width="8" height="9" fill="#facc15"/> <!-- Inti Api Kuning -->
                <rect x="4" y="6" width="6" height="6" fill="#ffffff"/> <!-- Inti Panas Putih -->

                <!-- Server Hangus (Hitam/Gelap di tengah api) -->
                <rect x="3" y="5" width="8" height="8" fill="#1f2937"/>

                <!-- Retakan/Kerusakan Server (Merah) -->
                <rect x="4" y="6" width="2" height="1" fill="#dc2626"/>
                <rect x="7" y="9" width="3" height="1" fill="#dc2626"/>
                <rect x="5" y="11" width="1" height="1" fill="#dc2626"/>

                <!-- Lidah Api Liar (Animasi via CSS flicker) -->
                <rect x="1" y="4" width="1" height="2" fill="#f97316" class="animate-flicker"/>
                <rect x="12" y="5" width="1" height="3" fill="#f97316" class="animate-flicker"/>
                <rect x="5" y="2" width="1" height="2" fill="#facc15" class="animate-flicker"/>
                <rect x="9" y="3" width="1" height="2" fill="#facc15" class="animate-flicker"/>
            </svg>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
            500
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-primary">
            <span>SYS: CRITICAL</span>
            <span>ERR: FATAL</span>
            <span>REBOOT: FAIL</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Server Meledak!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Terjadi kesalahan internal yang fatal. Tim teknisi pixel kami sedang menyiram air ke server room. Silakan kembali lagi nanti.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Retry (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">🔥</span> Coba Refresh
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#facc15] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Evakuasi (Home)
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.ERR // INTERNAL_SERVER_ERROR
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-flicker">
        SYSTEM FAILURE DETECTED
    </div>

</body>
</html>
