<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public int $counter = 0;
    public string $message = '';

    public function increment(): void
    {
        $this->counter++;
        $this->message = "Counter incremented to {$this->counter}";
        \Log::info('Livewire test increment called', ['counter' => $this->counter]);
    }

    public function decrement(): void
    {
        $this->counter--;
        $this->message = "Counter decremented to {$this->counter}";
        \Log::info('Livewire test decrement called', ['counter' => $this->counter]);
    }
}; ?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Livewire Test Component</h1>
    
    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <p class="text-4xl font-bold text-center mb-4">{{ $counter }}</p>
        
        <div class="flex gap-4 justify-center">
            <button 
                wire:click="decrement" 
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
            >
                - Decrement
            </button>
            <button 
                wire:click="increment" 
                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            >
                + Increment
            </button>
        </div>
        
        @if($message)
        <p class="mt-4 text-center text-green-600">{{ $message }}</p>
        @endif
    </div>
    
    <div class="bg-yellow-50 p-4 rounded">
        <strong>Instructions:</strong>
        <ol class="list-decimal list-inside mt-2">
            <li>Click the buttons above</li>
            <li>If the counter changes, Livewire is working</li>
            <li>If nothing happens, check browser console (F12) for errors</li>
            <li>Check Network tab for /livewire/update requests</li>
        </ol>
    </div>
    
    <div class="mt-4 bg-blue-50 p-4 rounded">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>Component: {{ get_class($this) }}</li>
            <li>Wire ID: {{ $__livewire->getId() }}</li>
        </ul>
    </div>
</div>
