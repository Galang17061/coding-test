<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $usaStates = [
            "AL" => 'Alabama',
            "AK" => 'Alaska',
            "AZ" => 'Arizona',
            "AR" => 'Arkansas',
            "CA" => 'California',
        ];

        $countries = [
            [
                'uuid' => Str::uuid(),
                'code' => 'geo',
                'name' => 'Georgia',
                'states' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'ind',
                'name' => 'India',
                'states' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'usa',
                'name' => 'United States of America',
                'states' => json_encode($usaStates),
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'ger',
                'name' => 'Germany',
                'states' => null,
            ],
        ];

        Country::insert($countries);
    }
}
