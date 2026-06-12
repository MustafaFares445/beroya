<?php

namespace Database\Seeders;

use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use App\Models\Province;
use App\Support\RealEstate;
use Illuminate\Database\Seeder;

class RealEstateLookupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RealEstate::provinceNames() as $provinceName) {
            Province::query()->updateOrCreate([
                'name' => $provinceName,
            ], [
                'is_active' => true,
            ]);
        }

        foreach (RealEstate::taxonomy() as $categoryName => $subcategoryNames) {
            $propertyCategory = PropertyCategory::query()->updateOrCreate([
                'name' => $categoryName,
            ]);

            foreach ($subcategoryNames as $subcategoryName) {
                PropertySubcategory::query()->updateOrCreate([
                    'property_category_id' => $propertyCategory->id,
                    'name' => $subcategoryName,
                ]);
            }
        }
    }
}
