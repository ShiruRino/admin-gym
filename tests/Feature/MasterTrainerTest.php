<?php

use App\Livewire\Trainers\Index;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected from trainers page to login', function () {
    $response = $this->get(route('trainers.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view trainers page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('trainers.index'));
    $response->assertOk();
    $response->assertSee('Master Trainer');
});

test('can create a new trainer via form modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Coach Hendra')
        ->set('phone', '081277665544')
        ->set('specialty', 'Functional Training & HIIT')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('trainers', [
        'name' => 'Coach Hendra',
        'phone' => '081277665544',
        'specialty' => 'Functional Training & HIIT',
    ]);
});

test('can edit an existing trainer', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trainer = Trainer::factory()->create([
        'name' => 'Trainer Lama',
        'specialty' => 'General Fitness',
    ]);

    Livewire::test(Index::class)
        ->call('openEditModal', $trainer->id)
        ->set('name', 'Trainer Baru')
        ->set('specialty', 'Powerlifting')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('trainers', [
        'id' => $trainer->id,
        'name' => 'Trainer Baru',
        'specialty' => 'Powerlifting',
    ]);
});

test('can toggle trainer active status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trainer = Trainer::factory()->create(['is_active' => true]);

    Livewire::test(Index::class)
        ->call('toggleActive', $trainer->id);

    expect($trainer->fresh()->is_active)->toBeFalse();
});

test('can sort trainers by column', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Trainer::factory()->create(['name' => 'Coach Anton', 'specialty' => 'Cardio']);
    Trainer::factory()->create(['name' => 'Coach Budi', 'specialty' => 'Crossfit']);

    Livewire::test(Index::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'specialty')
        ->assertSet('sortBy', 'specialty')
        ->assertSet('sortDirection', 'asc');
});

test('can upload profile picture when creating trainer', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('coach.jpg', 100, 'image/jpeg');

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Coach Bugar')
        ->set('phone', '081233445588')
        ->set('specialty', 'Strength & Conditioning')
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $trainer = Trainer::where('phone', '081233445588')->first();
    expect($trainer)->not->toBeNull();
    expect($trainer->photo_path)->not->toBeNull();
    expect($trainer->photo_url)->not->toBeNull();
    Storage::disk('public')->assertExists($trainer->photo_path);
});

test('can update and remove trainer profile picture', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $trainer = Trainer::factory()->create([
        'photo_path' => 'trainers/photos/dummy.jpg',
    ]);
    Storage::disk('public')->put('trainers/photos/dummy.jpg', 'content');

    Livewire::test(Index::class)
        ->call('openEditModal', $trainer->id)
        ->assertSet('existingPhotoUrl', $trainer->photo_url)
        ->call('removePhoto');

    expect($trainer->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('trainers/photos/dummy.jpg');
});
