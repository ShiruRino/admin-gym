<?php

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\PtSessionLog;
use App\Models\Trainer;
use App\Models\User;
use App\Notifications\MemberTemporaryPasswordNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('member can login via API with valid phone and password', function () {
    $member = Member::factory()->create([
        'phone' => '081234567890',
        'password' => Hash::make('secret123'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '081234567890',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Login berhasil.',
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'member' => ['id', 'member_code', 'name', 'phone'],
            ],
        ]);
});

test('member can register via API and receive Sanctum token', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Fajar Pratama',
        'email' => 'fajar@example.com',
        'phone' => '081298765432',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'gender' => 'male',
        'birth_date' => '1995-08-14',
        'address' => 'Jl. Kebon Jeruk No. 12',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Pendaftaran member berhasil.',
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'member' => ['id', 'member_code', 'name', 'phone', 'email'],
            ],
        ]);

    $member = Member::where('email', 'fajar@example.com')->first();
    expect($member)->not->toBeNull();
    expect($member->name)->toBe('Fajar Pratama');
    expect(str_starts_with($member->member_code, 'GYM-'))->toBeTrue();
    expect(Hash::check('secret123', $member->password))->toBeTrue();

    Notification::assertSentTo(
        $member,
        MemberTemporaryPasswordNotification::class
    );
});

test('member registration fails with duplicate email or phone', function () {
    Member::factory()->create([
        'email' => 'existing@example.com',
        'phone' => '081122334455',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Member Baru',
        'email' => 'existing@example.com',
        'phone' => '081122334455',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'phone']);
});

test('member can login via API with valid email and password', function () {
    $member = Member::factory()->create([
        'email' => 'budi.member@gymhub.test',
        'phone' => '089988776655',
        'password' => Hash::make('secret123'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'budi.member@gymhub.test',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'member' => [
                    'id' => $member->id,
                    'email' => 'budi.member@gymhub.test',
                ],
            ],
        ]);
});

test('api documentation endpoint returns valid openapi schema with auth and register endpoints', function () {
    $response = $this->get('/docs/api.json');

    $response->assertOk()
        ->assertJsonPath('openapi', fn ($version) => str_starts_with($version, '3.'))
        ->assertJsonStructure([
            'paths' => [
                '/v1/auth/login',
                '/v1/auth/register',
                '/v1/auth/forgot-password',
                '/v1/member/profile',
                '/v1/member/photo',
            ],
        ]);
});

test('member can request forgot password and receives email with temporary password', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'email' => 'hendra@example.com',
        'password' => Hash::make('oldpassword'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'hendra@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $freshMember = $member->fresh();
    expect(Hash::check('oldpassword', $freshMember->password))->toBeFalse();

    Notification::assertSentTo(
        $member,
        MemberTemporaryPasswordNotification::class,
        function (MemberTemporaryPasswordNotification $notification) use ($freshMember) {
            return $notification->isNewRegistration === false
                && Hash::check($notification->temporaryPassword, $freshMember->password);
        }
    );
});

test('forgot password returns 404 if email is not registered', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'unregistered@example.com',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Alamat email tidak terdaftar sebagai member.',
        ]);
});

test('forgot password returns 403 if member is inactive', function () {
    Member::factory()->create([
        'email' => 'inactive@example.com',
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'inactive@example.com',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir.',
        ]);
});

test('forgot password requires valid email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated member can upload profile photo via API', function () {
    Storage::fake('public');

    $member = Member::factory()->create();
    Sanctum::actingAs($member, ['*']);

    $file = UploadedFile::fake()->create('profile.jpg', 150, 'image/jpeg');

    $response = $this->postJson('/api/v1/member/photo', [
        'photo' => $file,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
        ])
        ->assertJsonStructure([
            'data' => [
                'photo_url',
                'member' => ['id', 'name', 'photo_url'],
            ],
        ]);

    $freshMember = $member->fresh();
    expect($freshMember->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($freshMember->photo_path);
});

test('authenticated member can delete profile photo via API', function () {
    Storage::fake('public');

    $member = Member::factory()->create([
        'photo_path' => 'members/photos/my-photo.jpg',
    ]);
    Storage::disk('public')->put('members/photos/my-photo.jpg', 'content');

    Sanctum::actingAs($member, ['*']);

    $response = $this->deleteJson('/api/v1/member/photo');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
        ]);

    expect($member->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('members/photos/my-photo.jpg');
});

test('member photo upload validates required image', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member, ['*']);

    $response = $this->postJson('/api/v1/member/photo', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['photo']);
});

test('member cannot login with invalid credentials', function () {
    $member = Member::factory()->create([
        'phone' => '081234567890',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '081234567890',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Nomor handphone atau PIN/password yang Anda masukkan salah.',
        ]);
});

test('inactive member cannot login via API', function () {
    $member = Member::factory()->create([
        'phone' => '081234567890',
        'password' => Hash::make('secret123'),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '081234567890',
        'password' => 'secret123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir.',
        ]);
});

test('unauthenticated request to protected endpoint returns 401', function () {
    $response = $this->getJson('/api/v1/member/profile');

    $response->assertStatus(401);
});

test('authenticated member can view profile via API', function () {
    $member = Member::factory()->create([
        'name' => 'Dimas Anggara',
        'member_code' => 'GYM-2611-0005',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/profile');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'name' => 'Dimas Anggara',
                'member_code' => 'GYM-2611-0005',
            ],
        ]);
});

test('authenticated member can view digital card payload', function () {
    $member = Member::factory()->create([
        'name' => 'Sarah Wijaya',
        'member_code' => 'GYM-2611-0099',
    ]);

    $plan = Plan::factory()->membership()->create(['name' => 'Gold 1 Bulan']);

    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'start_date' => now(),
        'end_date' => now()->addDays(30),
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/card');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'member_code' => 'GYM-2611-0099',
                'qr_payload' => 'GYM-2611-0099',
                'membership_status' => 'active',
                'plan_name' => 'Gold 1 Bulan',
            ],
        ]);
});

test('authenticated member can view subscription status', function () {
    $member = Member::factory()->create();
    $plan = Plan::factory()->membership()->create(['name' => 'Paket Platinum 3 Bulan']);

    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(85),
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/subscription');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'has_active_subscription' => true,
                'membership_status' => 'active',
                'active_subscription' => [
                    'plan_name' => 'Paket Platinum 3 Bulan',
                ],
            ],
        ]);
});

test('authenticated member can view PT session quota and logs', function () {
    $member = Member::factory()->create();
    $trainer = Trainer::factory()->create(['name' => 'Coach Budi']);
    $staff = User::factory()->create();

    $quota = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 10,
        'used_sessions' => 2,
        'remaining_sessions' => 8,
        'is_active' => true,
    ]);

    PtSessionLog::factory()->create([
        'member_pt_quota_id' => $quota->id,
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'recorded_by_user_id' => $staff->id,
        'notes' => 'Upper body workout',
        'used_at' => now(),
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/pt-sessions');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'total_quota' => 10,
                'used_quota' => 2,
                'remaining_quota' => 8,
            ],
        ])
        ->assertJsonFragment([
            'trainer_name' => 'Coach Budi',
            'notes' => 'Upper body workout',
        ]);
});

test('authenticated member can view attendances history', function () {
    $member = Member::factory()->create();

    Attendance::factory()->count(3)->create([
        'member_id' => $member->id,
        'check_in_time' => now(),
        'notes' => 'Latihan rutin',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/attendances');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'check_in_time', 'notes'],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
});

test('authenticated member can view invoices history', function () {
    $member = Member::factory()->create();
    $plan = Plan::factory()->membership()->create(['name' => 'Silver 1 Bulan']);

    Invoice::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 250000,
        'payment_status' => 'paid',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->getJson('/api/v1/member/invoices');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'plan_name' => 'Silver 1 Bulan',
            'amount' => 250000,
            'payment_status' => 'paid',
        ]);
});

test('authenticated member can logout and revoke token', function () {
    $member = Member::factory()->create([
        'phone' => '081234567890',
        'password' => Hash::make('secret123'),
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'phone' => '081234567890',
        'password' => 'secret123',
    ]);

    $token = $loginResponse->json('data.token');

    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logout berhasil. Sesi token telah dicabut.',
        ]);

    expect($member->tokens()->count())->toBe(0);
});

test('authenticated member can update own profile', function () {
    $member = Member::factory()->create([
        'address' => 'Alamat Lama',
        'emergency_contact' => '081111111',
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->putJson('/api/v1/member/profile', [
        'address' => 'Jl. Kebon Jeruk No. 15',
        'emergency_contact' => 'Kakak - 089988776655',
        'notes' => 'Fokus latihan beban',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'address' => 'Jl. Kebon Jeruk No. 15',
                'emergency_contact' => 'Kakak - 089988776655',
                'notes' => 'Fokus latihan beban',
            ],
        ]);

    expect($member->fresh()->address)->toBe('Jl. Kebon Jeruk No. 15')
        ->and($member->fresh()->emergency_contact)->toBe('Kakak - 089988776655');
});

test('authenticated member can change password', function () {
    $member = Member::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->putJson('/api/v1/member/password', [
        'current_password' => 'oldpassword123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan gunakan password baru untuk login berikutnya.',
        ]);

    expect(Hash::check('newpassword456', $member->fresh()->password))->toBeTrue();
});

test('authenticated member fails to change password with incorrect current password', function () {
    $member = Member::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($member, ['*']);

    $response = $this->putJson('/api/v1/member/password', [
        'current_password' => 'wrongcurrentpass',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('scramble documentation page is accessible', function () {
    $response = $this->get('/docs/api');

    $response->assertOk();
});
