<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password', 'sub', 'last_login_at', 'is_active', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Se il nome è vuoto o null, assegnagli il valore dell'email
            if (empty($user->name)) {
                $user->name = $user->email;
            }
            // 2. Se la password è vuota (es. login via SSO), genera una password casuale sicura
            if (empty($user->password)) {
                $user->password = Hash::make(Str::random(32));
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The roles assigned to this user (with pivot source: 'manual' or 'oidc').
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
                    ->withPivot('source');
    }

    /**
     * The applications directly assigned to this user (via user_application pivot).
     */
    public function directApplications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'user_application');
    }

    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /**
     * Returns the full set of applications this user can access.
     *
     * Merges direct permissions and role-based permissions, removes duplicates,
     * filters only active applications, and orders by sort_order ascending.
     *
     * @return Collection<int, Application>
     */
    public function accessibleApplications(): Collection
    {
        // Direct applications assigned to this user
        $direct = $this->directApplications()->get();

        // Applications accessible via any assigned role
        $viaRoles = $this->roles()
            ->with('applications')
            ->get()
            ->flatMap(fn (Role $role) => $role->applications);

        return $direct
            ->merge($viaRoles)
            ->unique('id')
            ->filter(fn (Application $app) => $app->is_active)
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Returns whether this user has administrative privileges.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
