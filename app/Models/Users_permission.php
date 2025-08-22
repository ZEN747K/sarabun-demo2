<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users_permission extends Model
{
    use HasFactory;

    protected $table = 'users_permissions';


    public $timestamps = false;

    protected $fillable = [
        'users_id',
        'permission_id',
        'position_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];
}
