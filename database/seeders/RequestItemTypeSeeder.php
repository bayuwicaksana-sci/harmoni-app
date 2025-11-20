<?php

namespace Database\Seeders;

use App\Models\RequestItemType;
use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequestItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pph21 = Tax::where('code', 'PPH-21')->first();
        $pph23 = Tax::where('code', 'PPH-23')->first();
        $types = [
            [
                'name' => 'Professional Services',
                'tax_id' => $pph21->id,
            ],
            [
                'name' => 'Consultant Fee',
                'tax_id' => $pph23->id,
            ],
            [
                'name' => 'Equipment & Material Purchase',
                'tax_id' => $pph23->id,
            ],
            [
                'name' => 'Rental Services',
                'tax_id' => $pph23->id,
            ],
            [
                'name' => 'Event Organizer',
                'tax_id' => $pph23->id,
            ],
            [
                'name' => 'Transportation & Logistics',
                'tax_id' => $pph23->id,
            ],
            [
                'name' => 'Training & Education',
                'tax_id' => $pph23->id,
            ],
        ];

        foreach ($types as $type) {
            RequestItemType::create($type);
        }
    }
}
