<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Finxp\Flexcube\Database\Factories\ServiceFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return ServiceFactory::new();
    }
}
