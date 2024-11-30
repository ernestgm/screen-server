<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Qr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\QrController
 */
final class QrControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function all_displays_view(): void
    {
        $qrs = Qr::factory()->count(3)->create();

        $response = $this->get(route('qrs.all'));

        $response->assertOk();
        $response->assertViewIs('qr.index');
        $response->assertViewHas('qr');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\QrController::class,
            'store',
            \App\Http\Requests\QrStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $response = $this->post(route('qrs.store'));

        $response->assertRedirect(route('qr.show', ['qr' => $qr]));

        $this->assertDatabaseHas(qrs, [ /* ... */]);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $qr = Qr::factory()->create();
        $qrs = Qr::factory()->count(3)->create();

        $response = $this->get(route('qrs.show', $qr));

        $response->assertOk();
        $response->assertViewIs('qr.show');
        $response->assertViewHas('qr');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\QrController::class,
            'update',
            \App\Http\Requests\QrUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $qr = Qr::factory()->create();

        $response = $this->put(route('qrs.update', $qr));

        $qr->refresh();

        $response->assertRedirect(route('qr.index'));
        $response->assertSessionHas('qr.id', $qr->id);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $qr = Qr::factory()->create();

        $response = $this->delete(route('qrs.destroy', $qr));

        $response->assertRedirect(route('qr.index'));

        $this->assertModelMissing($qr);
    }
}
