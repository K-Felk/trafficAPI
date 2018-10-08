<?php

class feedBack {

    public $data;
    public $errMsg;
    public $table_name = "feedback_response";

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        
    }

    public function getAll() {

        $query = "select * from " . $this->table_name;

        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                $this->data = $results;
                return $results;
               
            } else {
                $errMsg = "No feedback found.";
                return false;
            }
            



        } else {
            $error = $stmt->errorInfo();
            $this->errMsg = $error[2];
            return false;
            
        }

    }

    public function getByDate($startDate, $endDate) {
        //if the start date is left blank, figure out the earliest time entry logged and start from that
        if ($startDate == "") {
            $query = "select timestamp from $this->table_name order by timestamp asc limit 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $startDate = $result["timestamp"];


        }

        //ilikewise, if the end date is left off, find the last entry and make that the time cut-off
        if ($endDate == "") {
            $query = "select timestamp from $this->table_name order by timestamp desc limit 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $endDate = $result["timestamp"];


        }


        //create query
        $query = "SELECT * from  feedback_response WHERE timestamp >= '$startDate' and timestamp <= '$endDate'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                $this->errMsg = "No entries found.";
                return false;
            } else {
            
                $this->data = $results;
                return $results;
                
            }

        } else {
            
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return FALSE;
        }

    }



}



?>