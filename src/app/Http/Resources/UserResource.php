<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar_url,
            'created_at' => $this->created_at,
            'member_since' => $this->created_at ? $this->created_at->format('Y年m月') : null,
        ];
    }

}
