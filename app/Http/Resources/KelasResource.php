<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'guru' => $this->whenLoaded('guru', fn () => [
                'id' => $this->guru->id,
                'name' => $this->guru->name,
                'email' => $this->guru->email,
            ]),
            'siswa_count' => $this->when(isset($this->siswa_count), $this->siswa_count),
            'siswa' => UserResource::collection($this->whenLoaded('siswa')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
