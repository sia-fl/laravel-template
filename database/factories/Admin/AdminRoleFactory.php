<?php

namespace Database\Factories\Admin;

use App\Models\Admin\AdminPosition;
use App\Models\Admin\AdminRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AdminRoleFactory extends Factory
{
    protected $model = AdminRole::class;

    public function definition(): array
    {
        $num         = rand(1000, 9999);
        $name        = '角色' . $num;
        $code        = fnPinYin($name);
        $description = '描述/备注信息' . $num;
        return [
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),
            'status'      => _USED,
            'name'        => $name,
            'code'        => $code,
            'description' => $description,
        ];
    }
}
