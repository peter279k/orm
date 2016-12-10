<?php

namespace ORM;

use ORM\QueryBuilder\ParenthesisInterface;

class QueryBuilder implements QueryBuilderInterface
{
    /** @var string */
    protected $tableName = '';

    /** @var string */
    protected $alias = '';

    /** @var array */
    protected $columns = null;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /** @var string[] */
    protected $groupBy = [];

    /** @var string[] */
    protected $orderBy = [];

    /** @var EntityManager */
    protected $entityManager;

    /** @var string */
    protected $connection;

    /** @var EntityManager */
    public static $defaultEntityManager;

    /** @var string */
    public static $defaultConnection = 'default';

    /**
     * QueryBuilder constructor
     *
     * @param string $tableName
     * @param string $alias
     * @param EntityManager $entityManager
     * @param string $connection
     */
    public function __construct($tableName, $alias = '', EntityManager $entityManager = null, $connection = null)
    {
        $this->tableName = $tableName;
        $this->alias = $alias;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
    }

    protected static function convertPlaceholders(
        $expression,
        array $args,
        EntityManager $entityManager = null,
        $connection = null
    ) {
        if (!$entityManager) {
            $entityManager = static::$defaultEntityManager;
        }

        if (!$connection) {
            $connection = static::$defaultConnection;
        }

        $parts = explode('?', $expression);
        $expression = '';
        while ($part = array_shift($parts)) {
            $expression .= $part;
            if (count($args)) {
                $expression .= $entityManager->convertValue(array_shift($args), $connection);
            } elseif (count($parts)) {
                $expression .= '?';
            }
        }

        return $expression;
    }

    /**
     * Set $columns
     *
     * @param $columns
     * @return self
     */
    public function columns(array $columns = null)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Add $column
     *
     * Optionally you can provide an expression with question marks as placeholders filled with $args.
     *
     * @param string $column
     * @param array $args
     * @return QueryBuilder
     */
    public function column($column, $args = [], $alias = '')
    {
        if ($this->columns === null) {
            $this->columns = [];
        }

        if (!is_array($args)) {
            $args = [$args];
        }

        if (strpos($column, '?') !== false) {
            $expression = static::convertPlaceholders($column, $args, $this->entityManager, $this->connection);
        } else {
            $expression = $column;
        }

        $this->columns[] = $expression . ($alias ? ' AS ' . $alias : '');

        return $this;
    }

    /** {@inheritdoc} */
    public function where($column, $operator = '', $value = '')
    {
        return $this;
    }

    /** {@inheritdoc} */
    public function andWhere($column, $operator = '', $value = '')
    {
        return $this;
    }

    /** {@inheritdoc} */
    public function orWhere($column, $operator = '', $value = '')
    {
    }

    /** {@inheritdoc} */
    public function parenthesis()
    {
        // TODO: Implement parenthesis() method.
    }

    /** {@inheritdoc} */
    public function andParenthesis()
    {
        // TODO: Implement andParenthesis() method.
    }

    /** {@inheritdoc} */
    public function orParenthesis()
    {
        // TODO: Implement orParenthesis() method.
    }

    /** {@inheritdoc} */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /** {@inheritdoc} */
    public function join($tableName, array $options = [])
    {
        // TODO: Implement join() method.
    }

    /** {@inheritdoc} */
    public function leftJoin($tableName, array $options = [])
    {
        // TODO: Implement leftJoin() method.
    }

    /** {@inheritdoc} */
    public function rightJoin($tableName, array $options = [])
    {
        // TODO: Implement rightJoin() method.
    }

    /** {@inheritdoc} */
    public function fullJoin($tableName, array $options = [])
    {
        // TODO: Implement fullJoin() method.
    }

    /** {@inheritdoc} */
    public function groupBy($column, $args = [])
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        if (strpos($column, '?') !== false) {
            $this->groupBy[] = static::convertPlaceholders($column, $args, $this->entityManager, $this->connection);
        } else {
            $this->groupBy[] = $column;
        }

        return $this;
    }

    /** {@inheritdoc} */
    public function orderBy($column, $direction = self::DIRECTION_ASCENDING, $args = [])
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        if (strpos($column, '?') !== false) {
            $expression = static::convertPlaceholders($column, $args, $this->entityManager, $this->connection);
        } else {
            $expression = $column;
        }

        $this->orderBy[] = $expression . ' ' . $direction;

        return $this;
    }

    /** {@inheritdoc} */
    public function limit($limit)
    {
        $this->limit = (int)$limit;

        return $this;
    }

    /** {@inheritdoc} */
    public function offset($offset)
    {
        $this->offset = (int)$offset;

        return $this;
    }

    /** {@inheritdoc} */
    public function getQuery()
    {
        return 'SELECT ' . ($this->columns ? implode(',', $this->columns) : '*')
               . ' FROM ' . $this->tableName . ($this->alias ? ' AS ' . $this->alias : '')
               . (!empty($this->groupBy) ? ' GROUP BY ' . join(',', $this->groupBy) : '')
               . (!empty($this->orderBy) ? ' ORDER BY ' . join(',', $this->orderBy) : '')
               . ($this->limit ? ' LIMIT ' . $this->limit . ($this->offset ? ' OFFSET ' . $this->offset : '') : '');
    }
}
