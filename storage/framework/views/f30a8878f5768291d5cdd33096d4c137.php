<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
<meta name="theme-color" content="#6366f1" />

<title><?php echo e($title ?? config('app.name')); ?></title>


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


<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="dns-prefetch" href="https://fonts.bunny.net">


<link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" /></noscript>


<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">


<meta http-equiv="x-dns-prefetch-control" content="on">

<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
<?php echo app('flux')->fluxAppearance(); ?>

<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>



<style>
    [x-cloak] { display: none !important; }
    .loading-overlay { opacity: 0; visibility: hidden; }
</style>
<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views/partials/head.blade.php ENDPATH**/ ?>