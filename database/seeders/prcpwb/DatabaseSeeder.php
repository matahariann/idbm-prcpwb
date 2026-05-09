<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            VendorSeeder::class,
            StockSeeder::class,
            OrderSeeder::class,
            OrderLineSeeder::class,
            ForecastSeeder::class,
            ForecastLineSeeder::class,
            DailyRequestSeeder::class,
            DailyRequestLineSeeder::class,
            DeletedDailyRequestSeeder::class,
        ]);
    }
}
