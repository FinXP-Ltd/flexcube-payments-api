<?php

namespace Finxp\Flexcube\Tests\Mocks\Traits;

use Illuminate\Support\Str;

trait Uuids
{
    protected static function bootUuids(): void
    {
        static::creating(function ($model) {
            do {
                $uuid = (string) Str::uuid();
            } while (self::where($model->getKeyName(), $uuid)->exists());

            $model->{$model->getKeyName()} = $uuid;
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
