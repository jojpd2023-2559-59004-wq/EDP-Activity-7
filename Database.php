<?php
class Database {
    private $host = "localhost";
    private $db_name = "information_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Auto-create users table for authentication if it doesn't exist
            $this->createUsersTable();
            
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'Staff',
            status VARCHAR(20) NOT NULL DEFAULT 'Active'
        )";
        $this->conn->exec($query);
        
        // Insert default admin if no users exist
        $check = $this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($check == 0) {
            $this->conn->exec("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES ('Admin', 'User', 'admin@admin.com', 'admin123', 'Admin', 'Active')");
        }
    }
}
?>
