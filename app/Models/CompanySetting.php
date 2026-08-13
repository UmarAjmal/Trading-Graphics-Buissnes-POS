<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_name',
        'tagline',
        'logo_path',
        'phone_1',
        'phone_2',
        'whatsapp_number',
        'email',
        'address',
        'website',
        'ntn',
        'sales_tax_no',
        'currency',
        'invoice_prefix',
        'footer_note',
        'print_footer_message',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['logo_url'];

    /**
     * Get the logo URL attribute.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    /**
     * Get the company settings singleton.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'company_name' => 'Your Company Name',
            'currency' => 'PKR',
            'invoice_prefix' => 'INV-',
        ]);
    }

    /**
     * Update or create company settings.
     */
    public static function updateSettings(array $data): self
    {
        $settings = static::current();
        $settings->update($data);
        
        return $settings;
    }
}
