<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LostFoundWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk();
        $this->get('/board')->assertOk();
        $this->get('/report')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_report_item_submission(): void
    {
        $this->post('/report', [
            'title' => 'Test Keys',
            'status' => 'found',
            'created_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Cafeteria',
            'contact_info' => '012999888',
            'description' => 'Feature test',
        ])->assertRedirect(route('board.index'));

        $this->assertDatabaseHas('items', ['title' => 'Test Keys']);
    }

    public function test_admin_dashboard_and_delete(): void
    {
        $item = Item::create([
            'title' => 'Delete Me',
            'status' => 'lost',
            'reported_at' => now(),
            'location' => 'Lab',
            'contact_info' => 'test',
            'description' => 'to delete',
        ]);

        $this->get('/admin')->assertRedirect(route('admin.login'));

        $this->post('/admin/login', ['password' => 'RUPPSTAFF'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Management Dashboard')
            ->assertSee('Delete Me');

        $this->delete(route('admin.items.destroy', $item->id))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('items', ['id' => $item->id]);

        $this->post('/admin/logout')
            ->assertRedirect(route('home'));

        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }
}
