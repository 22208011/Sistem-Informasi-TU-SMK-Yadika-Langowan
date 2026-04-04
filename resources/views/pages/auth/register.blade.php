<x-layouts::auth>
    <div class="flex flex-col gap-6 animate-fade-in-up">
        <x-auth-header :title="__('Registrasi Akun Siswa')" :description="__('Gunakan NIS / NISN siswa yang sudah terdaftar untuk membuat akun.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-800/70 dark:bg-emerald-900/20 dark:text-emerald-100">
            <p class="font-semibold">Catatan Penting</p>
            <p class="mt-1 text-emerald-800/90 dark:text-emerald-100/90">
                Setelah registrasi berhasil, akun akan terhubung langsung dengan data siswa pada sistem tata usaha.
            </p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- NIS / NISN -->
            <flux:input
                name="nim"
                :label="__('NIS / NISN')"
                :value="old('nim')"
                type="text"
                required
                autofocus
                autocomplete="username"
                :placeholder="__('Contoh: 2024001 atau 0045678901')"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Kata Sandi')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Minimal 8 karakter')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Konfirmasi Kata Sandi')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Ulangi kata sandi')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg" data-test="register-user-button">
                    {{ __('Daftar Akun Siswa') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Sudah punya akun?') }}</span>
            <flux:link :href="route('login')" wire:navigate class="font-semibold text-blue-600 hover:text-blue-700">{{ __('Masuk di sini') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
