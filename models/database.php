<?php
class Database{
 
    // specify your own database credentials
    private $host = "localhost";
    private $db_name = "libspace_use";
    private $username = "space_display";
    private $password = "root";
    public $conn;
    public $errMsg;
 
    // get the database connection
    public function getConnection(){
 
        $this->conn = null;
 
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
            $this->errMsg = "Connection error: " . $exception->getMessage();
            return FALSE;
        }
 
        return $this->conn;
    }
}
?>