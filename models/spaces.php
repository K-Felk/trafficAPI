<?php

class Spaces {

    public $spaces = NULL;
    public $errMsg;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        $query = "select * from spaces";

        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                $this->spaces = $results;
            } else {
                $errMsg = "No Spaces Found.";
            }
            



        } else {
            $error = $stmt->errorInfo();
            $this->errMsg = $error[2];
            
        }

        

    }

}



?>