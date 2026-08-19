<?php

namespace App\Http\Resources;

use App\Services\PblCaseStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PblCaseDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $imageUrl = $this->formatImageUrl($this->image_url);
        
        return [
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
            'sections' => CaseSectionResource::collection($this->whenLoaded('sections')),
            'status' => $user ? PblCaseStatusService::getStatusString($this->resource, $user) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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