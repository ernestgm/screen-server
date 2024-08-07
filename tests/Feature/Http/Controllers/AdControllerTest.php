<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Ad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\AdController
 */
class AdControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_displays_view(): void
    {
        $ads = Ad::factory()->count(3)->create();

        $response = $this->get(route('ad.all'));

        $response->assertOk();
        $response->assertViewIs('ad.index');
        $response->assertViewHas('ad');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AdController::class,
            'store',
            \App\Http\Requests\AdStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $response = $this->post(route('ad.store'));

        $response->assertRedirect(route('ad.show', ['ad' => $ad]));

        $this->assertDatabaseHas(ads, [ /* ... */ ]);
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $ad = Ad::factory()->create();
        $ads = Ad::factory()->count(3)->create();

        $response = $this->get(route('ad.show', $ad));

        $response->assertOk();
        $response->assertViewIs('ads.show');
        $response->assertViewHas('ads');
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AdController::class,
            'update',
            \App\Http\Requests\AdUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_redirects(): void
    {
        $ad = Ad::factory()->create();

        $response = $this->put(route('ad.update', $ad));

        $ad->refresh();

        $response->assertRedirect(route('ad.index'));
        $response->assertSessionHas('ad.id', $ad->id);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_redirects(): void
    {
        $ad = Ad::factory()->create();

        $response = $this->delete(route('ad.destroy', $ad));

        $response->assertRedirect(route('ad.index'));

        $this->assertModelMissing($ad);
    }
}
