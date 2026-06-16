<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;

class Client extends User
{
    use Notifiable;
    protected $guard = 'client';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'company_name',
        'phone',
        'zip_code',
        'can_login',
        'avatar',
        'avatar_media_id',  // ADR-005 Wave 1
        'status',
        'country',
        'city',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ── ADR-005 Wave 1 Media Relations ─────────────────────────────────────

    /** The client's avatar as a Media record (Pattern A). */
    public function avatarMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_media_id');
    }

    // ── ADR-005 Wave 1 Read Helper ───────────────────────────────────────────

    /** Best available path: FK relation first, old path column as fallback. */
    public function resolvedAvatarPath(): ?string
    {
        return $this->avatarMedia?->file_path ?? $this->getRawOriginal('avatar') ?? null;
    }

    // ── Other Relations ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function templateReviews()
    {
        return $this->hasMany(TemplateReview::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id')
                    ->where('actor_type', 'client');
    }
}
