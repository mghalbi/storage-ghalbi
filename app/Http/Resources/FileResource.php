<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public static $wrap = false;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "parent_id" => $this->parent_id,
            "is_folder" => $this->isfolder,
            "mime" => $this->mime,
            "size" => $this->getFileSize(),
            "path" => $this->path,
            "owner" => $this->owner,
            "created_at" => $this->created_at->diffForHumans(),
            "updated_at" => $this->updated_at->diffForHumans(),
            "created_by" => $this->created_by,
            "updated_by" => $this->update_by,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
