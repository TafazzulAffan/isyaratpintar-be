<?php

namespace App\Http\Resources;

use App\Services\PblCaseStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PblCaseResource extends JsonResource
{
    private ?object $user = null;

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Format image_url menjadi path relatif (contoh: /pbl/abc.png)
        $imageUrl = $this->formatImageUrl($this->image_url);

        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'case_number' => $this->case_number,
            'title' => $this->title,
            'mata_pelajaran_id' => $this->mata_pelajaran_id,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'time_limit' => $this->time_limit,
            'start_date' => $this->start_date,
            'deadline' => $this->deadline,
            'mata_pelajaran' => new MataPelajaranResource($this->whenLoaded('mataPelajaran')),
        ];

        if ($this->user || auth()->check()) {
            $user = $this->user ?? auth()->user();
            $data['status'] = PblCaseStatusService::getStatusString($this->resource, $user);
        }

        return $data;
    }

    /**
     * Format image URL to relative path (e.g., /pbl/filename.png)
     */
    private function formatImageUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Jika sudah berupa path relatif (tidak mengandung http)
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '/' . ltrim($url, '/');
        }

        // Ekstrak path dari URL lengkap
        $path = parse_url($url, PHP_URL_PATH);
        // Hapus prefix /storage/ atau /api/storage/ -> menghasilkan /pbl/...
        $relative = preg_replace('#^/(api/)?storage/#', '/', $path);
        return $relative;
    }
}