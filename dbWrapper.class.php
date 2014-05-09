<?php

class dbWrapper {

	private $connection;
	public $charset;

////////////////////////////////////////////////////////////////////////////

	public function __construct($DBName, $DBUser, DBPassword, $DBHost = "localhost", $DBPort = "3306", $DBCharset = "utf8"){
		$this->charset = $DBCharset;

		// Establish connnection  
		try {
			$this->connection = new PDO( 'mysql:host=' . $DBHost .';dbname=' . $DBName . ';port=' . $DBPort . ';charset=' . $this->charset, $DBUser, $DBPassword);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if (version_compare(PHP_VERSION, '5.3.6') <= 0) {
				// Fallback for PHP Versions which ignore the charset-parameter in the connections dsn
				$this->connection->exec("SET NAMES " . $this->charset);
			}

		} catch (PDOException $exception) {
			throw new Exception('Could not connect to database!');
			return FALSE;
		}
		
	}

////////////////////////////////////////////////////////////////////////////

	// Returns a PDO statement where you have to bind the parameters and execute it yourself
	public function prepare($sql) {
		return new DBStatement($sql, $this->connection);
	}

////////////////////////////////////////////////////////////////////////////

	public function query($sql, $parameters = false){
		// Prevent the script from using SELECT on this method
		$sql = str_replace("\t"," ",$sql);
		$teil = explode(" ",$sql);
		$teil = trim($teil[0]);

		// DEBUG
		if ((strtolower($teil) == "select" OR strtolower($teil) == "(select" OR strtolower($teil) == "show")){
			throw new Exception("The Database Interface doesn't support select queries on the function 'query'. Use getArray instead.");
		}


		// Perform the query
		$dbObject = $this->prepare($sql);
		$statement = $dbObject->execute($parameters);

		return $statement;

	} // end of function dbquery()

 ////////////////////////////////////////////////////////////////////////////

	public function getList($sql, $parameters = null){

		// Prevent the script from using the method for other cases than 'select' or 'show'
		$sql = str_replace("\t"," ",$sql);
		$teil = explode(" ",$sql);
		$teil = trim($teil[0]);

		if (!(strtolower($teil) == "select" OR strtolower($teil) == "(select" OR strtolower($teil) == "show")){
			throw new Exception("ERROR: The function 'getList' may not be used for other orders than 'select' or 'show' ");
		}

		$dbObject = $this->prepare($sql);
		$dbObject->execute($parameters);

		return $dbObject->getArray();
	}

 ////////////////////////////////////////////////////////////////////////////

	public function getRow($sql, $parameters = null){

		// Prevent the script from using the method for other cases than 'select' or 'show'
		$sql = str_replace("\t"," ",$sql);
		$teil = explode(" ",$sql);
		$teil = trim($teil[0]);

		if (!(strtolower($teil) == "select" OR strtolower($teil) == "(select" OR strtolower($teil) == "show")){
			throw new Exception("ERROR: The function 'getRow' may not be used for other orders than 'select' or 'show' ");
		}

		$dbObject = $this->prepare($sql);
		$dbObject->execute($parameters);

		return $dbObject->getRow();
	}

 ////////////////////////////////////////////////////////////////////////////

	// Get the raw PDO DB Connection
	function getConnection () {
		return $this->connection;
	}

} // end of class

class DBStatement {
	private $connection;
	private $statement;
	private $sql;

	// Prepare the statement
	public function __construct($sql, $connection) {
 
		try {
			$this->connection = $connection;
			$this->statement = $this->connection->prepare($sql);
			$this->sql = $sql;
		} catch (PDOException $exception) {
			throw new Exception("<br>MySQL-Error-No:&nbsp;" . $exception->getCode() . "<br>MySQL-Error:&nbsp;" . $exception->getMessage() . "<br><br>Performed SQL:<br>".nl2br($this->sql));
		}
	}

	// Bind parameters and execute it
	public function execute($parameters = null, $setting2parameter = null) {
		
		// Bind parameters
		if ($parameters)
			foreach ($parameters as $key => $value) {
				if ($setting2parameter[$key]){
					$this->statement->bindValue($key, $value, $setting2parameter[$key]);
				} else {
					$this->statement->bindValue($key, $value);
				}
			}

		// Perform queries
		try {
			$this->statement->execute();
		} catch (PDOException $exception) {
			throw new Exception("<br>MySQL-Error-No:&nbsp;" . $exception->getCode() . "<br>MySQL-Error:&nbsp;" . $exception->getMessage() . "<br><br>Performed SQL:<br>".nl2br($this->sql));
		}

		return $this->statement;
	}

	// Returns the whole array
	public function getArray($fetchType = PDO::FETCH_ASSOC) {
		return $this->statement->fetchAll($fetchType);
	}

	// Returns just one row. Should be used if you only need the one (the first) row.
	public function getRow($fetchType = PDO::FETCH_ASSOC) {
		return $this->statement->fetch($fetchType);
	}

	// Get the raw PDO::STATEMENT
	public function getDBStatement() {
		return $this->statement;
	}

	// Get the id of the last inserted dataset
	public function getLastInsertId() {
		return $this->connection->lastInsertId();
	}
}

?>