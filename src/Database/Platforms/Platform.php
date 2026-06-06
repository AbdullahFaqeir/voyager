<?php

namespace TCG\Voyager\Database\Platforms;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Illuminate\Support\Collection;

abstract class Platform
{
    // abstract public static function getTypes(Collection $typeMapping);

    // abstract public static function registerCustomTypeOptions();

    /**
     * Get the platform name from a DBAL platform instance.
     * Replaces AbstractPlatform::getName(), which was removed in DBAL 4.
     */
    public static function getPlatformName(AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof AbstractMySQLPlatform => 'mysql',
            $platform instanceof PostgreSQLPlatform    => 'postgresql',
            $platform instanceof SQLitePlatform        => 'sqlite',
            $platform instanceof SQLServerPlatform     => 'sqlserver',
            default => throw new \Exception('Unsupported platform '.get_class($platform)),
        };
    }

    public static function getPlatform($platformName)
    {
        $platform = __NAMESPACE__.'\\'.ucfirst($platformName);

        if (!class_exists($platform)) {
            throw new \Exception("Platform {$platformName} doesn't exist");
        }

        return $platform;
    }

    public static function getPlatformTypes($platformName, Collection $typeMapping)
    {
        $platform = static::getPlatform($platformName);

        return $platform::getTypes($typeMapping);
    }

    public static function registerPlatformCustomTypeOptions($platformName)
    {
        $platform = static::getPlatform($platformName);

        return $platform::registerCustomTypeOptions();
    }
}
