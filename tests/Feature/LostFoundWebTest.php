<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmailCode;
use App\Models\Item;
use App\Models\ItemClaim;
use App\Models\User;
use App\Notifications\ClaimDisputeResolvedNotification;
use App\Notifications\ClaimReviewedNotification;
use App\Notifications\ClaimSubmittedNotification;
use App\Notifications\EmailCodeNotification;
use App\Notifications\ReportModeratedNotification;
use App\Services\EmailCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LostFoundWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_logout(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Campus Student',
            'email' => 'student@example.com',
            'phone' => '012345678',
            'student_id' => '20260001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertDatabaseHas('email_codes', [
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'purpose' => EmailCodeService::VERIFY_EMAIL,
        ]);
        Notification::assertSentTo($user, EmailCodeNotification::class);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
    }

    public function test_email_verification_code_unlocks_protected_pages(): void
    {
        $user = User::factory()->unverified()->create();
        EmailCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailCodeService::VERIFY_EMAIL,
            'code_hash' => Hash::make('123456'),
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)->get('/account')
            ->assertRedirect(route('verification.notice'));

        $this->post(route('verification.verify'), ['code' => '123456'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->get('/account')->assertOk();
    }

    public function test_password_reset_code_updates_password_without_revealing_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'reset-user@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        $this->post(route('password.email'), [
            'email' => 'reset-user@example.com',
        ])->assertRedirect(route('password.reset.form', ['email' => 'reset-user@example.com']));

        Notification::assertSentTo($user, EmailCodeNotification::class);
        $code = EmailCode::where('email', 'reset-user@example.com')
            ->where('purpose', EmailCodeService::PASSWORD_RESET)
            ->firstOrFail();
        $code->update(['code_hash' => Hash::make('654321')]);

        $this->post(route('password.update'), [
            'email' => 'reset-user@example.com',
            'code' => '654321',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        $this->post(route('password.email'), [
            'email' => 'missing@example.com',
        ])->assertRedirect(route('password.reset.form', ['email' => 'missing@example.com']));
    }

    public function test_registration_cannot_create_admin_accounts(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Role Attacker',
            'email' => 'role-attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
            'status' => 'suspended',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'role-attacker@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->postJson('/api/register', [
            'name' => 'API Role Attacker',
            'email' => 'api-role-attacker@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'suspended',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'api-role-attacker@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);
    }

    public function test_super_admin_command_creates_active_super_admin(): void
    {
        $this->artisan('lostfound:create-super-admin', [
            '--name' => 'Campus Admin',
            '--email' => 'campus-admin@example.com',
            '--password' => 'StrongAdmin123',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'campus-admin@example.com',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
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
        ItemClaim::create([
            'item_id' => $ownedItem->id,
            'user_id' => $other->id,
            'type' => 'found',
            'status' => 'pending',
            'contact_info' => '099999999',
            'message' => 'I found it near the gate',
        ]);

        $this->get('/account')->assertRedirect(route('login'));

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('My Dashboard')
            ->assertSee('Recent Activity')
            ->assertSee('Action Needed')
            ->assertSee('pending responses')
            ->assertSee('My Reported Wallet')
            ->assertSee('My submitted claim')
            ->assertSee('Needs your review')
            ->assertDontSee('Another User Report');
    }

    public function test_user_can_update_profile_and_password(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'phone' => '012345678',
            'student_id' => 'ST-2026',
        ])->assertRedirect(route('verification.notice'))->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'student_id' => 'ST-2026',
            'email_verified_at' => null,
        ]);
        Notification::assertSentTo($user->fresh(), EmailCodeNotification::class);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user->fresh())->put(route('account.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_can_delete_their_account_with_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('delete-me-123'),
        ]);
        $item = Item::create([
            'user_id' => $user->id,
            'title' => 'Delete My Report',
            'status' => 'lost',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => '012345678',
        ]);
        ItemClaim::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'type' => 'claim',
            'status' => 'pending',
            'contact_info' => '012345678',
            'message' => 'Delete my claim too',
        ]);

        $this->actingAs($user)->delete(route('account.destroy'), [
            'current_password' => 'delete-me-123',
            'confirmation' => 'DELETE',
        ])->assertRedirect(route('home'))->assertSessionHas('success');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        $this->assertDatabaseMissing('item_claims', ['user_id' => $user->id]);
    }

    public function test_api_account_endpoints_show_only_authenticated_user_activity(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownedItem = Item::create([
            'user_id' => $user->id,
            'title' => 'API Owned Report',
            'status' => 'found',
            'category' => 'ticket',
            'reported_at' => now(),
            'location' => 'Building D',
            'contact_info' => 'owner-contact',
        ]);
        Item::create([
            'user_id' => $other->id,
            'title' => 'Other API Report',
            'status' => 'lost',
            'category' => 'key',
            'reported_at' => now(),
            'location' => 'Library',
            'contact_info' => 'other-contact',
        ]);
        ItemClaim::create([
            'item_id' => $ownedItem->id,
            'user_id' => $user->id,
            'type' => 'claim',
            'status' => 'pending',
            'contact_info' => 'claimant-contact',
            'message' => 'Private claim proof',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/account')
            ->assertOk()
            ->assertJsonPath('stats.reports', 1)
            ->assertJsonPath('stats.claims', 1);

        $this->getJson('/api/account/reports')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'API Owned Report')
            ->assertJsonMissing(['title' => 'Other API Report']);

        $this->getJson('/api/account/claims')
            ->assertOk()
            ->assertJsonPath('data.0.message', 'Private claim proof')
            ->assertJsonPath('data.0.item.title', 'API Owned Report');
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

    public function test_board_uses_pagination_for_larger_result_sets(): void
    {
        foreach (range(1, 13) as $index) {
            Item::create([
                'title' => 'Paged Item '.$index,
                'status' => 'lost',
                'category' => 'other',
                'reported_at' => now()->subMinutes($index),
                'location' => 'Library',
                'contact_info' => '012345678',
            ]);
        }

        $this->get('/board')
            ->assertOk()
            ->assertSee('Paged Item 1')
            ->assertDontSee('Paged Item 13');

        $this->get('/board?page=2')
            ->assertOk()
            ->assertSee('Paged Item 13');
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
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password-123'),
        ]);
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

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-password-123',
        ])
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
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password-123'),
        ]);
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

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-password-123',
        ]);

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

    public function test_admin_dashboard_claims_section_uses_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $item = Item::create([
            'user_id' => $owner->id,
            'title' => 'Pagination Claim Target',
            'status' => 'found',
            'category' => 'other',
            'reported_at' => now(),
            'location' => 'Gate',
            'contact_info' => 'owner@example.com',
        ]);

        foreach (range(1, 16) as $index) {
            ItemClaim::create([
                'item_id' => $item->id,
                'user_id' => $claimant->id,
                'type' => 'claim',
                'status' => 'pending',
                'claimant_name' => 'Claimant '.$index,
                'contact_info' => 'contact'.$index.'@example.com',
                'message' => 'Proof '.$index,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/dashboard?section=claims')
            ->assertOk()
            ->assertSee('Claimant 1')
            ->assertDontSee('Claimant 16');

        $this->actingAs($admin)
            ->get('/admin/dashboard?section=claims&claims_page=2')
            ->assertOk()
            ->assertSee('Claimant 16');
    }

    public function test_admin_can_review_a_pending_claim(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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

        $this->actingAs($admin)
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
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create();

        $this->actingAs($superAdmin)
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

        $this->actingAs($superAdmin)
            ->get('/admin/dashboard?section=users')
            ->assertOk()
            ->assertSee($user->email);

        $this->actingAs($superAdmin)
            ->get('/admin/dashboard?section=audit')
            ->assertOk()
            ->assertSee('user.updated');
    }

    public function test_admin_can_send_user_verification_and_password_reset_support_codes(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->unverified()->create([
            'email' => 'needs-help@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.verification.send', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('email_codes', [
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailCodeService::VERIFY_EMAIL,
        ]);
        Notification::assertSentTo($user, EmailCodeNotification::class);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.verification_resent',
            'subject_type' => 'User',
            'subject_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.password-reset.send', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('email_codes', [
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailCodeService::PASSWORD_RESET,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset_requested',
            'subject_type' => 'User',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_user_filters_limit_the_users_section_results(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $activeUser = User::factory()->create([
            'name' => 'Active Member',
            'email' => 'active-member@example.com',
            'status' => 'active',
        ]);
        $suspendedAdmin = User::factory()->create([
            'name' => 'Suspended Admin',
            'email' => 'suspended-admin@example.com',
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $this->actingAs($superAdmin)
            ->get('/admin/dashboard?section=users&user_role=admin&user_status=suspended')
            ->assertOk()
            ->assertSee('suspended-admin@example.com')
            ->assertDontSee('active-member@example.com');
    }

    public function test_only_super_admin_can_manage_roles_and_final_super_admin_is_protected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'role' => 'admin',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.update', $superAdmin), [
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_role_can_access_dashboard_and_suspended_user_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suspended = User::factory()->create(['status' => 'suspended']);
        $normalUser = User::factory()->create([
            'password' => Hash::make('user-password-123'),
        ]);

        $this->actingAs($admin->refresh())->get('/admin/dashboard')->assertOk();

        $this->post('/admin/login', [
            'email' => $normalUser->email,
            'password' => 'user-password-123',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->actingAs($suspended->refresh())
            ->get('/account')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'This account is suspended.');
    }

    public function test_admin_can_hide_report_and_resolve_claim_dispute(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
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

        $this->actingAs($admin)
            ->patch(route('admin.items.moderate', $item), [
                'moderation_status' => 'hidden',
                'reason' => 'Duplicate or misleading report.',
            ])
            ->assertRedirect();

        Notification::assertSentTo($owner, ReportModeratedNotification::class);

        $this->get('/board')->assertDontSee('Questionable Report');

        $this->actingAs($admin)
            ->patch(route('admin.claims.dispute', $claim), [
                'dispute_status' => 'resolved',
                'status' => 'pending',
            ])
            ->assertRedirect();

        Notification::assertSentTo($claimant, ClaimDisputeResolvedNotification::class);

        $this->assertDatabaseHas('item_claims', [
            'id' => $claim->id,
            'dispute_status' => 'resolved',
            'status' => 'pending',
        ]);
        $this->assertGreaterThanOrEqual(3, AuditLog::count());
    }
}
