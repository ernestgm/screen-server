<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ProductController
 */
class ProductControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_responds_with(): void
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->get(route('product.all'));

        $response->assertOk();
        $response->assertJson($product);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductController::class,
            'store',
            \App\Http\Requests\ProductStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_responds_with(): void
    {
        $name = $this->faker->name;
        $description = $this->faker->text;

        $response = $this->post(route('product.store'), [
            'name' => $name,
            'description' => $description,
        ]);

        $products = Product::query()
            ->where('name', $name)
            ->where('description', $description)
            ->get();
        $this->assertCount(1, $products);
        $product = $products->first();

        $response->assertOk();
        $response->assertJson($product);
    }


    /**
     * @test
     */
    public function show_responds_with(): void
    {
        $product = Product::factory()->create();
        $products = Product::factory()->count(3)->create();

        $response = $this->get(route('product.show', $product));

        $response->assertOk();
        $response->assertJson($product);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductController::class,
            'update',
            \App\Http\Requests\ProductUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_responds_with(): void
    {
        $product = Product::factory()->create();
        $name = $this->faker->name;
        $description = $this->faker->text;
        $image = $this->faker->text;

        $response = $this->put(route('product.update', $product), [
            'name' => $name,
            'description' => $description,
            'image' => $image,
        ]);

        $product->refresh();

        $response->assertOk();
        $response->assertJson($product);
        $response->assertSessionHas('product.id', $product->id);

        $this->assertEquals($name, $product->name);
        $this->assertEquals($description, $product->description);
        $this->assertEquals($image, $product->image);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with(): void
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('product.destroy', $product));

        $response->assertOk();
        $response->assertJson($product);

        $this->assertModelMissing($product);
    }
}
