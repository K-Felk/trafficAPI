<?php

class user {
    public $userName;
    public $apiKey;
    public $ID;
    private $tableName = "apiKey";
    public $errMsg;
    
    

    public function __construct($db){
        $this->conn = $db;

    }

    public function setUser($apiKey) {
        $query = "select * from $this->tableName where apiKey like '$apiKey'";

        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) == 1) {
                $this->userName = $results['userName'];
                $this->apiKey = $results['apiKey'];
                $this->ID = $results['ID'];
                return true;
            } else {
                $errMsg = "No user Found.";
                return false;
            }
            



        } else {
            $error = $stmt->errorInfo();
            $this->errMsg = $error[2];
            return false;
            
        }

    }




}

?>