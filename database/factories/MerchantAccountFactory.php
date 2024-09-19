<?php

namespace Finxp\Flexcube\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;

class MerchantAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MerchantAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'merchant_id' => Merchant::factory()->create()->id,
            'account_number' => $this->faker->creditCardNumber(),
            'iban_number' => 'MT' . $this->faker->unique()->randomNumber,
            'account_desc' => $this->faker->company,
            'is_notification_active' => 0
        ];
    }
}
