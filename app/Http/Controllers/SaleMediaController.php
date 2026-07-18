<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SaleMediaController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403);

        $filename = $request->query('file');

        abort_unless(
            is_string($filename)
            && $filename !== ''
            && basename($filename) === $filename,
            404,
        );

        $path = 'sales/'.$filename;
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $filename, [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
