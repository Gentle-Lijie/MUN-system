<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RuntimeException;

class User extends Model
{
    public const DEFAULT_PASSWORD = '123456';

    public const ROLE_CHOICES = ['admin', 'dais', 'delegate', 'observer'];

    public const ROLE_PERMISSIONS = [
        'admin' => [
            'users:manage',
            'presidium:manage',
            'delegates:manage',
            'logs:read',
        ],
        'dais' => [
            'presidium:manage',
            'timeline:update',
            'crisis:dispatch',
            'messages:broadcast',
        ],
        'delegate' => [
            'delegate:self',
            'documents:submit',
            'messages:send',
        ],
        'observer' => [
            'observer:read',
            'reports:view',
        ],
    ];

    protected $table = 'Users';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'organization',
        'phone',
        'last_login',
        'session_token',
        'permissions',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class, 'user_id');
    }

    public function setPassword(string $rawPassword): void
    {
        $hash = password_hash($rawPassword, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new RuntimeException('Failed to hash password');
        }
        $this->password_hash = $hash;
    }

    public function checkPassword(string $rawPassword): bool
    {
        $hash = $this->password_hash;
        if (!$hash) {
            return false;
        }
        return password_verify($rawPassword, $hash);
    }

    public function issueSessionToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session_token = $token;
        return $token;
    }

    public function clearSessionToken(): void
    {
        $this->session_token = null;
    }

    /**
     * @return array<string>
     */
    public function effectivePermissions(): array
    {
        $raw = $this->permissions;
        if (is_string($raw) && $raw !== '' && $raw !== '[]') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }
        return self::ROLE_PERMISSIONS[$this->role] ?? [];
    }

    /**
     * @param array<int, string> $permissions
     */
    public function setEffectivePermissions(array $permissions): void
    {
        $this->permissions = json_encode(array_values($permissions), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<int, string>
     */
    public static function defaultPermissions(string $role): array
    {
        if ($role === 'observer') {
            return ['observer:read'];
        }
        if ($role === 'delegate') {
            return self::ROLE_PERMISSIONS['delegate'];
        }
        if (in_array($role, ['admin', 'dais'], true)) {
            $all = [];
            foreach (self::ROLE_PERMISSIONS as $permissions) {
                foreach ($permissions as $permission) {
                    if (!in_array($permission, $all, true)) {
                        $all[] = $permission;
                    }
                }
            }
            return $all;
        }
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'organization' => $this->organization,
            'committee' => $this->organization,
            'phone' => $this->phone,
            'lastLogin' => $formatDate($this->last_login),
            'createdAt' => $formatDate($this->created_at),
            'updatedAt' => $formatDate($this->updated_at),
            'permissions' => $this->effectivePermissions(),
        ];
    }
}
