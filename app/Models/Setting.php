<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Key/JSON system settings. Use Setting::get('vat') / Setting::put('vat', [...]).
class Setting extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'data', 'updated_by'];

    protected $casts = ['data' => 'array'];

    public static function get(string $key, array $default = []): array
    {
        return static::find($key)?->data ?? $default;
    }

    public static function put(string $key, array $data, ?int $uid = null): void
    {
        static::updateOrCreate(['id' => $key], ['data' => $data, 'updated_by' => $uid]);
    }
}
