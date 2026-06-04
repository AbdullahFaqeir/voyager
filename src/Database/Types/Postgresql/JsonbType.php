<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class JsonbType extends Type
{
    public const NAME = 'jsonb';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'jsonb';
    }
}
