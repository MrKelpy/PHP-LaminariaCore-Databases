<?php
	namespace src\mysql;
	use mysqli;
	
	/**
	 * This class is responsible for connecting to a MySQL server. Since it should have multiple ways to
	 * be instantiated, a factory pattern is used.
	 */
	class MySQLServerConnector
	{
		
		/**
		 * @var mysqli $connection The connection to the MySQL server.
		 */
		private mysqli $connection;
		
		/**
		 * Connects to the mysql server using the given parameters and sets the connection
		 * in the property.
		 * @param string $server The server to connect to. (e.g. localhost)
		 * @param string $database The database to connect to.
		 * @param string $user The user to connect with.
		 * @param string $password The password to connect with.
		 */
		public function __construct(string $server, string $database, string $user, string $password) {
			$this->connection = new mysqli($server, $user, $password, $database);
		}
		
		/**
		 * Gets the connection to the MySQL server.
		 * @return mysqli The connection to the MySQL server.
		 */
		public function getConnection(): mysqli {
			return $this->connection;
		}
		
		/**
		 * Creates a MySQLServerConnector with no authentication, just the server and database.
		 * @param string $server The server to connect to. (e.g. localhost)
		 * @param string $database The database to connect to.
		 * @return MySQLServerConnector The connection to the MySQL server.
		 */
		public static function makeNoAuth(string $server, string $database) : MySQLServerConnector {
			return new MySQLServerConnector($server, $database, "", "");
		}
		
		/**
		 * Creates a MySQLServerConnector with authentication.
		 * @param string $server The server to connect to. (e.g. localhost)
		 * @param string $database The database to connect to.
		 * @param string $user The user to connect with.
		 * @param string $password The password to connect with.
		 * @return MySQLServerConnector The connection to the MySQL server.
		 */
		public static function makeWithAuth(string $server, string $database, string $user, string $password) : MySQLServerConnector {
			return new MySQLServerConnector($server, $database, $user, $password);
		}
		
		/**
		 * Closes the connection to the MySQL server.
		 * @return void
		 */
		public function close(): void {
			$this->connection->close();
		}
		
		/**
		 * Reconnects to the MySQL server through the stored mysqli object.
		 * @return void
		 */
		public function reconnect(): void {
			$this->connection->ping();
		}
	}