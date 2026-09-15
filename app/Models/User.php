<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\PaymentRequest;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
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
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Roles: same admin/staff split used across the other portals, wired
     * up by CheckRole (route middleware 'role:admin,staff') rather than
     * checked ad hoc in controllers.
     */
    public const ROLES = [
        'admin' => 'Admin',
        'staff' => 'Staff',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Backs EnsureUserIsActive - a suspended account is signed out on its
     * very next request, not just blocked from logging back in.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'active' ? 'status-success' : 'status-danger';
    }

    /**
     * Up to two uppercase initials from the user's name, for the avatar
     * chip in the navbar / user menu / logout confirmation modal - same
     * convention used in every other portal built on this design system.
     */
    public function initials(): string
    {
        return collect(explode(' ', trim($this->name ?? 'U')))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Called from a Login event listener (see FortifyServiceProvider) on
     * every successful authentication - keeps last_login_at/ip and
     * last_activity_at current without a separate activity-tracking
     * middleware for this simple use case.
     */
    public function recordLogin(string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_activity_at' => now(),
        ])->save();
    }

    /**
     * Payment requests originally created by this user - see
     * PaymentRequest::created_by. Distinct from assignedPaymentRequests():
     * the requester and the current assignee are separate concepts and
     * neither relation overwrites the other.
     */
    public function createdPaymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'created_by');
    }

    /**
     * Payment requests currently sitting with this user for action - see
     * PaymentRequest::current_assignee_id. Backs "My Tasks".
     */
    public function assignedPaymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'current_assignee_id');
    }
}
