<?php
    include_once('config.php');
    
    /**
        mongo_query.php
        
        Query mongo db for climate data indices. Outputs in JSON for datatables.
        JSON structure should be something like this:
        {
            "iTotalRecords":1224,
            "iTotalDisplayRecords":1224,
            "aaData":[
                ["1","2013-12-21 00:00:00+01","NA"],
                ["2","2013-12-11 00:00:00+01","NA"],
                ["3","2013-12-01 00:00:00+01","NA"],
                ["4","2013-11-21 00:00:00+01","NA"],
                ["5","2013-11-11 00:00:00+01","NA"],
                ["6","2013-11-01 00:00:00+01","NA"],
                ["7","2013-10-21 00:00:00+01","NA"],
                ["8","2013-10-11 00:00:00+01","NA"],
                ["9","2013-10-01 00:00:00+01","NA"],
                ["10","2013-09-21 00:00:00+01","NA"]]
        }
        
        @param id the climate station id
        @param type the data period
        @return json object
    */
    $id = $_GET['id'];
    $type = $_GET['type'];
    
    $mc = new MongoClient();
    $db = $mc->selectDB($_dbname_mongo);
    $output = array();
    
    $coll_name = $id . "_" . $type;
    $collection = new MongoCollection($db, $coll_name);
    
    // just getting the document fields/columns
    if (isset($_GET['columns']))
    {
        //$output = array('No.', 'Date', 'Precip Precip');
        $cursor = $collection->find();
        $cursor->limit(1);
        
        if ($cursor->count() > 0)
        {
            foreach ($cursor as $value)
            {
                $fields = array_keys($value);
                $fields[0] = 'No.';
            }
            
            $output = $fields;
        }
    }
    else
    {
        if (isset($_GET['sSearch_1']) && $_GET['sSearch_1'] != "" && $_GET['sSearch_1'] != "~")
        {
            $date = explode('~', $_GET['sSearch_1']);
            
            if ($date[1]) // searching date between start and end date
            {
                $start = $date[0] . ' 00:00:00';
                $end = $date[1] . ' 23:59:59';
                $cursor = $collection->find(array("date" => array('$gte' => $start, '$lte' => $end)));
            }
            else
            {
                $start = $date[0] . ' 00:00:00'; // searching date after
                $cursor = $collection->find(array("date" => array('$gte' => $start)));
            }
            
            //$cursor = $collection->find();
        }
        else
        {
            $cursor = $collection->find();
        }

        $cursor->sort(array('date' => -1));

        if (isset($_GET['iDisplayStart']) && is_numeric($_GET['iDisplayStart']))
        {
            $cursor->skip($_GET['iDisplayStart']);
        }

        if (isset($_GET['iDisplayLength']) && is_numeric($_GET['iDisplayLength']))
        {
            $cursor->limit($_GET['iDisplayLength']);
        }

        $data = array();
        
        if ($cursor->count() > 0)
        {
            $i = isset($_GET["iDisplayStart"]) ? $_GET["iDisplayStart"] : 0;
            
            /*
            foreach ($cursor as $value)
            {
                $i++;
                
                if ($type == '10_days')
                {
                    $row[] = array("$i", $value['date'], $value['precip_10day']);
                }
                elseif ($type == 'monthly')
                {
                    $row[] = array("$i", $value['date'], $value['rx1day'], $value['tn10p'], $value['tnn'], $value['tx90p'], $value['txx'], $value['dtr']);
                }
                elseif ($type == 'yearly')
                {
                    $row[] = array("$i", $value['date'], $value['cdd'], $value['cwd'], $value['fd'], $value['id'], $value['r20mm'], $value['r99ptot']);
                }
                elseif ($type == 'monthly_fklim')
                {
                    $row[] = array("$i", $value['date'], $value['tave'], $value['tmax'], $value['tmin'], $value['rhave'], $value['slpave'], $value['pave'], $value['wsave'], $value['wdave'], $value['rain'], $value['sund'], $value['srep']);
                }
            }
            */
            
            $j = 0;
            $fields = array();
            
            foreach ($cursor as $value)
            {
                $i++; // record number
                $j++;
                
                if ($j == 1) // do this just once
                {
                    $fields = array_keys($value); // get the document fields
                    unset($fields[0]); // no need for _id
                }
                
                $doc = array();
                $doc[] = "$i";
                
                foreach ($fields as $field)
                {
                    $doc[] = $value[$field];
                }
                
                $row[] = $doc;
            }
            
            $data = $row;
            
            $output = array(
                'iTotalRecords' => $cursor->count(),
                'iTotalDisplayRecords' => $cursor->count(),
                'aaData' => $data
            );
        }
    }
    
    echo json_encode($output);
?>