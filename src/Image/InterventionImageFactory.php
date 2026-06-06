<?php

namespace TCG\Voyager\Image;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Builds Intervention Image v4 managers using whichever driver
 * is available on the host (Imagick preferred, GD fallback).
 */
abstract class InterventionImageFactory
{
    public static function manager(): ImageManagerInterface
    {
        return ImageManager::usingDriver(
            extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class
        );
    }

    public static function decode(mixed $source): ImageInterface
    {
        return static::manager()->decode($source);
    }
}
