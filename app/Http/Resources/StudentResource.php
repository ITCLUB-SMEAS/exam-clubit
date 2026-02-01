<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'name' => $this->name,
            'nisn' => $this->nisn,
            'gender' => $this->gender,
            'gender_label' => $this->gender === 'L' ? 'Laki-laki' : 'Perempuan',
            'photo_url' => $this->photo ? asset('storage/'.$this->photo) : null,
            'is_blocked' => $this->is_blocked,
            'blocked_reason' => $this->when($this->is_blocked, $this->blocked_reason),
            'classroom' => new ClassroomResource($this->whenLoaded('classroom')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
