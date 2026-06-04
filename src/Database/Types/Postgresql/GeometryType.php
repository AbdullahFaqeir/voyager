<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class GeometryType extends Type
{
    public const NAME = 'geometry';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'geometry';
    }
}
