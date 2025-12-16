<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	
	<!-- PWA Meta Tags -->
	<meta name="theme-color" content="#1a56db">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Ujian Online">
	<link rel="manifest" href="/manifest.json">
	<link rel="apple-touch-icon" href="/icons/icon-192x192.png">
	
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="{{ asset('assets/css/volt.css') }}" rel="stylesheet">
	<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" />
    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>

<body>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.body.classList.add('dark');
        }
    </script>

	@inertia

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/smooth-scroll.polyfills.min.js') }}"></script>
<script src="{{ asset('assets/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/volt.js') }}"></script>

<!-- Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((reg) => console.log('SW registered:', reg.scope))
            .catch((err) => console.log('SW registration failed:', err));
    });
}
</script>
</body>
</html>
