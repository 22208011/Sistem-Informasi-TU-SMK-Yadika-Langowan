<?php

use App\Models\LetterTemplate;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $category;
    public string $title = 'Template Surat';
    public string $description = 'Format dokumen standar untuk kemudahan administrasi.';

    // Upload state
    public $newTitle = '';
    public $newDescription = '';
    public $newFile;

    public function getTemplatesProperty()
    {
        return LetterTemplate::where('category', $this->category)->latest()->get();
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
            'newTitle' => 'required|string|max:255',
            'newFile' => 'required|file|max:10240', // 10MB max
            'newDescription' => 'nullable|string',
        ]);

        $originalName = $this->newFile->getClientOriginalName();
        $extension = $this->newFile->getClientOriginalExtension();
        
        $path = $this->newFile->storeAs('letter_templates', time() . '_' . \Illuminate\Support\Str::slug($this->newTitle) . '.' . $extension, 'public');

        LetterTemplate::create([
            'title' => $this->newTitle,
            'description' => $this->newDescription,
            'category' => $this->category,
            'file_path' => $path,
            'original_filename' => $originalName,
            'file_extension' => $extension,
        ]);

        $this->reset(['newTitle', 'newDescription', 'newFile']);
        
        $this->dispatch('close-modal', 'upload-template-modal-' . $this->category);
        session()->flash('widget_success', 'Template surat berhasil diunggah.');
    }

    public function downloadTemplate(LetterTemplate $template)
    {
        if (Storage::disk('public')->exists($template->file_path)) {
            return response()->download(Storage::disk('public')->path($template->file_path), $template->original_filename);
        }
        session()->flash('widget_error', 'File tidak ditemukan.');
    }

    public function deleteTemplate(LetterTemplate $template)
    {
        if (Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }
        $template->delete();
        session()->flash('widget_success', 'Template berhasil dihapus.');
    }
};
?>

<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2 text-zinc-800 dark:text-zinc-100">
                <flux:icon name="document-duplicate" class="size-6 text-orange-500" />
                {{ $title }}
            </h2>
            <flux:text class="text-zinc-500 text-sm mt-1">
                {{ $description }}
            </flux:text>
        </div>
        <div>
            <flux:modal.trigger name="upload-template-modal-{{ $category }}">
                <flux:button size="sm" variant="primary" icon="plus" class="rounded-lg!">Upload Format</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @if (session()->has('widget_success'))
    <div class="mb-4">
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('widget_success') }}
        </flux:callout>
    </div>
    @endif

    @if (session()->has('widget_error'))
    <div class="mb-4">
        <flux:callout variant="danger" icon="x-circle" dismissible>
            {{ session('widget_error') }}
        </flux:callout>
    </div>
    @endif

    @if($this->templates->isEmpty())
        <div class="flex flex-col items-center justify-center p-8 text-center rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
            <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
                <flux:icon name="document-plus" class="size-6 text-zinc-400" />
            </div>
            <p class="font-medium text-zinc-700 dark:text-zinc-300">Belum ada template</p>
            <p class="text-sm text-zinc-500 mt-1 max-w-sm">Klik "Upload Format" untuk menambahkan dokumen / master surat untuk kategori ini.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($this->templates as $template)
                @php
                    $iconSetting = $this->getIconForExtension($template->file_extension);
                @endphp
                
                <div class="group relative flex flex-col bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl hover:shadow-md hover:border-orange-200 dark:hover:border-orange-500/30 transition-all overflow-hidden">
                    <div class="p-4 flex-1 cursor-pointer" wire:click="downloadTemplate({{ $template->id }})">
                        <div class="flex gap-4">
                            <div class="shrink-0">
                                <div class="size-10 rounded-lg flex items-center justify-center shadow-sm border border-zinc-100 dark:border-zinc-800 {{ $iconSetting['bg'] }}">
                                    <flux:icon name="{{ $iconSetting['name'] }}" variant="solid" class="size-5 {{ $iconSetting['color'] }}" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors" title="{{ $template->title }}">
                                    {{ $template->title }}
                                </h3>
                                <p class="text-[11px] text-zinc-500 mt-0.5 line-clamp-1" title="{{ $template->description }}">
                                    {{ $template->description ?: 'Tidak ada deskripsi.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-100 dark:border-zinc-800 px-4 py-2.5 flex items-center justify-between mt-auto">
                        <div class="flex flex-col min-w-0">
                            <span class="text-[9px] font-medium uppercase tracking-wider text-zinc-400">{{ $template->file_extension }}</span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadTemplate({{ $template->id }})" class="text-zinc-500 hover:text-orange-600 h-7 w-7 px-0" aria-label="Download" />
                            
                            @if(auth()->user()->isAdmin())
                            <flux:dropdown direction="down" align="end">
                                <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-600 h-7 w-7 px-0" />
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

    {{-- Upload Modal --}}
    <flux:modal name="upload-template-modal-{{ $category }}" class="md:w-96">
        <form wire:submit="saveTemplate">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Upload Format {{ Str::title($category) }}</flux:heading>
                    <flux:text class="text-zinc-500 text-sm">Tambahkan format surat resmi agar mudah diakses saat mengelola fitur ini.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="newTitle" label="Judul/Nama Format" placeholder="Contoh: Surat Panggilan..." required />
                    
                    <flux:textarea wire:model="newDescription" label="Deskripsi Umum" placeholder="Catatan singkat..." />

                    <flux:input type="file" wire:model="newFile" label="File Dokumen" required />
                    
                    <div wire:loading wire:target="newFile" class="text-xs text-blue-600 mt-1">Mengunggah file...</div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveTemplate,newFile">
                        <span wire:loading.remove wire:target="saveTemplate">Simpan</span>
                        <span wire:loading wire:target="saveTemplate">Menyimpan...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
