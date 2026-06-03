<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_read_galleries(): void
    {
        Gallery::query()->create([
            'name' => 'Homs',
            'address' => 'Center',
        ]);

        $response = $this->getJson('/api/galleries');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
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
}
