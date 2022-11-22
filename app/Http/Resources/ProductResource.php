<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'price' => $this->price,
            'stock' => $this->stock,
            'one_liner' => $this->one_liner,
            'primary_attr' => (int) $this->primary_attr,
            'complexity' => (int) $this->complexity,
            'attack_capability' => (int) $this->attack_capability
        ];
    }
}
