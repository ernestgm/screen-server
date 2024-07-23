<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Screen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ScreenController
 */
class ScreenControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_responds_with(): void
    {
        $screens = Screen::factory()->count(3)->create();

        $response = $this->get(route('screen.all'));

        $response->assertOk();
        $response->assertJson($screen);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ScreenController::class,
            'store',
            \App\Http\Requests\ScreenStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_responds_with(): void
    {
        $name = $this->faker->name;
        $description = $this->faker->text;

        $response = $this->post(route('screen.store'), [
            'name' => $name,
            'description' => $description,
        ]);

        $screens = Screen::query()
            ->where('name', $name)
            ->where('description', $description)
            ->get();
        $this->assertCount(1, $screens);
        $screen = $screens->first();

        $response->assertOk();
        $response->assertJson($screen);
    }


    /**
     * @test
     */
    public function show_responds_with(): void
    {
        $screen = Screen::factory()->create();
        $screens = Screen::factory()->count(3)->create();

        $response = $this->get(route('screen.show', $screen));

        $response->assertOk();
        $response->assertJson($screen);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ScreenController::class,
            'update',
            \App\Http\Requests\ScreenUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_responds_with(): void
    {
        $screen = Screen::factory()->create();
        $name = $this->faker->name;
        $description = $this->faker->text;

        $response = $this->put(route('screen.update', $screen), [
            'name' => $name,
            'description' => $description,
        ]);

        $screen->refresh();

        $response->assertOk();
        $response->assertJson($screen);
        $response->assertSessionHas('screen.id', $screen->id);

        $this->assertEquals($name, $screen->name);
        $this->assertEquals($description, $screen->description);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with(): void
    {
        $screen = Screen::factory()->create();

        $response = $this->delete(route('screen.destroy', $screen));

        $response->assertOk();
        $response->assertJson($screen);

        $this->assertModelMissing($screen);
    }
}
