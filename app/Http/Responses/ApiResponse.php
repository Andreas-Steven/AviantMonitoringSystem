<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data,
        string $message,
        int $code = 200,
        ?array $meta = null,
    ): JsonResponse {
        $payload = [
            'code' => $code,
            'success' => true,
            'message' => $message,
        ];

        if ($meta !== null) {
            $payload['meta'] = (object) $meta;
        }

        $payload['data'] = $data;

        return response()->json($payload, $code);
    }

    public static function failure(
        string $message,
        int $code,
        mixed $data = null,
        ?array $meta = null,
    ): JsonResponse {
        $payload = [
            'code' => $code,
            'success' => false,
            'message' => $message,
        ];

        if ($meta !== null) {
            $payload['meta'] = (object) $meta;
        }

        $payload['data'] = $data;

        return response()->json($payload, $code);
    }
}
