<?php

namespace Database\Factories;

use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name_ar' => 'تصنيف '.$name,
            'name_en' => Str::title($name),
            'description_ar' => null,
            'description_en' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
