<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nomor_wa',
        'alamat',
        'password',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        $defaultAvatar = asset('assets/img/avatars/1.png');
        $path = $this->avatar;

        if (!is_string($path) || $path === '') {
            return $defaultAvatar;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, ['assets/', 'storage/'])) {
            return asset($normalized);
        }

        return asset('storage/' . $normalized);
    }

    protected function nomorWa(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => $this->sanitizeWhatsAppNumber($value),
        );
    }

    private function sanitizeWhatsAppNumber(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '08')) {
            return '628' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * @return HasMany<GraduationDocument, $this>
     */
    public function createdGraduationDocuments(): HasMany
    {
        return $this->hasMany(GraduationDocument::class, 'created_by');
    }

    /**
     * @return HasMany<GraduationDocument, $this>
     */
    public function updatedGraduationDocuments(): HasMany
    {
        return $this->hasMany(GraduationDocument::class, 'updated_by');
    }

    /**
     * @return HasMany<GraduationDocumentLog, $this>
     */
    public function graduationDocumentLogs(): HasMany
    {
        return $this->hasMany(GraduationDocumentLog::class, 'admin_user_id');
    }
}
