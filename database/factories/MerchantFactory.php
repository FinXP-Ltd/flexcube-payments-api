<?php

namespace Finxp\Flexcube\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;

class MerchantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Merchant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->company;
        return [
            'uuid' => Str::uuid()->toString(),
            'slug' => Str::slug($name, '-'),
            'name' => $name,
            'point_of_contact' => $this->faker->name,
            'street' => $this->faker->streetAddress,
            'unit_no' => $this->faker->buildingNumber,
            'city' => $this->faker->city,
            'country' => $this->faker->country,
            'postal' => $this->faker->postcode,
            'is_active' => 1,
            'is_iso' => 1,
            'customer_number' => $this->faker->unique()->randomNumber
        ];
    }
}
