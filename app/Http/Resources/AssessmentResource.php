<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
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
            'mata_pelajaran_id' => $this->mata_pelajaran_id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'time_limit' => $this->time_limit,
            'total_questions' => $this->questions_count ?? $this->questions()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
