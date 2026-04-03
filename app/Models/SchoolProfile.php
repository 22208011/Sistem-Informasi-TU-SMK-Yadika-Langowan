<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'npsn',
        'name',
        'status',
        'accreditation',
        'address',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'maps_url',
        'phone',
        'whatsapp_1',
        'whatsapp_1_name',
        'whatsapp_2',
        'whatsapp_2_name',
        'fax',
        'email',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'tiktok',
        'operational_days',
        'operational_start',
        'operational_end',
        'timezone',
        'principal_name',
        'principal_nip',
        'logo',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'operational_start' => 'datetime:H:i',
            'operational_end' => 'datetime:H:i',
        ];
    }

    /**
     * Get the first school profile (singleton pattern)
     */
    public static function getProfile(): ?self
    {
        return static::first();
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village,
            $this->district,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/'.$this->logo);
        }

        return null;
    }

    /**
     * Get Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return $this->maps_url;
    }

    /**
     * Get WhatsApp chat URL for contact 1
     */
    public function getWhatsapp1UrlAttribute(): ?string
    {
        if ($this->whatsapp_1) {
            $phone = preg_replace('/[^0-9]/', '', $this->whatsapp_1);
            if (str_starts_with($phone, '0')) {
                $phone = '62'.substr($phone, 1);
            }

            return "https://wa.me/{$phone}";
        }

        return null;
    }

    /**
     * Get WhatsApp chat URL for contact 2
     */
    public function getWhatsapp2UrlAttribute(): ?string
    {
        if ($this->whatsapp_2) {
            $phone = preg_replace('/[^0-9]/', '', $this->whatsapp_2);
            if (str_starts_with($phone, '0')) {
                $phone = '62'.substr($phone, 1);
            }

            return "https://wa.me/{$phone}";
        }

        return null;
    }

    /**
     * Get formatted operational hours
     */
    public function getOperationalHoursAttribute(): ?string
    {
        if ($this->operational_start && $this->operational_end) {
            $start = $this->operational_start->format('H:i');
            $end = $this->operational_end->format('H:i');

            return "{$start} - {$end} {$this->timezone}";
        }

        return null;
    }

    /**
     * Get Instagram URL
     */
    public function getInstagramUrlAttribute(): ?string
    {
        if ($this->instagram) {
            $handle = ltrim($this->instagram, '@');

            return "https://instagram.com/{$handle}";
        }

        return null;
    }

    /**
     * Get Facebook URL
     */
    public function getFacebookUrlAttribute(): ?string
    {
        if ($this->facebook) {
            if (str_contains($this->facebook, 'facebook.com')) {
                return $this->facebook;
            }

            return "https://facebook.com/{$this->facebook}";
        }

        return null;
    }
}
