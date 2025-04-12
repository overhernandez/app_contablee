<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'sistema_contable';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database error: ".$e->getMessage());
            die("Error de conexión. Contacte al administrador.");
        }
    }
}
?>
