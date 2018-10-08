<?php

class trafficLabels {

    public $trafficLabels;
    public $errMsg;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        $query = "select * from traffic_labels";

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