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
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'case_number' => $this->case_number,
            'title' => $this->title,
            'mata_pelajaran_id' => $this->mata_pelajaran_id,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'time_limit' => $this->time_limit,
            'start_date' => $this->start_date,
            'deadline' => $this->deadline,
            'mata_pelajaran' => new MataPelajaranResource($this->whenLoaded('mataPelajaran')),
        ];

        // Add status if user is provided or in request context
        if ($this->user || auth()->check()) {
            $user = $this->user ?? auth()->user();
            $data['status'] = PblCaseStatusService::getStatusString($this->resource, $user);
        }

        return $data;
    }
}
