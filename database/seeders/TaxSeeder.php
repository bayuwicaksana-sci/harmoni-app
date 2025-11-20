<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'code' => 'PPH-21',
                'name' => 'PPh 21',
                'value' => 0.0200
            ],
            [
                'code' => 'PPH-23',
                'name' => 'PPh 23',
                'value' => 0.0200
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }
    }
}
