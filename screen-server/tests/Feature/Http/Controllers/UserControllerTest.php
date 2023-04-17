<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\UserController
 */
class UserControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_displays_view(): void
    {
        $users = User::factory()->count(3)->create();

        $response = $this->get(route('user.index'));

        $response->assertOk();
        $response->assertViewIs('user.index');
        $response->assertViewHas('bussines');
    }


    /**
     * @test
     */
    public function create_displays_view(): void
    {
        $response = $this->get(route('user.create'));

        $response->assertOk();
        $response->assertViewIs('user.create');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\UserController::class,
            'store',
            \App\Http\Requests\UserStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $username = $this->faker->userName;
        $name = $this->faker->name;
        $lastname = $this->faker->lastName;

        $response = $this->post(route('user.store'), [
            'username' => $username,
            'name' => $name,
            'lastname' => $lastname,
        ]);

        $users = User::query()
            ->where('username', $username)
            ->where('name', $name)
            ->where('lastname', $lastname)
            ->get();
        $this->assertCount(1, $users);
        $user = $users->first();

        $response->assertRedirect(route('user.show', ['user' => $user]));
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $user = User::factory()->create();
        $users = User::factory()->count(3)->create();

        $response = $this->get(route('user.show', $user));

        $response->assertOk();
        $response->assertViewIs('user.show');
        $response->assertViewHas('user');
    }
}
