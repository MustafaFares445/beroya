<?php

namespace Tests\Feature;

use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use App\Models\PropertySubmission;
use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RealEstatePhaseFourPropertySubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_store_pending_property_submissions_and_resolve_province_from_office(): void
    {
        $context = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');

        $response = $this->postJson('/api/real-estate/property-submissions', [
            'offer_number' => 'SUB-3001',
            'office_id' => $context['office']->id,
            'main_category_id' => $context['category']->id,
            'subcategory_id' => $context['subcategory']->id,
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
            'submission_note' => 'Please call after 6 PM',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.offer_number', 'SUB-3001')
            ->assertJsonPath('data.province_id', $context['province']->id)
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.office_id', $context['office']->id)
            ->assertJsonPath('data.office_name', 'Downtown Office')
            ->assertJsonPath('data.property_nature', 'سكني')
            ->assertJsonPath('data.title_type', 'ملك')
            ->assertJsonPath('data.submission_note', 'Please call after 6 PM')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reject_reason', null)
            ->assertJsonPath('data.published_property_id', null)
            ->assertJsonPath('data.reviewed_at', null);

        $this->assertDatabaseHas('property_submissions', [
            'offer_number' => 'SUB-3001',
            'status' => 'pending',
            'province_id' => $context['province']->id,
            'office_id' => $context['office']->id,
            'submission_note' => 'Please call after 6 PM',
        ]);
    }

    public function test_real_estate_user_can_list_show_approve_and_reject_property_submissions(): void
    {
        $reviewContext = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');
        $submissionContext = $this->createLookupContext('Aleppo', 'تجاري', 'محلات');

        $submission = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                null,
                $submissionContext['province']->id,
                $submissionContext['category']->id,
                $submissionContext['subcategory']->id,
                'SUB-4001',
                'sale',
                null,
                'Keep this note on submission'
            ),
        ]);

        $secondSubmission = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                $reviewContext['office']->id,
                $reviewContext['province']->id,
                $reviewContext['category']->id,
                $reviewContext['subcategory']->id,
                'SUB-4002'
            ),
        ]);

        $reviewer = User::query()->create([
            'user_name' => 'real-estate-reviewer',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'real_estate_office_id' => $reviewContext['office']->id,
            'real_estate_role' => 'reviewer',
            'permetions_level' => 2,
            'salary' => 0,
            'phone' => '0999000009',
        ]);

        $this->actingAsSanctum($reviewer);

        $this->getJson('/api/real-estate/property-submissions')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/real-estate/property-submissions/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.offer_number', 'SUB-4001')
            ->assertJsonPath('data.province_name', 'Aleppo')
            ->assertJsonPath('data.office_name', null)
            ->assertJsonPath('data.submission_note', 'Keep this note on submission')
            ->assertJsonPath('data.status', 'pending');

        $approveResponse = $this->putJson('/api/real-estate/property-submissions/'.$submission->id.'/approve');

        $approveResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.office_name', 'Downtown Office')
            ->assertJsonPath('data.title_type', 'ملك')
            ->assertJsonPath('data.submission_note', 'Keep this note on submission');

        $this->assertNotNull($approveResponse->json('data.reviewed_at'));

        $publishedPropertyId = (int) $approveResponse->json('data.published_property_id');

        $this->assertDatabaseHas('properties', [
            'id' => $publishedPropertyId,
            'offer_number' => 'SUB-4001',
            'province_id' => $reviewContext['province']->id,
            'office_id' => $reviewContext['office']->id,
            'title_type' => 'ملك',
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('property_submissions', [
            'id' => $submission->id,
            'status' => 'approved',
            'published_property_id' => $publishedPropertyId,
            'submission_note' => 'Keep this note on submission',
        ]);

        $rejectResponse = $this->putJson('/api/real-estate/property-submissions/'.$secondSubmission->id.'/reject', [
            'reject_reason' => 'Missing customer documents',
        ]);

        $rejectResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reject_reason', 'Missing customer documents');

        $this->assertNotNull($rejectResponse->json('data.reviewed_at'));

        $this->assertDatabaseHas('property_submissions', [
            'id' => $secondSubmission->id,
            'status' => 'rejected',
            'reject_reason' => 'Missing customer documents',
        ]);
    }

    public function test_property_submission_approval_prefers_submission_office_then_reviewer_office(): void
    {
        $reviewContext = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');
        $submissionContext = $this->createLookupContext('Aleppo', 'تجاري', 'محلات', 'North Office');

        $reviewerWithOffice = User::query()->create([
            'user_name' => 'real-estate-reviewer',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'real_estate_office_id' => $reviewContext['office']->id,
            'real_estate_role' => 'reviewer',
            'permetions_level' => 2,
            'salary' => 0,
            'phone' => '0999000010',
        ]);

        $submissionWithoutOffice = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                null,
                $submissionContext['province']->id,
                $submissionContext['category']->id,
                $submissionContext['subcategory']->id,
                'SUB-5001'
            ),
        ]);

        $submissionWithOffice = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                $submissionContext['office']->id,
                $submissionContext['province']->id,
                $submissionContext['category']->id,
                $submissionContext['subcategory']->id,
                'SUB-5002'
            ),
        ]);

        $this->actingAsSanctum($reviewerWithOffice);

        $approveWithoutOffice = $this->putJson('/api/real-estate/property-submissions/'.$submissionWithoutOffice->id.'/approve');
        $approveWithoutOffice
            ->assertOk()
            ->assertJsonPath('data.office_id', $reviewContext['office']->id)
            ->assertJsonPath('data.office_name', 'Downtown Office')
            ->assertJsonPath('data.province_name', 'Damascus');

        $approveWithOffice = $this->putJson('/api/real-estate/property-submissions/'.$submissionWithOffice->id.'/approve');
        $approveWithOffice
            ->assertOk()
            ->assertJsonPath('data.office_id', $submissionContext['office']->id)
            ->assertJsonPath('data.office_name', 'North Office')
            ->assertJsonPath('data.province_name', 'Aleppo');
    }

    public function test_property_submission_approval_uses_first_active_office_when_no_reviewer_office_exists(): void
    {
        $context = $this->createLookupContext('Homs', 'سكني', 'منزل');

        $firstFallbackOffice = RealEstateOffice::query()->create([
            'province_id' => $context['province']->id,
            'name' => 'First Homs Office',
            'address' => 'First Homs Address',
            'is_active' => true,
        ]);

        RealEstateOffice::query()->create([
            'province_id' => $context['province']->id,
            'name' => 'Second Homs Office',
            'address' => 'Second Homs Address',
            'is_active' => true,
        ]);

        $managerWithoutOffice = User::query()->create([
            'user_name' => 'province-manager',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'real_estate_office_id' => null,
            'real_estate_role' => 'province_manager',
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '0999000011',
        ]);

        $submission = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                null,
                $context['province']->id,
                $context['category']->id,
                $context['subcategory']->id,
                'SUB-5003'
            ),
        ]);

        $this->actingAsSanctum($managerWithoutOffice);

        $approveFallbackOffice = $this->putJson('/api/real-estate/property-submissions/'.$submission->id.'/approve');
        $approveFallbackOffice
            ->assertOk()
            ->assertJsonPath('data.office_id', $firstFallbackOffice->id)
            ->assertJsonPath('data.office_name', 'First Homs Office')
            ->assertJsonPath('data.province_name', 'Homs');
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionPayload(
        ?int $officeId,
        int $provinceId,
        int $categoryId,
        int $subcategoryId,
        string $offerNumber,
        string $offerType = 'sale',
        ?string $rentDuration = null,
        ?string $submissionNote = null
    ): array {
        return array_filter([
            'offer_number' => $offerNumber,
            'office_id' => $officeId,
            'province_id' => $provinceId,
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
            'offer_type' => $offerType,
            'rent_duration' => $rentDuration,
            'owner_name' => 'Owner One',
            'owner_phone' => '0999000001',
            'submission_note' => $submissionNote,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function createLookupContext(
        string $provinceName,
        string $categoryName,
        string $subcategoryName,
        ?string $officeName = null
    ): array {
        $province = Province::query()->create([
            'name' => $provinceName,
            'is_active' => true,
        ]);

        $office = null;
        if ($officeName !== null) {
            $office = RealEstateOffice::query()->create([
                'province_id' => $province->id,
                'name' => $officeName,
                'address' => $officeName.' Address',
                'is_active' => true,
            ]);
        }

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
}
