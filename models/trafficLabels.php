<?php

class trafficLabels {

    public $trafficLabels = NULL;
    public $errMsg;
    private $tableName = "traffic_labels";
    private $conn;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        $query = "select * from $this->tableName";

        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                $this->trafficLabels = $results;
                
            } else {
                $errMsg = "No Labels Found.";
                
            }
            



        } else {
            $error = $stmt->errorInfo();
            $this->errMsg = $error[2];
            
            
        }

        

    }
    
    

}



?>