<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class TimeStampTzType extends Type
{
    public const NAME = 'timestamptz';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'timestamp(0) with time zone';
    }
}
