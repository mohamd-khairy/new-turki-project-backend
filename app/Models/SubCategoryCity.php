<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoryCity extends Model
{
    use HasFactory;

    protected $fillable = ['sub_category_id','city_id'];
    protected $hidden = ['created_at', 'updated_at','pivot'];

}
