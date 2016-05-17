<?php
    /**
        2014
    */
    
    // by default query yesterday's data
    $yesterday = date_parse(date('Y-m-j', strtotime('-1 days')));
    
    $year = $yesterday['year'];
    $month = $yesterday['month'];
    $day = $yesterday['day'];
    
    
    $nid = (isset($_GET['nid']) && is_numeric($_GET['nid'])) ? $_GET['nid'] : '';
    $dashboard_path = 'http://' . $_SERVER['HTTP_HOST'] . '/ccis/dashboard/?nid=';
?>
    <style>
        table { font-size: 18px; }
        table.data td { text-align: center; }
    </style>
    <table width="100%">
        <tr>
            <td style="vertical-align: bottom; text-align: left;">
                <form id="ntce">
                    <table>
                    <tr>
                    <td>Date</td>
                    <td>:</td>
                    <td>
                    <select name="year">
                        <option value="0">Year</option>
                        <?php
                            $lower_bound = 1970;
                            for ($y = date('Y'); $y >= $lower_bound; $y--):
                        ?>
                        <option value="<?php echo $y; ?>" <?php if ($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                        <?php
                            endfor;
                        ?>
                    </select>
                    <select name="month">
                        <?php
                            $months = array();
                            $months[0] = 'Month';
                            $months[1] = 'Jan';
                            $months[2] = 'Feb';
                            $months[3] = 'Mar';
                            $months[4] = 'Apr';
                            $months[5] = 'May';
                            $months[6] = 'Jun';
                            $months[7] = 'Jul';
                            $months[8] = 'Aug';
                            $months[9] = 'Sep';
                            $months[10] = 'Oct';
                            $months[11] = 'Nov';
                            $months[12] = 'Dec';
                        ?>
                        <?php
                            foreach ($months as $key => $value):
                        ?>
                        <option value="<?php echo $key; ?>" <?php if ($month == $key) echo 'selected'; ?>><?php echo $value; ?></option>
                        <?php
                            endforeach;
                        ?>
                    </select>
                    <select name="day">
                        <option value="0">Day</option>
                        <?php
                            for ($d = 1; $d <= 31; $d++):
                        ?>
                        <option value="<?php echo $d; ?>" <?php if ($day == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                        <?php
                            endfor;
                        ?>
                    </select>
                    </td>
                    </tr>
                    <tr>
                    <td>Range</td>
                    <td>:</td>
                    <td><input type="radio" name="range" value="20">20 years <input type="radio" name="range" value="25">25 years <input type="radio" name="range" value="30">30 years</td>
                    </tr>
                    <tr>
                    <td>Period</td>
                    <td>:</td>
                    <td><input type="radio" name="period" value="pentad">Pentad <input type="radio" name="period" value="dasarian">Dasarian <input type="radio" name="period" value="month">Month <input type="radio" name="period" value="trimonth">Tri-month</td>
                    </tr>
                    </table>
                    <input type="submit" name="submit" value="search">
                </form>
            </td>
            <td style="vertical-align: bottom; text-align: right;">
                <a href="<?php echo $dashboard_path . $nid; ?>" target="_blank">View Station Dashboard</a>
            </td>
        </tr>
    </table>
    
    <div id="tabs" style="margin-top: 7px;">
        <ul>
            <li><a href="#tabs-1">CH</a></li>
            <li><a href="#tabs-2">Temp</a></li>
        </ul>
        
        <div id="tabs-1" class="tab-container">
            
        </div>
        
        <div id="tabs-2" class="tab-container">
            
        </div>
    </div>