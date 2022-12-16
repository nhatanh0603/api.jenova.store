<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {   $body = $this->body;
        $begin = strpos($body, "alt=");
        $end = strpos($body, "\">", $begin);

        return [
            'slug' => $this->slug,
            'headline' => $this->headline,
            'main_img' => substr($body, $begin + 5, $end - 5 - $begin),
            'description' => $this->description,
            //'begin' => $begin,
            //'end' => $end,
            //'body' => html_entity_decode($this->body),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
