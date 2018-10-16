<?php

class user {
    public $userName;
    public $apiKey;
    public $ID;
    private $tableName = "apiKeys";
    public $errMsg;
    private $conn;
    public $diagnostic;
    
    

    public function __construct($db){
        $this->conn = $db;

    }

    public function setUser($apiKey) {
        $query = "select * from $this->tableName where apiKey = '$apiKey'";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return false;
        }



        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) == 1) {
                foreach ($results as $result) {
                    $this->userName = $result['userName'];
                    $this->apiKey = $result['apiKey'];
                    $this->ID = $result['IDnumber'];
                }
                return true;
            } else {
                $this->diagnostic = $query;
                $this->errMsg = "No user Found.";
                return false;
            }
            



        } else {
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return false;
            
        }

    }




}

?>