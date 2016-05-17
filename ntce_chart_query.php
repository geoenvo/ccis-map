<?php
    include_once('config.php');
    
    $_dbname_mongo = 'piku';
    
    /**
        chart_query.php
        
        Query mongo db for climate data indices. Outputs in JSON for flot chart.
        JSON structure should be something like this:
        {
            "usa": {
                label: "USA",
                data: [[1988, 483994], [1989, 479060], [1990, 457648], [1991, 401949], [1992, 424705], [1993, 402375], [1994, 377867], [1995, 357382], [1996, 337946], [1997, 336185], [1998, 328611], [1999, 329421], [2000, 342172], [2001, 344932], [2002, 387303], [2003, 440813], [2004, 480451], [2005, 504638], [2006, 528692]]
            },        
            "russia": {
                label: "Russia",
                data: [[1988, 'NA'], [1989, 203000], [1990, 171000], [1992, 42500], [1993, 37600], [1994, 36600], [1995, 21700], [1996, 19200], [1997, 21300], [1998, 13600], [1999, 14000], [2000, 19100], [2001, 21300], [2002, 23600], [2003, 25100], [2004, 26100], [2005, 31100], [2006, 34700]]
            },
            "uk": {
                label: "UK",
                data: [[1988, 62982], [1989, 62027], [1990, 60696], [1991, 62348], [1992, 58560], [1993, 56393], [1994, 54579], [1995, 50818], [1996, 50554], [1997, 48276], [1998, 47691], [1999, 47529], [2000, 47778], [2001, 48760], [2002, 50949], [2003, 57452], [2004, 60234], [2005, 60076], [2006, 59213]]
            },
            "germany": {
                label: "Germany",
                data: [[1988, 55627], [1989, 55475], [1990, 58464], [1991, 55134], [1992, 52436], [1993, 47139], [1994, 43962], [1995, 43238], [1996, 42395], [1997, 40854], [1998, 40993], [1999, 41822], [2000, 41147], [2001, 40474], [2002, 40604], [2003, 40044], [2004, 38816], [2005, 38060], [2006, 36984]]
            },
            "denmark": {
                label: "Denmark",
                data: [[1988, 3813], [1989, 3719], [1990, 3722], [1991, 3789], [1992, 3720], [1993, 3730], [1994, 3636], [1995, 3598], [1996, 3610], [1997, 3655], [1998, 3695], [1999, 3673], [2000, 3553], [2001, 3774], [2002, 3728], [2003, 3618], [2004, 3638], [2005, 3467], [2006, 3770]]
            },
            "sweden": {
                label: "Sweden",
                data: [[1988, 6402], [1989, 6474], [1990, 6605], [1991, 6209], [1992, 6035], [1993, 6020], [1994, 6000], [1995, 6018], [1996, 3958], [1997, 5780], [1998, 5954], [1999, 6178], [2000, 6411], [2001, 5993], [2002, 5833], [2003, 5791], [2004, 5450], [2005, 5521], [2006, 5271]]
            },
            "norway": {
                label: "Norway",
                data: [[1988, 4382], [1989, 4498], [1990, 4535], [1991, 4398], [1992, 4766], [1993, 4441], [1994, 4670], [1995, 4217], [1996, 4275], [1997, 4203], [1998, 4482], [1999, 4506], [2000, 4358], [2001, 4385], [2002, 5269], [2003, 5066], [2004, 5194], [2005, 4887], [2006, 4891]]
            }
        }
        
        @param id the id of the climate station
        @param type the data period
        @return json object
    */
    $stat_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
    
    if ($stat_id)
    {
        $year = (isset($_GET['year']) && is_numeric($_GET['year'])) ? $_GET['year'] : date('Y');
        
        $month = (isset($_GET['month']) && is_numeric($_GET['month'])) ? $_GET['month'] : date('m');
        $month = ($month < 10) ? ('0' . $month) : $month;
        
        $day = (isset($_GET['day']) && is_numeric($_GET['day'])) ? $_GET['day'] : date('j');
        $day = ($day < 10) ? ('0' . $day) : $day;
        
        $range = (isset($_GET['range']) && is_numeric($_GET['range'])) ? $_GET['range'] : 20;
        $period = (isset($_GET['period']) && $_GET['period'] != 'undefined') ? strtolower($_GET['period']) : 'pentad';
        $param = (isset($_GET['param'])  && $_GET['param'] != 'undefined') ? strtolower($_GET['param']) : 'ch';
        
        $mc = new MongoClient();
        $mongodb = $mc->selectDB($_dbname_mongo);
        
        $date = $year . '-' . $month . '-' . $day . ' ' . '12:00:00';
        $ymd = $year . '-' . $month . '-' . $day;
        
        $coll_name = $stat_id . '_' . $param . '_' . $period; // 96001_ch_dasarian
            
        $coll = new MongoCollection($mongodb, $coll_name);
        
        // query for between the date
        $query = array(
            'start' => array('$lte' => $date),
            'end' => array('$gte' => $date)
        );
        
        $cursor = $coll->find($query);
        $cursor->sort(array('start' => -1));
        $cursor->limit(1);
        
        $output = array();
        
        if ($cursor->count() > 0)
        {
                foreach ($cursor as $value)
                {
                    if ($param == 'temp')
                    {
                        $output['tn']['tnn'] = array (
                            'label' => "tnn ($ymd)",
                            'data' => array(array(0, $value['tnn']))
                        );
                        
                        $output['tg']['tgg'] = array (
                            'label' => "tgg ($ymd)",
                            'data' => array(array(0, $value['tgg']))
                        );
                        
                        $output['tx']['txx'] = array (
                            'label' => "txx ($ymd)",
                            'data' => array(array(0, $value['txx']))
                        );
                    }
                    else if ($param == 'ch')
                    {
                        $output['ch']['chtot'] = array (
                            'label' => "chtot ($ymd)",
                            'data' => array(array(0, $value['chtot']))
                        );
                    }
                }
        }
        
        // now get past values
        $years = $range;
        
        if ($range == 20)
        {
            $range = '1991-2010';
        }
        else if ($range == 25)
        {
            $range = '1986-2010';
        }
        else if ($range == 30)
        {
            $range = '1981-2010';
        }
        else
        {
            $range = '1991-2010';
        }
        
        $coll_name = $stat_id . '_' . $param . '_' . $period . '_' . $range; // 96001_ch_dasarian_1981-2010
        
        $coll = new MongoCollection($mongodb, $coll_name);
        
        $day_of_year = date('z', mktime(0, 0, 0, $month, $day, $year)) + 1; // start from zero, so add 1
        
        $query = array($period => $day_of_year); // use running period, so 1-365/366
        
        $cursor = $coll->find($query);
        $cursor->sort(array('start' => -1));
        
        if ($cursor->count() > 0)
        {
            foreach ($cursor as $value)
            {
                if ($param == 'temp')
                {
                    $output['tn']['tnx'] = array (
                        'label' => "tnx $years years ($range)",
                        'data' => array(array(0, $value['tnx']))
                    );
                    
                    $output['tn']['tng'] = array (
                        'label' => "tng $years years ($range)",
                        'data' => array(array(0, $value['tng']))
                    );
                    
                    $output['tn']['tnn2'] = array (
                        'label' => "tnn $years years ($range)",
                        'data' => array(array(0, $value['tnn']))
                    );
                    
                    $output['tg']['tgx'] = array (
                        'label' => "tgx $years years ($range)",
                        'data' => array(array(0, $value['tgx']))
                    );
                    
                    $output['tg']['tgg2'] = array (
                        'label' => "tgg $years years ($range)",
                        'data' => array(array(0, $value['tgg']))
                    );
                    
                    $output['tg']['tgn'] = array (
                        'label' => "tgn $years years ($range)",
                        'data' => array(array(0, $value['tgn']))
                    );
                    
                    $output['tx']['txx2'] = array (
                        'label' => "txx $years years ($range)",
                        'data' => array(array(0, $value['txx']))
                    );
                    
                    $output['tx']['txg'] = array (
                        'label' => "txg $years years ($range)",
                        'data' => array(array(0, $value['txg']))
                    );
                    
                    $output['tx']['txn'] = array (
                        'label' => "txn $years years ($range)",
                        'data' => array(array(0, $value['txn']))
                    );
                }
                else if ($param == 'ch')
                {
                    $output['ch']['min'] = array (
                        'label' => "min $years years ($range)",
                        'data' => array(array(0, $value['min']))
                    );
                    
                    $output['ch']['avg'] = array (
                        'label' => "avg $years years ($range)",
                        'data' => array(array(0, $value['avg']))
                    );
                    
                    $output['ch']['max'] = array (
                        'label' => "max $years years ($range)",
                        'data' => array(array(0, $value['max']))
                    );
                }
            }
        }
        
        echo json_encode($output, JSON_NUMERIC_CHECK);
    }
?>