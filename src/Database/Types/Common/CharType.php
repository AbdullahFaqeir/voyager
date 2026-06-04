<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class CharType extends Type
{

    public const NAME = 'char';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = empty($column['length']) ? 1 : $column['length'];

        return "char({$column['length']})";
    }
}
