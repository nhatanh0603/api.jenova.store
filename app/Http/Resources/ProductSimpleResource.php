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
            $this->mergeWhen($this->pivot->quantity, [
                'quantity' => $this->pivot->quantity
            ]),
            $this->mergeWhen($extra, [
                $extra[0]->name => $extra[0]->value,
                $extra[1]->name => (int) $extra[1]->value,
                $extra[2]->name => (int) $extra[2]->value,
                $extra[3]->name => (int) $extra[3]->value
            ])
        ];
    }
}
