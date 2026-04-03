<x-layouts::auth>
    <div class="flex flex-col gap-6 animate-fade-in-up">
        <x-auth-header :title="__('Log in to your account')" :description="__('Masukkan NIS/NISN (untuk siswa) atau email dan password')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

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
                    placeholder="Contoh: 2024001, 0045678901, atau email@example.com"
                    class="transition-all duration-300 focus:ring-2 focus:ring-blue-500/20"
                />
            </div>

            <!-- Password -->
            <div class="relative animate-fade-in-up delay-200">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                    class="transition-all duration-300 focus:ring-2 focus:ring-blue-500/20"
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 hover:text-blue-600 transition-colors" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <div class="animate-fade-in-up delay-300">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
            </div>

            <div class="flex items-center justify-end animate-fade-in-up delay-400">
                <flux:button variant="primary" type="submit" class="w-full bg-linear-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg" data-test="login-button">
                    <flux:icon name="arrow-right-end-on-rectangle" class="size-5 mr-2" />
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400 animate-fade-in-up delay-500">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="font-semibold text-blue-600 hover:text-blue-700 transition-colors">{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
