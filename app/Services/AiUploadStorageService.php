<?php

namespace App\Services;

use App\Models\AiUploadAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiUploadStorageService
{
    /**
     * @param  array<int,mixed>  $attachments
     * @return array<int,array{name:string,type:string,mime_type:string,preview:string,asset_id:int}>
     */
    public function storeAttachments(int $tenantId, int $conversationId, array $attachments): array
    {
        $stored = [];
        $disk = (string) config('ai.uploads.disk', 'local');
        $baseDirectory = trim((string) config('ai.uploads.directory', 'ai_uploads'), '/');

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $name = trim((string) ($attachment['name'] ?? 'attachment'));
            $type = trim((string) ($attachment['type'] ?? 'file'));
            $mimeType = trim((string) ($attachment['mime_type'] ?? 'application/octet-stream'));
            $contentText = (string) ($attachment['content_text'] ?? '');
            $contentBase64 = (string) ($attachment['content_base64'] ?? '');

            $binaryPayload = '';
            $textPreview = '';
            if ($contentBase64 !== '') {
                $binaryPayload = $this->decodeBase64Payload($contentBase64);
            }
            if ($contentText !== '') {
                $textPreview = mb_substr(trim($contentText), 0, 5000);
                if ($binaryPayload === '') {
                    $binaryPayload = $contentText;
                    $mimeType = $mimeType !== '' ? $mimeType : 'text/plain';
                }
            }

            if ($binaryPayload === '') {
                continue;
            }

            $extension = $this->resolveExtension($name, $mimeType, $type);
            $path = "{$baseDirectory}/{$tenantId}/{$conversationId}/".Str::uuid()->toString().".{$extension}";
            Storage::disk($disk)->put($path, $binaryPayload);

            $asset = AiUploadAsset::query()->create([
                'tenant_id' => $tenantId,
                'ai_conversation_id' => $conversationId,
                'attachment_type' => $type !== '' ? $type : 'file',
                'name' => $name,
                'mime_type' => $mimeType !== '' ? $mimeType : null,
                'size_bytes' => strlen($binaryPayload),
                'storage_disk' => $disk,
                'storage_path' => $path,
                'text_preview' => $textPreview !== '' ? $textPreview : null,
                'meta' => [
                    'source' => 'chat_request',
                ],
            ]);

            $stored[] = [
                'name' => $name,
                'type' => $type !== '' ? $type : 'file',
                'mime_type' => $mimeType,
                'preview' => $textPreview,
                'asset_id' => (int) $asset->id,
            ];
        }

        return $stored;
    }

    private function decodeBase64Payload(string $payload): string
    {
        $normalized = trim($payload);
        if (str_contains($normalized, 'base64,')) {
            $parts = explode('base64,', $normalized, 2);
            $normalized = $parts[1] ?? '';
        }

        $decoded = base64_decode($normalized, true);

        return is_string($decoded) ? $decoded : '';
    }

    private function resolveExtension(string $name, string $mimeType, string $type): string
    {
        $fromName = pathinfo($name, PATHINFO_EXTENSION);
        if (is_string($fromName) && $fromName !== '') {
            return strtolower($fromName);
        }

        if ($type === 'image') {
            return 'png';
        }

        return match (strtolower($mimeType)) {
            'text/plain' => 'txt',
            'application/json' => 'json',
            'text/csv' => 'csv',
            default => 'bin',
        };
    }
}
