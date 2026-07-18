<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SaleMediaTest extends TestCase
{
    public function test_signed_sale_media_url_serves_private_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('sales/test-image.jpg', 'private-image-content');

        $url = URL::temporarySignedRoute(
            'sale-media.show',
            now()->addMinutes(5),
            ['file' => 'test-image.jpg'],
            absolute: false,
        );

        $this->get($url)
            ->assertOk()
            ->assertContent('private-image-content');
    }

    public function test_unsigned_sale_media_request_is_forbidden(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('sales/test-image.jpg', 'private-image-content');

        $this->get('/sale-media?file=test-image.jpg')
            ->assertForbidden();
    }

    public function test_signed_sale_media_request_rejects_path_traversal(): void
    {
        Storage::fake('local');

        $url = URL::temporarySignedRoute(
            'sale-media.show',
            now()->addMinutes(5),
            ['file' => '../.env'],
            absolute: false,
        );

        $this->get($url)->assertNotFound();
    }
}
