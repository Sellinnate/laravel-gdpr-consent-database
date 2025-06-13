<?php

namespace Selli\LaravelGdprConsentDatabase\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class TestUser extends Model
{
    use HasGdprConsents, HasFactory;

    protected $table = 'test_users';
    
    protected $fillable = [
        'name',
        'email',
    ];
}
