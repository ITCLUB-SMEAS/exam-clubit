<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
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
                            primary: '#d600ff',  /* Neon Violet - Identitas Misterius */
                            accent: '#00e5ff',   /* Cyan Neon */
                            yellow: '#ffcc00',   /* Kuning Koin */
                            text: '#e0e0e0',
                        }
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'hard-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
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

        /* Efek Glitch pada teks 401 */
        .glitch-text {
            position: relative;
            text-shadow: 3px 3px 0px #d600ff, -3px -3px 0px #00e5ff;
        }

        /* Membuat grid latar belakang */
        .bg-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="h-screen w-full flex flex-col items-center justify-center font-pixel text-pixel-text bg-grid selection:bg-pixel-primary selection:text-white relative">

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
        <h1 class="text-6xl md:text-8xl font-bold mb-2 glitch-text tracking-widest text-pixel-primary">
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
                <div class="absolute inset-0 bg-pixel-primary translate-x-1 translate-y-1"></div>
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
    <div class="absolute bottom-6 right-6 text-[10px] text-pixel-primary animate-pulse-fast">
        PLEASE LOGIN TO CONTINUE
    </div>

</body>
</html>
