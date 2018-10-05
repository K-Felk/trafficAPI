<?php


class Traffic {
 
    // database connection and table name
    private $conn;
    private $table_name = "traffic";
    private $isSet = FALSE;
 
    // object properties
    public $level;
    public $entryID;
    public $spaceID;
    public $comments;
    public $spaceName;
    public $trafficLabel;
    public $errMsg = NULL;
    
    
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
        

    }

    //sets data to a specific entry
    public function setFromDatabase($entryID, $spaceID) {
        //create query
        $query = "SELECT t.*, s.name as 'spaceName', l.name as 'trafficLabel' FROM traffic t, spaces s, traffic_labels l WHERE entryID = " .
         $entryID ." and t.space =" . $spaceID . " and s.ID = t.space and l.ID = t.level";
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                
                $this->errMsg = "No entries found for that ID number and space.";
                return FALSE;

            } else {
                
                $this->errMsg = NULL;
                foreach ($results as $result) {
                    $this->level = $result["level"];
                    $this->spaceID = $result["spaceID"];
                    $this->entryID = $result["entryID"];
                    $this->comments = $result["comments"];
                    $this->spaceName = $result["spaceName"];
                    $this->trafficLabel = $result["trafficLabel"];
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

    //dump all entries for this entryID

    public function getAllbyEntry($entryID) {
        //create query
        $query = "SELECT t.*, s.name as 'spaceName', l.name as 'trafficLabel' FROM traffic t, spaces s, traffic_labels l WHERE entryID =" .
        $entryID . " and s.ID = t.space and l.ID = t.level";
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                
                $this->errMsg = "No entries found for that ID number.";
                return FALSE;

            } else {
                
                return $results;
            }
        } else {
            
            $error = $this->conn->errorInfo();
            $this->errMsg = $error[2];
            return FALSE;
        }

        

    }

    //get all entries for a specific space

    public function getAllbySpace($spaceID) {
        //create query
        $query = "SELECT t.*, s.name as 'spaceName', l.name as 'trafficLabel' FROM traffic t, spaces s, traffic_labels l WHERE t.space =" .
        $spaceID . " and s.ID = t.space and l.ID = t.level";
 
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 1) {
                
                $this->errMsg = "No entries found for that space ID.";
                return FALSE;

            } else {
                
                return $results;
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
        $returnArray["level"] = $this->level;
        $returnArray["spaceID"] = $this->spaceID;
        $returnArray["entryID"] = $this->entryID;
        $returnArray["comments"] = $this->comments;
        $returnArray["spaceName"] = $this->spaceName;
        $returnArray["trafficLabel"] = $this->trafficLabel;

        return $returnArray;

    }

    //unset any data
    public function unSet() {
        unset($this->level);
        unset($this->spaceID);
        unset($this->entryID);
        unset($this->comments);
        unset($this->spaceName);
        unset($this->trafficLabel);
        $this->isSet = false;




    }

    public function getByDate($startDate, $endDate, $spaceID) {
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
        $query = "SELECT e.time as 'time', t.*, s.name as 'spaceName', l.name as 'trafficLabel' 
        FROM traffic t, spaces s, traffic_labels l, entries e 
        WHERE t.entryID = e.entryID and t.space = $spaceID and e.time >= '$startDate' and e.time <= '$endDate' and s.ID = t.space and l.ID = t.level";
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