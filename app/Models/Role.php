<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The applications associated with this role.
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'role_application');
    }

    /**
     * The users that have been assigned this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
                    ->withPivot('source');
    }
}
