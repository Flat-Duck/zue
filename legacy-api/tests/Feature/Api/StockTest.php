<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Stock;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($user, [], 'web');

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_gets_stocks_list(): void
    {
        $stocks = Stock::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.stocks.index'));

        $response->assertOk()->assertSee($stocks[0]->id);
    }

    /**
     * @test
     */
    public function it_stores_the_stock(): void
    {
        $data = Stock::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.stocks.store'), $data);

        $this->assertDatabaseHas('stocks', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_stock(): void
    {
        $stock = Stock::factory()->create();

        $data = [];

        $response = $this->putJson(route('api.stocks.update', $stock), $data);

        $data['id'] = $stock->id;

        $this->assertDatabaseHas('stocks', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_stock(): void
    {
        $stock = Stock::factory()->create();

        $response = $this->deleteJson(route('api.stocks.destroy', $stock));

        $this->assertModelMissing($stock);

        $response->assertNoContent();
    }
}
