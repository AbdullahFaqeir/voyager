<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class TxidSnapshotType extends Type
{
    public const NAME = 'txid_snapshot';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'txid_snapshot';
    }
}
