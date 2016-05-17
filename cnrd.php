<?php
    /**
        CNRD (Consecutive No Rain Days) Map
        Peta Hari Tanpa Hujan Berturut-turut
        2014
        
        Related source files:
            config.php
            cnrd.php
            cnrd_export.php
            cnrd_mysql_layer.php
        
        Libraries used:
            jquery-ui
            openlayers
            mpdf
    */
    
    include_once('config.php');
    
    // set default value for form fields
    $year = (isset($_GET['year']) && is_numeric($_GET['year'])) ? $_GET['year'] : date('Y');
    
    $month = 0;
    
    if (isset($_GET['month']) && is_numeric($_GET['month']))
    {
        $month = $_GET['month'];
    }
    
    $day = 0;
    
    if (isset($_GET['day']) && is_numeric($_GET['day']))
    {
        $day = $_GET['day'];
    }
    
    $type = 'cdd'; // cdd, cwd, or ch
    
    if (isset($_GET['type']) && $_GET['type'] != 'undefined')
    {
        $type = strtolower($_GET['type']);
    }
    
    $category = 'general'; // general or agriculture
    
    if (isset($_GET['category']) && $_GET['category'] != 'undefined')
    {
        $category = strtolower($_GET['category']);
    }
    
    $period = 'pentad'; // pentad, dasarian, month, or trimonth
    
    if (isset($_GET['period']) && $_GET['period'] != 'undefined')
    {
        $period = strtolower($_GET['period']);
    }
?>
<html>
    <head>
        <title>OpenLayers Map</title>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-1.10.2.js"></script>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-ui-1.10.4.custom.js"></script>
        <script type="text/javascript" src="OpenLayers.js"></script>
        <script type="text/javascript" src="asset/popover/Popover.js"></script>
        <link rel="stylesheet" type="text/css" href="asset/jquery-ui/css/ccis/jquery-ui-1.10.4.custom.css">
        <link rel="stylesheet" type="text/css" href="asset/popover/openlayerspopovers.css">
        <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Pathway+Gothic+One|Open+Sans+Condensed:300">
        <link rel="stylesheet" type="text/css" href="asset/style.css">
    </head>
    <body>
        <div id="map-id">
            <div id="legend">
                <div class="header">
                    <div class="title left">Legend</div>
                    <div class="right"><a class="toggle" href="javascript:void(0);">Hide</a></div>
                    <div class="clear"></div>
                </div>
                <div class="content">
                    
                    <?php if ($type == 'cdd' || $type == 'cwd'): ?>
                    
                    <p>Klasifikasi (Jumlah Hari)<br>Classification (Days)</p>
                    
                    <?php elseif ($type == 'ch'): ?>
                    
                    <?php endif; ?>
                    
                    <form action="?">
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
                        <div>
                            <input id="cdd" type="radio" name="type" value="cdd" <?php if ($type == 'cdd') echo 'checked'; ?>> <label for="cdd" title="Consecutive Dry Days (Hari Tanpa Hujan Berturut-turut)">CDD</label>
                            <input id="cwd" type="radio" name="type" value="cwd" <?php if ($type == 'cwd') echo 'checked'; ?>> <label for="cwd" title="Consecutive Wet Days (Hari Hujan Berturut-turut)">CWD</label>
                            <input id="ch" type="radio" name="type" value="ch" <?php if ($type == 'ch') echo 'checked'; ?>> <label for="ch" title="">CH</label>
                        </div>
                        <div class="suboption cdd cwd">
                            <input id="general" type="radio" name="category" value="general" <?php if ($category == 'general') echo 'checked'; ?>> <label for="general" title="">General</label>
                            <input id="agriculture" type="radio" name="category" value="agriculture" <?php if ($category == 'agriculture') echo 'checked'; ?>> <label for="agriculture" title="">Agriculture</label>
                        </div>
                        <div class="suboption ch">
                            <input id="pentad" type="radio" name="period" value="pentad" <?php if ($period == 'pentad') echo 'checked'; ?>> <label for="pentad" title="">Pentad</label>
                            <input id="dasarian" type="radio" name="period" value="dasarian" <?php if ($period == 'dasarian') echo 'checked'; ?>> <label for="dasarian" title="">Dasarian</label>
                            <input id="month" type="radio" name="period" value="month" <?php if ($period == 'month') echo 'checked'; ?>> <label for="month" title="">Month</label>
                            <input id="trimonth" type="radio" name="period" value="trimonth" <?php if ($period == 'trimonth') echo 'checked'; ?>> <label for="trimonth" title="">Tri-month</label>
                        </div>
                        <input type="submit" name="submit" value="search">
                    </form>
                    <table id="details">
                        <tbody>
                            <tr>
                                <td>Date</td>
                                <td>:</td>
                                <td>
                                    
                                    <?php 
                                        // search parameters
                                        // month and day display
                                        
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
                                        else
                                        {
                                            $d = '-' . $day;
                                        }
                                        
                                        echo $year . $m . $d;
                                        
                                        if ($type == 'ch')
                                        {
                                            echo ' (end date)';
                                        }
                                    ?>
                                    
                                </td>
                            </tr>
                            
                            <?php if ($type == 'cdd' || $type == 'cwd'): ?>
                            
                            <?php if ($type == 'cdd'): ?>
                            
                            <tr>
                                <td>CDD</td>
                                <td>:</td>
                                <td>Consecutive Dry Days<br>(Hari Tanpa Hujan Berturut-turut)</td>
                            </tr>
                            
                            <?php elseif ($type == 'cwd'): ?>
                            
                            <tr>
                                <td>CWD</td>
                                <td>:</td>
                                <td>Consecutive Wet Days<br>(Hari Hujan Berturut-turut)</td>
                            </tr>
                            
                            <?php endif; ?>
                            
                            <tr>
                                <td>Cat.</td>
                                <td>:</td>
                                
                                <?php if ($category == 'general'): ?>
                                
                                <td>General</td>
                                
                                <?php elseif ($category == 'agriculture'): ?>
                                
                                <td>Agriculture</td>
                                
                                <?php endif; ?>
                                
                            </tr>
                            
                            <?php elseif ($type == 'ch'): ?>
                            
                            <tr>
                                <td>CH Period</td>
                                <td>:</td>
                                <td><?php echo ucfirst($period); ?></td>
                            </tr>
                            
                            <?php endif; ?>
                            
                        </tbody>
                    </table>
                    <table>
                        <tbody>
                            
                            <?php
                                // print the legend
                                if ($type == 'cdd' || $type == 'cwd'): // cdd/cwd legend
                            ?>
                            
                            <?php
                                foreach ($_cat_lookup[$type] as $cat):
                            ?>
                            
                            <tr>
                                <td><?php echo $cat['range']; ?></td>
                                <td class="color"><div class="circle" style="background: <?php echo $cat['hexcolor']; ?>;"></div></td>
                                <td><?php echo $cat['classification']; ?></td>
                            </tr>
                            
                            <?php
                                endforeach;
                            ?>
                            
                            <?php
                                elseif ($type == 'ch'): // ch legend
                            ?>
                            
                            <?php
                                foreach ($_cat_lookup[$type] as $cat):
                            ?>
                            
                            <tr>
                                <td class="color"><div class="circle" style="background: <?php echo $cat['hexcolor']; ?>;"></div></td>
                                <td><?php echo $cat['range']; ?></td>
                            </tr>
                            
                            <?php
                                endforeach;
                            ?>
                            
                            <?php
                                endif;
                            ?>
                            
                            <!--
                            <tr>
                                <td>-</td>
                                <td class="color"><div class="circle" style="background: #336D07;"></div></td>
                                <td>masih ada hujan (no drought)</td>
                            </tr>
                            <tr>
                                <td>1 - 5</td>
                                <td class="color"><div class="circle" style="background: #9EFF79;"></div></td>
                                <td>sangat pendek (very short)</td>
                            </tr>
                            <tr>
                                <td>6 - 10</td>
                                <td class="color"><div class="circle" style="background: #F9FF0B;"></div></td>
                                <td>pendek (short)</td>
                            </tr>
                            <tr>
                                <td>11 - 20</td>
                                <td class="color"><div class="circle" style="background: #EB9608;"></div></td>
                                <td>menengah (moderate)</td>
                            </tr>
                            <tr>
                                <td>21 - 30</td>
                                <td class="color"><div class="circle" style="background: #744B00;"></div></td>
                                <td>panjang (long)</td>
                            </tr>
                            <tr>
                                <td>31 - 60</td>
                                <td class="color"><div class="circle" style="background: #FCC1C3;"></div></td>
                                <td>sangat panjang (very long)</td>
                            </tr>
                            <tr>
                                <td>&gt; 60</td>
                                <td class="color"><div class="circle" style="background: #FB0400;"></div></td>
                                <td>kekeringan ekstrim (extreme drought)</td>
                            </tr>
                            <tr>
                                <td>-</td>
                                <td class="color"><div class="circle" style="background: #cccccc;"></div></td>
                                <td>data tidak tersedia (not available)</td>
                            </tr>
                            -->
                            
                        </tbody>
                    </table>
                    
                    <?php
                        // don't show export for ch
                        if ($type == 'cdd' || $type == 'cwd'):
                    ?>
                    
                    <a href="cnrd_export.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>&day=<?php echo $day; ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>" target="_blank">Export</a>
                    
                    <?php endif; ?>
                    
                </div>
            </div>
            <div id="show-legend" class="header">
                <a class="toggle" href="javascript:void(0);">Legend</a>
            </div>
        </div>
        <script type="text/javascript">
            var epsg4326 = new OpenLayers.Projection('EPSG:4326');
            
            ///OpenLayers.ProxyHost = "proxy.php?url=";
            
            // set map boundaries
            var mapextent = new OpenLayers.Bounds(
                10466478.562, -1432391.523,
                15827702.319, 824746.261
            );
            
            // create map on div
            var map = new OpenLayers.Map('map-id');
            
            // use openstreetmap as baselayer
            var osm_layer = new OpenLayers.Layer.OSM(
                'OpenStreetMap',
                '',
                {},
                { isBaseLayer: true }
            );
            
            var defaultStyle = { // marker custom styling
                //externalGraphic: 'img/markers/marker.png',
                pointRadius: 7,
                fillColor: '${fill_color}', // 
                strokeColor: '#000',
                strokeWidth: 1,
                fillOpacity: 0.8,
                strokeOpacity: 0.8,
                strokeLinecap: 'round',
                strokeDashstyle: 'solid',
                graphicOpacity: 1,
                labelAlign: 'cm'
            }
            
            var selectStyle = { // on selecting a feature
                //externalGraphic: '',
                //pointRadius: 5,
                //fillColor: '#EE9900',
                //strokeColor: '#EE9900',
                //strokeWidth: 1,
                fillOpacity: 1,
                //strokeOpacity: 1,
                //strokeLinecap: 'round',
                //strokeDashstyle: 'solid',
                //graphicOpacity: 1,
                //labelAlign: 'cm'
            }
            
            var stylemap = new OpenLayers.StyleMap({
                'default': new OpenLayers.Style(defaultStyle),
                'select': new OpenLayers.Style(selectStyle)
            });
            
            // apply different style for point
            var lookup = {
                'NA': { externalGraphic: 'img/markers/marker-black.png' }
            }
            
            //stylemap.addUniqueValueRules('default', 'cdd', lookup);
            
            var dialog;
            
            // climate station layer
            var mysql_layers = new OpenLayers.Layer.Vector(
                'Climate Station',
                {
                    projection: epsg4326,
                    strategies: [
                        new OpenLayers.Strategy.Fixed(),
                    ],
                    protocol: new OpenLayers.Protocol.HTTP({ // load climate station points from geojson
                        url: 'cnrd_mysql_layer.php?' + 'year=' + <?php echo $year; ?> + '&month=' + <?php echo $month; ?> + '&day=' + <?php echo $day; ?> + '&type=<?php echo $type; ?>' + '&category=<?php echo $category; ?>' + '&period=<?php echo $period; ?>',
                        format: new OpenLayers.Format.GeoJSON()
                    }),
                    styleMap: stylemap,
                    eventListeners: {
                        featureselected: function(evt) { // when climate station feature is clicked load popup
                            //console.log('featureselected');
                            var feature = evt.feature;
                            
                            // close climate station popover
                            /*
                            if (feature.popoverpopup != null) {
                                map.removePopup(feature.popoverpopup);
                                feature.popoverpopup.destroy();
                                feature.popoverpopup = null;
                            }
                            */
                        },
                        featureunselected: function(evt) { // when feature popup is closed
                            // destroy dialog
                            //console.log('featureunselected');
                            var feature = evt.feature;
                            
                        }
                    }
                }
            );

            var report = function(e) {
                OpenLayers.Console.log(e.type, e.feature.id)
            };

            var highlightCtrl = new OpenLayers.Control.SelectFeature(
                mysql_layers,
                {
                    hover: true,
                    highlightOnly: true,
                    renderIntent: 'temporary',
                    eventListeners: {
                        beforefeaturehighlighted: report,
                        featurehighlighted: report,
                        featureunhighlighted: report
                    }
                }
            );

            var selectCtrl = new OpenLayers.Control.SelectFeature(
                mysql_layers,
                {
                    clickout: true,
                    callbacks: {
                        over: function(feature) { // when hovering over marker
                            var date;
                            var start;
                            var end;
                            var period;
                            var period_value;
                            
                            var stationName = feature.data.station;
                            var stat_id = feature.data.stat_id;
                            var type = feature.data.type;
                            var category = feature.data.category;
                            var value = feature.data.value;
                            var classification = feature.data.classification;
                            
                            if (type === 'CDD' || type === 'CWD') {
                                date = feature.data.date;
                            }
                            
                            if (type === 'CH') {
                                start = feature.data.start;
                                end = feature.data.end;
                                period = feature.data.period;
                                period_value = feature.data.period_value;
                            }
                            
                            var longName = false;
                            var popoverBody = '';
                            
                            if (stationName.length > 14) {
                                stationName = stationName.substring(0,13) + '...';
                                longName = true;
                            }
                            
                            if ( classification.length > 14) {
                                longName = true;
                            }
                            
                            var popoverWidth = 180;
                            var popoverHeight = 160;
                            
                            if (longName) {
                                popoverWidth = 240;
                                popoverHeight = 160;
                            }
                            
                            var arrowLeft = Math.floor(popoverWidth / 2) - 14;
                            var arrowTop = (popoverHeight - 1);
                            
                            // popover title
                            var html = '<div>';
                            
                            if (longName) {
                                html += feature.data.station;
                            }
                            else {
                                html += stationName;
                            }
                            
                            html += '<img src="img/markers/arrow.png" style="position:absolute; left:' + arrowLeft + 'px; top:'+ arrowTop +'px;" alt="">';
                            html += '</div>';
                            
                            popoverBody += 'Station code: ' + stat_id + '<br>';
                            
                            if (type === 'CDD' || type === 'CWD') {
                                popoverBody += 'Date: ' + date + '<br>';
                                popoverBody += type + ' (' + category + '): ' + value + '<br>';
                                popoverBody += 'Classification: ' + classification + '<br>';
                            }
                            
                            if (type === 'CH') {
                                popoverBody += 'Start: ' + start + '<br>';
                                popoverBody += 'End: ' + end + '<br>';
                                //popoverBody += period + ': ' + period_value + '<br>';
                                
                                if (value != 'NA') {
                                    value = value + 'mm';
                                }
                                
                                popoverBody += type + 'tot: ' + value + '<br>';
                            }
                            
                            popoverpopup = new OpenLayers.Popup.Popover(
                                'popoverPopup',
                                feature.geometry.getBounds().getCenterLonLat(),
                                popoverBody, // popover body
                                html,
                                function(evt) {
                                    while (map.popups.length) {
                                        map.popups[0].destroy();
                                    }
                                }
                            );
                            
                            //console.log(popoverpopup);
                            popoverpopup['dimensions']['w'] = popoverWidth;
                            popoverpopup['dimensions']['h'] = popoverHeight;
                            
                            feature.popoverpopup = popoverpopup;
                            popoverpopup.panMapIfOutOfView = popoverHeight;
                            map.addPopup(popoverpopup);
                        },
                        out: function(feature) { // when mouse moves outside of marker
                            /* leave popover open
                            if (feature.popoverpopup != null) {
                                map.removePopup(feature.popoverpopup);
                                feature.popoverpopup.destroy();
                                feature.popoverpopup = null;
                            }
                            */
                        }
                    }
                }
            );

            //map.addControl(highlightCtrl);
            map.addControl(selectCtrl);
            
            //highlightCtrl.activate();
            selectCtrl.activate();
            
            map.addLayers([osm_layer, mysql_layers]);
            map.addControl(new OpenLayers.Control.LayerSwitcher());
            map.zoomToExtent(mapextent);
            
            $(document).ready(function(){
                $('#legend a.toggle').click(function() {
                    $('#legend').hide();
                    $('#show-legend').show();
                });
                
                $('#show-legend a.toggle').click(function() {
                    $('#show-legend').hide();
                    $('#legend').show();
                });
                
                var selectType = $('form input[name="type"]');
                var selectedType = $('form input[name="type"]:checked');
                $('.' + selectedType.val()).show();
                
                selectType.on('change', function() {
                    $('.suboption').hide();
                    $('.' + $(this).val()).show();
                });
            });
        </script>
    </body>
</html>