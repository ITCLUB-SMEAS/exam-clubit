<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Unavailable</title>
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
                            primary: '#f59e0b',  /* Amber - Maintenance/Warning */
                            accent: '#60a5fa',   /* Blue - Zzz Sleep bubbles */
                            server: '#334155',   /* Abu-abu gelap server */
                            led: '#10b981',      /* Hijau LED (dimmed) */
                            white: '#ffffff',
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'float-slow': 'float 4s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'blink': 'blink 2s infinite',
                    },
                    keyframes: {
                        'float': {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        'blink': {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.3' },
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

        /* Efek Glitch pada teks 503 */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #f59e0b, -3px -3px 0px #60a5fa;
        }

        /* Membuat grid latar belakang */
        .bg-grid {
            background-image:
                linear-gradient(rgba(245, 158, 11, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(245, 158, 11, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Animasi untuk huruf Zzz */
        .sleep-z-1 { animation: float 3s ease-in-out infinite; animation-delay: 0s; }
        .sleep-z-2 { animation: float 3s ease-in-out infinite; animation-delay: 1s; }
        .sleep-z-3 { animation: float 3s ease-in-out infinite; animation-delay: 2s; }
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-white relative">

    <!-- Layer Efek CRT -->
    <div class="scanlines"></div>

    <!-- Main Content -->
    <div class="z-10 text-center px-4 max-w-2xl">

        <!-- Pixel Art SVG (Sleeping Server) -->
        <div class="mb-8 inline-block relative">
            <svg width="140" height="140" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Server Rack Body (Abu-abu) -->
                <rect x="3" y="2" width="8" height="12" fill="#334155"/>
                <!-- Server Outline -->
                <rect x="2" y="2" width="1" height="12" fill="#1e293b"/>
                <rect x="11" y="2" width="1" height="12" fill="#1e293b"/>
                <rect x="3" y="1" width="8" height="1" fill="#1e293b"/>

                <!-- Rack Units / Slots (Garis-garis) -->
                <rect x="4" y="4" width="6" height="1" fill="#1e293b"/>
                <rect x="4" y="7" width="6" height="1" fill="#1e293b"/>
                <rect x="4" y="10" width="6" height="1" fill="#1e293b"/>

                <!-- Status LEDs (Hijau Redup - Tidur) -->
                <rect x="9" y="3" width="1" height="1" fill="#10b981" class="animate-blink"/>
                <rect x="9" y="6" width="1" height="1" fill="#10b981" class="animate-blink" style="animation-delay: 0.5s"/>
                <rect x="9" y="9" width="1" height="1" fill="#10b981" class="animate-blink" style="animation-delay: 1s"/>
            </svg>

            <!-- Floating Zzz Animation (Tidur) -->
            <div class="absolute -top-6 right-0 text-xl text-pixel-accent font-bold sleep-z-3">z</div>
            <div class="absolute -top-2 right-4 text-lg text-pixel-accent font-bold sleep-z-2">Z</div>
            <div class="absolute top-2 right-8 text-sm text-pixel-accent font-bold sleep-z-1">z</div>
        </div>

        <!-- Header Besar -->
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
            503
        </h1>

        <!-- Status Bar Style -->
        <div class="flex justify-center items-center gap-4 mb-8 text-xs md:text-sm text-pixel-primary">
            <span>MODE: SLEEP</span>
            <span>MAINTENANCE: TRUE</span>
            <span>UPTIME: PAUSED</span>
        </div>

        <!-- Pesan Error -->
        <h2 class="text-lg md:text-xl text-white mb-6 leading-relaxed uppercase">
            Server Sedang Istirahat!
        </h2>
        <p class="text-xs md:text-sm text-gray-400 mb-10 leading-6 font-sans">
            Layanan sedang tidak tersedia sementara waktu karena pemeliharaan rutin atau beban berlebih. Mohon jangan ganggu server yang sedang tidur.
        </p>

        <!-- Tombol Aksi -->
        <div class="flex flex-col md:flex-row gap-6 justify-center">

            <!-- Button Retry (Primary) -->
            <button onclick="window.location.reload()" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-white text-black border-2 border-black px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    <span class="mr-2">⏰</span> Bangunkan (Reload)
                </button>
            </button>

            <!-- Button Home (Secondary) -->
            <a href="/" class="group relative inline-block focus:outline-none">
                <!-- Shadow Keras (Bawah) -->
                <div class="absolute inset-0 bg-pixel-accent translate-x-1 translate-y-1"></div>
                <!-- Konten Tombol -->
                <button class="relative bg-pixel-bg text-pixel-accent border-2 border-pixel-accent px-6 py-4 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0_#60a5fa] transition-all active:translate-x-1 active:translate-y-1 active:shadow-none text-xs md:text-sm uppercase font-bold">
                    Cek Status
                </button>
            </a>
        </div>

    </div>

    <!-- Hiasan Sudut -->
    <div class="absolute top-6 left-6 text-[10px] text-gray-600">
        SYS.MAINT // SERVICE_UNAVAILABLE
    </div>
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-pulse-slow">
        RETRY AFTER: 60s
    </div>

</body>
</html>
