<?php

use Illuminate\Support\Arr;

if(!function_exists('gatherChilds')) {
    function gatherChilds($collection)
    {
        $collection = $collection->sortByDesc('level')->values()->toArray();

        foreach ($collection as $key => $child) {
            $position = collect($collection)->where('id', $child['parent_id'])->keys()->first();

            if($position) {
                if(!isset($collection[$position]['children'])) {
                    $collection[$position]['children'] = [];
                }

                array_push($collection[$position]['children'], Arr::pull($collection, $key));
            }
        }

        return collect($collection)->sort()->values();
    }
}
