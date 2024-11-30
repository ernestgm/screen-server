<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\LogController
 */
class LogControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_displays_view(): void
    {
        $logs = Log::factory()->count(3)->create();

        $response = $this->get(route('log.index'));

        $response->assertOk();
        $response->assertViewIs('log.index');
        $response->assertViewHas('log');
    }


    /**
     * @test
     */
    public function create_displays_view(): void
    {
        $response = $this->get(route('log.create'));

        $response->assertOk();
        $response->assertViewIs('log.create');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\LogController::class,
            'store',
            \App\Http\Requests\LogStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects(): void
    {
        $response = $this->post(route('log.store'));

        $response->assertRedirect(route('log.show', ['log' => $log]));

        $this->assertDatabaseHas(logs, [ /* ... */]);
    }


    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $log = Log::factory()->create();
        $logs = Log::factory()->count(3)->create();

        $response = $this->get(route('log.show', $log));

        $response->assertOk();
        $response->assertViewIs('log.show');
        $response->assertViewHas('log');
    }
}
