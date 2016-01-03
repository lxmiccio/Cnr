<?php
    require("CredenzialiPersonale.php");
    if (isset($_REQUEST['lat'])) {
        $latitude1 = $_REQUEST['lat'];
    } else {
        die("Fatal Error:    Cannot Get Latitude");
    }
    if (isset($_REQUEST['lng'])) {
        $longitude1 = $_REQUEST['lng'];
    } else {
        die("Fatal Error:    Cannot Get Longitude");
    }
    if (isset($_REQUEST['lat1'])) {
        $latitude2 = $_REQUEST['lat1'];
    } else {
        die("Fatal Error:    Cannot Get Latitude1");
    }
    if (isset($_REQUEST['lng1'])) {
        $longitude2 = $_REQUEST['lng1'];
    }
    else {
        die("Fatal Error:    Cannot Get Longitude1");
    }

    $geojson = json_decode(file_get_contents("cnr.geojson"), true);
    for($iterator = 0; $iterator < count($geojson['features']); $iterator++) {
        if($latitude1 == $geojson["features"][$iterator]["geometry"]["coordinates"][0] && $longitude1 == $geojson["features"][$iterator]["geometry"]["coordinates"][1]) {
            $sede1 = $geojson["features"][$iterator]["properties"]["City"];
        }
        if($latitude2 == $geojson["features"][$iterator]["geometry"]["coordinates"][0] && $longitude2 == $geojson["features"][$iterator]["geometry"]["coordinates"][1]) {
            $sede2 = $geojson["features"][$iterator]["properties"]["City"];
        }
    }

    $csv   = "date,Laureati,Sede" . "\n";
    $connection = pg_connect("dbname=" . $db_name . " host=" . $db_host . " password=" . $db_psw . " port=" . $db_port . " user=" . $db_user) or die("Fatal Error:    Cannot connect to " . $db_name);
    $query = "SELECT replace(\"data_laurea1\", '/', '-'), COUNT(*), \"SEZIONE\" 
                FROM \"Personale_IRPI_2\" 
                WHERE \"data_laurea1\" IS NOT NULL AND \"SEZIONE\" IS NOT NULL AND (\"SEZIONE\"='" . $sede1 . "' OR \"SEZIONE\"='" . $sede2 . "') 
                GROUP BY \"data_laurea1\", \"SEZIONE\" 
                ORDER BY \"SEZIONE\", to_timestamp(replace(\"data_laurea1\", '/', '-'), 'DD-MM-YYYY hh24:mi:ss')::timestamp without time zone";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_row($result)) {
        $csv .= $row[0] . "," . $row[1] . "," . $row[2] . "\n";
    }
    echo $csv;
?>
