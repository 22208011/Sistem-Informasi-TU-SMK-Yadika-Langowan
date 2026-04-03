<?php

use App\Models\LetterTemplate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Template Surat')] class extends Component {
    use WithFileUploads;

    public $activeCategory = 'semua';
    public $search = '';

    // Modal forms
    public $title = '';
    public $description = '';
    public $category = 'siswa';
    public $file;

    public function getCategoriesProperty()
    {
        return [
            'semua' => ['label' => 'Semua Kategori', 'icon' => 'folder-open'],
            'siswa' => ['label' => 'Surat Siswa', 'icon' => 'academic-cap'],
            'pegawai' => ['label' => 'Surat Pegawai', 'icon' => 'briefcase'],
            'tugas' => ['label' => 'Surat Tugas', 'icon' => 'clipboard-document-list'],
            'lainnya' => ['label' => 'Lain-lain', 'icon' => 'document-duplicate'],
        ];
    }

    public function getTemplatesProperty()
    {
        return LetterTemplate::query()
            ->when($this->activeCategory !== 'semua', function ($query) {
                $query->where('category', $this->activeCategory);
            })
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();
    }

    public function setCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function getIconForExtension($extension)
    {
        $extension = strtolower($extension);
        if (in_array($extension, ['doc', 'docx'])) {
            return ['name' => 'document-text', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100 dark:bg-blue-900/30'];
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return ['name' => 'table-cells', 'color' => 'text-green-600', 'bg' => 'bg-green-100 dark:bg-green-900/30'];
        } elseif ($extension === 'pdf') {
            return ['name' => 'document', 'color' => 'text-red-600', 'bg' => 'bg-red-100 dark:bg-red-900/30'];
        }
        return ['name' => 'document', 'color' => 'text-zinc-600', 'bg' => 'bg-zinc-100 dark:bg-zinc-800'];
    }

    public function saveTemplate()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:siswa,pegawai,tugas,lainnya',
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        $originalName = $this->file->getClientOriginalName();
        $extension = $this->file->getClientOriginalExtension();
        
        // Simpan file ke storage
        $path = $this->file->storeAs('letter_templates', time() . '_' . \Illuminate\Support\Str::slug($this->title) . '.' . $extension, 'public');

        LetterTemplate::create([
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'file_path' => $path,
            'original_filename' => $originalName,
            'file_extension' => $extension,
        ]);

        $this->reset(['title', 'description', 'category', 'file']);
        
        // Menutup modal dengan meng-emit event flux
        $this->dispatch('close-modal', 'upload-template-modal');
        session()->flash('success', 'Template surat berhasil diunggah.');
    }

    public function downloadTemplate(LetterTemplate $template)
    {
        if (Storage::disk('public')->exists($template->file_path)) {
            return response()->download(Storage::disk('public')->path($template->file_path), $template->original_filename);
        }
        
        session()->flash('error', 'File tidak ditemukan.');
    }

    public function deleteTemplate(LetterTemplate $template)
    {
        if (Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }
        $template->delete();
        session()->flash('success', 'Template berhasil dihapus.');
    }
}
?>

<div class="space-y-6 flex flex-col h-full">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Template Format Surat</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Kumpulan format dokumen standar untuk keperluan administrasi dan tata usaha.</flux:text>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari template..." icon="magnifying-glass" class="w-full sm:w-64" />
            <flux:modal.trigger name="upload-template-modal">
                <flux:button variant="primary" icon="plus" class="shrink-0">Upload Template</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('success'))
    <flux:callout variant="success" icon="check-circle" heading="Berhasil" dismissible>
        {{ session('success') }}
    </flux:callout>
    @endif

    @if (session()->has('error'))
    <flux:callout variant="danger" icon="x-circle" heading="Gagal" dismissible>
        {{ session('error') }}
    </flux:callout>
    @endif

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
        
        {{-- Sidebar Kategori --}}
        <div class="md:col-span-4 lg:col-span-3">
            <flux:card class="bg-white dark:bg-zinc-900 border-none shadow-sm h-fit p-0 overflow-hidden">
                <div class="p-4 bg-zinc-900 dark:bg-black">
                    <div class="flex items-center gap-3">
                        <flux:icon name="folder" class="size-6 text-orange-400" />
                        <div>
                            <h3 class="font-bold text-white leading-tight">Kategori</h3>
                            <p class="text-xs text-zinc-400">Pilih jenis dokumen</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-2 space-y-1">
                    @foreach($this->categories as $key => $cat)
                        <button 
                            wire:click="setCategory('{{ $key }}')" 
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors text-left font-medium
                                {{ $activeCategory === $key 
                                    ? 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400' 
                                    : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                        >
                            <flux:icon name="{{ $cat['icon'] }}" variant="{{ $activeCategory === $key ? 'solid' : 'outline' }}" class="size-5 shrink-0" />
                            {{ $cat['label'] }}
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Cards Listing --}}
        <div class="md:col-span-8 lg:col-span-9 min-h-[500px]">
            {{-- Category Header Title --}}
            <div class="mb-6">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <flux:icon name="{{ $this->categories[$activeCategory]['icon'] }}" class="size-6 text-orange-500" />
                    {{ $this->categories[$activeCategory]['label'] }}
                </h2>
                <flux:text class="text-zinc-500 text-sm mt-1">
                    Menampilkan semua format dokumen untuk kategori {{ strtolower($this->categories[$activeCategory]['label']) }}.
                </flux:text>
            </div>

            @if($this->templates->isEmpty())
                <div class="flex flex-col items-center justify-center p-12 text-center rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="size-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                        <flux:icon name="document-magnifying-glass" class="size-8 text-zinc-400" />
                    </div>
                    <flux:heading size="lg">Tidak ada template ditemukan</flux:heading>
                    <flux:text class="text-zinc-500 mt-2 max-w-sm">
                        Belum ada file format surat di kategori ini atau pencarian tidak cocok. Silakan upload template baru.
                    </flux:text>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($this->templates as $template)
                        @php
                            $iconSetting = $this->getIconForExtension($template->file_extension);
                        @endphp
                        
                        <div class="group relative flex flex-col bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl hover:shadow-md hover:border-orange-200 dark:hover:border-orange-500/30 transition-all overflow-hidden">
                            <div class="p-5 flex-1 cursor-pointer" wire:click="downloadTemplate({{ $template->id }})">
                                <div class="flex gap-4">
                                    <div class="shrink-0">
                                        <div class="size-12 rounded-lg flex items-center justify-center shadow-sm border border-zinc-100 dark:border-zinc-800 {{ $iconSetting['bg'] }}">
                                            <flux:icon name="{{ $iconSetting['name'] }}" variant="solid" class="size-6 {{ $iconSetting['color'] }}" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 truncate group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors" title="{{ $template->title }}">
                                            {{ $template->title }}
                                        </h3>
                                        <p class="text-xs text-zinc-500 mt-1 line-clamp-2" title="{{ $template->description }}">
                                            {{ $template->description ?: 'Tidak ada deskripsi.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-100 dark:border-zinc-800 px-5 py-3 flex items-center justify-between mt-auto">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">{{ $template->file_extension }}</span>
                                    <span class="text-xs text-zinc-500 truncate w-[120px] sm:w-[150px]" title="{{ $template->original_filename }}">{{ $template->original_filename }}</span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="downloadTemplate({{ $template->id }})" class="text-zinc-500 hover:text-orange-600 focus:text-orange-600" aria-label="Download" />
                                    
                                    {{-- Optional: Delete button, only show for admin maybe? --}}
                                    @if(auth()->user()->isAdmin())
                                    <flux:dropdown direction="down" align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-600" />
                                        <flux:menu>
                                            <flux:menu.item wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Yakin ingin menghapus template ini?" icon="trash" class="text-red-500 hover:bg-red-50 hover:text-red-600">
                                                Hapus
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Upload Modal --}}
    <flux:modal name="upload-template-modal" class="md:w-96">
        <form wire:submit="saveTemplate">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Upload Template Baru</flux:heading>
                    <flux:text class="text-zinc-500 text-sm">Tambahkan format surat resmi agar mudah diakses oleh staf lain.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="title" label="Judul/Nama Format" placeholder="Contoh: Surat Tugas Pembina Ekskul" required />

                    <flux:select wire:model="category" label="Kategori" class="w-full">
                        <option value="siswa">Surat Siswa</option>
                        <option value="pegawai">Surat Pegawai</option>
                        <option value="tugas">Surat Tugas</option>
                        <option value="lainnya">Lain-lain</option>
                    </flux:select>

                    <flux:textarea wire:model="description" label="Deskripsi Umum" placeholder="Catatan singkat kegunaan surat ini..." />

                    <flux:input type="file" wire:model="file" label="File Dokumen" required />
                    
                    <div wire:loading wire:target="file" class="text-xs text-blue-600 mt-1">Mengunggah file...</div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveTemplate,file">
                        <span wire:loading.remove wire:target="saveTemplate">Simpan File</span>
                        <span wire:loading wire:target="saveTemplate">Menyimpan...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
