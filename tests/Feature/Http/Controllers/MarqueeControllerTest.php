<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Marquee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\MarqueeController
 */
class MarqueeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_displays_view(): void
    {
        $marquees = Marquee::factory()->count(3)->create();

        $response = $this->get(route('marquee.all'));

        $response->assertOk();
        $response->assertViewIs('marquee.index');
        $response->assertViewHas('marquee');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MarqueeController::class,
            'store',
            \App\Http\Requests\MarqueeStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $response = $this->post(route('marquee.store'));

        $response->assertRedirect(route('marquee.show', ['marquee' => $marquee]));

        $this->assertDatabaseHas(marquees, [ /* ... */ ]);
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $marquee = Marquee::factory()->create();
        $marquees = Marquee::factory()->count(3)->create();

        $response = $this->get(route('marquee.show', $marquee));

        $response->assertOk();
        $response->assertViewIs('marquee.show');
        $response->assertViewHas('marquee');
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MarqueeController::class,
            'update',
            \App\Http\Requests\MarqueeUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_redirects(): void
    {
        $marquee = Marquee::factory()->create();

        $response = $this->put(route('marquee.update', $marquee));

        $marquee->refresh();

        $response->assertRedirect(route('marquee.index'));
        $response->assertSessionHas('marquee.id', $marquee->id);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_redirects(): void
    {
        $marquee = Marquee::factory()->create();

        $response = $this->delete(route('marquee.destroy', $marquee));

        $response->assertRedirect(route('marquee.index'));

        $this->assertModelMissing($marquee);
    }
}
