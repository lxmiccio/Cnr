<?php
    class Year {
        public $location;
        public $number;
        public $year;

        public function __construct($location, $year) {
            $this->location = $location;
            $this->number = 1;
            $this->year = $year;
        }

        public function increaseNumber() {
            $this->number++;
        }
    }

    require("CredenzialiPersonale.php");
    if (isset($_REQUEST['lat'])) {
        $latitude = $_REQUEST['lat'];
    } else {
        die("Fatal Error:    Cannot Get Latitude");
    }
    if (isset($_REQUEST['lng'])) {
        $longitude = $_REQUEST['lng'];
    } else {
        die("Fatal Error:    Cannot Get Longitude");
    }
    if (isset($_REQUEST['lat1'])) {
        $latitude1 = $_REQUEST['lat1'];
    } else {
        die("Fatal Error:    Cannot Get Latitude1");
    }
    if (isset($_REQUEST['lng1'])) {
        $longitude1 = $_REQUEST['lng1'];
    }
    else {
        die("Fatal Error:    Cannot Get Longitude1");
    }
    if ($latitude == 16.21915709999996 && $longitude == 39.3277825) {
        $sede = "Cosenza";
    } else if ($latitude == 16.88221120000003 && $longitude == 41.1122236) {
        $sede = "Bari";
    } else if ($latitude == 12.363586127758026 && $longitude == 43.09344450421002) {
        $sede = "Perugia";
    } else if ($latitude == 7.638289999999984 && $longitude == 45.0162) {
        $sede = "Torino";
    } else if ($latitude == 11.92901 && $longitude == 45.39468) {
        $sede = "Padova";
    }
    if ($latitude1 == 16.21915709999996 && $longitude1 == 39.3277825) {
        $sede1 = "Cosenza";
    } else if ($latitude1 == 16.88221120000003 && $longitude1 == 41.1122236) {
        $sede1 = "Bari";
    } else if ($latitude1 == 12.363586127758026 && $longitude1 == 43.09344450421002) {
        $sede1 = "Perugia";
    } else if ($latitude1 == 7.638289999999984 && $longitude1 == 45.0162) {
        $sede1 = "Torino";
    } else if ($latitude1 == 11.92901 && $longitude1 == 45.39468) {
        $sede1 = "Padova";
    }
    $csv   = "date,Laureati,Sede" . "\n";
    $years = array();
    $connection = pg_connect("dbname=" . $db_name . " host=" . $db_host . " password=" . $db_psw . " port=" . $db_port . " user=" . $db_user) or die("Fatal Error:    Cannot connect to " . $db_name);
    $query = 'SELECT "data_laurea1", "SEZIONE" FROM "Personale_IRPI_2" WHERE "SEZIONE"=\'' . $sede . '\' OR "SEZIONE"=\'' . $sede1 . '\'';
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_row($result)) {
        if (!count($years)) {
            array_push($years, new Year($row[1], date("Y", strtotime($row[0]))));
        } else {
            $found = false;
            foreach ($years as $year) {
                if (strlen($row[0]) !== 0 && strlen($row[0]) !== 0) {
                    if (!strcmp($year->location, $row[1]) && $year->year === date("Y", strtotime($row[0]))) {
                        $found = true;
                        $year->increaseNumber();
                    }
                }
            }
            if (!$found && strlen($row[0]) !== 0) {
                array_push($years, new Year($row[1], date("Y", strtotime($row[0]))));
            }
        }
    }
    foreach ($years as $year) {
        $csv .= $year->year . "," . $year->number . "," . $year->location . "\n";
    }
    echo $csv;
?>