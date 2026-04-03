<?php

namespace App\Livewire\Concerns;

trait WithNotification
{
    public string $notificationMessage = '';
    public string $notificationType = '';

    public function showNotification(string $type, string $message): void
    {
        $this->notificationType = $type;
        $this->notificationMessage = $message;
        $this->dispatch('scroll-to-top');
    }

    public function dismissNotification(): void
    {
        $this->notificationMessage = '';
        $this->notificationType = '';
    }

    public function success(string $message): void
    {
        $this->showNotification('success', $message);
    }

    public function error(string $message): void
    {
        $this->showNotification('error', $message);
    }

    public function warning(string $message): void
    {
        $this->showNotification('warning', $message);
    }

    public function info(string $message): void
    {
        $this->showNotification('info', $message);
    }
}
