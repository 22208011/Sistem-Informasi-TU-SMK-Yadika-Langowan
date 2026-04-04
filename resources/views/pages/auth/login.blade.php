<x-layouts::auth>
    <div class="flex flex-col gap-6 animate-fade-in-up">
        <x-auth-header :title="__('Masuk ke Sistem Tata Usaha')" :description="__('Gunakan NIS / NISN (siswa) atau email (pegawai), lalu masukkan kata sandi Anda.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-800/70 dark:bg-blue-900/20 dark:text-blue-100">
            <p class="font-semibold">Informasi Login</p>
            <p class="mt-1 text-blue-800/90 dark:text-blue-100/90">
                Jika Anda siswa, gunakan NIS atau NISN. Jika Anda pegawai/admin, gunakan email akun yang terdaftar.
            </p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- NIS / NISN / Email -->
            <div class="animate-fade-in-up delay-100">
                <flux:input
                    name="login"
                    :label="__('NIS / NISN / Email')"
                    :value="old('login')"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="NIS | NISN | Email"
                    class="transition-all duration-300 focus:ring-2 focus:ring-blue-500/20"
                />
            </div>

            <!-- Password -->
            <div class="relative animate-fade-in-up delay-200">
                <flux:input
                    name="password"
                    :label="__('Kata Sandi')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Masukkan kata sandi')"
                    viewable
                    class="transition-all duration-300 focus:ring-2 focus:ring-blue-500/20"
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 hover:text-blue-600 transition-colors" :href="route('password.request')" wire:navigate>
                        {{ __('Lupa kata sandi?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <div class="animate-fade-in-up delay-300">
                <flux:checkbox name="remember" :label="__('Ingat saya di perangkat ini')" :checked="old('remember')" />
            </div>

            <div class="flex items-center justify-end animate-fade-in-up delay-400">
                <flux:button variant="primary" type="submit" class="w-full bg-linear-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg" data-test="login-button">
                    {{ __('Masuk ke Sistem') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400 animate-fade-in-up delay-500">
                <span>{{ __('Belum memiliki akun siswa?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="font-semibold text-blue-600 hover:text-blue-700 transition-colors">{{ __('Daftar sekarang') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
