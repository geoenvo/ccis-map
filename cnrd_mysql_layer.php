<?php
    include_once('config.php');
    
    /**
      construct geojson feature array from mysql
      and fetch cdd/cwd value for station from mongodb
      
      @param dbhandle $mongodb
        mongo database handle
      @param array $cat_lookup
        hexcolor and classification for given cdd/cwd
      @param string $year
      @param string $month
      @param string $day
      @param string $type
        cdd/cwd/ch
      @param string $category
        cdd/cwd category (general or agriculture)
      @param string $period
        ch period (pentad, dasarian, month, or trimonth)
      @param string $id
      @param string $namestat
        name of station
      @param float $lat
        latitude coordinate
      @param float $lon
        longitude coordinate
      @param int $stat_id
        station number/code
      
      @return array $feature
        geojson feature array
    */
    function createfeature($mongodb, $cat_lookup, $year, $month, $day, $type, $category, $period, $id, $namestat, $lat, $lon, $stat_id)
    {
        //unset($data["field_station_geodata_lat"]);
        //unset($data["field_station_geodata_lon"]);
        
        // set this to true to allow query from form
        $form_query = true;
        
        $feature = array();
        $feature['type'] = 'Feature';
        $feature['id'] = $id;

        $feature['geometry'] = array();
        $feature['geometry']['type'] = 'Point';
        $feature['geometry']["coordinates"] = array($lon + 0, $lat + 0);

        $feature['properties']['station'] = $namestat;
        $feature['properties']['stat_id'] = $stat_id;
        
        // set the mongo collection
        // <stat_id>_<cdd|cwd>
        // <stat_id>_ch_<pentad|dasarian|month|trimonth>
        $coll_name = '';
        
        if ($type == 'cdd' || $type == 'cwd')
        {
            $coll_name = $stat_id . '_' . $type;
        }
        else if ($type == 'ch')
        {
            $coll_name = $stat_id . '_' . $type . '_' . $period;
        }
        
        // get the cdd from mongodb
        // latest data, date descending
        $coll = new MongoCollection($mongodb, $coll_name);
        
        $cursor = null;
        
        if ($form_query)
        {
            $start = '';
            $end = '';
            
             // searching year only
            // query for cdd and cwd
            $start = $year . '-01-01 00:00:00';
            $end = $year . '-12-31 23:59:59';
            
            // query for ch
            $date = $year . '-' . '01' . '-' . '01' . ' ' . '12:00:00';
            
            if ($month != 0) // search year and month
            {                
                // get the last day in the month
                $month_last_day = date('t', strtotime($year . '-' . $month));
                
                $start = $year . '-' . $month . '-01 00:00:00';
                $end = $year . '-' . $month . '-' . $month_last_day . ' 23:59:59';
                
                $date = $year . '-' . $month . '-' . '01' . ' ' . '12:00:00';
            }
            
            if ($month != 0 && $day != 0)
            {   
                $start = $year . '-' . $month . '-' . $day . ' 00:00:00';
                $end = $year . '-' . $month . '-' . $day . ' 23:59:59';
                
                $date = $year . '-' . $month . '-' . $day . ' ' . '12:00:00';
            }
            
            $query = null;
            
            // different queries for cdd/cwd and ch
            if ($type == 'cdd' || $type == 'cwd')
            {
                $query = array('date' => array('$gte' => $start, '$lte' => $end));
            }
            else if ($type == 'ch')
            {
                // query for between the date
                /*
                $query = array(
                    'start' => array('$lte' => $date),
                    'end' => array('$gte' => $date)
                );
                */
                
                // above not valid, just query the end date
                $query = array('end' => array('$gte' => $start, '$lte' => $end));
            }
            
            $cursor = $coll->find($query);
        }
        else
        {
            $cursor = $coll->find();
        }
        
        // different sorting fields
        if ($type == 'cdd' || $type == 'cwd')
        {
            $cursor->sort(array('date' => -1)); // get latest value
        }
        else if ($type == 'ch')
        {
            $cursor->sort(array('start' => -1));
        }
        
        $cursor->limit(1);
        
        // default is NA
        $feature['properties']['date'] = 'NA';
        $feature['properties']['start'] = 'NA';
        $feature['properties']['end'] = 'NA';
        $feature['properties']['type'] = strtoupper($type);
        $feature['properties']['category'] = ucfirst($category);
        $feature['properties']['value'] = 'NA';
        $feature['properties']['fill_color'] = $cat_lookup[$type]['no_data']['hexcolor'];
        $feature['properties']['classification'] = $cat_lookup[$type]['no_data']['classification'];
        
        if ($cursor->count() > 0)
        {
            $cat = '';
            
            if ($category == 'general')
            {
                $cat = 'met';
            }
            else if ($category == 'agriculture')
            {
                $cat = 'agri';
            }
            
            foreach ($cursor as $value)
            {
                $field_name = '';
                
                if ($type == 'cdd')
                {
                    $field_name = 'cdd' . '.' . $cat; // somehow cdd and cwd mongo fields have same name => 20160222 fixed
                }
                else if ($type == 'cwd')
                {
                    $field_name = 'cwd' . '.' . $cat;
                }
                else if ($type == 'ch')
                {
                    $field_name = 'chtot';
                }
                
                $val = $value[$field_name]; 
                
                if ($val == null || $val == 'NA') // not available or invalid
                {
                    
                }
                else
                {
                    if ($value['date'] != null)
                    {
                        $row_date = date('Y-m-d', strtotime($value['date']));
                        $feature['properties']['date'] = $row_date;
                    }
                    
                    if ($value['start'] != null)
                    {
                        $row_start = date('Y-m-d', strtotime($value['start']));
                        $row_end = date('Y-m-d', strtotime($value['end']));
                        
                        $feature['properties']['start'] = $row_start;
                        $feature['properties']['end'] = $row_end;
                    }
                    
                    $feature['properties']['value'] = $val;
                    
                    if ($type == 'cdd' || $type == 'cwd')
                    {
                        if ($val == 0) {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['no_drought']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['no_drought']['classification'];
                        }
                        else if ($val >= 1 && $val <= 5)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['very_short']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['very_short']['classification'];
                        }
                        else if ($val >= 6 && $val <= 10)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['short']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['short']['classification'];
                        }
                        else if ($val >= 11 && $val <= 20)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['moderate']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['moderate']['classification'];
                        }
                        else if ($val >= 21 && $val <= 30)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['long']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['long']['classification'];
                        }
                        else if ($val >= 31 && $val <= 60)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['very_long']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['very_long']['classification'];
                        }
                        else if ($val > 60)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['extreme_drought']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['extreme_drought']['classification'];
                        }
                    }
                    else if ($type == 'ch')
                    {
                        $feature['properties']['period'] = ucfirst($period);
                        $feature['properties']['period_value'] = $value[$period];
                        
                        /*
                        if ($val < 1)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['0']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['0']['classification'];
                        }
                        else
                        {
                            $upper = round($val, -1); // round nearest ten up
                            
                            if ($upper > 300)
                            {
                                $upper = 300;
                            }
                            
                            $feature['properties']['fill_color'] = $cat_lookup[$type]["$upper"]['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]["$upper"]['classification'];
                        }
                        */
                        
                        if ($val >= 0 && $val <= 10)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['0-10']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['0-10']['classification'];
                        }
                        else if ($val >= 11 && $val <= 20)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['11-20']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['11-20']['classification'];
                        }
                        else if ($val >= 21 && $val <= 50)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['21-50']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['21-50']['classification'];
                        }
                        else if ($val >= 51 && $val <= 100)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['51-100']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['51-100']['classification'];
                        }
                        else if ($val >= 101 && $val <= 150)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['101-150']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['101-150']['classification'];
                        }
                        else if ($val >= 151 && $val <= 200)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['151-200']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['151-200']['classification'];
                        }
                        else if ($val >= 201 && $val <= 300)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['201-300']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['201-300']['classification'];
                        }
                        else if ($val >= 301 && $val <= 400)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['301-400']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['301-400']['classification'];
                        }
                        else if ($val >= 400 && $val <= 500)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['400-500']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['400-500']['classification'];
                        }
                        else if ($val > 500)
                        {
                            $feature['properties']['fill_color'] = $cat_lookup[$type]['501']['hexcolor'];
                            $feature['properties']['classification'] = $cat_lookup[$type]['501']['classification'];
                        }
                    }
                }
            }
        }
        
        //s$feature["properties"]["visible"] = "true";

        return $feature;
    }
    
    ///////////////////////////////////////////////////
    
    // set date values, default is current year
    $year = (isset($_GET['year']) && is_numeric($_GET['year'])) ? $_GET['year'] : date('Y');
    
    $month = (isset($_GET['month']) && is_numeric($_GET['month'])) ? $_GET['month'] : date('m');
    $month = ($month < 10) ? ('0' . $month) : $month;
    
    $day = (isset($_GET['day']) && is_numeric($_GET['day'])) ? $_GET['day'] : date('j');
    $day = ($day < 10) ? ('0' . $day) : $day;
    
    $type = (isset($_GET['type']) && $_GET['type'] != 'undefined') ? strtolower($_GET['type']) : 'cdd';
    
    $category = (isset($_GET['category']) && $_GET['category'] != 'undefined') ? strtolower($_GET['category']) : 'general';
    
    $period = (isset($_GET['period']) && $_GET['period'] != 'undefined') ? strtolower($_GET['period']) : 'pentad';
    
    // create mysql and mongo dbhandles
    
    $_dbname_mongo = 'piku';
    $mc = new MongoClient();
    $mongodb = $mc->selectDB($_dbname_mongo);

    $conn = mysql_connect($_dbhost, $_dbuser, $_dbpass);
    
    if(!$conn)
    {
        die('Could not connect: ' . mysql_error());
    }
    
    $feature = array();
    $feature['type'] = 'FeatureCollection';
    $feature['features'] = array();
    
    mysql_select_db($_dbname);
    
    $sql = 'SELECT
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
            $mongodb,
            $_cat_lookup,
            $year,
            $month,
            $day,
            $type,
            $category,
            $period,
            $row['entity_id'],
            $row['title'],
            $row['field_station_geodata_lat'],
            $row['field_station_geodata_lon'],
            $row['field_stationcode_value']
        );
    } 
    
    echo json_encode($feature);
    
    mysql_close($conn);
?>
