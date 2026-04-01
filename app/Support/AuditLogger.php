<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, Model|int|null $entity = null, array $metadata = []): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'company_id' => $user?->company_id,
            'action' => $action,
            'entity_type' => $entity instanceof Model ? $entity::class : null,
            'entity_id' => $entity instanceof Model ? $entity->getKey() : (is_int($entity) ? $entity : null),
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
