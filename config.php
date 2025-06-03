<?php

Trait ErrorHandler {
    protected function handleError($errno, $error) {
        if (DEV_MODE) {
            die("Error: [ " . $errno . " ]: " . $error . ".");
        } else {
            // "[ date ] [errno]: error"
            error_log("[ " . date('m-d-Y H:i:s') . " ] [" . $errno . "] " . $error . PHP_EOL, 3, 'errors/error_log.log');
            // TODO: Email error messages
            die("An error occurred. Please try again later.");
        }
    }
}

class DBCONFIG {
    use ErrorHandler;

    private $dbHost     = "";
    private $dbUsername = "";
    private $dbPassword = "";
    private $dbName     = "";

    private $ErrorHandler;

    private $dbconfig;

    public function __construct() {
        define('DEV_MODE', true);
        ini_set('display_errors', DEV_MODE ? 1 : 0);
        ini_set('display_startup_errors', DEV_MODE ? 1 : 0);
        ini_set('log_errors', DEV_MODE ? 0 : 1);
        DEV_MODE ? error_reporting(E_ALL & ~E_NOTICE) : error_reporting(0);

        try {
            $this->dbconfig = $this->connectDB();

            // Create and select database
            $this->createAndSelectDatabase();

            // Execute SQL files
            $this->executeSQLFiles();
        } catch (Exception $err) {
            $this->ErrorHandler->handleError($err->getCode(), $err->getMessage());
        }

        return $this->dbconfig;
    }

    private function connectDB() {
        $conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword);
        if ($conn->connect_errno) {
            throw new Exception($conn->connect_error, $conn->connect_errno);
        }
        return $conn;
    }

    private function createAndSelectDatabase() {
        $sql = "CREATE DATABASE IF NOT EXISTS " . $this->dbName . " CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        if (!$this->dbconfig->query($sql)) {
            throw new Exception($this->dbconfig->error, $this->dbconfig->errno);
        }
        if (!$this->dbconfig->select_db($this->dbName)) {
            throw new Exception($this->dbconfig->error, $this->dbconfig->errno);
        }
    }

    private function executeSQLFiles() {
        // Directory containing SQL files
        $sqlDir = './sql';

        // Get all SQL files in the directory
        $tblFiles = glob("$sqlDir/*.sql");

        // Execute non-alter files first
        foreach ($tblFiles as $path) {
            if (basename($path) !== 'alters.sql') {
                $this->executeSqlFile($path);
            }
        }

        // Execute alter file last if found
        foreach ($tblFiles as $path) {
            if (basename($path) === 'alters.sql') {
                $this->executeSqlFile($path);
            }
        }
    }

    private function executeSqlFile($path) {
        // Read the SQL file
        $sql = file_get_contents($path);

        // Separate SQL queries
        $queries = explode(';', $sql);

        // Execute each query separately
        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                if (!$this->dbconfig->query($query)) {
                    new Exception($this->dbconfig->error, $this->dbconfig->errno);
                }
            }
        }
    }
}

class ErrorHandler {
    protected function handleError($errno, $error) {
        if (DEV_MODE) {
            die("Error: [ " . $errno . " ]: " . $error . ".");
        } else {
            // "[ date ] [errno]: error"
            error_log("[ " . date('m-d-Y H:i:s') . " ] [" . $errno . "] " . $error . PHP_EOL, 3, 'errors/error_log.log');
            // TODO: Email error messages
            die("An error occurred. Please try again later.");
        }
    }
}

/**
 * DB Class 
 * This class is used for database related (connect, insert, update, and delete) operations 
 */
class DB extends DBCONFIG {
    use ErrorHandler;

    private $db;
    private $ErrorHandler;

    public function __construct() {
        try {
            $this->db = parent::__construct();
        } catch (Exception $err) {
            $this->ErrorHandler->handleError($err->getCode(), $err->getMessage());
        }
    }
    public function __destruct() {
        if ($this->db !== null) {
            $this->db = null;
        }
    }

    /**
     * Returns rows from the database based on the conditions 
     * @param string name of the table 
     * @param array select, where, order_by, limit and return_type conditions 
     */
    public function getRows($table, $conditions = array()) {
        $sql = 'SELECT ';
        $sql .= array_key_exists("select", $conditions) ? $conditions['select'] : '*';
        $sql .= ' FROM ' . $table;

        $values = [];
        $types = '';

        if (array_key_exists("join_type", $conditions) && isset($conditions['join_type']['table'], $conditions['join_type']['condition'])) {
            switch ($conditions['join_type']['type']) {
                case 'inner':
                    $sql .= ' INNER JOIN ';
                    break;
                case 'left':
                    $sql .= ' LEFT JOIN ';
                    break;
                case 'right':
                    $sql .= ' RIGHT JOIN ';
                    break;
                case 'full':
                    $sql .= ' FULL JOIN ';
                    break;
                case 'self':
                    $sql .= ' SELF JOIN ';
                    break;
                default:
                    $sql .= ' JOIN ';
                    break;
            }
            $sql .= $conditions['join_type']['table'];
            $sql .= ' ON ' . $conditions['join_type']['condition'];
        }

        if (array_key_exists("where", $conditions) && is_array($conditions['where'])) {
            $sql .= ' WHERE ';
            $i = 0;
            foreach ($conditions['where'] as $key => $value) {
                $pre = ($i > 0) ? ' AND ' : '';
                $sql .= $pre . $key . ' = ?';
                $values[] = $value;
                $i++;
            }
        }

        if (array_key_exists("like", $conditions) && is_array($conditions['like'])) {
            $sql .= (strpos($sql, 'WHERE') !== FALSE) ? ' AND ' : ' WHERE ';
            $i = 0;
            $likeSQL = '';
            foreach ($conditions['like'] as $key => $value) {
                $pre = ($i > 0) ? ' AND ' : '';
                $likeSQL .= $pre . $key . " LIKE ?";
                $values[] = "%$values%";
                $i++;
            }
            $sql .= '(' . $likeSQL . ')';
        }

        if (array_key_exists("like_or", $conditions) && !empty($conditions['like_or'])) {
            $sql .= (strpos($sql, 'WHERE') !== FALSE) ? ' AND ' : ' WHERE ';
            $i = 0;
            $likeSQL = '';
            foreach ($conditions['like_or'] as $key => $value) {
                $pre = ($i > 0) ? ' OR ' : '';
                $likeSQL .= $pre . $key . " LIKE ?";
                $values[] = "%$value%";
                $i++;
            }
            $sql .= '(' . $likeSQL . ')';
        }

        if (array_key_exists("order_by", $conditions)) {
            $sql .= ' ORDER BY ' . $conditions['order_by'];
        }

        if (array_key_exists("start", $conditions) && array_key_exists("limit", $conditions)) {
            $sql .= ' LIMIT ?, ?';
            $values[] = $conditions['start'];
            $values[] = $conditions['limit'];
        } elseif (!array_key_exists("start", $conditions) && array_key_exists("limit", $conditions)) {
            $sql .= ' LIMIT ?';
            $values[] = $conditions['limit'];
        }

        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            // Create the type string dynamically
            foreach ($values as $value) {
                if (is_int($value)) {
                    $types .= 'i';   
                } elseif (is_float($value)) {
                    $types .= 'd';
                } elseif (is_string($value)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }

            if (!empty($types)) {
                $stmt->bind_param($types, ...$values);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if (array_key_exists("return_type", $conditions) && $conditions['return_type'] != 'all') {
                switch ($conditions['return_type']) {
                    case 'count':
                        return $result->num_rows;
                        break;
                    case 'single':
                        return $result->fetch_assoc();
                        break;
                    default:
                        return false;
                }
            } else {
                $data = [];
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
                return !empty($data) ? $data : false;
            }
        } else {
            return false;
        }
    }

    /**
     * Insert data into the database 
     * @param string name of the table 
     * @param array the data for inserting into the table 
     */
    public function insert($table, $data) {
        if (!empty($data) && is_array($data)) {
            $columns = implode(", ", array_keys($data));
            $placeholders = rtrim(str_repeat('?, ', count($data)), ', ');
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($query);
        
            if ($stmt) {
                $types = '';
                $values= [];
                foreach ($data as $value) {
                    if (is_int($value)) {
                        $types .= 'i';
                    } elseif (is_float($value)) {
                        $types .= 'd';
                    } elseif (is_string($value)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    $values[] = $value;
                }
                $stmt->bind_param($types, ...$values);
                if ($stmt->execute()) {
                    return $this->db->insert_id;
                }
            }
        }
        return false;
    }

    /**
     * Update data into the database 
     * @param string name of the table 
     * @param array the data for updating into the table 
     * @param array where condition on updating data 
     */
    public function update($table, $data, $conditions) {
        if (!empty($data) && is_array($data)) {
            $colvalSet = '';
            $whereSql = '';
            $i = 0;
            if (!array_key_exists('modified', $data)) {
                $data['modified'] = date("Y-m-d H:i:s");
            }
            foreach ($data as $key => $val) {
                $pre = ($i > 0) ? ', ' : '';
                $colvalSet .= $pre . $key . "='" . $val . "'";
                $i++;
            }
            if (!empty($conditions) && is_array($conditions)) {
                $whereSql .= ' WHERE ';
                $i = 0;
                foreach ($conditions as $key => $value) {
                    $pre = ($i > 0) ? ' AND ' : '';
                    $whereSql .= $pre . $key . " = '" . $value . "'";
                    $i++;
                }
            }
            $sql = "UPDATE " . $table . " SET " . $colvalSet . $whereSql;
            $query = $this->db->prepare($sql);
            $update = $query->execute();
            return $update ? $query->num_rows() : false;
        } else {
            return false;
        }
    }

    /**
     * Delete data from the database 
     * @param string name of the table 
     * @param array where condition on deleting data 
     */
    public function delete($table, $conditions) {
        $whereSql = '';
        $values = [];
        
        if (!empty($conditions) && is_array($conditions)) {
            $whereSql .= ' WHERE ';
            $i = 0;
            foreach ($conditions as $key => $value) {
                $pre = ($i > 0) ? ' AND ' : '';
                $whereSql .= $pre . $key . ' = ?';
                $values[] = $value;
                $i++;
            }
        }
        $sql = "DELETE FROM " . $table . $whereSql;
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $stmt->bind_param(str_repeat('s', count($values)), ...$values);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } else {
            return false;
        }
    }
}
