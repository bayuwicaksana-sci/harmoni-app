<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'PT Pertamina',
                'code' => 'DMF',
            ],
            [
                'name' => 'PT Bank Mandiri',
                'code' => 'BM',
            ],
            [
                'name' => 'PT Telkom Indonesia',
                'code' => 'TLK',
            ],
            [
                'name' => 'PT Unilever Indonesia Tbk',
                'code' => 'UNI',
            ],
            [
                'name' => 'PT Astra International Tbk',
                'code' => 'AST',
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
