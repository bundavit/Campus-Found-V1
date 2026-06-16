<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemClaim;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ClaimReviewedNotification;
use App\Notifications\ClaimSubmittedNotification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LostFoundWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_logout(): void
    {
        $this->post('/register', [
            'name' => 'Campus Student',
            'email' => 'student@example.com',
            'phone' => '012345678',
            'student_id' => '20260001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_account_dashboard_is_private_and_shows_only_the_users_activity(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownedItem = Item::create([
            'user_id' => $user->id,
            'title' => 'My Reported Wallet',
            'status' => 'lost',
            'category' => 'wallet',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => '012345678',
        ]);
        Item::create([
            'user_id' => $other->id,
            'title' => 'Another User Report',
            'status' => 'found',
            'category' => 'key',
            'reported_at' => now(),
            'location' => 'Building A',
            'contact_info' => 'other',
        ]);
        ItemClaim::create([
            'item_id' => $ownedItem->id,
            'user_id' => $user->id,
            'type' => 'claim',
            'status' => 'pending',
            'contact_info' => '012345678',
            'message' => 'My submitted claim',
        ]);

        $this->get('/account')->assertRedirect(route('login'));

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('My Dashboard')
            ->assertSee('My Reported Wallet')
            ->assertSee('My submitted claim')
            ->assertDontSee('Another User Report');
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'phone' => '012345678',
            'student_id' => 'ST-2026',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'student_id' => 'ST-2026',
        ]);

        $this->actingAs($user->fresh())->put(route('account.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_report_owner_can_edit_but_other_user_cannot(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Original Item',
            'status' => 'lost',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'owner',
        ]);

        $this->actingAs($other)->get(route('report.edit', $item))->assertForbidden();

        $this->actingAs($owner)->put(route('report.update', $item), [
            'title' => 'Updated Item',
            'status' => 'lost',
            'category' => 'other',
            'created_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Building A',
            'contact_info' => 'owner',
        ])->assertRedirect(route('board.index'));

        $this->assertDatabaseHas('items', ['id' => $item->id, 'title' => 'Updated Item']);
    }

    public function test_uploaded_image_is_optimized_to_webp(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/report', [
            'title' => 'Photo Item',
            'status' => 'lost',
            'category' => 'other',
            'created_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Library',
            'contact_info' => '012345678',
            'image' => UploadedFile::fake()->image('large.jpg', 2400, 1800),
        ])->assertRedirect(route('board.index'));

        $item = Item::firstOrFail();
        $this->assertStringEndsWith('.webp', $item->image_path);
        Storage::disk('public')->assertExists($item->image_path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($item->image_path));
        $this->assertLessThanOrEqual(1600, max($width, $height));
    }

    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk();
        $this->get('/board')->assertOk();
        $this->get('/report')->assertRedirect(route('login'));
        $this->get('/claims')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_report_item_submission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/report', [
            'title' => 'Test Keys',
            'status' => 'found',
            'category' => 'key',
            'created_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Cafeteria',
            'contact_info' => '012999888',
            'description' => 'Feature test',
            'verification_question' => 'What keychain is attached?',
            'verification_answer' => 'Blue tag',
        ])->assertRedirect(route('board.index'));

        $this->assertDatabaseHas('items', ['title' => 'Test Keys', 'category' => 'key', 'user_id' => $user->id]);
    }

    public function test_api_item_create_and_list(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/items', [
            'title' => 'API Water Bottle',
            'status' => 'found',
            'category' => 'bottle_umbrella',
            'created_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Cafeteria',
            'contact_info' => 'telegram @student',
            'description' => 'Metal bottle with RUPP sticker',
            'verification_question' => 'What sticker is on it?',
            'verification_answer' => 'RUPP',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'API Water Bottle')
            ->assertJsonPath('data.category', 'bottle_umbrella');

        $this->getJson('/api/items?status=all&sort=desc')
            ->assertOk()
            ->assertJsonFragment(['title' => 'API Water Bottle']);
    }

    public function test_board_category_filter(): void
    {
        Item::create([
            'title' => 'Lost Laptop',
            'status' => 'lost',
            'category' => 'electronic',
            'reported_at' => now(),
            'location' => 'Lab',
            'contact_info' => 'test',
            'description' => 'MacBook',
        ]);

        Item::create([
            'title' => 'Lost Keys',
            'status' => 'lost',
            'category' => 'key',
            'reported_at' => now(),
            'location' => 'Hall',
            'contact_info' => 'test',
            'description' => 'car keys',
        ]);

        $this->get('/board?category=electronic')
            ->assertOk()
            ->assertSee('Lost Laptop')
            ->assertDontSee('Lost Keys');
    }

    public function test_admin_dashboard_and_delete(): void
    {
        $item = Item::create([
            'title' => 'Delete Me',
            'status' => 'lost',
            'category' => 'other',
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
            ->assertSee('Delete Me')
            ->assertSee('Claims');

        $this->get('/admin/dashboard?section=claims')
            ->assertOk()
            ->assertSee('No claims yet');

        $this->delete(route('admin.items.destroy', $item->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);

        $this->post('/admin/logout')
            ->assertRedirect(route('home'));

        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_claim_on_found_item(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Wallet',
            'status' => 'found',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Cafeteria',
            'contact_info' => '012111222',
            'description' => 'Brown leather',
            'verification_question' => 'What is inside the wallet?',
        ]);

        $this->actingAs($claimant);
        $this->post('/claims', [
            'item_id' => $item->id,
            'claimant_name' => 'Davit',
            'contact_info' => '099888777',
            'verification_answer' => 'Student card',
        ])->assertRedirect();

        $this->assertDatabaseHas('item_claims', [
            'item_id' => $item->id,
            'type' => 'claim',
            'contact_info' => '099888777',
            'message' => 'Student card',
            'status' => 'pending',
        ]);

        $this->get('/claims?type=claim')
            ->assertOk()
            ->assertSee('Claim')
            ->assertSee('Found Wallet')
            ->assertSee('View Details')
            ->assertSee('Brown leather');

        $this->actingAs($owner);
        $claim = ItemClaim::firstOrFail();
        $this->patch(route('claims.review', $claim), ['status' => 'approved'])->assertRedirect();

        $this->get('/board')
            ->assertOk()
            ->assertSee('Recently Claimed');

        $this->get('/')
            ->assertOk()
            ->assertSee('Recently Claimed')
            ->assertSee('Found Wallet');
    }

    public function test_api_claim_create_list_show_update_and_delete(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Calculator',
            'status' => 'found',
            'category' => 'electronic',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => '012111222',
            'description' => 'Casio scientific calculator',
            'verification_question' => 'What initials are written on it?',
        ]);

        Sanctum::actingAs($claimant);
        $this->postJson('/api/claims', [
            'item_id' => $item->id,
            'claimant_name' => 'Sophea',
            'contact_info' => '099123456',
            'message' => 'I can identify the sticker on the back.',
            'verification_answer' => 'SK',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'claim')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.claimant_name', 'Sophea')
            ->assertJsonPath('data.item.title', 'Found Calculator');

        $claim = ItemClaim::firstOrFail();

        $this->getJson('/api/claims?type=claim&status=pending')
            ->assertOk()
            ->assertJsonFragment(['claimant_name' => 'Sophea']);

        $this->getJson("/api/claims/{$claim->id}")
            ->assertOk()
            ->assertJsonPath('data.id', (string) $claim->id)
            ->assertJsonPath('data.item.title', 'Found Calculator');

        Sanctum::actingAs($owner);

        $this->patchJson("/api/claims/{$claim->id}/status", [
            'status' => 'approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('item_claims', [
            'id' => $claim->id,
            'status' => 'approved',
        ]);

        $this->deleteJson("/api/claims/{$claim->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Claim deleted successfully');

        $this->assertDatabaseMissing('item_claims', ['id' => $claim->id]);
    }

    public function test_simplified_claim_requires_private_proof_and_prevents_duplicates(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Student Card',
            'status' => 'found',
            'category' => 'id_card',
            'reported_at' => now(),
            'location' => 'Building A',
            'contact_info' => 'owner@example.com',
            'description' => 'Student card in a blue holder',
        ]);

        $this->actingAs($claimant)
            ->from('/board')
            ->post('/claims', [
                'item_id' => $item->id,
                'contact_info' => 'claimant@example.com',
            ])
            ->assertRedirect('/board')
            ->assertSessionHasErrors('ownership_proof');

        $this->post('/claims', [
            'item_id' => $item->id,
            'contact_info' => 'claimant@example.com',
            'ownership_proof' => 'My student number ends in 204 and the holder has a torn corner.',
        ])->assertRedirect();

        $this->assertDatabaseHas('item_claims', [
            'item_id' => $item->id,
            'user_id' => $claimant->id,
            'message' => 'My student number ends in 204 and the holder has a torn corner.',
            'status' => 'pending',
        ]);

        $this->from('/board')
            ->post('/claims', [
                'item_id' => $item->id,
                'contact_info' => 'claimant@example.com',
                'ownership_proof' => 'Submitting this twice should be blocked.',
            ])
            ->assertRedirect('/board')
            ->assertSessionHasErrors('item_id');

        $this->assertSame(1, ItemClaim::where('item_id', $item->id)->count());
    }

    public function test_claimant_can_upload_private_proof_image_and_owner_can_review_it(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Headphones',
            'status' => 'found',
            'category' => 'electronic',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'owner@example.com',
            'description' => 'Black headphones',
        ]);

        $this->actingAs($claimant)->post('/claims', [
            'item_id' => $item->id,
            'contact_info' => 'claimant@example.com',
            'ownership_proof' => 'The left ear cup has a silver initials sticker.',
            'proof_image' => UploadedFile::fake()->image('receipt.png', 600, 400),
        ])->assertRedirect();

        $claim = ItemClaim::firstOrFail();
        $this->assertNotNull($claim->proof_image_path);
        Storage::disk('public')->assertExists($claim->proof_image_path);

        $this->actingAs($owner)
            ->get('/claims?type=claim')
            ->assertOk()
            ->assertSee('Private ownership proof')
            ->assertSee('The left ear cup has a silver initials sticker.')
            ->assertSee('/storage/'.$claim->proof_image_path, false);
    }

    public function test_user_cannot_claim_their_own_report(): void
    {
        $owner = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Keys',
            'status' => 'found',
            'category' => 'key',
            'reported_at' => now(),
            'location' => 'Gate',
            'contact_info' => 'owner@example.com',
            'description' => 'Two keys',
        ]);

        $this->actingAs($owner)
            ->from('/board')
            ->post('/claims', [
                'item_id' => $item->id,
                'contact_info' => 'owner@example.com',
                'ownership_proof' => 'These are mine.',
            ])
            ->assertRedirect('/board')
            ->assertSessionHasErrors('item_id');

        $this->assertDatabaseCount('item_claims', 0);
    }

    public function test_private_claim_proof_is_hidden_from_unrelated_visitors(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Watch',
            'status' => 'found',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'owner@example.com',
            'description' => 'Silver watch',
        ]);
        ItemClaim::create([
            'item_id' => $item->id,
            'user_id' => $claimant->id,
            'type' => 'claim',
            'status' => 'approved',
            'claimant_name' => 'Private Claimant',
            'contact_info' => 'private@example.com',
            'message' => 'The back is engraved with my full name.',
            'proof_image_path' => 'claim-proofs/private.webp',
        ]);

        $this->get('/claims')
            ->assertOk()
            ->assertDontSee('private@example.com')
            ->assertDontSee('The back is engraved with my full name.')
            ->assertDontSee('claim-proofs/private.webp');
    }

    public function test_found_report_on_lost_item(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Lost Phone',
            'status' => 'lost',
            'category' => 'electronic',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => '012333444',
            'description' => 'iPhone 13',
        ]);

        $this->actingAs($finder);
        $this->post('/claims', [
            'item_id' => $item->id,
            'contact_info' => '011555666',
            'message' => 'Found at the front desk.',
        ])->assertRedirect();

        $this->assertDatabaseHas('item_claims', [
            'item_id' => $item->id,
            'type' => 'found',
            'status' => 'pending',
        ]);

        $this->get('/claims?type=return')
            ->assertOk()
            ->assertSee('Found Report')
            ->assertSee('Lost Phone')
            ->assertSee('View Details')
            ->assertSee('iPhone 13');

        $this->actingAs($owner);
        $claim = ItemClaim::firstOrFail();
        $this->patch(route('claims.review', $claim), ['status' => 'approved']);

        $this->get('/board')
            ->assertOk()
            ->assertSee('Recently Claimed');

        $this->get('/')
            ->assertOk()
            ->assertSee('Recently Claimed')
            ->assertSee('Lost Phone');
    }

    public function test_admin_can_delete_claim_from_claims_page(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Bag',
            'status' => 'found',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Gate',
            'contact_info' => '012000111',
            'description' => 'test',
        ]);

        $this->actingAs($claimant);
        $this->post('/claims', [
            'item_id' => $item->id,
            'contact_info' => '099111222',
            'message' => 'This is mine',
        ]);

        $claimId = ItemClaim::first()->id;

        $this->post('/admin/login', ['password' => 'RUPPSTAFF']);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Found Bag');

        $this->get('/admin/dashboard?section=claims&claim_status=claim')
            ->assertOk()
            ->assertSee('Found Bag')
            ->assertSee('View');

        $this->from('/claims')
            ->delete(route('admin.claims.destroy', $claimId))
            ->assertRedirect('/claims')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('item_claims', ['id' => $claimId]);
    }

    public function test_admin_can_review_a_pending_claim(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Wallet',
            'status' => 'found',
            'category' => 'wallet',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'owner-contact',
        ]);
        $claim = ItemClaim::create([
            'item_id' => $item->id,
            'user_id' => $claimant->id,
            'type' => 'claim',
            'status' => 'pending',
            'claimant_name' => 'Student',
            'contact_info' => 'claimant-contact',
            'verification_answer' => 'Blue zipper',
        ]);

        $this->withSession(['is_admin' => true])
            ->patch("/admin/claims/{$claim->id}/review", ['status' => 'approved'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Claim approved.');

        $this->assertDatabaseHas('item_claims', [
            'id' => $claim->id,
            'status' => 'approved',
        ]);
    }

    public function test_claim_events_send_email_notifications(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Found Laptop',
            'status' => 'found',
            'category' => 'electronic',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'owner@example.com',
        ]);

        $this->actingAs($claimant)->post('/claims', [
            'item_id' => $item->id,
            'contact_info' => 'claimant@example.com',
            'ownership_proof' => 'The login screen has my initials.',
        ]);

        Notification::assertSentTo($owner, ClaimSubmittedNotification::class);

        $claim = ItemClaim::firstOrFail();
        $this->actingAs($owner)->patch(route('claims.review', $claim), ['status' => 'approved']);
        Notification::assertSentTo($claimant, ClaimReviewedNotification::class);
    }

    public function test_admin_can_manage_users_and_audit_changes(): void
    {
        $user = User::factory()->create();

        $this->withSession(['is_admin' => true])
            ->patch(route('admin.users.update', $user), [
                'role' => 'admin',
                'status' => 'suspended',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'subject_type' => 'User',
            'subject_id' => $user->id,
        ]);

        $this->withSession(['is_admin' => true])
            ->get('/admin/dashboard?section=users')
            ->assertOk()
            ->assertSee($user->email);

        $this->withSession(['is_admin' => true])
            ->get('/admin/dashboard?section=audit')
            ->assertOk()
            ->assertSee('user.updated');
    }

    public function test_admin_role_can_access_dashboard_and_suspended_user_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suspended = User::factory()->create(['status' => 'suspended']);

        $this->actingAs($admin->refresh())->get('/admin/dashboard')->assertOk();

        $this->actingAs($suspended->refresh())
            ->get('/account')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'This account is suspended.');
    }

    public function test_admin_can_hide_report_and_resolve_claim_dispute(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Questionable Report',
            'status' => 'found',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Gate',
            'contact_info' => 'owner@example.com',
        ]);
        $claim = ItemClaim::create([
            'item_id' => $item->id,
            'user_id' => $claimant->id,
            'type' => 'claim',
            'status' => 'rejected',
            'claimant_name' => 'Student',
            'contact_info' => 'claimant@example.com',
            'message' => 'Private proof',
        ]);

        $this->actingAs($claimant)->post(route('claims.dispute', $claim), [
            'reason' => 'The reporter misunderstood my proof.',
        ])->assertRedirect();

        $this->assertDatabaseHas('item_claims', [
            'id' => $claim->id,
            'dispute_status' => 'open',
        ]);

        $this->withSession(['is_admin' => true])
            ->patch(route('admin.items.moderate', $item), [
                'moderation_status' => 'hidden',
                'reason' => 'Duplicate or misleading report.',
            ])
            ->assertRedirect();

        $this->get('/board')->assertDontSee('Questionable Report');

        $this->withSession(['is_admin' => true])
            ->patch(route('admin.claims.dispute', $claim), [
                'dispute_status' => 'resolved',
                'status' => 'pending',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('item_claims', [
            'id' => $claim->id,
            'dispute_status' => 'resolved',
            'status' => 'pending',
        ]);
        $this->assertGreaterThanOrEqual(3, AuditLog::count());
    }
}
