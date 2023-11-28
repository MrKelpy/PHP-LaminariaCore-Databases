<?php
	namespace LaminariaCore;
	
	use ArgumentCountError;
	use mysqli;
	use mysqli_stmt;
	
	/**
	 * This class is responsible for accessing the database and performing any queries needed to get
	 * data from it
	 */
	class MySQLDatabaseManager
	{
		/**
		 * @var MySQLServerConnector The connector accessing the MySQL server.
		 */
		private MySQLServerConnector $connector;
		
		/**
		 * General constructor for the MySQLDatabaseManager. Takes a MySQLServerConnector to access the
		 * MySQL server.
		 * @param MySQLServerConnector $connector The connector accessing the MySQL server.
		 */
		public function __construct(MySQLServerConnector $connector) {
			$this->connector = $connector;
		}
		
		/**
		 * Gets the MySQLServerConnector used to access the MySQL server.
		 * @return MySQLServerConnector The MySQLServerConnector used to access the MySQL server.
		 */
		public function getConnector(): MySQLServerConnector {
			return $this->connector;
		}
		
		/**
		 * Gets the MySQLi connection to the MySQL server.
		 * @return mysqli The MySQLi connection to the MySQL server.
		 */
		public function getConnection(): mysqli {
			return $this->connector->getConnection();
		}
		
		/**
		 * Switches to a database given a name.
		 * @param string $database The name of the database to switch to.
		 * @return void
		 */
		public function useDatabase(string $database): void {
			$this->getConnection()->select_db($database);
		}
		
		/**
		 * Inserts the selected values into the selected table, according to the specified fields.
		 * @param string $table The table to insert the values into.
		 * @param array $fields The fields to insert the values into.
		 * @param array $values The values to insert into the table.
		 * @return void
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function insert(string $table, array $fields, array ...$values): void {
			
			// Checks if the number of fields and values are the same. If not, throw an error.
			if (count($fields) != count($values)) {
				throw new ArgumentCountError("The number of fields and values must be the same.");
			}
			
			// Creates the sanitised query string and determines the fields to insert into.
			$placeholders = str_split(str_repeat("?, ", count($fields)));
			$fields = count($fields) != 0 ? $this->arrayToQueryString($fields) : "";
			
			
			$query = "INSERT INTO $table " . $fields . " VALUES " . $this->arrayToQueryString($placeholders);
			$this->bindParameters($query, $values)->execute();
		}
		
		/**
		 * Inserts the given values into the given table, assuming the values are in the same order as the fields, and we want
		 * to insert into all the fields.
		 * @param string $table The table to insert the values into.
		 * @param array $values The values to insert into the table.
		 * @return void
		 */
		public function insertWhole(string $table, array ...$values): void {
			$this->insert($table, array(), array_values($values));
		}
		
		/**
		 * Deletes entries from a table based on a specified condition.
		 *
		 * @param string $table     The table from which to delete entries.
		 * @param string $condition The condition for deleting entries (e.g., "id = x").
		 * @return void
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function deleteFrom(string $table, string $condition): void
		{
			// Construct the DELETE query with the specified condition and execute it.
			$query = "DELETE FROM $table WHERE $condition";
			$this->sendQuery($query);
		}
		
		/**
		 * Updates the values of an entry in a table based on a specified condition.
		 *
		 * @param string $table  The table in which to update the entry.
		 * @param array  $values An associative array of field-value pairs to update.
		 * @param string $condition The condition for updating entries (e.g., "id = ?").
		 * @return void
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function update(string $table, array $values, string $condition): void
		{
			// Construct the UPDATE query with the specified values.
			$setClause = $this->arrayToQueryString($values);
			$query = "UPDATE $table SET $setClause WHERE $condition";
			
			// Send the UPDATE query to the database.
			$this->sendQuery($query);
		}
		
		/**
		 * Selects specific fields from a table based on a condition.
		 *
		 * @param array  $fields    The fields to select.
		 * @param string $table     The table from which to select.
		 * @param string $condition The condition for selecting entries (e.g., "id = ?").
		 * @return array The resulting matrix from the SELECT query.
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function selectWithCondition(array $fields, string $table, string $condition): array
		{
			// Construct the SELECT query with the specified fields and condition.
			$fieldList = $this->arrayToQueryString($fields);
			$query = "SELECT $fieldList FROM $table WHERE $condition";
			
			return $this->sendQuery($query);
		}
		
		/**
		 * Selects specific fields from a table without any condition.
		 *
		 * @param array  $fields The fields to select.
		 * @param string $table  The table from which to select.
		 * @return array The resulting matrix from the SELECT query.
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function selectWithoutCondition(array $fields, string $table): array
		{
			// Construct the SELECT query with the specified fields.
			$fieldList = $this->arrayToQueryString($fields);
			$query = "SELECT $fieldList FROM $table";
			
			return $this->sendQuery($query);
		}
		
		/**
		 * Selects all fields from a table based on a condition.
		 *
		 * @param string $table     The table from which to select.
		 * @param string $condition The condition for selecting entries (e.g., "id = x").
		 * @return array The resulting matrix from the SELECT query.
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function selectAllWithCondition(string $table, string $condition): array
		{
			// Construct the SELECT query with all fields and the specified condition.
			$query = "SELECT * FROM $table WHERE $condition";
			
			return $this->sendQuery($query);
		}
		
		/**
		 * Selects all fields from a table without any condition.
		 *
		 * @param string $table The table from which to select.
		 * @return array The resulting matrix from the SELECT query.
		 * @noinspection SqlNoDataSourceInspection
		 */
		public function selectAllWithoutCondition(string $table): array
		{
			// Construct the SELECT query with all fields.
			$query = "SELECT * FROM $table";
			
			return $this->sendQuery($query);
		}
		
		/**
		 * Sends a query to the database and returns the results as a matrix.
		 * @param string $query The query to send to the database.
		 * @return array The results of the query as a matrix.
		 */
		public function sendQuery(string $query): array
		{
			// Executes the query and initialises the results matrix.
			$result = $this->getConnection()->query($query);
			$resultsMatrix = array();
			
			// If the query failed, return the empty results matrix.
			if (!$result) return $resultsMatrix;
			
			// Parse the results into a matrix and return it.
			while ($row = $result->fetch_assoc())
				$resultsMatrix[] = $row;
			
			return $resultsMatrix;
		}
		
		/**
		 * Sends a non-query to the database and returns the number of rows affected.
		 * @param string $statement The statement to send to the database.
		 * @return int The number of rows affected by the statement.
		 */
		public function sendNonQuery(string $statement): int {
			$this->getConnection()->query($statement);
			return $this->getConnection()->affected_rows;
		}
		
		/**
		 * Runs a MySQL script from a given filepath.
		 * @param string $path The path to the MySQL script.
		 * @return void
		 */
		public function runMySQLScript(string $path): void {
			$commands = file_get_contents($path);
			$this->getConnection()->multi_query($commands);
		}
		
		/**
		 * Converts an array into a query string used to specify fields or values in a query.
		 * @param array $array The array to convert into a query string.
		 * @return string The query string.
		 */
		public function arrayToQueryString(array $array): string {
			
			// If the array is empty, return an empty string.
			if (count($array) == 0) return "";
			
			// Otherwise, concatenate the array items with ", " in between.
			$query = "";
			foreach ($array as $item) $query .= $item . ", ";
			
			return '('. substr($query, 0, strlen($query) - 2) .')';
		}
		
		/**
		 * Binds the given parameters to the given query, so they're sanitised, preventing SQL injection.
		 * @param string $query The query to bind the parameters to.
		 * @param array $values The values to bind to the query.
		 * @return mysqli_stmt The statement with the parameters bound to it.
		 */
		public function bindParameters(string $query, array $values): mysqli_stmt {
			$result = $this->getConnection()->prepare($query);
			$result->bind_param(str_repeat("s", count($values)), ...$values);
			return $result;
		}
	}