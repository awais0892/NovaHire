<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, MustVerifyEmailTrait;

    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'application_status_changed' => [
            'mail' => true,
            'database' => true,
            'broadcast' => true,
        ],
        'interview_scheduled' => [
            'mail' => true,
            'database' => true,
            'broadcast' => true,
        ],
        'interview_cancelled' => [
            'mail' => true,
            'database' => true,
            'broadcast' => true,
        ],
        'interview_invitation_responded' => [
            'mail' => true,
            'database' => true,
            'broadcast' => true,
        ],
        'interview_reminder' => [
            'mail' => true,
            'database' => true,
            'broadcast' => true,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password',
        'company_id', 'avatar', 'status', 'last_login_at', 'google_id',
        'notification_preferences', 'two_factor_enabled', 'two_factor_code',
        'two_factor_code_expires_at', 'two_factor_attempts', 'two_factor_last_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
            'two_factor_enabled' => 'boolean',
            'two_factor_code_expires_at' => 'datetime',
            'two_factor_last_sent_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function savedFilters()
    {
        return $this->hasMany(SavedFilter::class);
    }

    public function scopeMyCompany($query)
    {
        return $query->where('company_id', auth()->user()->company_id ?? null);
    }

    public function mergedNotificationPreferences(): array
    {
        $saved = is_array($this->notification_preferences) ? $this->notification_preferences : [];

        return array_replace_recursive(self::DEFAULT_NOTIFICATION_PREFERENCES, $saved);
    }

    public function notificationChannelsFor(string $notificationKey, array $fallback = ['database']): array
    {
        $prefs = $this->mergedNotificationPreferences();
        $channels = [];

        $source = $prefs[$notificationKey] ?? [];
        // Always persist in-app notification first so UI updates are not blocked by
        // external transport failures (e.g. Resend test-mode restrictions).
        foreach (['database', 'mail', 'broadcast'] as $channel) {
            if (($source[$channel] ?? false) === true) {
                $channels[] = $channel;
            }
        }

        $resolved = $channels ?: $fallback;

        if (!$this->broadcastNotificationsEnabled()) {
            $resolved = array_values(array_filter(
                $resolved,
                fn(string $channel) => $channel !== 'broadcast'
            ));
        }

        return $resolved !== [] ? $resolved : ['database'];
    }

    private function broadcastNotificationsEnabled(): bool
    {
        return (bool) config('recruitment.realtime_notifications.broadcast_enabled', false);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = trim((string) ($this->avatar ?? ''));

        if ($avatar === '') {
            return null;
        }

        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }

        if (str_starts_with($avatar, '/')) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }
}
