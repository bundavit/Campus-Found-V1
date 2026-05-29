<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ItemDataService
{
    public function all(): array
    {
        return Item::query()
            ->orderByDesc('reported_at')
            ->get()
            ->map(fn (Item $item) => $item->toLegacyArray())
            ->all();
    }

    public function find(string $id): ?array
    {
        $item = Item::find($id);

        return $item?->toLegacyArray();
    }

    public function recent(int $limit = 4): array
    {
        return Item::query()
            ->orderByDesc('reported_at')
            ->limit($limit)
            ->get()
            ->map(fn (Item $item) => $item->toLegacyArray())
            ->all();
    }

    public function filtered(array $filters = []): array
    {
        $query = Item::query();

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['date'])) {
            $query->whereDate('reported_at', $filters['date']);
        }

        $direction = ($filters['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy('reported_at', $direction)
            ->get()
            ->map(fn (Item $item) => $item->toLegacyArray())
            ->all();
    }

    public function create(array $data, ?UploadedFile $image = null): array
    {
        [$imageUrl, $imagePath] = $this->storeImage($image);

        $item = Item::create([
            'title' => $data['title'],
            'status' => $data['status'],
            'reported_at' => Carbon::parse($data['created_at']),
            'location' => $data['location'],
            'contact_info' => $data['contact_info'],
            'description' => $data['description'] ?? '',
            'image_url' => $imageUrl,
            'image_path' => $imagePath,
        ]);

        return $item->toLegacyArray();
    }

    public function delete(string $id): bool
    {
        $item = Item::find($id);
        if (! $item) {
            return false;
        }

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return true;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeImage(?UploadedFile $image): array
    {
        if (! $image) {
            return ['', ''];
        }

        $path = $image->store('items', 'public');

        return [asset('storage/'.$path), $path];
    }
}
