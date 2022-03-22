<?php

require_once('DBConnection.php');

class QueryBuilder
{
    private $pdo;
	private static $instance = null;
	private $dbh = null, $table, $columns, $sql, $bindValues, $getSQL,
	$where, $orWhere, $whereCount=0, $isOrWhere = false,
	$rowCount=0, $limit, $orderBy;

    public function __construct()
    {
        $this->pdo = DBConnection::connect();
    }

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new DB();
		}
		return self::$instance;
	}
	public function query($query, $args = [], $quick = false)
	{
		$this->resetQuery();
		$query = trim($query);
		$this->getSQL = $query;
		$this->bindValues = $args;

		if ($quick == true) {
			$stmt = $this->dbh->prepare($query);
			$stmt->execute($this->bindValues);
			$this->rowCount = $stmt->rowCount();
			return $stmt->fetchAll();
		}else{
			if (strpos( strtoupper($query), "SELECT" ) === 0 ) {
				$stmt = $this->dbh->prepare($query);
				$stmt->execute($this->bindValues);
				$this->rowCount = $stmt->rowCount();

				$rows = $stmt->fetchAll(PDO::FETCH_CLASS,'MareiObj');
				$collection= [];
				$collection = new MareiCollection;
				$x=0;
				foreach ($rows as $key => $row) {
					$collection->offsetSet($x++,$row);
				}

				return $collection;

			}else{
				$this->getSQL = $query;
				$stmt = $this->dbh->prepare($query);
				$stmt->execute($this->bindValues);
				return $stmt->rowCount();
			}
		}
	}
	public function exec()
	{
		//assimble query
			$this->sql .= $this->where;
			$this->getSQL = $this->sql;
			$stmt = $this->dbh->prepare($this->sql);
			$stmt->execute($this->bindValues);
			return $stmt->rowCount();
	}

	private function resetQuery()
	{
		$this->table = null;
		$this->columns = null;
		$this->sql = null;
		$this->bindValues = null;
		$this->limit = null;
		$this->orderBy = null;
		$this->getSQL = null;
		$this->where = null;
		$this->orWhere = null;
		$this->whereCount = 0;
		$this->isOrWhere = false;
		$this->rowCount = 0;
		$this->lastIDInserted = 0;
	}

	public function delete($table_name, $id=null)
	{
		$this->resetQuery();

		$this->sql = "DELETE FROM `{$table_name}`";
		
		if (isset($id)) {
			if (is_numeric($id)) {
				$this->sql .= " WHERE `id` = ?";
				$this->bindValues[] = $id;
			}elseif (is_array($id)) {
				$arr = $id;
				$count_arr = count($arr);
				$x = 0;

				foreach ($arr as  $param) {
					if ($x == 0) {
						$this->where .= " WHERE ";
						$x++;
					}else{
						if ($this->isOrWhere) {
							$this->where .= " Or ";
						}else{
							$this->where .= " AND ";
						}
						
						$x++;
					}
					$count_param = count($param);

					if ($count_param == 1) {
						$this->where .= "`id` = ?";
						$this->bindValues[] =  $param[0];
					}elseif ($count_param == 2) {
						$operators = explode(',', "=,>,<,>=,>=,<>");
						$operatorFound = false;

						foreach ($operators as $operator) {
							if ( strpos($param[0], $operator) !== false ) {
								$operatorFound = true;
								break;
							}
						}

						if ($operatorFound) {
							$this->where .= $param[0]." ?";
						}else{
							$this->where .= "`".trim($param[0])."` = ?";
						}

						$this->bindValues[] =  $param[1];
					}elseif ($count_param == 3) {
						$this->where .= "`".trim($param[0]). "` ". $param[1]. " ?";
						$this->bindValues[] =  $param[2];
					}

				}
			}
			$this->sql .= $this->where;

			$this->getSQL = $this->sql;
			$stmt = $this->dbh->prepare($this->sql);
			$stmt->execute($this->bindValues);
			return $stmt->rowCount();
		}
		return $this;
	}

	public function update($table_name, $fields = [], $id=null)
	{
		$this->resetQuery();
		$set ='';
		$x = 1;

		foreach ($fields as $column => $field) {
			$set .= "`$column` = ?";
			$this->bindValues[] = $field;
			if ( $x < count($fields) ) {
				$set .= ", ";
			}
			$x++;
		}
		$this->sql = "UPDATE `{$table_name}` SET $set";
		if (isset($id)) {
			if (is_numeric($id)) {
				$this->sql .= " WHERE `id` = ?";
				$this->bindValues[] = $id;
			}elseif (is_array($id)) {
				$arr = $id;
				$count_arr = count($arr);
				$x = 0;

				foreach ($arr as  $param) {
					if ($x == 0) {
						$this->where .= " WHERE ";
						$x++;
					}else{
						if ($this->isOrWhere) {
							$this->where .= " Or ";
						}else{
							$this->where .= " AND ";
						}
						
						$x++;
					}
					$count_param = count($param);

					if ($count_param == 1) {
						$this->where .= "`id` = ?";
						$this->bindValues[] =  $param[0];
					}elseif ($count_param == 2) {
						$operators = explode(',', "=,>,<,>=,>=,<>");
						$operatorFound = false;

						foreach ($operators as $operator) {
							if ( strpos($param[0], $operator) !== false ) {
								$operatorFound = true;
								break;
							}
						}

						if ($operatorFound) {
							$this->where .= $param[0]." ?";
						}else{
							$this->where .= "`".trim($param[0])."` = ?";
						}

						$this->bindValues[] =  $param[1];
					}elseif ($count_param == 3) {
						$this->where .= "`".trim($param[0]). "` ". $param[1]. " ?";
						$this->bindValues[] =  $param[2];
					}

				}
			}
			$this->sql .= $this->where;

			$this->getSQL = $this->sql;
			$stmt = $this->dbh->prepare($this->sql);
			$stmt->execute($this->bindValues);
			return $stmt->rowCount();
		}
		return $this;
	}

	public function insert( $table_name, $fields = [] )
	{
		$this->resetQuery();

		$keys = implode('`, `', array_keys($fields));
		$values = '';
		$x=1;
		foreach ($fields as $field => $value) {
			$values .='?';
			$this->bindValues[] =  $value;
			if ($x < count($fields)) {
				$values .=', ';
			}
			$x++;
		}
 
		$this->sql = "INSERT INTO `{$table_name}` (`{$keys}`) VALUES ({$values})";
		$this->getSQL = $this->sql;
		$stmt = $this->dbh->prepare($this->sql);
		$stmt->execute($this->bindValues);
		$this->lastIDInserted = $this->dbh->lastInsertId();

		return $this->lastIDInserted;
	}
	public function table($table_name)
	{
		$this->resetQuery();
		$this->table = $table_name;
		return $this;
	}

	public function select($columns)
	{
		$columns = explode(',', $columns);
		foreach ($columns as $key => $column) {
			$columns[$key] = trim($column);
		}
		
		$columns = implode('`, `', $columns);
		

		$this->columns = "`{$columns}`";
		return $this;
	}
	public function get()
	{
		$this->assimbleQuery();
		$this->getSQL = $this->sql;

		$stmt = $this->dbh->prepare($this->sql);
		$stmt->execute($this->bindValues);
		$this->rowCount = $stmt->rowCount();

		$rows = $stmt->fetchAll(PDO::FETCH_CLASS,'MareiObj');
		$collection= [];
		$collection = new MareiCollection;
		$x=0;
		foreach ($rows as $key => $row) {
			$collection->offsetSet($x++,$row);
		}

		return $collection;
	}
	private function assimbleQuery()
	{
		if ( $this->columns !== null ) {
			$select = $this->columns;
		}else{
			$select = "*";
		}

		$this->sql = "SELECT $select FROM `$this->table`";

		if ($this->where !== null) {
			$this->sql .= $this->where;
		}

		if ($this->orderBy !== null) {
			$this->sql .= $this->orderBy;
		}

		if ($this->limit !== null) {
			$this->sql .= $this->limit;
		}
	}
	public function orderBy($field_name, $order = 'ASC')
	{
		$field_name = trim($field_name);
		$order =  trim(strtoupper($order));
		if ($field_name !== null && ($order == 'ASC' || $order == 'DESC')) {
			if ($this->orderBy ==null ) {
				$this->orderBy = " ORDER BY $field_name $order";
			}else{
				$this->orderBy .= ", $field_name $order";
			}
		}
		return $this;
	}
	public function count()
	{
		$countSQL = "SELECT COUNT(*) FROM `$this->table`";

		if ($this->where !== null) {
			$countSQL .= $this->where;
		}

		if ($this->limit !== null) {
			$countSQL .= $this->limit;
		}
		$stmt = $this->dbh->prepare($countSQL);
		$stmt->execute($this->bindValues);

		$this->getSQL = $countSQL;

		return $stmt->fetch(PDO::FETCH_NUM)[0];
	}
	public function limit($limit, $offset=null)
	{
		if ($offset ==null ) {
			$this->limit = " LIMIT {$limit}";
		}else{
			$this->limit = " LIMIT {$limit} OFFSET {$offset}";
		}

		return $this;
	}
    public function where()
	{
		if ($this->whereCount == 0) {
			$this->where .= " WHERE ";
			$this->whereCount+=1;
		}else{
			$this->where .= " AND ";
		}

		$this->isOrWhere= false;
		$num_args = func_num_args();
		$args = func_get_args();
		if ($num_args == 1) {
			if (is_numeric($args[0])) {
				$this->where .= "`id` = ?";
				$this->bindValues[] =  $args[0];
			}elseif (is_array($args[0])) {
				$arr = $args[0];
				$count_arr = count($arr);
				$x = 0;

				foreach ($arr as  $param) {
					if ($x == 0) {
						$x++;
					}else{
						if ($this->isOrWhere) {
							$this->where .= " Or ";
						}else{
							$this->where .= " AND ";
						}
						
						$x++;
					}
					$count_param = count($param);
					if ($count_param == 1) {
						$this->where .= "`id` = ?";
						$this->bindValues[] =  $param[0];
					}elseif ($count_param == 2) {
						$operators = explode(',', "=,>,<,>=,>=,<>");
						$operatorFound = false;

						foreach ($operators as $operator) {
							if ( strpos($param[0], $operator) !== false ) {
								$operatorFound = true;
								break;
							}
						}

						if ($operatorFound) {
							$this->where .= $param[0]." ?";
						}else{
							$this->where .= "`".trim($param[0])."` = ?";
						}

						$this->bindValues[] =  $param[1];
					}elseif ($count_param == 3) {
						$this->where .= "`".trim($param[0]). "` ". $param[1]. " ?";
						$this->bindValues[] =  $param[2];
					}
				}
			}
		}elseif ($num_args == 2) {
			$operators = explode(',', "=,>,<,>=,>=,<>");
			$operatorFound = false;
			foreach ($operators as $operator) {
				if ( strpos($args[0], $operator) !== false ) {
					$operatorFound = true;
					break;
				}
			}

			if ($operatorFound) {
				$this->where .= $args[0]." ?";
			}else{
				$this->where .= "`".trim($args[0])."` = ?";
			}

			$this->bindValues[] =  $args[1];

		}elseif ($num_args == 3) {
			
			$this->where .= "`".trim($args[0]). "` ". $args[1]. " ?";
			$this->bindValues[] =  $args[2];
		}

		return $this;
	}
	public function orWhere()
	{
		if ($this->whereCount == 0) {
			$this->where .= " WHERE ";
			$this->whereCount+=1;
		}else{
			$this->where .= " OR ";
		}
		$this->isOrWhere= true;
		$num_args = func_num_args();
		$args = func_get_args();
		if ($num_args == 1) {
			if (is_numeric($args[0])) {
				$this->where .= "`id` = ?";
				$this->bindValues[] =  $args[0];
			}elseif (is_array($args[0])) {
				$arr = $args[0];
				$count_arr = count($arr);
				$x = 0;

				foreach ($arr as  $param) {
					if ($x == 0) {
						$x++;
					}else{
						if ($this->isOrWhere) {
							$this->where .= " Or ";
						}else{
							$this->where .= " AND ";
						}
						
						$x++;
					}
					$count_param = count($param);
					if ($count_param == 1) {
						$this->where .= "`id` = ?";
						$this->bindValues[] =  $param[0];
					}elseif ($count_param == 2) {
						$operators = explode(',', "=,>,<,>=,>=,<>");
						$operatorFound = false;

						foreach ($operators as $operator) {
							if ( strpos($param[0], $operator) !== false ) {
								$operatorFound = true;
								break;
							}
						}

						if ($operatorFound) {
							$this->where .= $param[0]." ?";
						}else{
							$this->where .= "`".trim($param[0])."` = ?";
						}

						$this->bindValues[] =  $param[1];
					}elseif ($count_param == 3) {
						$this->where .= "`".trim($param[0]). "` ". $param[1]. " ?";
						$this->bindValues[] =  $param[2];
					}
				}
			}
		}elseif ($num_args == 2) {
			$operators = explode(',', "=,>,<,>=,>=,<>");
			$operatorFound = false;
			foreach ($operators as $operator) {
				if ( strpos($args[0], $operator) !== false ) {
					$operatorFound = true;
					break;
				}
			}
			if ($operatorFound) {
				$this->where .= $args[0]." ?";
			}else{
				$this->where .= "`".trim($args[0])."` = ?";
			}

			$this->bindValues[] =  $args[1];

		}elseif ($num_args == 3) {
			
			$this->where .= "`".trim($args[0]). "` ". $args[1]. " ?";
			$this->bindValues[] =  $args[2];
		}

		return $this;
	}
	public function getCount()
	{
		return $this->rowCount;
	}
	public function rowCount()
	{
		return $this->rowCount;
	}
    public function groupBy(string ...$column_name):DB
    {
        $this->groupBy = implode(',', $column_name);
        return $this;
    }
    public function leftJoin(string $table_name, $FK, $PK):DB
    {
        $this->leftJoin = " LEFT JOIN  $table_name  ON  $FK  =  $PK";
        return $this;
    }

    public function rightJoin(string $table_name, $FK, $PK):DB
    {
        $this->rightJoin = " RIGHT JOIN  $table_name  ON  $FK  =  $PK";
        return $this;
    }

    public function join(string $table_name, $FK, $PK):DB
    {
        $this->join = " JOIN  $table_name  ON  $FK  =  $PK";
        return $this;
    }


}
