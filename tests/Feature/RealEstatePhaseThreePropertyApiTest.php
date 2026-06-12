<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RealEstatePhaseThreePropertyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshPropertyUploadsDirectory();
    }

    protected function tearDown(): void
    {
        $this->refreshPropertyUploadsDirectory();

        parent::tearDown();
    }

    public function test_public_properties_mask_owner_contacts_for_guests(): void
    {
        $context = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');

        $property = Property::query()->create([
            ...$this->propertyPayload(
                $context['office']->id,
                $context['category']->id,
                $context['subcategory']->id,
                'OFF-1001'
            ),
            'province_id' => $context['province']->id,
        ]);

        $this->getJson('/api/real-estate/properties')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $property->id)
            ->assertJsonPath('data.0.province_name', 'Damascus')
            ->assertJsonPath('data.0.office_name', 'Downtown Office')
            ->assertJsonPath('data.0.owner_name', '')
            ->assertJsonPath('data.0.owner_phone', '');

        $this->getJson("/api/real-estate/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $property->id)
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.office_name', 'Downtown Office')
            ->assertJsonPath('data.owner_name', '')
            ->assertJsonPath('data.owner_phone', '');
    }

    public function test_real_estate_user_can_crud_properties_and_manage_images(): void
    {
        $context = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');
        $secondContext = $this->createLookupContext('Aleppo', 'تجاري', 'محلات', 'North Office');

        $viewer = User::factory()->create([
            'permetions_level' => 4,
            'real_estate_office_id' => $context['office']->id,
            'real_estate_role' => 'agent',
        ]);

        $this->actingAsSanctum($viewer);

        $createResponse = $this->postJson('/api/real-estate/properties', $this->propertyPayload(
            $context['office']->id,
            $context['category']->id,
            $context['subcategory']->id,
            'OFF-2001'
        ));

        $createResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.offer_number', 'OFF-2001')
            ->assertJsonPath('data.province_id', $context['province']->id)
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.office_name', 'Downtown Office')
            ->assertJsonPath('data.main_category_name', 'سكني')
            ->assertJsonPath('data.subcategory_name', 'منزل')
            ->assertJsonPath('data.property_nature', 'سكني')
            ->assertJsonPath('data.title_type', 'ملك')
            ->assertJsonPath('data.owner_name', 'Owner One')
            ->assertJsonPath('data.owner_phone', '0999000001');

        $propertyId = (int) $createResponse->json('data.id');

        $this->getJson('/api/real-estate/properties?office_id='.$context['office']->id)
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propertyId);

        $updateResponse = $this->putJson("/api/real-estate/properties/{$propertyId}", [
            'offer_number' => 'OFF-2001',
            'office_id' => $secondContext['office']->id,
            'main_category_id' => $secondContext['category']->id,
            'subcategory_id' => $secondContext['subcategory']->id,
            'property_nature' => 'سكني',
            'title_type' => 'ملك',
            'area' => 'New Area',
            'district' => 'New District',
            'address' => 'New Address',
            'building' => 'Building B',
            'floor' => '5',
            'direction' => 'South',
            'rooms_count' => 4,
            'area_size' => 180,
            'price' => 85000,
            'ownership_type' => 'Owner',
            'offer_type' => 'rent',
            'rent_duration' => 'monthly',
            'owner_name' => 'Owner Two',
            'owner_phone' => '0999000002',
            'status' => 'available',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.province_id', $secondContext['province']->id)
            ->assertJsonPath('data.province_name', 'Aleppo')
            ->assertJsonPath('data.office_name', 'North Office')
            ->assertJsonPath('data.main_category_name', 'تجاري')
            ->assertJsonPath('data.subcategory_name', 'محلات')
            ->assertJsonPath('data.property_nature', 'سكني')
            ->assertJsonPath('data.title_type', 'ملك')
            ->assertJsonPath('data.owner_name', 'Owner Two')
            ->assertJsonPath('data.owner_phone', '0999000002');

        $imageUploadResponse = $this->post(
            "/api/real-estate/properties/{$propertyId}/images",
            [
                'images' => [
                    UploadedFile::fake()->image('property-1.jpg'),
                    UploadedFile::fake()->image('property-2.jpg'),
                ],
            ]
        );

        $imageUploadResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data.images');

        $images = $imageUploadResponse->json('data.images');
        $firstImage = $images[0];
        $secondImage = $images[1];

        $this->assertStringContainsString('/data/uploads/properties/', (string) $firstImage['url']);
        $this->assertStringContainsString('/data/uploads/properties/', (string) $secondImage['url']);
        $this->assertTrue(File::exists(public_path('data/uploads/properties/'.$firstImage['image'])));
        $this->assertTrue(File::exists(public_path('data/uploads/properties/'.$secondImage['image'])));

        $firstImageId = (int) $firstImage['id'];

        $this->deleteJson("/api/real-estate/property-images/{$firstImageId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.images');

        $this->assertDatabaseMissing('property_images', [
            'id' => $firstImageId,
        ]);
        $this->assertFalse(File::exists(public_path('data/uploads/properties/'.$firstImage['image'])));

        $this->deleteJson("/api/real-estate/properties/{$propertyId}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('properties', [
            'id' => $propertyId,
        ]);
        $this->assertFalse(File::exists(public_path('data/uploads/properties/'.$secondImage['image'])));
        $this->assertDatabaseMissing('property_images', [
            'image' => $secondImage['image'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyPayload(
        int $officeId,
        int $categoryId,
        int $subcategoryId,
        string $offerNumber
    ): array {
        return [
            'offer_number' => $offerNumber,
            'office_id' => $officeId,
            'main_category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'property_nature' => 'سكني',
            'title_type' => 'ملك',
            'area' => 'City Center',
            'district' => 'Downtown',
            'address' => 'Main Street 10',
            'building' => 'Building A',
            'floor' => '3',
            'direction' => 'East',
            'rooms_count' => 3,
            'area_size' => 120,
            'price' => 65000,
            'ownership_type' => 'Owner',
            'offer_type' => 'sale',
            'rent_duration' => null,
            'owner_name' => 'Owner One',
            'owner_phone' => '0999000001',
            'status' => 'available',
        ];
    }

    /**
     * @return array{
     *     province: Province,
     *     office: RealEstateOffice,
     *     category: PropertyCategory,
     *     subcategory: PropertySubcategory
     * }
     */
    private function createLookupContext(
        string $provinceName,
        string $categoryName,
        string $subcategoryName,
        string $officeName
    ): array {
        $province = Province::query()->create([
            'name' => $provinceName,
            'is_active' => true,
        ]);

        $office = RealEstateOffice::query()->create([
            'province_id' => $province->id,
            'name' => $officeName,
            'address' => $officeName.' Address',
            'is_active' => true,
        ]);

        $category = PropertyCategory::query()->create([
            'name' => $categoryName,
        ]);

        $subcategory = PropertySubcategory::query()->create([
            'property_category_id' => $category->id,
            'name' => $subcategoryName,
        ]);

        return [
            'province' => $province,
            'office' => $office,
            'category' => $category,
            'subcategory' => $subcategory,
        ];
    }

    private function refreshPropertyUploadsDirectory(): void
    {
        $directory = public_path('data/uploads/properties');

        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);
    }
}
