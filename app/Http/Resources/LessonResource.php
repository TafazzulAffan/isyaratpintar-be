<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'slug' => $this->slug,
            'mata_pelajaran_id' => $this->mata_pelajaran_id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'file_url' => $this->generateFileUrl($this->file_url),
            'resume' => $this->resume,
            'completed' => (bool) ($this->completed ?? false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Generate path untuk akses file (hanya path tanpa /storage)
     */
    private function generateFileUrl(?string $fileUrl): ?string
    {
        if (empty($fileUrl)) {
            return null;
        }

        // ✅ Jika sudah full URL, extract path saja
        if (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($fileUrl);
            $path = $parsedUrl['path'] ?? $fileUrl;
        } else {
            $path = $fileUrl;
        }

        // Hapus /storage/ dari path
        return str_replace('/storage/', '/', $path);
    }
}
