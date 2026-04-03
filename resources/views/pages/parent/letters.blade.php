<?php

use App\Models\Student;
use App\Models\Guardian;
use App\Models\Letter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Surat dari Sekolah - Portal Orang Tua')] class extends Component {
    public ?int $selectedLetterId = null;

    public function mount()
    {
        if (!auth()->user()->isParent()) {
            return redirect()->route('dashboard');
        }
    }

    #[Computed]
    public function guardian(): ?Guardian
    {
        return auth()->user()->guardian;
    }

    #[Computed]
    public function student(): ?Student
    {
        return $this->guardian?->student;
    }

    #[Computed]
    public function letters()
    {
        if (!$this->student) {
            return collect();
        }

        return Letter::where('student_id', $this->student->id)
            ->whereIn('status', ['sent', 'approved'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function selectedLetter(): ?Letter
    {
        if (!$this->selectedLetterId) {
            return null;
        }

        return $this->letters->firstWhere('id', $this->selectedLetterId);
    }

    public function viewLetter(int $id)
    {
        $this->selectedLetterId = $id;
        $this->modal('letter-detail')->show();
    }

    public function closeLetter()
    {
        $this->selectedLetterId = null;
        $this->modal('letter-detail')->close();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Surat dari Sekolah') }}</flux:heading>
            <flux:text class="mt-1">
                @if ($this->student)
                    {{ __('Surat untuk :name', ['name' => $this->student->name]) }}
                @else
                    {{ __('Data anak belum terhubung') }}
                @endif
            </flux:text>
        </div>
        <flux:button variant="ghost" href="{{ route('parent.dashboard') }}" icon="arrow-left" wire:navigate>
            {{ __('Kembali') }}
        </flux:button>
    </div>

    @if (!$this->student)
        <flux:card class="bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800">
            <flux:card.body>
                <div class="flex items-center gap-4">
                    <flux:icon name="exclamation-triangle" class="size-8 text-yellow-500" />
                    <flux:text>{{ __('Akun Anda belum terhubung dengan data siswa.') }}</flux:text>
                </div>
            </flux:card.body>
        </flux:card>
    @else
        <!-- Student Info -->
        <flux:card>
            <flux:card.body>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-3">
                        <flux:avatar
                            :initials="Str::of($this->student->name)->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('')"
                        />
                        <div>
                            <flux:text class="font-medium">{{ $this->student->name }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">{{ $this->student->nis }}</flux:text>
                        </div>
                    </div>
                    <flux:badge color="blue">{{ $this->student->classroom?->name ?? '-' }}</flux:badge>
                </div>
            </flux:card.body>
        </flux:card>

        <!-- Letters List -->
        @if ($this->letters->isNotEmpty())
            <div class="space-y-4">
                @foreach ($this->letters as $letter)
                    <flux:card class="hover:shadow-md transition-shadow cursor-pointer" wire:click="viewLetter({{ $letter->id }})">
                        <flux:card.body>
                            <div class="flex items-start gap-4">
                                <div class="p-3 rounded-lg bg-{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}-100 dark:bg-{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}-900/20">
                                    <flux:icon name="envelope-open" class="size-6 text-{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}-600 dark:text-{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}-400" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <flux:badge size="sm" color="{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}">
                                            {{ \App\Models\Letter::TYPES[$letter->letter_type] ?? $letter->letter_type }}
                                        </flux:badge>
                                        <flux:text size="sm" class="text-zinc-500">
                                            {{ $letter->letter_number }}
                                        </flux:text>
                                    </div>
                                    <flux:heading size="md">{{ $letter->subject }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-500 mt-1">
                                        {{ __('Diterbitkan: :date', ['date' => $letter->issued_at?->format('d F Y') ?? $letter->created_at->format('d F Y')]) }}
                                    </flux:text>
                                </div>
                                <flux:icon name="chevron-right" class="size-5 text-zinc-400" />
                            </div>
                        </flux:card.body>
                    </flux:card>
                @endforeach
            </div>
        @else
            <flux:card>
                <flux:card.body>
                    <div class="text-center py-12">
                        <flux:icon name="envelope" class="size-16 text-zinc-300 mx-auto mb-4" />
                        <flux:heading size="lg" class="text-zinc-500">{{ __('Tidak Ada Surat') }}</flux:heading>
                        <flux:text class="text-zinc-400 mt-2">
                            {{ __('Belum ada surat dari sekolah untuk anak Anda.') }}
                        </flux:text>
                    </div>
                </flux:card.body>
            </flux:card>
        @endif
    @endif

    <!-- Letter Detail Modal -->
    <flux:modal name="letter-detail" class="max-w-2xl">
        @if ($this->selectedLetter)
            <flux:modal.header>
                <div class="flex items-center gap-3">
                    <flux:badge color="{{ \App\Models\Letter::TYPE_COLORS[$this->selectedLetter->letter_type] ?? 'zinc' }}">
                        {{ \App\Models\Letter::TYPES[$this->selectedLetter->letter_type] ?? $this->selectedLetter->letter_type }}
                    </flux:badge>
                    <flux:heading size="lg">{{ $this->selectedLetter->subject }}</flux:heading>
                </div>
            </flux:modal.header>
            <flux:modal.body>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <flux:text class="text-zinc-500">{{ __('Nomor Surat') }}</flux:text>
                            <flux:text class="font-mono">{{ $this->selectedLetter->letter_number }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-zinc-500">{{ __('Tanggal') }}</flux:text>
                            <flux:text>{{ $this->selectedLetter->issued_at?->format('d F Y') ?? '-' }}</flux:text>
                        </div>
                    </div>

                    <hr class="border-zinc-200 dark:border-zinc-700">

                    <div>
                        <flux:text class="text-zinc-500 mb-2">{{ __('Isi Surat') }}</flux:text>
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($this->selectedLetter->content)) !!}
                        </div>
                    </div>

                    @if ($this->selectedLetter->notes)
                        <div class="p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:text class="text-zinc-500 mb-1">{{ __('Catatan') }}</flux:text>
                            <flux:text>{{ $this->selectedLetter->notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:modal.body>
            <flux:modal.footer>
                <flux:button wire:click="closeLetter">{{ __('Tutup') }}</flux:button>
            </flux:modal.footer>
        @endif
    </flux:modal>
</div>
