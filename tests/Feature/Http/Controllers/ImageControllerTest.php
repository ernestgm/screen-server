<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ImageController
 */
class ImageControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_responds_with(): void
    {
        $images = Image::factory()->count(3)->create();

        $response = $this->get(route('image.all'));

        $response->assertOk();
        $response->assertJson($image);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ImageController::class,
            'store',
            \App\Http\Requests\ImageStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_responds_with(): void
    {
        $response = $this->post(route('image.store'));

        $response->assertOk();
        $response->assertJson($image);

        $this->assertDatabaseHas(images, [ /* ... */ ]);
    }


    /**
     * @test
     */
    public function show_responds_with(): void
    {
        $image = Image::factory()->create();
        $images = Image::factory()->count(3)->create();

        $response = $this->get(route('image.show', $image));

        $response->assertOk();
        $response->assertJson($image);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ImageController::class,
            'update',
            \App\Http\Requests\ImageUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_responds_with(): void
    {
        $image = Image::factory()->create();

        $response = $this->put(route('image.update', $image));

        $image->refresh();

        $response->assertOk();
        $response->assertJson($image);
        $response->assertSessionHas('image.id', $image->id);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with(): void
    {
        $image = Image::factory()->create();

        $response = $this->delete(route('image.destroy', $image));

        $response->assertOk();
        $response->assertJson($screen);

        $this->assertModelMissing($image);
    }
}
