<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if(!isset($this->order_detail))
            $this->order_detail = null;

        return [
            'id' => $this->id,
            'unique_product' => $this->unique_product,
            'total_price' => $this->total_price,
            'placed_at' => $this->created_at,
            'first_item' => collect(json_decode($this->extra_data))->first(),
            $this->mergeWhen($this->order_detail, [
                'order_detail' => $this->order_detail ? OrderDetailResource::collection($this->order_detail) : ''
            ])
        ];
    }
}
