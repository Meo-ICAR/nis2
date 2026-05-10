<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Application extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'description',
        'url',
        'project',
        'category',
        'icon_url',
        'url_documentation',
        'url_cockpit',
        'url_sandbox',
        'sort_order',
        'is_active',
        'is_strategic',
        'scientific_owner',
        'scientific_contact',
        'internal_technical_contact',
        'external_technical_contact',
        'external_technical_email',
        'criticality_level',
        'hosting_type',
        'has_mfa',
        'backup_strategy',
        'backup_replication',
        'url_job_anonimization_db',
        'management_url',
        'service_tag',
        'external_id',
        'data_sensitivity',
        'cpu',
        'ram',
        'hd',
        'ports',
        'runtime_type',
        'support_contract_expiry',
        'contract_notes',
        'client_id',
        'client_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_strategic' => 'boolean',
            'has_mfa' => 'boolean',
            'support_contract_expiry' => 'date',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The roles that have access to this application.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_application');
    }

    /**
     * The users that have direct access to this application.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_application');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope: only active applications.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: applications whose support contract expires within the given number of days.
     */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->whereNotNull('support_contract_expiry')
            ->whereBetween('support_contract_expiry', [
                Carbon::today(),
                Carbon::today()->addDays($days),
            ]);
    }

    /**
     * Scope: applications whose support contract has already expired.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('support_contract_expiry')
            ->where('support_contract_expiry', '<', Carbon::today());
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Returns the contract status based on the support_contract_expiry date.
     *
     * @return 'expired'|'expiring'|'valid'|null
     */
    public function contractStatus(): ?string
    {
        if ($this->support_contract_expiry === null) {
            return null;
        }

        $today = Carbon::today();
        $expiry = Carbon::instance($this->support_contract_expiry)->startOfDay();

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($expiry->lte($today->copy()->addDays(30))) {
            return 'expiring';
        }

        return 'valid';
    }
}
