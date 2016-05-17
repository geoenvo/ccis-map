<?php
    include_once('config.php');

    /**
        output station grouped by CDD classification to pdf
        makes use of mpdf library: http://www.mpdf1.com/mpdf/index.php
        
        @param int $year
        @param int $month
        @param int $day
        @param string $type
        @param string $category
        
        @return pdf file stream if 'pdf=1' parameter is given else, output to browser
    */
    
    $year = $_GET['year'];
    $month = $_GET['month'];
    $day = $_GET['day'];
    $type = $_GET['type'];
    $category = $_GET['category'];
    
    if ($year == null) // must at least have year
    {
        exit;
    }
    
    $_dbname_mongo = 'piku';
    
    $mc = new MongoClient();
    $mongodb = $mc->selectDB($_dbname_mongo);

    $conn = mysql_connect($_dbhost, $_dbuser, $_dbpass);
    
    if(!$conn)
    {
        die('Could not connect: ' . mysql_error());
    }
    
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
                t1.status = 1
            ORDER BY
                title ASC';
    
    $retval = mysql_query($sql, $conn);
    
    if(!$retval)
    {
        die('Could not get data: ' . mysql_error());
    }
    
    while($row = mysql_fetch_assoc($retval))
    {
        $stat_name = $row['title'];
        $stat_id = $row['field_stationcode_value'];
        $stat_class = 'no_data';
        
        // mongodb section start
        
        $coll_name = $stat_id . '_' . $type;
        
        $coll = new MongoCollection($mongodb, $coll_name);
        
        $cursor = null;
        
        if ($form_query)
        {
            $start = '';
            $end = '';
            
            // searching year only
            $start = $year . '-01-01 00:00:00';
            $end = $year . '-12-31 23:59:59';
            
            if ($month != 0) // search year and month
            {
                if ($month < 10)
                {
                    $month  = '0' . $month;
                }
                
                // get the last day in the month
                $month_last_day = date('t', strtotime($year . '-' . $month));
                
                $start = $year . '-' . $month . '-01 00:00:00';
                $end = $year . '-' . $month . '-' . $month_last_day . ' 23:59:59';
            }
            
            if ($month != 0 && $day != 0)
            {   
                $start = $year . '-' . $month . '-' . $day . ' 00:00:00';
                $end = $year . '-' . $month . '-' . $day . ' 23:59:59';
            }
            
            $query = array('date' => array('$gte' => $start, '$lte' => $end));
            
            $cursor = $coll->find($query);
        }
        else
        {
            $cursor = $coll->find();
        }
        
        $cursor->sort(array('date' => -1));
        $cursor->limit(1);
        
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
                $val = $value[$type . '.' . $cat]; // somehow cdd and cwd mongo fields have same name => 20160222 fixed
                
                if ($val == null || $val == 'NA') // not available or invalid
                {
                    $stat_class = 'no_data';
                }
                else
                {
                    if ($val == 0) {
                        $stat_class = 'no_drought';
                    }
                    else if ($val >= 1 && $val <= 5)
                    {
                        $stat_class = 'very_short';
                    }
                    else if ($val >= 6 && $val <= 10)
                    {
                        $stat_class = 'short';
                    }
                    else if ($val >= 11 && $val <= 20)
                    {
                        $stat_class = 'moderate';
                    }
                    else if ($val >= 21 && $val <= 30)
                    {
                        $stat_class = 'long';
                    }
                    else if ($val >= 31 && $val <= 60)
                    {
                        $stat_class = 'very_long';
                    }
                    else if ($val > 60)
                    {
                        $stat_class = 'extreme_drought';
                    }
                }
            } // endforeach
        }
        
        // mongodb section end
        $stat_info = $stat_name . ' (' . $stat_id . ')';
        $_cat_lookup[$type][$stat_class]['stations'] .= '<li>' . htmlspecialchars($stat_info) . '</li>';
    } // endwhile
        
    $m = '';
    $d = '';
    
    if ($month == 0)
    {
    
    }
    else if (strlen($month) < 2)
    {
        $m = '-0' . $month;
    }
    else
    {
        $m = '-' . $month;
    }
    
    if ($day == 0)
    {
    
    }
    else if ($strlen($day) < 2)
    {
        $d = '-0' . $day;
    }
    else
    {
        $d = '-' . $day;
    }
    
    $period = $year . $m . $d;
    $type1 = strtoupper($type);
    $category = ucfirst($category);
    
    $pdf_output = <<< PDF_OUTPUT
<style>
    table { width: 100%; page-break-inside: avoid; }
    th, td { vertical-align: top; }
</style>

<!--<p>Hari Tanpa Hujan Berturut-turut (Consecutive Dry Days)</p>-->
<p>Klasifikasi (Jumlah Hari)<br>Classification (Days)</p>
<p>
{$type1}: {$category}
<br>
Periode: {$period}
</p>
<table cellspacing="0" border="1">
    <thead>
        <tr>
            <th>
                {$_cat_lookup[$type]['no_data']['classification']}
                <br>
                {$_cat_lookup[$type]['no_data']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['no_drought']['classification']}
                <br>
                {$_cat_lookup[$type]['no_drought']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['very_short']['classification']}
                <br>
                {$_cat_lookup[$type]['very_short']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['short']['classification']}
                <br>
                {$_cat_lookup[$type]['short']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['moderate']['classification']}
                <br>
                {$_cat_lookup[$type]['moderate']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['long']['classification']}
                <br>
                {$_cat_lookup[$type]['long']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['very_long']['classification']}
                <br>
                {$_cat_lookup[$type]['very_long']['range']}
            </th>
            <th>
                {$_cat_lookup[$type]['extreme_drought']['classification']}
                <br>
                {$_cat_lookup[$type]['extreme_drought']['range']}
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <ol>
                    {$_cat_lookup[$type]['no_data']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['no_drought']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['very_short']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['short']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['moderate']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['long']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['very_long']['stations']}
                </ol>
            </td>
            <td>
                <ol>
                    {$_cat_lookup[$type]['extreme_drought']['stations']}
                </ol>
            </td>
        </tr>
    </tbody>
</table>
PDF_OUTPUT;


//==============================================================
//==============================================================
//==============================================================

include('libs/mpdf/mpdf.php');

if (isset($_GET['pdf']))
{
    $mpdf = new mPDF(); 
    $mpdf->WriteHTML($pdf_output);
    $mpdf->Output();
}
else
{
    echo $pdf_output;
}

exit;

//==============================================================
//==============================================================
//==============================================================


?>
