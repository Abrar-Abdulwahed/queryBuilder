<?php
// namespace DevCoder;
require_once ('Select.php');
require_once ('DBConnection.php');
class QueryBuilder
{
    public $pdo;
    public function __construct()
    {
        $this->pdo = DBConnection::connect();
    }
    private $fields = [];
    private $conditions = [];
    private $order = [];
    private $group = [];
    private $from = [];
    private $innerJoin = [];
    private $leftJoin = [];
    private $rightJoin = [];
    private $limit;
    private $count = '';

    public function reset()
    {
        $this->fields = [];
        $this->conditions = [];
        $this->order = [];
        $this->group = [];
        $this->from = [];
        $this->innerJoin = [];
        $this->leftJoin = [];
        $this->rightJoin = [];
        $this->limit = null;
        $this->count = '';
    }

    public function select(string ...$select): self
    {
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }
        return $this;
    }

    public function __toString(): string
    {
        
        $query =  'SELECT ' . implode(', ', $this->fields)
            . ' FROM ' . implode(', ', $this->from)
            . ($this->leftJoin   === [] ? '' : ' LEFT JOIN '. implode(' LEFT JOIN ', $this->leftJoin))
            . ($this->rightJoin  === [] ? '' : ' RIGHT JOIN '. implode(' RIGHT JOIN ', $this->rightJoin))
            . ($this->innerJoin  === [] ? '' : ' INNER JOIN '. implode(' INNER JOIN ', $this->innerJoin))
            . ($this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions))
            . ($this->order      === [] ? '' : ' ORDER BY ' . implode(', ', $this->order))
            . ($this->group      === [] ? '' : ' GROUP BY ' . implode(', ', $this->group))
            . ($this->limit      === null ? '' : ' LIMIT ' . $this->limit);
        return $query;
    }

    public function where(string ...$where): self
    {
        foreach ($where as $arg) {
            $this->conditions[] = $arg;
        }
        return $this;
    }
    public function orWhere(string $column, string $opreation, $value){
        $condition = $column . " " . $opreation . "  '$value'";
        $this->condition = $this->condition . ' OR ' . $condition;

        return $this;
    }
    public function count(string $column): self
    {
        $this->count = $column;
        return $this;
    }
    
    public function from(string $table, ?string $alias = null): self
    {
        $this->from[] = $alias === null ? $table : "${table} AS ${alias}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function orderBy(string ...$order): self
    {
        foreach ($order as $arg) {
            $this->order[] = $arg;
        }
        return $this;
    }
    public function groupBy(string ...$group): self
    {
        foreach ($group as $arg) {
            $this->group[] = $arg;
        }
        return $this;
    }

    public function innerJoin(string ...$join): self
    {
        $this->leftJoin = [];
        foreach ($join as $arg) {
            $this->innerJoin[] = $arg;
        }
        return $this;
    }

    public function leftJoin(string ...$join): self
    {
        $this->innerJoin = [];
        foreach ($join as $arg) {
            $this->leftJoin[] = $arg;
        }
        return $this;
    }
    public function rightJoin(string ...$join): self
    {
        $this->innerJoin = [];
        foreach ($join as $arg) {
            $this->rightJoin[] = $arg;
        }
        return $this;
    }
}