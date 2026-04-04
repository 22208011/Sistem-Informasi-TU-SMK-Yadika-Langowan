@php
    $appUrl = rtrim((string) config('app.url', url('/')), '/');
    $appName = (string) config('app.name');
    $metaTitle = $title ?? $appName;
    $metaDescription = 'Sistem Informasi Tata Usaha untuk SMK Yadika Langowan.';
    $logoMetaUrl = $appUrl.'/images/logo-yadika.png';
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="theme-color" content="#6366f1" />
<meta name="description" content="{{ $metaDescription }}" />

<title>{{ $metaTitle }}</title>

<link rel="canonical" href="{{ $appUrl }}" />

<meta property="og:site_name" content="{{ $appName }}" />
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ $appUrl }}" />
<meta property="og:image" content="{{ $logoMetaUrl }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $metaTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $logoMetaUrl }}" />

{{-- Prevent FOUC for dark mode --}}
<script>
    (function() {
        var t = localStorage.getItem('theme');
        if (t === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

{{-- Preconnect to external resources --}}
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="dns-prefetch" href="https://fonts.bunny.net">

{{-- Critical font preload --}}
<link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" /></noscript>

{{-- Favicons --}}
<link rel="icon" href="{{ $logoMetaUrl }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ $logoMetaUrl }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ $logoMetaUrl }}">

{{-- Performance hints --}}
<meta http-equiv="x-dns-prefetch-control" content="on">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
@livewireStyles

{{-- Prevent FOUC --}}
<style>
    [x-cloak] { display: none !important; }
    .loading-overlay { opacity: 0; visibility: hidden; }
</style>
