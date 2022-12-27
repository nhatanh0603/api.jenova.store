<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateController extends Controller
{
    /* GENERATE MEDIA LINK */
    public function generateMediaLinkForDownload($type)
    {
        return $this->generateLinks($type);
    }

    /* GENERATE HERO DETAIL */
    public function generateHeroDetail($id, $ability_only = false)
    {
        $heroes = json_decode(Storage::get('heroes-detail.json') ?: $this->crawlHeroesDetail());

        return $ability_only ? $heroes[$id]->abilities : $heroes[$id];
    }

    public function generateNews()
    {
        return $this->crawlNews();
    }

    /* GENERATE SEEDER ARRAY FILE FOR INSERT */
    public function generateSeeder($type)
    {
        switch ($type) {
            case 'product':
                return $this->storeProductSeederFile();
                break;

            case 'attribute':
                return $this->storeAttributeSeederFile();
                break;

            case 'attribute-product':
                return $this->storeAttributeProductSeederFile();
                break;

            case 'category':
                return $this->storeCategorySeederFile();
                break;

            case 'category-product':
                return $this->storeCategoryProductSeederFile();
                break;

            case 'news':
                return $this->crawlAndStoreNews();

            default:
                return response()->json([
                    'message' => 'type = product, attribute, attribute-product, category, category-product, news'
                ]);
                break;
        }
    }

    /* TẠO DANH SÁCH LINK DOWNLOAD CỦA ẢNH VÀ VIDEO CỦA HERO */
    protected function generateLinks($media_type)
    {
        $target = 'https://cdn.cloudflare.steamstatic.com/apps/dota2';
        $heroes_list = json_decode(Storage::get('heroes-list.json') ?: $this->crawlHeroesList());

        switch ($media_type) {
            case 'image':
                $target .= '/images/dota_react/heroes/';
                $extension = '.png';
                break;

            case 'ability':
                $target .= '/images/dota_react/abilities/';
                return $this->crawlAbilityImageLink($target);
                break;

            case 'poster':
                $target .= '/videos/dota_react/heroes/renders/';
                $extension = '.png';
                break;

            case 'video':
                $target .= '/videos/dota_react/heroes/renders/';
                $extension = '.webm';
                break;

            default:
                return response()->json([
                    'message' => 'media_type = image, ability, poster, video'
                ]);
                break;
        }

        foreach ($heroes_list as $key => $hero) {
            $name = explode('npc_dota_hero_', $hero->name)[1];

            $list_of_link[$key] = $target . $name . $extension;
        }

        return $list_of_link;
    }

    /* TẠO DANH SÁCH LINK DOWNLOAD CỦA ABILITY IMAGE CỦA HERO */
    protected function crawlAbilityImageLink($target)
    {
        $heroes = json_decode(Storage::get('heroes-detail.json'));

        if(!$heroes) {
            $heroes = $this->crawlHeroesDetail();
        }

        $count = 0;

        foreach ($heroes as $hero) {
            foreach ($hero->abilities as $ability) {
                $ability_links[$count] = $target . $ability->name . '.png';
                $count++;
            }
        }

        return $ability_links;
    }

    /* TẠO ARRAY ĐỂ INSERT VÀO TABLE ATTRIBUTE_PRODUCTS */
    protected function generateAttributeProductSeeder()
    {
        $heroes = json_decode(Storage::get('heroes.json'), true);
        $attributes = json_decode(Storage::get('attributes.json'), true);
        $ability_suffixes = json_decode(Storage::get('ability-suffix.json'), true);
        $total_counter = 0;

        foreach($heroes as $i => $hero) {
            for ($j = 0; $j < count($attributes); $j++) {
                $value = $hero[$attributes[$j]['original_name']];

                if($attributes[$j]['original_name'] == 'role_levels') {
                    $value = $hero[$attributes[$j]['original_name']][$j - 12];
                }

                $attribute_products[$total_counter++] = [
                    'product_id' => $i + 1,
                    'attribute_id' => $j + 1,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            $ability_counter = $j + 1;

            for ($k = 0; $k < count($hero['abilities']); $k++) {
                $l = 0;

                while ($l < 10) {
                    $value = $hero['abilities'][$k][$ability_suffixes[$l]['original_name']];

                    $l++;

                    $attribute_products[$total_counter++] = [
                        'product_id' => $i + 1,
                        'attribute_id' => $ability_counter++,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
        }

        Storage::put('attribute-product-seeder.json', json_encode($attribute_products));
    }

    /* CRAWL HEROES LIST */
    protected function crawlHeroesList()
    {

        $heroes_list = Http::get('https://www.dota2.com/datafeed/herolist?language=english')
                            ->object()->result->data->heroes;

        Storage::put('heroes-list.json', json_encode($heroes_list));

        return $heroes_list;
    }

    /* CRAWL HEROES DETAIL */
    protected function crawlHeroesDetail()
    {
        $heroes_list = $this->crawlHeroesList();

        foreach ($heroes_list as $key => $value) {
            $response = Http::get('https://www.dota2.com/datafeed/herodata?language=english&hero_id=' . $value->id);

            $result[$key] = $response->object()->result->data->heroes[0];
        }

        foreach ($result as $key => $hero) {
            $result[$key] = $this->replaceAbilitySpecial($hero);
        }

        Storage::put('heroes-detail.json', json_encode($result));

        return $result;
    }

    /* CRAWL NEWS */
    protected function crawlAndStoreNews()
    {
        $response = Http::get('https://store.steampowered.com/events/ajaxgetpartnereventspageable/?clan_accountid=0&appid=570&offset=0&count=100&l=english&origin=https:%2F%2Fwww.dota2.com')->object();

        //return str_contains($response->events[0]->announcement_body->body, '{STEAM_CLAN_IMAGE}');

        $result = [];
        $img_link = [];
        $index = 0;

        foreach ($response->events as $key => $event) {
            if(str_contains($event->announcement_body->body, '{STEAM_CLAN_IMAGE}')) {
                $temp = $this->replaceTag($event->announcement_body->body);
                $img_link[] = $temp['img_link'];
                $result[$index]['slug'] = Str::slug($event->announcement_body->headline, '-') . '-' . $event->announcement_body->posttime;
                $result[$index]['headline'] = $event->announcement_body->headline;
                $result[$index]['description'] = $temp['description'];
                $result[$index]['body'] = $temp['body'];
                $result[$index]['created_at'] = date("Y-m-d H:i:s", $event->announcement_body->posttime);
                $result[$index]['updated_at'] = date("Y-m-d H:i:s", $event->announcement_body->updatetime);
                $index++;
                // if($event->announcement_body->headline == 'Dota Plus Update — Winter 2022') {
                //     $result[$index]['body'] = $this->replaceTag($event->announcement_body->body);
                // }
            }
        }

        Storage::put('news-image-link-download.txt', implode("\n", collect($img_link)->flatten()->unique()->values()->toArray()));

        Storage::put('seeder-news.json', json_encode($result));

        //print_r($result[0]['body']);
        return $result;
    }

    /* ĐỔI BIẾN TRONG LOC CỦA HERO THÀNH THÔNG SỐ */
    protected function replaceAbilitySpecial($hero)
    {
        foreach($hero->abilities as $key => $ability) {
            foreach($ability->special_values as $special) {
                $needle = '%' . $special->name . '%';
                $locs = ['desc_loc', 'lore_loc', 'shard_loc', 'scepter_loc'];

                for ($i = 0; $i < 4; $i++) {
                    if(str_contains($ability->{$locs[$i]}, $needle)) {
                        if(str_contains($ability->{$locs[$i]}, $needle . '%')) {
                            $hero->abilities[$key]->{$locs[$i]} = str_replace($needle . '%', $special->values_float[0], $hero->abilities[$key]->{$locs[$i]});
                        } else {
                            $hero->abilities[$key]->{$locs[$i]} = str_replace($needle, $special->values_float[0], $hero->abilities[$key]->{$locs[$i]});
                        }
                    }
                }
            }
        }

        return $hero;
    }

    /* GHI VÀO STORAGE DANH SÁCH INSERT ARRAY CỦA PRODUCT */
    protected function storeProductSeederFile()
    {
        $heroes = json_decode(Storage::get('heroes-detail.json'));

        if(!$heroes) {
            $heroes = $this->crawlHeroesDetail();
        }

        foreach ($heroes as $key => $hero) {
            $name = explode('npc_dota_hero_', $hero->name)[1];

            $insert_list[$key] = [
                'name' => $name == 'zuus' ? 'zeus': $name,
                'slug' => Str::slug(Str::lower($hero->name_loc, '-')),
                'display_name' => $hero->name_loc,
                //'price' => fake()->randomFloat(2, 49, 199), //rand(29, 99) + rand(10, 100)/100,
                'price' => $this->calcPrice($hero),
                'stock' => fake()->numberBetween(2000, 3000),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        // return collect($insert_list)->sortByDesc(function($hero) {
        //     return $hero['price'];
        // })->values()->all();

        return Storage::put('seeder-products.json', json_encode($insert_list));
    }

    /* GHI VÀO STORAGE DANH SÁCH INSERT ARRAY CỦA ATTRIBUTE */
    protected function storeAttributeSeederFile()
    {
        $attribute_names = json_decode(Storage::get('attribute-names.json') ?: $this->storeAttributeNames(), true);

        foreach($attribute_names as $key => $attribute) {
            $insert_array[$key] = [
                'id' => $key + 1,
                'name' => $attribute['jnv_name'],
                'parent_id' => null,
                'level' => 1,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        $ability_suffixes = json_decode(Storage::get('ability-suffixes.json') ?: $this->storeAbilitySuffix());

        $total = 14; // Tổng số skill của invoker
        $jump = count($ability_suffixes);
        $initial_id = count($attribute_names) + 1;

        for ($i = 0; $i < $total; $i++) {
            foreach ($ability_suffixes as $key => $suffix) {
                $index = $i * $jump + $key;
                $abilities[$index] = [
                    'id' => $index + $initial_id,
                    'name' => 'ability_' . ($i + 1) . $suffix->suffix,
                    'parent_id' => $key == 0 ? null : $i * $jump + $initial_id,
                    'level' => $suffix->suffix ? 2 : 1,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ];
            }
        }

        $insert_array = array_merge($insert_array, $abilities);

        return Storage::put('seeder-attributes.json', json_encode($insert_array));
    }

    /* GHI VÀO STORAGE DANH SÁCH INSERT ARRAY CỦA ATTRIBUTE_PRODUCT */
    protected function storeAttributeProductSeederFile()
    {
        $heroes = json_decode(Storage::get('heroes-detail.json') ?: $this->crawlHeroesDetail(), true);
        $attribute_names = json_decode(Storage::get('attribute-names.json') ?: $this->storeAttributeNames(), true);
        $ability_suffixes = json_decode(Storage::get('ability-suffixes.json') ?: $this->storeAbilitySuffix(), true);
        $total_counter = 0;

        foreach($heroes as $i => $hero) {
            for ($j = 0; $j < count($attribute_names); $j++) {
                $value = $hero[$attribute_names[$j]['original_name']];

                if($attribute_names[$j]['original_name'] == 'role_levels') {
                    $value = $hero[$attribute_names[$j]['original_name']][$j - 12];
                }

                $attribute_products[$total_counter++] = [
                    'product_id' => $i + 1,
                    'attribute_id' => $j + 1,
                    'value' => $value,
                    'description' => $hero['name_loc'] . ' - ' . $attribute_names[$j]['jnv_name'],
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ];
            }

            $ability_counter = $j + 1;

            for ($k = 0; $k < count($hero['abilities']); $k++) {
                $l = 0;

                while ($l < 10) {
                    $value = $hero['abilities'][$k][$ability_suffixes[$l]['original_name']];

                    $l++;

                    $attribute_products[$total_counter++] = [
                        'product_id' => $i + 1,
                        'attribute_id' => $ability_counter++,
                        'value' => $value,
                        'description' => $hero['name_loc'] . ' - Ability[' . ($k + 1) . '] - ' . $ability_suffixes[$l - 1]['original_name'],
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ];
                }
            }
        }

        return Storage::put('seeder-attribute-products.json', json_encode($attribute_products));
    }

    /* GHI VÀO STORAGE DANH SÁCH INSERT ARRAY CỦA CATEGORY */
    protected function storeCategorySeederFile()
    {
        $categories = [
            [
                'id' => 1,
                'name' => 'heroes',
                'display_name' => 'Heroes',
                'description' => 'Category: Heroes',
                'parent_id' => null,
                'level' => 1,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ],

            [
                'id' => 2,
                'name' => 'strength-heroes',
                'display_name' => 'Strength Heroes',
                'description' => 'Category: Strength Heroes',
                'parent_id' => 1,
                'level' => 2,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ],

            [
                'id' => 3,
                'name' => 'agility-heroes',
                'display_name' => 'Agility Heroes',
                'description' => 'Category: Agility Heroes',
                'parent_id' => 1,
                'level' => 2,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ],

            [
                'id' => 4,
                'name' => 'intelligence-heroes',
                'display_name' => 'Intelligence Heroes',
                'description' => 'Category: Intelligence Heroes',
                'parent_id' => 1,
                'level' => 2,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]
        ];

        return Storage::put('seeder-categories.json', json_encode($categories));
    }

    /* GHI VÀO STORAGE DANH SÁCH INSERT ARRAY CỦA CATEGORY_PRODUCT */
    protected function storeCategoryProductSeederFile()
    {
        $categories = json_decode(Storage::get('seeder-categories.json') ?: $this->storeCategorySeederFile(), true);
        $heroes = json_decode(Storage::get('heroes-detail.json') ?: $this->crawlHeroesDetail(), true);

        foreach ($heroes as $key => $hero) {
            $category_products[] = [
                'category_id' => 1,
                'product_id' => $key + 1,
                'description' => $categories[0]['description'] . ' - ' . $hero['name_loc'],
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];

            $category_key = $hero['primary_attr'] == 0 ? 1 : ($hero['primary_attr'] == 1 ? 2 : 3);

            $category_products[] = [
                'category_id' => $category_key + 1,
                'product_id' => $key + 1,
                'description' => $categories[$category_key]['description'] . ' - ' . $hero['name_loc'],
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        return Storage::put('seeder-category-products.json', json_encode($category_products));
    }

    /* GHI VÀO STORAGE DANH SÁCH TÊN ATTRIBUTE TỪ 1-36 */
    protected function storeAttributeNames()
    {
        $attributes = [
            [
                'jnv_name' => 'history',
                'original_name' => 'bio_loc'
            ],

            [
                'jnv_name' => 'lore',
                'original_name' => 'hype_loc'
            ],

            [
                'jnv_name' => 'one_liner',
                'original_name' => 'npe_desc_loc'
            ],

            [
                'jnv_name' => 'str_base',
                'original_name' => 'str_base'
            ],

            [
                'jnv_name' => 'str_gain',
                'original_name' => 'str_gain'
            ],

            [
                'jnv_name' => 'agi_base',
                'original_name' => 'agi_base'
            ],

            [
                'jnv_name' => 'agi_gain',
                'original_name' => 'agi_gain'
            ],

            [
                'jnv_name' => 'int_base',
                'original_name' => 'int_base'
            ],

            [
                'jnv_name' => 'int_gain',
                'original_name' => 'int_gain'
            ],

            [
                'jnv_name' => 'primary_attr',
                'original_name' => 'primary_attr'
            ],

            [
                'jnv_name' => 'complexity',
                'original_name' => 'complexity'
            ],

            [
                'jnv_name' => 'attack_capability',
                'original_name' => 'attack_capability'
            ],

            [
                'jnv_name' => 'carry',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'support',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'nuker',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'disabler',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'jungler',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'durable',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'escape',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'pusher',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'initiator',
                'original_name' => 'role_levels'
            ],

            [
                'jnv_name' => 'damage_min',
                'original_name' => 'damage_min'
            ],

            [
                'jnv_name' => 'damage_max',
                'original_name' => 'damage_max'
            ],

            [
                'jnv_name' => 'attack_rate',
                'original_name' => 'attack_rate'
            ],

            [
                'jnv_name' => 'attack_range',
                'original_name' => 'attack_range'
            ],

            [
                'jnv_name' => 'projectile_speed',
                'original_name' => 'projectile_speed'
            ],

            [
                'jnv_name' => 'armor',
                'original_name' => 'armor'
            ],

            [
                'jnv_name' => 'magic_resistance',
                'original_name' => 'magic_resistance'
            ],

            [
                'jnv_name' => 'movement_speed',
                'original_name' => 'movement_speed'
            ],

            [
                'jnv_name' => 'turn_rate',
                'original_name' => 'turn_rate'
            ],

            [
                'jnv_name' => 'sight_range_day',
                'original_name' => 'sight_range_day'
            ],

            [
                'jnv_name' => 'sight_range_night',
                'original_name' => 'sight_range_night'
            ],

            [
                'jnv_name' => 'max_health',
                'original_name' => 'max_health'
            ],

            [
                'jnv_name' => 'health_regen',
                'original_name' => 'health_regen'
            ],

            [
                'jnv_name' => 'max_mana',
                'original_name' => 'max_mana'
            ],

            [
                'jnv_name' => 'mana_regen',
                'original_name' => 'mana_regen'
            ]
        ];

        Storage::put('attribute-names.json', json_encode($attributes));

        return json_encode($attributes);
    }

    /* GHI VÀO STORAGE DANH SÁCH TÊN ATTRIBUTE SUFFIX CỦA CÁC ABILITY */
    protected function storeAbilitySuffix()
    {
        $ability_suffixes = [
            [
                'suffix' => '',
                'original_name' => 'name'
            ],

            [
                'suffix' => '_name',
                'original_name' => 'name_loc'
            ],

            [
                'suffix' => '_desc',
                'original_name' => 'desc_loc'
            ],

            [
                'suffix' => '_lore',
                'original_name' => 'lore_loc'
            ],

            [
                'suffix' => '_shard',
                'original_name' => 'shard_loc'
            ],

            [
                'suffix' => '_scepter',
                'original_name' => 'scepter_loc'
            ],

            [
                'suffix' => '_has_scepter',
                'original_name' => 'ability_has_scepter'
            ],

            [
                'suffix' => '_has_shard',
                'original_name' => 'ability_has_shard'
            ],

            [
                'suffix' => '_is_granted_by_scepter',
                'original_name' => 'ability_is_granted_by_scepter'
            ],

            [
                'suffix' => '_is_granted_by_shard',
                'original_name' => 'ability_is_granted_by_shard'
            ]
        ];

        Storage::put('ability-suffixes.json', json_encode($ability_suffixes));

        return json_encode($ability_suffixes);
    }

    /* REPLACE TAG IN NEWS BODY */
    protected function replaceTag($body)
    {
        $result = $body;
        $begin = false;

        /* Deal with [url] tag */
        do {
            $begin = strpos($result,'[url='); //trả về vị trí của ký tự đầu tiên
            $end = strpos($result,'[/url]');

            if($end) {
                $full_tag = substr($result, $begin, ($end + 6) - $begin); // [url=https://www.dota2.com/battlepass2022#RazorArcana]Voidstorm Asylum Razor Arcana[/url]
                $link = substr($full_tag, 5, strpos($full_tag, ']') - 5);
                $text = substr($full_tag, strpos($full_tag, ']') + 1, strpos($full_tag,'[/url]') - strpos($full_tag, ']') - 1);
                $result = substr_replace($result, '<a href="' . $link . '">' . $text . '</a>', $begin, ($end + 6) - $begin);
            }
        } while ($end);

        /* Deal with [img] tag */
        do {
            $begin = strpos($result,'[img]');
            $end = strpos($result,'[/img]');

            if($end) {
                $raw_link = substr($result, $begin + 5, $end - ($begin + 5)); // {STEAM_CLAN_IMAGE}/3703047/4e0ef9a4290fe01b814de461be569593958cd5e2.png

                $img_name = substr($raw_link, strripos($raw_link, '/') + 1);

                $result = substr_replace($result, '<img src="{JENOVA_NEWS_IMAGE}/' . $img_name . '" alt="' . $img_name . '">', $begin, ($end + 6) - $begin);

                $link_for_download[] = str_replace('{STEAM_CLAN_IMAGE}', 'https://cdn.cloudflare.steamstatic.com/steamcommunity/public/images/clans',$raw_link);
            }
        } while ($end);

        /* Deal with [table] */
        $result = str_replace("[table]\n", "<table>", $result);
        $result = str_replace("[/table]\n", "</table>", $result);
        $result = str_replace("[tr]\n", "<tr>", $result);
        $result = str_replace("[/tr]\n", "</tr>", $result);
        $result = str_replace("[th]", "<th>", $result);
        $result = str_replace("[/th]\n", "</th>", $result);
        $result = str_replace("[td]", "<td>", $result);
        $result = str_replace("[/td]\n", "</td>", $result);
        $result = str_replace("[list]\n", "<ul>", $result);
        $result = str_replace("[/list]", "</ul>", $result);
        $result = str_replace("[/list]", "</ul>", $result);
        $result = str_replace("[/list]", "</ul>", $result);

        /* Deal with [*] */
        do {
            $begin = strpos($result, "[*]");

            if(!$begin) break;

            $end = strpos($result, "\n", $begin);

            $result = substr_replace($result, "<li>", $begin, 3);
            $result = substr_replace($result, "</li>", $end + 1, 1);
        } while ($begin);

        /* Deal with \n */
        $result = str_replace("\n", "<br>", $result);
        $result = str_replace("[/", "</", $result);
        $result = str_replace("[", "<", $result);
        $result = str_replace("]", ">", $result);

        /* Filter Description */
        preg_match("/(<br>[A-Z])/", $result, $math, PREG_OFFSET_CAPTURE);
        $begin = $math[0][1];
        $temp = strip_tags(substr($result, $begin));
        $end = strpos($temp, ".");

        if(strpos($temp, "per bundle"))
            $end = strpos($temp, "per bundle") + 10;
        else if(strpos($temp, "unrivaled power"))
            $end = strpos($temp, "unrivaled power") + 15;
        else if(strpos($temp, "October 30th for $29.99"))
            $end = strpos($temp, "October 30th for $29.99") + 23;
        else if(strpos($temp, "gameplay update"))
            $end = strpos($temp, "gameplay update") + 15;
        else if(strpos($temp, "Gameplay update to the world"))
            $end = strpos($temp, "Gameplay update to the world") + 15;
        else if(strpos($temp, "Gameplay patch in today"))
            $end = strpos($temp, "Gameplay patch in today") + 14;


        $description = substr($temp, 0, $end);

        $response['body'] = $result; /* htmlentities($result) */
        $response['description'] = $description;
        $response['img_link'] = $link_for_download;

        return $response;
    }

    private $role_price = [
        [31, 49, 95], // 'carry'
        [8, 17, 32], // 'support'
        [16, 26, 45], // 'nuker'
        [7, 14, 27], // 'disabler'
        [4, 9, 19], // 'jungler'
        [9, 18, 37], // 'durable'
        [10, 19, 38], // 'escape'
        [14, 25, 45], // 'pusher'
        [11, 20, 41] // 'initiator'
    ];

    /* Calculate Hero's Price */
    protected function calcPrice($hero)
    {
        $base_price = ($hero->max_health / 100) + $hero->health_regen
                    + ($hero->max_mana / 100) + $hero->mana_regen
                    + ($hero->str_base / 10) + $hero->str_gain
                    + ($hero->agi_base / 10) + $hero->agi_gain
                    + ($hero->int_base / 10) + $hero->int_gain
                    + (fake()->numberBetween($hero->damage_min, $hero->damage_max) / 5)
                    - $hero->attack_rate + $hero->armor * 2
                    + ($hero->movement_speed / 50) - $hero->turn_rate
                    + ($hero->sight_range_day / 1000) + ($hero->sight_range_night / 1000);

        foreach ($hero->role_levels as $key => $role) {
            if($role > 0)
                $base_price += $this->role_price[$key][$role - 1];
        }

        return number_format($base_price, 2);
    }
}
