<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Doctrine\DBAL\Driver\Middleware;
use TCG\Voyager\Database\Types\Type;
use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Schema\Index as DoctrineIndex;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\PDO\Connection as DoctrinePDOConnection;

abstract class SchemaManager
{

    // todo: trim parameters

    /**
     * Cached DBAL connections, keyed by Laravel connection name.
     *
     * @var array<string, array{pdo: \PDO, connection: Connection}>
     */
    protected static array $connections = [];

    public static function __callStatic($method, $args)
    {
        $connection = static::getDatabaseConnection();

        if (method_exists($connection, $method)) {
            return $connection->$method(...$args);
        }

        // Fall back to the schema manager (e.g. dropTable, alterTable, renameTable)
        return $connection->createSchemaManager()->$method(...$args);
    }

    public static function manager(): AbstractSchemaManager
    {
        return self::getDatabaseConnection()->createSchemaManager();
    }

    public static function getDatabaseConnection(): Connection
    {
        $laravelConnection = DB::connection();
        $connectionName = $laravelConnection->getName() ?? 'default';
        $pdo = $laravelConnection->getPdo();

        // Reuse the cached DBAL connection as long as it wraps the same PDO,
        // so platform type mappings registered on it are preserved.
        if (isset(static::$connections[$connectionName]) && static::$connections[$connectionName]['pdo'] === $pdo) {
            return static::$connections[$connectionName]['connection'];
        }

        $config = $laravelConnection->getConfig();

        // Map Laravel driver names to Doctrine DBAL driver names
        $driver = match ($config['driver'] ?? '') {
            'mysql', 'mariadb', 'pdo_mysql' => 'pdo_mysql',
            'pgsql', 'pdo_pgsql' => 'pdo_pgsql',
            'sqlite', 'pdo_sqlite' => 'pdo_sqlite',
            'sqlsrv', 'pdo_sqlsrv' => 'pdo_sqlsrv',
            default => throw new \InvalidArgumentException('Unsupported database driver ['.($config['driver'] ?? '').'].'),
        };

        $connectionParams = [
            'driver' => $driver, 'dbname' => $config['database'] ?? '', 'user' => $config['username'] ?? '',
            'password' => $config['password'] ?? '', 'host' => $config['host'] ?? '',
        ];

        // Reuse Laravel's PDO instance so Doctrine operates on the exact same
        // database connection (essential for sqlite :memory: and transactions).
        $configuration = new Configuration();
        $configuration->setMiddlewares([
            new class($pdo) implements Middleware {

                public function __construct(private \PDO $pdo)
                {
                }

                public function wrap(DoctrineDriver $driver): DoctrineDriver
                {
                    return new class($driver, $this->pdo) extends AbstractDriverMiddleware {

                        public function __construct(DoctrineDriver $wrappedDriver, private \PDO $pdo)
                        {
                            parent::__construct($wrappedDriver);
                        }

                        public function connect(#[\SensitiveParameter] array $params): DoctrineDriver\Connection
                        {
                            return new DoctrinePDOConnection($this->pdo);
                        }
                    };
                }
            },
        ]);

        $connection = DriverManager::getConnection($connectionParams, $configuration);

        static::$connections[$connectionName] = [
            'pdo'        => $pdo,
            'connection' => $connection,
        ];

        return $connection;
    }

    /**
     * Get all table names as plain strings (DBAL 4 returns name objects).
     *
     * @return string[]
     * @throws Exception
     */
    public static function listTableNames(): array
    {
        return array_map(fn($name) => $name->getUnqualifiedName()->getValue(),
            static::manager()->introspectTableNames());
    }

    public static function tableExists($table): bool
    {
        if (!is_array($table)) {
            $table = [$table];
        }

        return static::manager()->tablesExist($table);
    }

    public static function listTables(): array
    {
        $tables = [];

        foreach (static::listTableNames() as $tableName) {
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * @param  string  $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     * @throws \Doctrine\DBAL\Exception
     */
    public static function listTableDetails(string $tableName): Table
    {
        $columns = static::manager()->introspectTableColumns(OptionallyQualifiedName::quoted($tableName));

        $foreignKeys = [];
        $platform = SchemaManager::getDatabaseConnection()->getDatabasePlatform();
        if (method_exists($platform, 'supportsForeignKeyConstraints') && $platform->supportsForeignKeyConstraints()) {
            $foreignKeys = static::manager()->introspectTableForeignKeyConstraints(OptionallyQualifiedName::quoted($tableName));
        }

        $indexes = static::manager()->introspectTableIndexes(OptionallyQualifiedName::quoted($tableName));

        // DBAL 4 introspects the primary key as a separate constraint instead
        // of an index; convert it back to a primary index so the rest of the
        // codebase (and views) keep seeing it among the indexes.
        $hasPrimaryIndex = false;
        foreach ($indexes as $index) {
            if ($index->isPrimary()) {
                $hasPrimaryIndex = true;
                break;
            }
        }

        if (!$hasPrimaryIndex) {
            $primaryKey = static::manager()->introspectTablePrimaryKeyConstraint(
                OptionallyQualifiedName::quoted($tableName)
            );

            if ($primaryKey !== null) {
                $pkColumns = array_map(
                    fn ($column) => $column->getIdentifier()->getValue(),
                    $primaryKey->getColumnNames()
                );

                $indexes[] = new DoctrineIndex(
                    $primaryKey->getObjectName()?->getIdentifier()->getValue() ?? 'primary',
                    $pkColumns,
                    true,
                    true
                );
            }
        }

        return new Table($tableName, $columns, $indexes, [], $foreignKeys, []);
    }

    /**
     * Describes given table.
     *
     * @param  string  $tableName
     *
     * @return \Illuminate\Support\Collection
     * @throws \Doctrine\DBAL\Exception
     */
    public static function describeTable(string $tableName): Collection
    {
        Type::registerCustomPlatformTypes();

        $table = static::listTableDetails($tableName);

        return collect($table->columns)->map(function ($column) use ($table) {
            $columnArr = Column::toArray($column);

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = $columnArr['type']['name'];

            // Set the indexes and key
            $columnArr['key'] = null;
            if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                foreach ($columnArr['indexes'] as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]['type'];
                $columnArr['key'] = substr($indexType, 0, 3);
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames($tableName): array
    {
        Type::registerCustomPlatformTypes();

        $columnNames = [];

        foreach (static::manager()->introspectTableColumns(OptionallyQualifiedName::unquoted($tableName)) as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    public static function createTable($table): void
    {
        if (!($table instanceof DoctrineTable)) {
            $table = Table::make($table);
        }

        static::manager()->createTable($table);
    }

    public static function getDoctrineTable($table)
    {
        $table = trim($table);

        if (!static::tableExists($table)) {
            throw new \Exception("Table $table does not exist.");
        }

        return static::manager()->listTableDetails($table);
    }

    public static function getDoctrineColumn($table, $column)
    {
        return static::getDoctrineTable($table)->getColumn($column);
    }
}
