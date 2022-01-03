<?php

namespace App\Models\Admin;

use App\Cache\Cache;
use App\ModelFilters\Admin\AdminDepartmentFilter;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperAdminDepartment
 */
class AdminDepartment extends Model
{
    use HasFactory;

    protected $table = 'admin_department';

    protected $hidden = [];

    function modelFilter()
    {
        return $this->provideFilter(AdminDepartmentFilter::class);
    }

    function admin_position()
    {
        return $this->hasMany(AdminPosition::class, 'admin_department_id', 'id');
    }
}
