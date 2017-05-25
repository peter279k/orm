<?php

namespace ORM\Dbal;

use ORM\Exception;

/**
 * Database abstraction for MySQL databases
 *
 * @package ORM\Dbal
 * @author  Thomas Flori <thflori@gmail.com>
 */
class Mysql extends Dbal
{
    protected static $typeMapping = [
        'tinyint' => Type\Number::class,
        'smallint' => Type\Number::class,
        'mediumint' => Type\Number::class,
        'int' => Type\Number::class,
        'bigint' => Type\Number::class,
        'decimal' => Type\Number::class,
        'float' => Type\Number::class,
        'double' => Type\Number::class,

        'varchar' => Type\VarChar::class,
        'char' => Type\VarChar::class,

        'text' => Type\Text::class,
        'tinytext' => Type\Text::class,
        'mediumtext' => Type\Text::class,
        'longtext' => Type\Text::class,

        'datetime' => Type\DateTime::class,
        'date' => Type\DateTime::class,
        'timestamp' => Type\DateTime::class,

        'time' => Type\Time::class,
        'enum' => Type\Enum::class,
        'set' => Type\Set::class,
        'json' => Type\Json::class,
    ];

    public function insert($entity, $useAutoIncrement = true)
    {
        $statement = $this->buildInsertStatement($entity);
        $pdo = $this->em->getConnection();

        if ($useAutoIncrement && $entity::isAutoIncremented()) {
            $pdo->query($statement);
            return $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        }

        $pdo->query($statement);
        $this->em->sync($entity, true);
        return true;
    }

    public function describe($table)
    {
        try {
            $result = $this->em->getConnection()->query('DESCRIBE ' . $this->escapeIdentifier($table));
        } catch (\PDOException $exception) {
            throw new Exception('Unknown table ' . $table, 0, $exception);
        }

        $cols = [];
        while ($rawColumn = $result->fetch(\PDO::FETCH_ASSOC)) {
            $columnDefinition = $this->normalizeColumnDefinition($rawColumn);
            $cols[] = Column::factory($columnDefinition, $this->getType($columnDefinition));
        }

        return $cols;
    }

    /**
     * Normalize a column definition
     *
     * The column definition from "DESCRIBE <table>" is to special as useful. Here we normalize it to a more
     * ANSI-SQL style.
     *
     * @param array $rawColumn
     * @return array
     */
    protected function normalizeColumnDefinition($rawColumn)
    {
        $definition = [];
        $definition['data_type'] = $this->normalizeType($rawColumn['Type']);
        $definition['column_name'] = $rawColumn['Field'];
        $definition['is_nullable'] = $rawColumn['Null'] === 'YES';
        $definition['column_default'] = $rawColumn['Default'] !== null ? $rawColumn['Default'] :
            ($rawColumn['Extra'] === 'auto_increment' ? 'sequence(AUTO_INCREMENT)' : null);
        $definition['character_maximum_length'] = null;
        $definition['datetime_precision'] = null;

        switch ($definition['data_type']) {
            case 'varchar':
            case 'char':
                $definition['character_maximum_length'] = $this->extractParenthesis($rawColumn['Type']);
                break;
            case 'datetime':
            case 'timestamp':
            case 'time':
                $definition['datetime_precision'] = $this->extractParenthesis($rawColumn['Type']);
                break;
            case 'set':
            case 'enum':
                $definition['enumeration_values'] = $this->extractParenthesis($rawColumn['Type']);
                break;
        }

        return $definition;
    }

    protected function getType($columnDefinition)
    {
        if (isset(static::$typeMapping[$columnDefinition['data_type']])) {
            $factory = [static::$typeMapping[$columnDefinition['data_type']], 'factory'];
            return call_user_func($factory, $this, $columnDefinition);
        }

        return parent::getType($columnDefinition);
    }
}