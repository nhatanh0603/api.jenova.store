<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $extra = $this->extra_columns();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'price' => $this->price,
            'stock' => $this->stock,
            $this->mergeWhen($extra, [
                'one_liner' => $extra ? $extra[0]->value : null,
                'primary_attr' => $extra ? (int) $extra[1]->value : null,
                'complexity' => $extra ? (int) $extra[2]->value : null,
                'attack_type' => $extra ? (int) $extra[3]->value : null
            ])
        ];
    }
}
