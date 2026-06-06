<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Traits\Translatable;

class MenuItem extends Model
{
    use Translatable;

    protected $translatorMethods = [
        'link' => 'translatorLink',
    ];

    protected $table = 'menu_items';

    protected $guarded = [];

    protected $translatable = ['title'];

    protected static function booted(): void
    {
        static::created(function (self $model) {
            $model->menu->removeMenuFromCache();
        });

        static::saved(function (self $model) {
            $model->menu->removeMenuFromCache();
        });

        static::deleted(function (self $model) {
            $model->menu->removeMenuFromCache();
        });
    }

    public function children()
    {
        return $this->hasMany(Voyager::modelClass('MenuItem'), 'parent_id')
            ->with('children');
    }

    public function menu()
    {
        return $this->belongsTo(Voyager::modelClass('Menu'));
    }

    public function link($absolute = false)
    {
        return $this->prepareLink($absolute, $this->route, $this->parameters, $this->url);
    }

    public function translatorLink($translator, $absolute = false)
    {
        return $this->prepareLink($absolute, $translator->route, $translator->parameters, $translator->url);
    }

    protected function prepareLink($absolute, $route, $parameters, $url)
    {
        if (is_null($parameters)) {
            $parameters = [];
        }

        if (is_string($parameters)) {
            $parameters = json_decode($parameters, true);
        } elseif (is_array($parameters)) {
            $parameters = $parameters;
        } elseif (is_object($parameters)) {
            $parameters = json_decode(json_encode($parameters), true);
        }

        if (!is_null($route)) {
            if (!Route::has($route)) {
                return '#';
            }

            return route($route, $parameters, $absolute);
        }

        if ($absolute) {
            return url($url);
        }

        return $url;
    }

    protected function parameters(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value ?? ''),
            set: fn ($value) => is_array($value) ? json_encode($value) : $value,
        )->withoutObjectCaching();
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?? '',
        );
    }

    /**
     * Return the Highest Order Menu Item.
     *
     * @param number $parent (Optional) Parent id. Default null
     *
     * @return number Order number
     */
    public function highestOrderMenuItem($parent = null)
    {
        $order = 1;

        $item = $this->where('parent_id', '=', $parent)
            ->orderBy('order', 'DESC')
            ->first();

        if (!is_null($item)) {
            $order = intval($item->order) + 1;
        }

        return $order;
    }
}
