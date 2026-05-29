<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'title',
        'status',
        'location',
        'contact_info',
        'description',
        'image_url',
        'image_path',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    public function displayImageUrl(): string
    {
        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        return $this->image_url ?? '';
    }

    public function toLegacyArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'created_at' => $this->reported_at?->toIso8601String() ?? $this->created_at->toIso8601String(),
            'location' => $this->location,
            'contact_info' => $this->contact_info,
            'description' => $this->description ?? '',
            'image_url' => $this->displayImageUrl(),
            'image_path' => $this->image_path ?? '',
        ];
    }
}
