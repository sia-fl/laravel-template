<?php

namespace App\Models\Admin;

use App\ModelFilters\Admin\AdminUserFilter;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin IdeHelperAdminUser
 */
class AdminUser extends User
{
    use HasApiTokens, HasFactory, Notifiable, ModelTrait;

    protected $table = 'admin_user';

    protected $guarded = [];

    const Fulltext = [
        'username',
        'code',
        'id_code',
        'phone',
        'email'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at'
    ];


    protected $columns = [
        ''
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    function modelFilter()
    {
        return $this->provideFilter(AdminUserFilter::class);
    }

    function admin_position()
    {
        return $this->hasOne(AdminPosition::class, 'id', 'admin_position_id');
    }

    function admin_role_ids()
    {
        return $this->hasMany(AdminUserRole::class, 'admin_user_id', 'id')->select('admin_role_id')->pluck('admin_role_id');
    }

    function multiColumns()
    {
        $this->username = [
            ''
        ];
    }
}
