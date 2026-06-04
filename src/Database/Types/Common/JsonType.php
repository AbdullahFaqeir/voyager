<?php

namespace TCG\Voyager\Database\Types\Common;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class JsonType extends Type
{
    public const NAME = 'json';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'json';
    }
}
