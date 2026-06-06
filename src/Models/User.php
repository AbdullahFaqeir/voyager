<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use TCG\Voyager\Contracts\User as UserContract;
use TCG\Voyager\Tests\Database\Factories\UserFactory;
use TCG\Voyager\Traits\VoyagerUser;

class User extends Authenticatable implements UserContract
{
    use VoyagerUser, HasFactory;

    protected $guarded = [];

    public $additional_attributes = ['locale'];

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? config('voyager.user.default_avatar', 'users/default.png'),
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => collect(json_decode((string) $value)),
            set: fn ($value) => $value ? $value->toJson() : json_encode([]),
        )->withoutObjectCaching();
    }

    protected function locale(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->settings->get('locale'),
            set: fn ($value) => ['settings' => $this->settings->merge(['locale' => $value])->toJson()],
        )->withoutObjectCaching();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
