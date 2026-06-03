<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;

class ApiResponse
{
    public static function success(mixed $data = null, int $statusCode = 200, array $extra = []): JsonResponse
    {
        $message = self::resolveMessage((string) ($extra['message'] ?? 'responses.success'));
        unset($extra['message']);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => self::mergeData($data, $extra),
        ], $statusCode);
    }

    public static function failureMessage(string $message, int $statusCode = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'failure',
            'message' => self::resolveMessage($message),
            'data' => $data,
        ], $statusCode);
    }

    public static function failureData(mixed $data, int $statusCode = 400, string $message = 'responses.failure'): JsonResponse
    {
        return response()->json([
            'status' => 'failure',
            'message' => self::resolveMessage($message),
            'data' => $data,
        ], $statusCode);
    }

    private static function resolveMessage(string $messageOrKey): string
    {
        if (Lang::has($messageOrKey, 'ar')) {
            return Lang::get($messageOrKey, [], 'ar');
        }

        return $messageOrKey;
    }

    private static function mergeData(mixed $data, array $extra): mixed
    {
        if ($extra === []) {
            return $data;
        }

        if ($data === null) {
            return $extra;
        }

        if (is_array($data)) {
            return array_merge($data, $extra);
        }

        return array_merge(['value' => $data], $extra);
    }
}
