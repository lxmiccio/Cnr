<?php
require("CredenzialiPersonale.php");
/**
 * Get the name of the city whose coordinates matches the ones passed as parameters
 * @param $latitude
 * @param $longitude
 * @return $section
 */
function getSection($latitude, $longitude) {
    $geojson = json_decode(file_get_contents("Cnr.geojson"), true);
    $section = "";
    for ($iterator = 0; $iterator < count($geojson['features']); $iterator++) {
        if (round($latitude, 12, PHP_ROUND_HALF_DOWN) == round($geojson["features"][$iterator]["geometry"]["coordinates"][1], 12, PHP_ROUND_HALF_DOWN) && round($longitude, 12, PHP_ROUND_HALF_DOWN) == round($geojson["features"][$iterator]["geometry"]["coordinates"][0], 12, PHP_ROUND_HALF_DOWN)) {
            $section = $geojson["features"][$iterator]["properties"]["City"];
        }
    }
    return $section;
}
/**
 * It returns an array containing all the qualifications in italian language
 * @param $connection
 * @return $qualifications
 */
function getQualification($connection) {
    $qualifications = array();
    $query = "SELECT DISTINCT qualifica as qualification
                FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                WHERE qualifica IS NOT NULL
                ORDER BY qualifica ASC";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_assoc($result)) {
        $qualifications[] = $row["qualification"];
    }
    return $qualifications;
}
/**
 * It returns an array containing all the qualifications in english language
 * @param $connection
 * @return $qualifications
 */
function getQualificationEn($connection) {
    $qualifications = array();
    $query = "SELECT DISTINCT qualifica_en as qualification
                FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                WHERE qualifica_en IS NOT NULL
                ORDER BY qualifica_en ASC";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_assoc($result)) {
        $qualifications[] = $row["qualification"];
    }
    return $qualifications;
}
/**
 * It returns an array containing all the profiles in italian language
 * @param $connection
 * @return $profiles
 */
function getProfiles($connection) {
    $profiles = array();
    $query = "SELECT DISTINCT profilo as qualification
                FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                WHERE profilo IS NOT NULL
                ORDER BY profilo ASC";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_assoc($result)) {
        $profiles[] = $row["qualification"];
    }
    return $profiles;
}
/**
 * It returns an array containing all the profiles in english language
 * @param $connection
 * @return $profiles
 */
function getProfilesEn($connection) {
    $profiles = array();
    $query = "SELECT DISTINCT profilo_en as qualification
                FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                WHERE profilo_en IS NOT NULL
                ORDER BY profilo_en ASC";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_assoc($result)) {
        $profiles[] = $row["qualification"];
    }
    return $profiles;
}
/**
 * It returns an array containing all the engagement dates
 * @param $connection
 * @return $profiles
 */
function getHiringDates($connection) {
    $hiringDates = array();
    $query = "SELECT DISTINCT substring(\"ASSUNZIONE\", 0, 5) as hiringDate
                    FROM ista_personale_da_esportare_per_visualizzazione_notesti 
                    WHERE \"ASSUNZIONE\" IS NOT NULL AND \"ASSUNZIONE\" NOT LIKE '%/%'
                    ORDER BY substring(\"ASSUNZIONE\", 0, 5) ASC";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_row($result)) {
        $hiringDates[] = $row[0];
    }
    return $hiringDates;
}
$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
$connection = pg_connect("dbname=" . $db_name . " host=" . $db_host . " password=" . $db_psw . " port=" . $db_port . " user=" . $db_user) or die("Fatal Error:    Cannot connect to " . $db_name);
if ($id == 1) {
    generateMap($connection);
} else {
    $latitude = $_GET["lat"];
    $longitude = $_GET["lng"];
    if ($id == 2) {
        generateChart1($connection, $latitude, $longitude);
    } else if ($id == 3) {
        generateChart2($connection, $latitude, $longitude);
    } else if ($id == 4) {
        generateChart3($connection, $latitude, $longitude);
    } else if ($id == 5) {
        generateChart4($connection, $latitude, $longitude);
    } else if ($id == 6) {
        generateChart5($connection, $latitude, $longitude);
    }
}
function generateMap($connection) {
    $cities = array();
    $geojson = json_decode(file_get_contents("Cnr.geojson"), true);
    $sections = "";
    foreach ($geojson["features"] as $feature) {
        $cities[] = $feature["properties"]["City"];
        $sections .= "'" . $feature["properties"]["City"] . "', ";
    }
    $sections = substr($sections, 0, -2);
    $query = "SELECT \"SEZIONE\" as section, COUNT(*) as employees
			    FROM \"Personale_IRPI_2\"
			    WHERE \"SEZIONE\" IS NOT NULL AND \"SEZIONE\" IN (" . $sections . ")
			    GROUP BY \"SEZIONE\"";
    $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
    while ($row = pg_fetch_assoc($result)) {
        foreach ($geojson["features"] as &$feature) {
            if (!strcmp($row["section"], $feature["properties"]["City"])) {
                $feature["properties"]["np"] = $row["employees"];
            }
        }
    }
    echo json_encode($geojson);
}
function generateChart1($connection, $latitude, $longitude) {
    $section = getSection($latitude, $longitude);
    $hiringDates = array();
    $hiringDates = getHiringDates($connection);
    $qualifications = array();
    $qualifications = getQualification($connection);
    $csv = "data,";
    foreach ($qualifications as $qualification) {
        $csv .= $qualification . ",";
    }
    $csv = substr($csv, 0, -1) . "\n";
    foreach ($hiringDates as $hiringDate) {
        $csv .= $hiringDate . ",";
        foreach ($qualifications as $qualification) {
            $query = "SELECT COUNT(CASE WHEN substring(\"ASSUNZIONE\", 0, 5) = '" . $hiringDate . "' AND qualifica ILIKE '" . $qualification . "' THEN 0 END)
                        FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                        WHERE \"ASSUNZIONE\" IS NOT NULL AND qualifica IS NOT NULL AND \"SEZIONE\" ILIKE '" . $section . "'";
            $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
            while ($row = pg_fetch_row($result)) {
                $csv .= $row[0] . ",";
            }
        }
        $csv = substr($csv, 0, -1) . "\n";
    }
    echo $csv;
}
function generateChart2($connection, $latitude, $longitude) {
    $section = getSection($latitude, $longitude);
    $qualifications = array();
    $qualifications = getQualification($connection);
    $csv = "tipoQualifica,numero,sede" . "\n";
    foreach ($qualifications as $qualification) {
        $csv .= $qualification . ", ";
        $query = "SELECT COUNT(CASE WHEN qualifica = '" . $qualification . "' THEN 0 END) as number
                        FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                        WHERE \"SEZIONE\" IS NOT NULL AND \"SEZIONE\" = '" . $section . "'";
        $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
        while ($row = pg_fetch_assoc($result)) {
            $csv .= $row["number"] . ", " . $section . "\n";
        }
    }
    echo $csv;
}
function generateChart3($connection, $latitude, $longitude) {
    $section = getSection($latitude, $longitude);
    $qualifications = array();
    $qualifications = getQualificationEn($connection);
    $csv = "tipoQualifica,numero,sede" . "\n";
    foreach ($qualifications as $qualification) {
        $csv .= $qualification . ", ";
        $query = "SELECT COUNT(CASE WHEN qualifica_en = '" . $qualification . "' THEN 0 END) as number
                        FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                        WHERE \"SEZIONE\" IS NOT NULL AND \"SEZIONE\" = '" . $section . "'";
        $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
        while ($row = pg_fetch_assoc($result)) {
            $csv .= $row["number"] . ", " . $section . "\n";
        }
    }
    echo $csv;
}
function generateChart4($connection, $latitude, $longitude) {
    $section = getSection($latitude, $longitude);
    $profiles = array();
    $profiles = getProfiles($connection);
    $csv = "tipoQualifica,numero,sede" . "\n";
    foreach ($profiles as $profile) {
        $csv .= $profile . ", ";
        $query = "SELECT COUNT(CASE WHEN profilo = '" . $profile . "' THEN 0 END) as number
                        FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                        WHERE \"SEZIONE\" IS NOT NULL AND \"SEZIONE\" = '" . $section . "'";
        $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
        while ($row = pg_fetch_assoc($result)) {
            $csv .= $row["number"] . ", " . $section . "\n";
        }
    }
    echo $csv;
}
function generateChart5($connection, $latitude, $longitude) {
    $section = getSection($latitude, $longitude);
    $profiles = array();
    $profiles = getProfilesEn($connection);
    $csv = "tipoQualifica,numero,sede" . "\n";
    foreach ($profiles as $profile) {
        $csv .= $profile . ", ";
        $query = "SELECT COUNT(CASE WHEN profilo_en = '" . $profile . "' THEN 0 END) as number
                        FROM \"ista_personale_da_esportare_per_visualizzazione_notesti\"
                        WHERE \"SEZIONE\" IS NOT NULL AND \"SEZIONE\" = '" . $section . "'";
        $result = pg_query($connection, $query) or die("Fatal Error:    Cannot Perform Query");
        while ($row = pg_fetch_assoc($result)) {
            $csv .= $row["number"] . ", " . $section . "\n";
        }
    }
    echo $csv;
}
?>
