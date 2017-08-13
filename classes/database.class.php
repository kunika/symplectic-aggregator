<?php
	
	class Database {
		
		private $host = DB_HOST;
		private $user = DB_USER;
		private $pass = DB_PASSWORD;
		private $dbname = DB_NAME;
		
		private $dbh;
		private $stmt;
		private $error;
		
		public function __construct() {
			try {
				$this->dbh = new PDO(
					sprintf('mysql:host=%s;dbname=%s', $this->host, $this->dbname), 
					$this->user, 
					$this->pass,
					array(
						PDO::ATTR_PERSISTENT => true,
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					)
				);
				$this->dbh->exec("set names utf8mb4");
			} catch(PDOException $e) {
				$this->error = $e->getMessage();
			}
			return;
		}
		
		public function prepare($sql) {
			$this->stmt = $this->dbh->prepare($sql);
			return;
		}

		public function bind($param, $value, $type = null) {
			if (is_null($type)) {
				switch (true) {
					case is_int($value):
						$type = PDO::PARAM_INT;
						break;
					case is_bool($value):
						$type = PDO::PARAM_BOOL;
						break;
					case is_null($value):
						$type = PDO::PARAM_NULL;
						break;
					default:
						$type = PDO::PARAM_STR;
				}
			}
			$this->stmt->bindValue($param, $value, $type);
			return;
		}
		
		public function execute() {
			return $this->stmt->execute();
		}
		
		public function resultset() {
			$this->execute();
			return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		
		public function single() {
			$this->execute();
			return $this->stmt->fetch(PDO::FETCH_ASSOC);
		}
		
		public function rowCount() {
			return $this->stmt->rowCount();
		}

		public function lastInsertId() {
			return $this->dbh->lastInsertId();
		}
		
		public function beginTransaction() {
			return $this->dbh->beginTransaction();
		}

		public function endTransaction() {
		    return $this->dbh->commit();
		}
		
		public function cancelTransaction() {
			return $this->dbh->rollBack();
		}
		
		public function debugDumpParams() {
			return $this->stmt->debugDumpParams();
		}
		
		public function close() {
			$this->dbh = null;
			return;
		}

	}
	
?>