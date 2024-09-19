<?php

namespace Finxp\Flexcube\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;

class ApiAccessFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApiAccess::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'key' => ApiAccess::KEY_PREFIX . Str::random(33),
            'secret' => ApiAccess::SECRET_PREFIX . Str::random(33),
            'merchant_id' => Merchant::factory()->create()->id,
            'revoked' => false
        ];
    }
}
