<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class BitVaryingType extends Type
{
    public const NAME = 'bit varying';
    public const DBTYPE = 'varbit';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $length = empty($field['length']) ? 255 : $field['length'];

        return "varbit({$length})";
    }
}
