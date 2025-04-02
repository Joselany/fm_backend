<?php 
namespace Core\DB;

class Sql {

    const HOSTNAME = "db-postgresql-nyc3-42118-do-user-11870758-0.l.db.ondigitalocean.com";
    const USERNAME = "doadmin";
    const PASSWORD = "AVNS_CwNo5NSmEe_sKwXlYgX";
    const DBNAME = "defaultdb";
    const PORT = 25060;
    const SSLMODE = "require";

    private $conn;

    public function __construct()
    {
        $dsn = "pgsql:host=" . self::HOSTNAME . ";port=" . self::PORT . ";dbname=" . self::DBNAME . ";sslmode=" . self::SSLMODE;
        
        try {
            $this->conn = new \PDO($dsn, self::USERNAME, self::PASSWORD);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Error connecting to the database: " . $e->getMessage());
        }
    }

    private function setParams($statement, $parameters = array())
    {
        foreach ($parameters as $key => $value) {
            $this->bindParam($statement, $key, $value);
        }
    }

    private function bindParam($statement, $key, $value)
    {
        if (is_numeric($value)) {
            $value = (int) $value;
        }
        $statement->bindValue($key, $value);
    }

    public function query($rawQuery, $params = array())
    {
        try {
            $stmt = $this->conn->prepare($rawQuery);
            $stmt->execute($params);
    
            if (stripos(trim($rawQuery), 'INSERT') === 0) {
                return $this->conn->lastInsertId();
            }
    
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == '22009') {
                error_log('PDOException: ' . $e->getMessage());
                return false;
            } else {
                throw $e;
            }
        }
    }

    public function select($rawQuery, $params = array()): array
    {
        $stmt = $this->conn->prepare($rawQuery);
        $this->setParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>