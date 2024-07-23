<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use App\Models\Devices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DevicesController
 */
class DevicesControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function all_responds_with(): void
    {
        $devices = Devices::factory()->count(3)->create();

        $response = $this->get(route('device.all'));

        $response->assertOk();
        $response->assertJson($device);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DevicesController::class,
            'store',
            \App\Http\Requests\DevicesStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_responds_with(): void
    {
        $response = $this->post(route('device.store'));

        $response->assertOk();
        $response->assertJson($device);

        $this->assertDatabaseHas(devices, [ /* ... */ ]);
    }


    /**
     * @test
     */
    public function show_responds_with(): void
    {
        $device = Devices::factory()->create();
        $devices = Devices::factory()->count(3)->create();

        $response = $this->get(route('device.show', $device));

        $response->assertOk();
        $response->assertJson($device);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DevicesController::class,
            'update',
            \App\Http\Requests\DevicesUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_responds_with(): void
    {
        $device = Devices::factory()->create();

        $response = $this->put(route('device.update', $device));

        $device->refresh();

        $response->assertOk();
        $response->assertJson($device);
        $response->assertSessionHas('device.id', $device->id);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with(): void
    {
        $device = Devices::factory()->create();
        $device = Device::factory()->create();

        $response = $this->delete(route('device.destroy', $device));

        $response->assertOk();
        $response->assertJson($device);

        $this->assertModelMissing($device);
    }
}
