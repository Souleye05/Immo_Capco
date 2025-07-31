<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
  use HasFactory;

  protected $fillable = [
    'key',
    'value',
    'type',
    'description',
    'group',
    'is_public',
  ];

  protected $casts = [
    'is_public' => 'boolean',
    'value' => 'json',
  ];

  // Scopes
  public function scopePublic($query)
  {
    return $query->where('is_public', true);
  }

  public function scopeByGroup($query, string $group)
  {
    return $query->where('group', $group);
  }

  public function scopeByKey($query, string $key)
  {
    return $query->where('key', $key);
  }

  // Helper methods
  public static function get(string $key, $default = null)
  {
    $setting = static::where('key', $key)->first();
    return $setting ? $setting->value : $default;
  }

  public static function set(string $key, $value, string $type = 'string', string $description = '', string $group = 'general', bool $isPublic = false)
  {
    return static::updateOrCreate(
      ['key' => $key],
      [
        'value' => $value,
        'type' => $type,
        'description' => $description,
        'group' => $group,
        'is_public' => $isPublic,
      ]
    );
  }

  public function getFormattedValueAttribute()
  {
    return match ($this->type) {
      'boolean' => $this->value ? 'Yes' : 'No',
      'json', 'array' => is_array($this->value) ? implode(', ', $this->value) : $this->value,
      'number', 'integer' => number_format($this->value),
      default => $this->value,
    };
  }
}
