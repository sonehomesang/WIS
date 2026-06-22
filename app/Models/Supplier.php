<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'wis_id', 'slug', 'name', 'name_en', 'contact_person', 'contact_phone', 'contact_email',
        'address', 'tax_id', 'payment_terms', 'default_currency', 'vat_rate', 'notes',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean', 'vat_rate' => 'decimal:2'];

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function vatChanges(): HasMany
    {
        return $this->hasMany(SupplierVatChange::class)->orderByDesc('changed_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /**
     * Effective VAT: this supplier's own rate if set, otherwise the global setting.
     * Workflow records snapshot this at transaction time (Phase 6.7).
     *
     * @return array{rate: float, enabled: bool, source: string}
     */
    public function resolveVat(): array
    {
        if ($this->vat_rate !== null) {
            return ['rate' => (float) $this->vat_rate, 'enabled' => true, 'source' => 'supplier'];
        }

        $global = Setting::get('vat', ['rate' => 10, 'enabled' => true]);

        return [
            'rate' => (float) ($global['rate'] ?? 10),
            'enabled' => (bool) ($global['enabled'] ?? true),
            'source' => 'global',
        ];
    }
}
