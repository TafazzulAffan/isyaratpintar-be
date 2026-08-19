<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseSectionItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'image_url' => $this->formatImageUrl($this->image_url),
            'order' => $this->order,
        ];
    }

    /**
     * Format image URL to relative path (e.g., /pbl/filename.png)
     */
    private function formatImageUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '/' . ltrim($url, '/');
        }

        $path = parse_url($url, PHP_URL_PATH);
        $relative = preg_replace('#^/(api/)?storage/#', '/', $path);
        return $relative;
    }
}
