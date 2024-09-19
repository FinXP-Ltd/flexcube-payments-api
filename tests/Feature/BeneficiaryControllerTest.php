<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Database\Factories\BeneficiaryFactory;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Finxp\Flexcube\Tests\Mocks\Models\Beneficiary;

class BeneficiaryControllerTest extends TestCase
{
    /** @test */
    public function itShouldStoreBeneficiary()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $payload = array(
            'iban' => 'NL87INGB2295108179',
            'name' => 'Test User'
        );

        $res = $this->postJson(route('beneficiary.store'), $payload)
            ->assertStatus(Response::HTTP_OK);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('name', $data['data']);
    }

    /** @test */
    public function itShouldFailOnValidation()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $payload = array(
            'name' => 'Test User'
        );

        $this->postJson(route('beneficiary.store'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function itShouldGetBeneficiaries()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        Beneficiary::factory()->count(3)
            ->create([
                'user_id' => $user->id
            ]);

        $res = $this->getJson(route('beneficiary.index'))
            ->assertStatus(Response::HTTP_OK);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(sizeof($data['data']) == 3);
    }

    /** @test */
    public function itShouldGetBeneficiary()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $beneficiary = Beneficiary::factory()
            ->create([
                'user_id' => $user->id
            ]);

        $res = $this->getJson(route('beneficiary.show', ['uuid' => $beneficiary->uuid]))
            ->assertStatus(Response::HTTP_OK);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(sizeof($data['data']) > 0);
    }

    /** @test */
    public function itShouldUpdateBeneficiary()
    {
        Beneficiary::query()->forceDelete();
        $this->withoutExceptionHandling();

        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $beneficiary = Beneficiary::factory()
            ->create([
                'user_id' => $user->id,
                'iban' => 'NL46RABO5301200062'
            ]);

        $payload = array(
            'name'    => 'Updated Name',
            'bic'     => $beneficiary->bic,
            'iban'    => 'NL46RABO5301200062'
        );

        $res = $this->putJson(route('beneficiary.update', ['uuid' => $beneficiary->uuid]), $payload)
            ->assertStatus(Response::HTTP_OK);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue($data['data']['name'] === 'Updated Name');
    }

    /** @test */
    public function itShouldNotUpdateBeneficiaryIfNotFound()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $payload = array(
            'name'    => 'Updated Name',
            'bic'     => '123',
            'iban'    => 'NL87INGB2295108179'
        );

        $this->putJson(route('beneficiary.update', ['uuid' => '12323444']), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function itShouldSoftDelete()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $beneficiary = Beneficiary::factory()
            ->create([
                'user_id' => $user->id
            ]);

        $this->json(Request::METHOD_DELETE, route('beneficiary.destroy', ['uuid' => $beneficiary->uuid]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['code', 'status', 'message']);

        $this->assertSoftDeleted('beneficiaries', [
            'id' => $beneficiary->id
        ]);
    }
}