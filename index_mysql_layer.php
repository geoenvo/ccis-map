<?php
    include_once('config.php');
    
    /*
      construct geojson feature array
    */
    function createfeature($id, $nid, $namestat, $lat, $lon, $stat_id)
    {
        //unset($data["field_station_geodata_lat"]);
        //unset($data["field_station_geodata_lon"]);
        
        $feature = array();
        $feature["type"] = "Feature";
        $feature["id"] = $id;

        $feature["geometry"] = array();
        $feature["geometry"]["type"] = "Point";
        $feature["geometry"]["coordinates"] = array($lon+0, $lat+0);
        
        $feature["properties"]["nid"] = $nid;
        $feature["properties"]["station"] = $namestat;
        $feature["properties"]["stat_id"] = $stat_id;
        //s$feature["properties"]["visible"] = "true";

        return $feature;
    }

    $conn = mysql_connect($_dbhost, $_dbuser, $_dbpass);
    
    if(!$conn)
    {
        die('Could not connect: ' . mysql_error());
    }
    
    $feature = array();
    $feature['type'] = "FeatureCollection";
    $feature['features'] = array();
    
    mysql_select_db($_dbname);
    
    $sql = 'SELECT
                t1.nid as node_id,
                t2.entity_id,
                title,
                field_station_geodata_lat,
                field_station_geodata_lon,
                field_stationcode_value
            FROM
                node AS t1
                INNER JOIN field_revision_field_station_geodata AS t2
                    ON t1.vid = t2.entity_id
                INNER JOIN field_data_field_stationcode AS t3
                    ON t2.entity_id = t3.entity_id
            WHERE
                t1.status = 1';
    
    $retval = mysql_query($sql, $conn);
    
    if(!$retval)
    {
        die('Could not get data: ' . mysql_error());
    }
    
    while($row = mysql_fetch_assoc($retval))
    {
        $feature['features'][] = createfeature(
            $row['entity_id'],
            $row['node_id'],
            $row['title'],
            $row['field_station_geodata_lat'],
            $row['field_station_geodata_lon'],
            $row['field_stationcode_value']
        );
    } 
    
    echo json_encode($feature);
    
    mysql_close($conn);
?>