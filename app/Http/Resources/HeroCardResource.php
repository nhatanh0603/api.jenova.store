<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HeroCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $extra = $this->extra_columns([3, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 27]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'unit' => $this->unit,
            $this->mergeWhen($extra, [
                $extra[0]->name => $extra[0]->value,
                $extra[1]->name => (int) $extra[1]->value,
                $extra[2]->name => (int) $extra[2]->value,
                $extra[3]->name => (int) $extra[3]->value,
                'roles' => [
                    $extra[4]->name => (int) $extra[4]->value,
                    $extra[5]->name => (int) $extra[5]->value,
                    $extra[6]->name => (int) $extra[6]->value,
                    $extra[7]->name => (int) $extra[7]->value,
                    $extra[8]->name => (int) $extra[8]->value,
                    $extra[9]->name => (int) $extra[9]->value,
                    $extra[10]->name => (int) $extra[10]->value,
                    $extra[11]->name => (int) $extra[11]->value,
                    $extra[12]->name => (int) $extra[12]->value
                ],
                $extra[13]->name => (int) $extra[13]->value,
                $extra[14]->name => (int) $extra[14]->value,
                $extra[15]->name => (int) $extra[15]->value
            ])
        ];
    }
}
