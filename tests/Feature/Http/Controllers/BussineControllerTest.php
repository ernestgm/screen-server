<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\BusinessController
 */
class BussineControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_displays_view(): void
    {
        $bussines = Business::factory()->count(3)->create();

        $response = $this->get(route('bussine.index'));

        $response->assertOk();
        $response->assertViewIs('bussine.index');
        $response->assertViewHas('bussines');
    }


    /**
     * @test
     */
    public function create_displays_view(): void
    {
        $bussine = User::factory()->create();

        $response = $this->get(route('bussine.create'));

        $response->assertOk();
        $response->assertViewIs('bussine.create');
        $response->assertViewHas('user');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\BusinessController::class,
            'store',
            \App\Http\Requests\BusinessStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $name = $this->faker->name;
        $description = $this->faker->text;

        $response = $this->post(route('bussine.store'), [
            'name' => $name,
            'description' => $description,
        ]);

        $bussines = Business::query()
            ->where('name', $name)
            ->where('description', $description)
            ->get();
        $this->assertCount(1, $bussines);
        $bussine = $bussines->first();

        $response->assertRedirect(route('bussine.show', ['bussine' => $bussine]));
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $bussine = Business::factory()->create();
        $bussines = Business::factory()->count(3)->create();

        $response = $this->get(route('bussine.show', $bussine));

        $response->assertOk();
        $response->assertViewIs('bussine.show');
        $response->assertViewHas('bussine');
    }
}
