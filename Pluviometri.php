<?php
    require("CredenzialiPluviometri.php");
    if (isset($_REQUEST['id'])) {
        $id = $_REQUEST['id'];
    } else {
        die("Fatal Error:    Cannot Get Id");
    }
    if ($id == 1 or $id == 2 or $id == 3) {
        $connection = pg_connect("dbname=" . $db_name . " host=" . $db_host . " password=" . $db_psw . " port=" . $db_port . " user=" . $db_user) or die("Fatal Error:    Cannot connect to " . $db_name);
        if ($id == 1) {
            $query = "SELECT region, lon, lat FROM spatialtemporal GROUP BY region, lon, lat ORDER BY region";
            $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
            $geojson = "{" . "\n" . "    \"type\": \"FeatureCollection\"," . "\n" . "\n" . "    \"features\":" . "\n" . "    [" . "\n";
            while ($row = pg_fetch_row($result)) {
                $geojson .= '        { "type": "Feature", "properties": { "Region": "' . ucwords(strtolower($row[0])) . '", "Longitude": "' . round($row[1], 5) . '", "Latitude": "' . round($row[2], 5) . '" }, "geometry": { "type": "Point", "coordinates": [ ' . $row[1] . ', ' . $row[2] . ' ] } },' . "\n";
            }
            $geojson = substr($geojson, 0, -2) . "\n" . "    ]" . "\n" . "}";
            echo $geojson;
        }
        if ($id == 2 or $id == 3) {
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
            if ($id == 2) {
                if (isset($_REQUEST['radius'])) {
                    $radius = $_REQUEST['radius'];
                } else {
                    die("Fatal Error:    Cannot Get Radius");
                }
                $query = "SELECT tempo_calcolo, avg(CASE WHEN piovuta_96 > 0 THEN piovuta_96 ELSE NULL END) as piovuta_96 FROM spatialtemporal WHERE ST_Distance(ST_MakePoint(" . $longitude . ", " . $latitude . "), ST_MakePoint(lon, lat)) <= '" . $radius . "' GROUP BY tempo_calcolo ORDER BY tempo_calcolo";
                $result = pg_query($connection, $query) or die("Fatal Error:    Cannot perform query");
                $csv = 'date,Piovuta';
                while ($row = pg_fetch_row($result)) {
                    $csv = substr($csv . "\n" . $row[0], 0, -3) . "," . round($row[1], 5);
                }
                echo $csv;
            }
            if ($id == 3) {
                $query = "SELECT tempo_calcolo, CASE WHEN piovuta_96 > 0 THEN piovuta_96 ELSE NULL END as piovuta_96 FROM spatialtemporal WHERE lon = '" . $longitude . "' AND lat = '" . $latitude . "' ORDER BY tempo_calcolo";
                $result = pg_query($connection, $query) or die("Fatal Error:    Cannot perform query");
                $csv = 'date,Piovuta';
                while ($row = pg_fetch_row($result)) {
                    $csv = substr($csv . "\n" . $row[0], 0, -3) . "," . round($row[1], 5);
                }
                echo $csv;
            }
            pg_close($connection);
            pg_free_result($result);
        }
    } else {
        die("Fatal Error:    Invalid Id Value");
    }
?>