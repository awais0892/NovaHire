<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryImageService
{
    public function uploadAvatar(UploadedFile $file, int $userId): string
    {
        $baseFolder = trim((string) config('services.cloudinary.folder', 'novahire'), '/');
        $folder = trim($baseFolder . '/avatars/' . $userId, '/');

        return $this->uploadImage($file, $folder);
    }

    public function uploadImage(UploadedFile $file, string $folder): string
    {
        $credentials = $this->resolveCredentials();
        $timestamp = now()->timestamp;
        $folder = trim($folder, '/');

        $signedParams = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];

        $signature = $this->sign($signedParams, $credentials['api_secret']);
        $filePath = $file->getRealPath();

        if ($filePath === false || $filePath === '' || !is_file($filePath)) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        $endpoint = sprintf(
            'https://api.cloudinary.com/v1_1/%s/image/upload',
            $credentials['cloud_name']
        );

        $response = Http::timeout($credentials['timeout'])
            ->acceptJson()
            ->attach('file', $contents, $file->getClientOriginalName())
            ->post($endpoint, [
                'api_key' => $credentials['api_key'],
                'timestamp' => $timestamp,
                'folder' => $folder,
                'signature' => $signature,
            ]);

        if ($response->failed()) {
            $message = (string) data_get($response->json(), 'error.message', '');
            if ($message === '') {
                $message = $response->body();
            }

            throw new RuntimeException('Cloudinary upload failed: ' . $message);
        }

        $secureUrl = (string) data_get($response->json(), 'secure_url', '');
        if ($secureUrl === '') {
            throw new RuntimeException('Cloudinary upload failed: missing secure URL in response.');
        }

        return $secureUrl;
    }

    private function resolveCredentials(): array
    {
        $cloudName = trim((string) config('services.cloudinary.cloud_name', ''));
        $apiKey = trim((string) config('services.cloudinary.api_key', ''));
        $apiSecret = trim((string) config('services.cloudinary.api_secret', ''));
        $timeout = max(5, (int) config('services.cloudinary.timeout', 15));

        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            $cloudinaryUrl = (string) config('services.cloudinary.url', '');
            if ($cloudinaryUrl !== '') {
                $parsed = parse_url($cloudinaryUrl);
                if (is_array($parsed)) {
                    $cloudName = $cloudName !== '' ? $cloudName : trim((string) ($parsed['host'] ?? ''), '/');
                    $apiKey = $apiKey !== '' ? $apiKey : (string) ($parsed['user'] ?? '');
                    $apiSecret = $apiSecret !== '' ? $apiSecret : (string) ($parsed['pass'] ?? '');
                }
            }
        }

        $missing = [];
        if ($cloudName === '') {
            $missing[] = 'cloud_name';
        }
        if ($apiKey === '') {
            $missing[] = 'api_key';
        }
        if ($apiSecret === '') {
            $missing[] = 'api_secret';
        }

        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            throw new RuntimeException(
                'Cloudinary credentials are missing (' . implode(', ', $missing) . '). Set CLOUDINARY_CLOUD_NAME/CLOUDINARY_API_KEY/CLOUDINARY_API_SECRET (or CLOUD_NAME/CLOUD_API_KEY/CLOUD_API_SECRET / CLOUDINARY_KEY/CLOUDINARY_SECRET), or provide CLOUDINARY_URL.'
            );
        }

        return [
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'timeout' => $timeout,
        ];
    }

    private function sign(array $params, string $apiSecret): string
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $pairs[] = $key . '=' . $value;
        }

        return sha1(implode('&', $pairs) . $apiSecret);
    }
}
