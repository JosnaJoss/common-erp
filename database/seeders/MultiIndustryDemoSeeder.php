<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MultiIndustryDemoSeeder extends Seeder
{
    /**
     * Seed realistic multi-industry dummy data across 3 distinct companies:
     * 1. Elegance Boutique (Retail / Clothing / Fashion)
     * 2. Kanchinad Foods (Food Processing / Curry Masala Manufacturing)
     * 3. AquaFresh Beverages (Water Purification / Beverage Manufacturing / Distribution)
     */
    public function run(): void
    {
        $this->command->info('------------------------------------------------------------');
        $this->command->info('Starting Multi-Industry Demo Seeding for Common ERP...');
        $this->command->info('------------------------------------------------------------');

        $this->call([
            IndustryTemplatesSeeder::class,
            EleganceBoutiqueSeeder::class,
            KanchinadFoodsSeeder::class,
            AquaFreshBeveragesSeeder::class,
            CraftFurnitureSeeder::class,
        ]);

        $this->command->info('------------------------------------------------------------');
        $this->command->info('Multi-Industry Demo Seeding completed successfully!');
        $this->command->info('------------------------------------------------------------');
    }
}

