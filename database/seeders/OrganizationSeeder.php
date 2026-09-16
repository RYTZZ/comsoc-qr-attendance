<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            'SorSU - Bulan Campus',
            'Bicol University',
            'Sorsogon State University',
        ];

        foreach ($schools as $school) {
            Organization::firstOrCreate(
                ['name' => $school],
                ['is_active' => true]
            );
        }
    }
}
