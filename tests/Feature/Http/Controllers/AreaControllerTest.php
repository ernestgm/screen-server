<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\AreaController
 */
class AreaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_displays_view(): void
    {
        $areas = Area::factory()->count(3)->create();

        $response = $this->get(route('area.all'));

        $response->assertOk();
        $response->assertViewIs('areas.index');
        $response->assertViewHas('area');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AreaController::class,
            'store',
            \App\Http\Requests\AreaStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $name = $this->faker->name;

        $response = $this->post(route('area.store'), [
            'name' => $name,
        ]);

        $areas = Area::query()
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $areas);
        $area = $areas->first();

        $response->assertRedirect(route('area.show', ['area' => $area]));
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $area = Area::factory()->create();
        $areas = Area::factory()->count(3)->create();

        $response = $this->get(route('area.show', $area));

        $response->assertOk();
        $response->assertViewIs('area.show');
        $response->assertViewHas('area');
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AreaController::class,
            'update',
            \App\Http\Requests\AreaUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_redirects(): void
    {
        $area = Area::factory()->create();
        $name = $this->faker->name;

        $response = $this->put(route('area.update', $area), [
            'name' => $name,
        ]);

        $area->refresh();

        $response->assertRedirect(route('area.index'));
        $response->assertSessionHas('area.id', $area->id);

        $this->assertEquals($name, $area->name);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_redirects(): void
    {
        $area = Area::factory()->create();

        $response = $this->delete(route('area.destroy', $area));

        $response->assertRedirect(route('area.index'));

        $this->assertModelMissing($area);
    }
}
