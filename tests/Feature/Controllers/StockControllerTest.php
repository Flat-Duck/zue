<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Stock;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_displays_index_view_with_stocks(): void
    {
        $stocks = Stock::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('stocks.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.stocks.index')
            ->assertViewHas('stocks');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_stock(): void
    {
        $response = $this->get(route('stocks.create'));

        $response->assertOk()->assertViewIs('app.stocks.create');
    }

    /**
     * @test
     */
    public function it_stores_the_stock(): void
    {
        $data = Stock::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('stocks.store'), $data);

        $this->assertDatabaseHas('stocks', $data);

        $stock = Stock::latest('id')->first();

        $response->assertRedirect(route('stocks.edit', $stock));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_stock(): void
    {
        $stock = Stock::factory()->create();

        $response = $this->get(route('stocks.show', $stock));

        $response
            ->assertOk()
            ->assertViewIs('app.stocks.show')
            ->assertViewHas('stock');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_stock(): void
    {
        $stock = Stock::factory()->create();

        $response = $this->get(route('stocks.edit', $stock));

        $response
            ->assertOk()
            ->assertViewIs('app.stocks.edit')
            ->assertViewHas('stock');
    }

    /**
     * @test
     */
    public function it_updates_the_stock(): void
    {
        $stock = Stock::factory()->create();

        $data = [];

        $response = $this->put(route('stocks.update', $stock), $data);

        $data['id'] = $stock->id;

        $this->assertDatabaseHas('stocks', $data);

        $response->assertRedirect(route('stocks.edit', $stock));
    }

    /**
     * @test
     */
    public function it_deletes_the_stock(): void
    {
        $stock = Stock::factory()->create();

        $response = $this->delete(route('stocks.destroy', $stock));

        $response->assertRedirect(route('stocks.index'));

        $this->assertModelMissing($stock);
    }
}
