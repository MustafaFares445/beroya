<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\GalleryPhone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_read_galleries(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Homs',
            'address' => 'Center',
        ]);

        $response = $this->getJson('/api/galleries');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/galleries/{$gallery->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $gallery->id)
            ->assertJsonPath('data.name', 'Homs')
            ->assertJsonPath('data.address', 'Center');
    }

    public function test_admin_can_create_gallery(): void
    {
        $admin = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($admin);

        $response = $this->postJson('/api/galleries', [
            'name' => 'Tartous',
            'address' => 'Harbor',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Tartous');
    }

    public function test_manager_cannot_create_gallery_but_can_create_phone(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
            'gallery_id' => $gallery->id,
        ]);

        $this->actingAsSanctum($manager);

        $forbiddenResponse = $this->postJson('/api/galleries', [
            'name' => 'Damascus',
            'address' => 'Road',
        ]);

        $forbiddenResponse
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');

        $phoneResponse = $this->postJson('/api/gallery-phones', [
            'phone' => '+963940000000',
            'gallery_id' => $gallery->id,
        ]);

        $phoneResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.phone', '+963940000000');
    }

    public function test_public_can_read_gallery_phones_collection_and_item(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Latakia',
            'address' => 'Corniche',
        ]);

        $phone = GalleryPhone::query()->create([
            'phone' => '+963940000111',
            'gallery_id' => $gallery->id,
        ]);

        $indexResponse = $this->getJson('/api/gallery-phones');

        $indexResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $phone->id)
            ->assertJsonPath('data.0.phone', '+963940000111')
            ->assertJsonPath('data.0.gallery_id', $gallery->id);

        $this->getJson("/api/gallery-phones/{$phone->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $phone->id)
            ->assertJsonPath('data.phone', '+963940000111')
            ->assertJsonPath('data.gallery_id', $gallery->id);
    }

    public function test_admin_can_update_and_delete_gallery(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Old Address',
        ]);

        $admin = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($admin);

        $updateResponse = $this->putJson("/api/galleries/{$gallery->id}", [
            'name' => 'Damascus Central',
            'address' => 'New Address',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $gallery->id)
            ->assertJsonPath('data.name', 'Damascus Central')
            ->assertJsonPath('data.address', 'New Address');

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'name' => 'Damascus Central',
            'address' => 'New Address',
        ]);

        $deleteResponse = $this->deleteJson("/api/galleries/{$gallery->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $gallery->id);

        $this->assertDatabaseMissing('galleries', [
            'id' => $gallery->id,
        ]);
    }

    public function test_manager_can_update_and_delete_gallery_phone(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $phone = GalleryPhone::query()->create([
            'phone' => '+963940000222',
            'gallery_id' => $gallery->id,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
            'gallery_id' => $gallery->id,
        ]);

        $this->actingAsSanctum($manager);

        $updateResponse = $this->putJson("/api/gallery-phones/{$phone->id}", [
            'phone' => '+963944444444',
            'gallery_id' => $gallery->id,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $phone->id)
            ->assertJsonPath('data.phone', '+963944444444')
            ->assertJsonPath('data.gallery_id', $gallery->id);

        $this->assertDatabaseHas('galleries_phones', [
            'id' => $phone->id,
            'phone' => '+963944444444',
            'gallery_id' => $gallery->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/gallery-phones/{$phone->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $phone->id);

        $this->assertDatabaseMissing('galleries_phones', [
            'id' => $phone->id,
        ]);
    }
}
