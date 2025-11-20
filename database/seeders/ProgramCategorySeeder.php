<?php

namespace Database\Seeders;

use App\Models\ProgramCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kesehatan',
                'code' => 'HEALTH',
            ],
            [
                'name' => 'Pendidikan',
                'code' => 'EDU',
            ],
            [
                'name' => 'Ekonomi',
                'code' => 'ECON',
            ],
            [
                'name' => 'Lingkungan',
                'code' => 'ENV',
            ],
            [
                'name' => 'Sosial dan Budaya',
                'code' => 'SOCULT',
            ],
        ];

        foreach ($categories as $category) {
            // DB::table('program_categories')->insert([
            //     ...$category,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);
            ProgramCategory::create($category);
        }
    }
}
