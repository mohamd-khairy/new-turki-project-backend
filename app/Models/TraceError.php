<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TraceError extends Model
{

    
      protected $fillable = [
        'class_name',
        'method_name',
        'error_desc'
      
    ];

}
