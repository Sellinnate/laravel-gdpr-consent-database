<?php

namespace Selli\LaravelGdprConsentDatabase\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class TestUser extends Model
{
    use HasFactory, HasGdprConsents;

    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
    ];
}
