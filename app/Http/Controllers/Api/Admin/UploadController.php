<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Hands the dashboard a short-lived signature so the browser can upload
 * straight to Cloudinary. The API secret never leaves the server, and the
 * image bytes never pass through it.
 */
class UploadController extends Controller
{
    public function signature(): JsonResponse
    {
        $config = config('services.cloudinary');

        if (blank($config['cloud_name']) || blank($config['api_key']) || blank($config['api_secret'])) {
            return response()->json([
                'message' => 'Image uploads are not configured. Add your Cloudinary credentials to the server .env.',
            ], 503);
        }

        // Only the params Cloudinary signs — `file`, `api_key` and
        // `resource_type` are deliberately excluded from the digest.
        $params = [
            'folder' => $config['folder'],
            'timestamp' => now()->getTimestamp(),
        ];

        return response()->json([
            'data' => [
                ...$params,
                'signature' => $this->sign($params, $config['api_secret']),
                'api_key' => $config['api_key'],
                'cloud_name' => $config['cloud_name'],
                'endpoint' => "https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/upload",
            ],
        ]);
    }

    /**
     * Cloudinary's scheme: params sorted by key, joined as a query string with
     * raw (un-encoded) values, the API secret appended, then SHA-1.
     */
    private function sign(array $params, string $secret): string
    {
        ksort($params);

        return sha1(urldecode(http_build_query($params)).$secret);
    }
}
