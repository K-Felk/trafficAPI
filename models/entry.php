<?php


class Entry {
 
    // database connection and table name
    private $conn;
    private $table_name = "entries";
    private $isSet = FALSE;
 
    // object properties
    public $time;
    public $use;
    public $entryID;
    public $initials;
    public $errMsg = NULL;
    public $query;
    
    
    
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        

    }

    //sets data to a specific entry
    public function setFromDatabase($entryID) {
        //create query
        $query = "SELECT time, 'use', entryID, initials FROM " . $this->table_name . " where entryID=" . $entryID;
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                
                $this->errMsg = "No entries found for that ID number.";
                return FALSE;

            } else {
                
                $this->errMsg = NULL;
                foreach ($results as $result) {
                    $this->time = $result["time"];
                    $this->use = $result["use"];
                    $this->entryID = $result["entryID"];
                    $this->initials = $result["initials"];
                }
                $this->isSet = TRUE;
                return TRUE;
            }
        } else {
            
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return FALSE;
        }

        

    }

    //returns an array of the currently set values
    public function getArray() {
        if (!$this->isSet){
            $this->errMsg = "No Value Set.";
            return FALSE; 
        }

        $returnArray = array();
        $returnArray["time"] = $this->time;
        $returnArray["use"] = $this->use;
        $returnArray["entryID"] = $this->entryID;
        $returnArray["initials"] = $this->initials;

        return $returnArray;

    }

    //unset any data
    public function unSetData() {
        unset($this->time);
        unset($this->use);
        unset($this->entryID);
        unset($this->initials);
        $this->isSet = false;




    }
    //get entries by multiple ranges of include/exclude times and dates

    public function getByDateAverages($includeDates, $excludeDates, $includeHours, $excludeHours) {

        //at least one include date must be set.  All other values are optional, but at least an empty array must be passed
        //  Dates must be in the format "YYYY-DD-MM"
        //hours need to be an integer
        $includeDateQuery = "";

        $it = 0;
        $includeDateQuery .= " (";
        foreach ($includeDates as $range) {
            if ($it >= 1)  {
                $includeDateQuery .= " OR ";
            }

            $includeDateQuery .= "(date(e.time) BETWEEN '" . $range[0] . "' AND '" . $range[1] . "') ";
            ++$it;
        }
        $includeDateQuery .= ") ";

        $it = 0;

        if (!empty($excludeDates)) {
            $it = 0;
            $includeDateQuery .= " AND (";
            foreach ($excludeDates as $range) {
                if ($it >= 1)  {
                    $includeDateQuery .= " AND ";
                }
                $includeDateQuery .= "(date(e.time) NOT BETWEEN '" . $range[0] . "' AND '" . $range[1] . "') ";
                ++$it;
            }
            $includeDateQuery .= ") ";
        }
        

        if (!empty($includeHours)) {
            $it = 0;
            $includeDateQuery .= " AND (";
            foreach ($includeHours as $range) {
                if ($it >= 1)  {
                    $includeDateQuery .= " OR ";
                }
                $includeDateQuery .= "(HOUR(e.time) BETWEEN " . $range[0] . " AND " . $range[1] . " )";
                ++$it;
            }
            $includeDateQuery .= ") ";
        }


        if (!empty($excludeHours)) {
            $it = 0;
            $includeDateQuery .= " AND (";
            foreach ($excludeHours as $range) {
                if ($it >= 1)  {
                    $includeDateQuery .= " AND ";
                }
                $includeDateQuery .= "(HOUR(e.time) NOT BETWEEN " . $range[0] . " AND " . $range[1] . ")";
                ++$it;
            }
            $includeDateQuery .= ")";
        }

        $query = "SELECT
        t.space,
        AVG(t.level) as average,
        s.name
        FROM
        entries e,
        traffic t,
        spaces s
        WHERE
        t.level != -1 AND
        t.space = s.ID 
        AND t.entryID = e.entryID AND" . $includeDateQuery . "
        GROUP BY
        t.space";

        $stmt = $this->conn->prepare($query);
        

        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                $this->errMsg = "No entries found ";
                return false;
            } else {
            
                return $results;
            }

        } else {
            
            $error = $stmt->errorInfo();
            $this->errMsg = $error[2];
            return FALSE;
        }








    }



    //get entries by a specific date range
    public function getByDate($startDate, $endDate) {
        //if the start date is left blank, figure out the earliest time entry logged and start from that
        if ($startDate == "") {
            $query = "select time from $this->table_name order by time asc limit 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $startDate = $result["time"];


        }

        //ilikewise, if the end date is left off, find the last entry and make that the time cut-off
        if ($endDate == "") {
            $query = "select time from $this->table_name order by time desc limit 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $endDate = $result["time"];


        }


        //create query
        $query = "SELECT time, 'use', entryID, initials FROM " . $this->table_name . " where time >= '$startDate' and time <= '$endDate'  order by time DESC";
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                $this->errMsg = "No entries found.";
                return false;
            } else {
            
                return $results;
            }

        } else {
            
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return FALSE;
        }

    }

    

    //dumps the entire table to an associative array and returns it
    public function getAll() {
        

        //create query
        $query = "SELECT time, 'use', entryID, initials FROM " . $this->table_name . " order by time DESC";
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                $this->errMsg = "No entries found.";
                return false;
            } else {
            
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