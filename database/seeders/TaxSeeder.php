<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tax::create([
            'name' => 'Default Tax',
            'code' => 'VAT',
            'rate' => 0.1, // 10%
            'is_active' => true,
            'is_default' => true,
            'country' => 'Zimbabwe',
            'description' => 'Default tax rate of 10%'
        ]);
    }
}
