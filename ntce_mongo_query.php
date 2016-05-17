<?php
    include_once('config.php');
    
    $_dbname_mongo = 'piku';
    
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
        
        /*
        $query_info =
            "<table>
                <tr>
                    <td>Date</td>
                    <td>:</td><td>$year-$month-$day</td>
                </tr>
                <tr><td>Range</td><td>:</td><td>$range years</td></tr>
                <tr><td>Period</td><td>:</td><td>$period</td></tr>
            </table>";
        */
        $query_info =
            "<table>
                <tr>
                    <td>Date</td>
                    <td>:</td><td>$year-$month-$day</td>
                </tr>
                <tr><td>Period</td><td>:</td><td>$period</td></tr>
            </table>";
            
        $no_data_found = '<br>No data found.<br>';
        
        $output = '';
        $output .= $query_info;
        
        if (isset($_GET['form'])) // getting value for form
        {
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
            
            if ($cursor->count() > 0)
            {                
                $output .= '<table class="data" width="100%" cellspacing="0" border="1">';
                
                $j = 0;
                $fields = array();
                
                foreach ($cursor as $value)
                {
                    $j++;
                    
                    if ($j == 1)
                    {
                        $fields = array_keys($value);
                        unset($fields[0]); // no need for _id
                        
                        array_unshift($fields, $fields[3]); // put period at front
                        unset($fields[3]);
                        
                        $th = '';
                        
                        foreach ($fields as $field)
                        {
                            $tooltip = isset($_tooltip_indices[$field]) ? $_tooltip_indices[$field] : '';
                            
                            $th .= '<th title="' . htmlspecialchars($tooltip) . '">' . $field . '</th>';
                        }
                        
                        $output .= '<thead><tr>' . $th  . '</tr></thead>';
                        $output .= '<tbody>';
                    }
                    
                    $tr = '<tr>';
                    $td = '';
                    
                    foreach ($fields as $field)
                    {
                        if ($field == 'start' || $field == 'end')
                        {
                            $date = date_parse($value[$field]);
                            $date = date('Y-m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
                            $td .= '<td>' . $date . '</td>';
                        }
                        else
                        {
                            $bgcolor = '';
                            
                            
                            if (strpos($field, 'anom') !== false && is_numeric($value[$field]))
                            {
                                if ($value[$field] <= 0) // less than or equal
                                {
                                    $bgcolor = 'style="background-color:#FF9797;"'; // red
                                    
                                    if ($param == 'temp')
                                    {
                                        $bgcolor = 'style="background-color:#A8CFFF;"'; // blue
                                    }
                                }
                                else // greater than
                                {
                                    $bgcolor = 'style="background-color:#A8CFFF;"'; // blue
                                    
                                    if ($param == 'temp')
                                    {
                                        $bgcolor = 'style="background-color:#FF9797;"'; // red
                                    }
                                }
                                    
                            }
                            
                            $td .= "<td $bgcolor>" . $value[$field] . '</td>';
                        }
                    }
                    
                    $tr .= $td . '</tr>';
                    $output .= $tr;
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
            }
            else
            {
                $output .= $no_data_found;
            }
        }
        else // getting past values
        {
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
            
            /*
            $query = array(
                'start' => array('$lte' => $date),
                'end' => array('$gte' => $date)
            );
            */
            
            $day_of_year = date('z', mktime(0, 0, 0, $month, $day, $year)) + 1; // start from zero, so add 1
            
            $query = array($period => $day_of_year); // use running period, so 1-365/366
            
            $cursor = $coll->find($query);
            $cursor->sort(array('start' => -1));
            //$cursor->limit(1);
            
            $indices = array();
            
            ///$query_info = "<br>Range: $range ($years years)<br>";
            $query_info = "<br>$years years ($range)<br>";
            $output = $query_info;
            
            
            if ($cursor->count() > 0)
            {
                $output .= '<table class="data" width="100%" cellspacing="0" border="1">';
                
                $j = 0;
                $fields = array();
                
                foreach ($cursor as $value)
                {
                    $j++;
                    
                    if ($j == 1)
                    {
                        $fields = array_keys($value);
                        unset($fields[0]); // no need for _id
                        
                        array_unshift($fields, $fields[3]); // put period at front
                        unset($fields[3]);
                        
                        $th = '';
                        
                        foreach ($fields as $field)
                        {
                            $tooltip = isset($tooltip_indices[$field]) ? $tooltip_indices[$field] : '';
                            
                            $th .= '<th title="' . htmlspecialchars($tooltip) . '">' . $field . '</th>';
                        }
                        
                        $output .= '<thead><tr>' . $th  . '</tr></thead>';
                        $output .= '<tbody>';
                    }
                    
                    $tr = '<tr>';
                    $td = '';
                    
                    foreach ($fields as $field)
                    {
                        if ($field == 'start' || $field == 'end')
                        {
                            $date = date_parse($value[$field]);
                            //$date = date('Y-m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
                            $date = date('m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year'])); // show month and day only
                            $td .= '<td>' . $date . '</td>';
                        }
                        else
                        {
                            $td .= '<td>' . $value[$field] . '</td>';
                            
                            if ($field != $period) // construct the indices array
                            {
                                $indices[$field][] = $value[$field];
                            }
                        }
                    }
                    
                    $tr .= $td . '</tr>';
                    $output .= $tr;
                }
                
                $show_avg = false;
                
                if ($show_avg == true)
                {
                    // calculate the average
                    $avg_row = '<tr><td colspan="3">Avg</td>';
                        
                    foreach ($indices as $ind)
                    {
                        $ind_count = count(array_filter($ind, 'is_numeric')); // dont divide by NA values
                        if ($ind_count == 0) // all is NA
                        {
                            $avg_row .= "<td>NA</td>";
                        }
                        else
                        {
                            $ind_avg = array_sum($ind) / $ind_count;
                            $avg_row .= "<td>$ind_avg</td>";
                        }
                    }
                    
                    $avg_row .= '</tr>';
                    $output .= $avg_row;
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
            }
            else
            {
                $output = $no_data_found;
            }
        }
        
        echo $output;
    }
?>