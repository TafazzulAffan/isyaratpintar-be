<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionDurationResource extends JsonResource
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
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'duration_seconds' => $this->duration_seconds,
            'duration_minutes' => $this->getDurationMinutes(),
            'duration_formatted' => $this->getDurationFormatted(),
            'session_type' => $this->session_type,
            'entity_id' => $this->entity_id,
        ];
    }
}
