<?php
    /**
        NTCE (Normal Tabulation For Climate Element) Map
        2014
        
        Related source files:
            config.php
            ntce.php
            ntce_mongo_query.php
            ntce_popup.php
        
        Libraries used:
            jquery-ui
            openlayers
    */
?>
<html>
    <head>
        <title>OpenLayers Map</title>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-1.10.2.js"></script>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-ui-1.10.4.custom.js"></script>
        <script type="text/javascript" src="OpenLayers.js"></script>
        <script type="text/javascript" src="asset/popover/Popover.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.axislabels.js"></script>
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
                    <p>Normal Tabulation For Climate Element</p>
                </div>
            </div>
            <div id="show-legend" class="header">
                <a class="toggle" href="javascript:void(0);">Legend</a>
            </div>
        </div>
        <script type="text/javascript">
            xCallBack = function (ctx, x, y, radius, shadow) {
               ctx.arc(x, y, radius, 0,  Math.PI * 2, false);
               var text = 'X'
               var metrics = ctx.measureText(text);
               ctx.font="15px Arial";
               ctx.fillStyle = "green";
               ctx.fillText(text,x-metrics.width/2,y+4);
            }
            
            ///////////////////////////////////////////////////

            checkCallBack = function (ctx, x, y, radius, shadow) {
               ctx.arc(x, y, radius, 0,  Math.PI * 2, false);
               var text = '✓'
               var metrics = ctx.measureText(text);
               ctx.font="15px Arial";
               ctx.fillStyle = "green";
               ctx.fillText(text,x-metrics.width/2,y+4);
            }
            
            ///////////////////////////////////////////////////
            
            // flot: event handler for showing tooltip on flot chart hover
            $.fn.useTooltip = function () {
                var previousPoint = null;
                
                $(this).bind('plothover', function (event, pos, item) {
                    if (item) {
                        if (previousPoint != item.dataIndex) {
                            previousPoint = item.dataIndex;

                            $('#tooltip').remove();
                            
                            var y = item.datapoint[1]; // the value
                            
                            showTooltip(
                                item.pageX,
                                item.pageY,
                                item.series.label + ': ' + '<strong>' + y + '</strong>'
                            );
                        }
                    }
                    else {
                        $("#tooltip").remove();
                        previousPoint = null;
                    }
                });
            }; // end useTooltip()
            
            ///////////////////////////////////////////////////

            // flot: tooltip markup and style
            function showTooltip(x, y, contents) {
                $('<div id="tooltip">' + contents + '</div>').css({
                    position: 'absolute',
                    display: 'none',
                    top: y - 40,
                    left: x - 40,
                    border: '2px solid #808080',
                    padding: '3px',
                    'font-size': '9px',
                    'border-radius': '5px',
                    'background-color': '#fff',
                    'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
                    'z-index': 1000,
                    opacity: 0.9
                }).appendTo('body').fadeIn(200);
            }
            
            ///////////////////////////////////////////////////
            
            // default flot chart options
            var chartOptions = {
                legend: { show: false },
                points: { show: true, radius: 2, lineWidth: 4, fill: true },
                lines: { show: false },
                xaxis: {
                    show: true,
                    tickLength: 0,
                    tickFormatter: function(val, axis) { return ''; }
                },
                yaxis: {
                    show: true,
                    color: 'grey',
                    tickLength: 0,
                    tickDecimals: 2
                },
                grid: {
                    hoverable: true,
                    mouseActiveRadius: 5,
                    show: true, 
                    color: 'grey',
                    /*
                    markings: [ 
                      { yaxis: { from: 40, to: 40 }, color:"black" },
                      { yaxis: { from: 50, to: 50 }, color:"black" }, 
                      { yaxis: { from: 60, to: 60 }, color:"black" }
                    ]
                    */
                }
            };
            
            var mongo_query_script = './ntce_mongo_query.php?';
            var chart_query_script = './ntce_chart_query.php?';
            
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
                externalGraphic: 'img/markers/marker.png',
                pointRadius: 20,
                fillColor: '#EE9900',
                strokeColor: '#EE9900',
                strokeWidth: 1,
                fillOpacity: 1,
                strokeOpacity: 1,
                strokeLinecap: 'round',
                strokeDashstyle: 'solid',
                graphicOpacity: 1,
                labelAlign: 'cm'
            }
            
            var stylemap = new OpenLayers.StyleMap({
                'default': new OpenLayers.Style(defaultStyle)
            });
            
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
                        url: 'index_mysql_layer.php',
                        format: new OpenLayers.Format.GeoJSON()
                    }),
                    //styleMap: stylemap,
                    style: defaultStyle,
                    eventListeners: {
                        featureselected: function(evt) { // when climate station feature is clicked load popup
                            //console.log('featureselected');
                            var feature = evt.feature;
                            
                            $.ajax({
                                type: 'GET',
                                url: 'ntce_popup.php?id=' + feature.data.stat_id + '&name=' + feature.data.station + '&nid=' + feature.data.nid,
                                async: false,
                                success: function(data) {
                                    var activeTab;
                                    var stationName = feature.data.station;
                                    var stat_id = feature.data.stat_id;
                                    var dialogContent = $('<div id="dialogContent" title=""></div>');
                                    
                                    dialogContent.html(data);
                                    dialogContent.attr('title', stationName + ' (' + stat_id + ')');
                                    $('body').append(dialogContent);
                                    
                                    $('#tabs').tabs({
                                        create: function(event, ui) {
                                            activeTab = 1;
                                        }
                                    });
                                    
                                    $('#tabs').on('tabsactivate', function(event, ui) {
                                        activeTab = ui.newTab.index() + 1;
                                    });
                                    
                                    dialog = $('#dialogContent').dialog({
                                        width: 900,
                                        height: 500,
                                        open: function(event, ui) {
                                            function submitForm(year, month, day, range, period) {
                                                var validForm = true;
                                                
                                                var year = year || $('select[name="year"]').val();
                                                var month = month || $('select[name="month"]').val();
                                                var day = day || $('select[name="day"]').val();
                                                var range = range || $('input[name="range"]:checked').val();
                                                var period = period || $('input[name="period"]:checked').val();
                                                
                                                if (validForm) {
                                                    if (typeof range === 'undefined') {
                                                        $('input[name="range"]:first').prop('checked', true)
                                                        range = $('input[name="range"]:checked').val();
                                                    }
                                                    if (typeof period === 'undefined') {
                                                        $('input[name="period"]:first').prop('checked', true)
                                                        period = $('input[name="period"]:checked').val();
                                                    }
                                                    
                                                    // load climate data of station for current date in period
                                                    
                                                    $('#tabs-1').html('');
                                                    $('#tabs-2').html('');
                                                    
                                                    var param = 'CH';
                                                    
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: mongo_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&period=' + period + '&param=' + param + '&form=1',
                                                        async: false,
                                                        success: function(data) {
                                                            $('#tabs-1').append(data);
                                                        }
                                                    });
                                                    
                                                    // load past
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: mongo_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&range=' + range + '&period=' + period + '&param=' + param,
                                                        async: false,
                                                        success: function(data) {   
                                                            $('#tabs-1').append(data);
                                                        }
                                                    });
                                                    
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: chart_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&range=' + range + '&period=' + period + '&param=' + param,
                                                        async: false,
                                                        dataType: 'json',
                                                        success: function(data) {   
                                                            var chData = [];
                                                            var chMarkings = [];
                                                            
                                                            $.each(data, function(key, val) {
                                                                $.each(val, function(key2, val2) {
                                                                    
                                                                    if (key2 != 'chtot') {
                                                                        val2.color = 'black';
                                                                        var marking = {
                                                                            yaxis: {
                                                                                from: val2.data[0][1],
                                                                                to: val2.data[0][1]
                                                                            },
                                                                            color: 'black'
                                                                        };
                                                                        
                                                                        chMarkings.push(marking);
                                                                    }
                                                                    else {
                                                                        val2.color = 'blue';
                                                                        
                                                                        var marking = {
                                                                            yaxis: {
                                                                                from: val2.data[0][1],
                                                                                to: val2.data[0][1]
                                                                            },
                                                                            color: 'blue'
                                                                        };
                                                                        
                                                                        chMarkings.push(marking);
                                                                    }
                                                                    
                                                                    chData.push(val2);
                                                                });
                                                            });
                                                            
                                                            var chChartOptions = chartOptions;
                                                            chChartOptions['grid']['markings'] = chMarkings;
                                                            chChartOptions['xaxis']['axisLabel'] = 'ch';
                                                            var chChart = $('<div class="chart ch" style="width:200px; height:200px;"></div>');
                                                            
                                                            $('#tabs-1').append(chChart);
                                                            
                                                            if (activeTab == 2) {
                                                                chChart.appendTo('#tabs-2');
                                                            }
                                                            else {
                                                                chChart.appendTo('#tabs-1');
                                                            }
                                                            
                                                            $.plot($(chChart), chData, chChartOptions);
                                                            chChart.useTooltip();
                                                            
                                                            if (activeTab == 2) {
                                                                chChart.appendTo('#tabs-1');
                                                            }
                                                            
                                                            $('#tabs-1').append('<div class="clear"></div>');
                                                        }
                                                    });
                                                    
                                                    param = 'TEMP';
                                                    
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: mongo_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&period=' + period + '&param=' + param + '&form=1',
                                                        async: false,
                                                        success: function(data) {
                                                            $('#tabs-2').append(data);
                                                        }
                                                    });
                                                    
                                                    // load past
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: mongo_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&range=' + range + '&period=' + period + '&param=' + param,
                                                        async: false,
                                                        success: function(data) {        
                                                            $('#tabs-2').append(data);
                                                        }
                                                    });
                                                    
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: chart_query_script + 'id=' + stat_id + '&year=' + year + '&month=' + month + '&day=' + day + '&range=' + range + '&period=' + period + '&param=' + param,
                                                        async: false,
                                                        dataType: 'json',
                                                        success: function(data) {   
                                                            var tnData = [];
                                                            var tgData = [];
                                                            var txData = [];
                                                            
                                                            var tnMarkings = [];
                                                            var tgMarkings = [];
                                                            var txMarkings = [];
                                                            
                                                            $.each(data, function(key, val) {
                                                                if (key == 'tn') {
                                                                    $.each(val, function(key2, val2) {
                                                                        if (key2 != 'tnn') {
                                                                            val2.color = 'black';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'black'
                                                                            };
                                                                            
                                                                            tnMarkings.push(marking);
                                                                        }
                                                                        else {
                                                                            val2.color = 'turquoise';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'turquoise'
                                                                            };
                                                                            
                                                                            tnMarkings.push(marking);
                                                                        }
                                                                        
                                                                        tnData.push(val2);
                                                                    });
                                                                }
                                                                else if (key == 'tg') {
                                                                    $.each(val, function(key2, val2) {
                                                                        if (key2 != 'tgg') {
                                                                            val2.color = 'black';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'black'
                                                                            };
                                                                            
                                                                            tgMarkings.push(marking);
                                                                        }
                                                                        else {
                                                                            val2.color = 'green';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'green'
                                                                            };
                                                                            
                                                                            tgMarkings.push(marking);
                                                                        }
                                                                        
                                                                        tgData.push(val2);
                                                                    });
                                                                }
                                                                else if (key == 'tx') {
                                                                    $.each(val, function(key2, val2) {
                                                                        if (key2 != 'txx') {
                                                                            val2.color = 'black';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'black'
                                                                            };
                                                                            
                                                                            txMarkings.push(marking);
                                                                        }
                                                                        else {
                                                                            val2.color = 'red';
                                                                            
                                                                            var marking = {
                                                                                yaxis: {
                                                                                    from: val2.data[0][1],
                                                                                    to: val2.data[0][1]
                                                                                },
                                                                                color: 'red'
                                                                            };
                                                                            
                                                                            txMarkings.push(marking);
                                                                        }
                                                                        
                                                                        txData.push(val2);
                                                                    });
                                                                }
                                                            });
                                                            
                                                            /*
                                                            tnn = [[0,22]];
                                                            var data = [
                                                                { data: tnn, color: 'green', points: {symbol: xCallBack } },
                                                                { data: d2, color: 'green', points: {symbol: checkCallBack }}
                                                            ];
                                                            */
                                                            
                                                            var tnChartOptions = chartOptions;
                                                            tnChartOptions['grid']['markings'] = tnMarkings;
                                                            tnChartOptions['xaxis']['axisLabel'] = 'tn';
                                                            var tnChart = $('<div class="chart tn" style="width:200px; height:200px;"></div>');
                                                            
                                                            /*
                                                                HACK: flot chart rendering in inactive tab
                                                                tick display overlaps with grid
                                                                
                                                                render chart in active tab first
                                                                then move to correct tab
                                                            */
                                                            if (activeTab == 1) {
                                                                tnChart.appendTo('#tabs-1');
                                                            }
                                                            else {
                                                                tnChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            $.plot($(tnChart), tnData, tnChartOptions);
                                                            tnChart.useTooltip();
                                                            
                                                            if (activeTab == 1) {
                                                                tnChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            var tgChartOptions = chartOptions;
                                                            tgChartOptions['grid']['markings'] = tgMarkings;
                                                            tgChartOptions['xaxis']['axisLabel'] = 'tg';
                                                            var tgChart = $('<div class="chart tg" style="width:200px; height:200px;"></div>');
                                                            
                                                            if (activeTab == 1) {
                                                                tgChart.appendTo('#tabs-1');
                                                            }
                                                            else {
                                                                tgChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            $.plot($(tgChart), tgData, tgChartOptions);
                                                            tgChart.useTooltip();
                                                            
                                                            if (activeTab == 1) {
                                                                tgChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            var txChartOptions = chartOptions;
                                                            txChartOptions['grid']['markings'] = txMarkings;
                                                            txChartOptions['xaxis']['axisLabel'] = 'tx';
                                                            var txChart = $('<div class="chart tx" style="width:200px; height:200px;"></div>');
                                                            
                                                            if (activeTab == 1) {
                                                                txChart.appendTo('#tabs-1');
                                                            }
                                                            else {
                                                                txChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            $.plot($(txChart), txData, txChartOptions);
                                                            txChart.useTooltip();
                                                            
                                                            if (activeTab == 1) {
                                                                txChart.appendTo('#tabs-2');
                                                            }
                                                            
                                                            $('#tabs-2').append('<div class="clear"></div>');
                                                        }
                                                    });
                                                }
                                                
                                                return false;
                                            }
                                            
                                            $('form').submit(function(e) {
                                                return submitForm();
                                            });
                                            
                                            submitForm(
                                                $('select[name="year"]').val(),
                                                $('select[name="month"]').val(),
                                                $('select[name="day"]').val(),
                                                $('input[name="range"]:checked').val(),
                                                $('input[name="period"]:checked').val()
                                            ); // submit for current date minus 1
                                        },
                                        close: function(event, ui) {
                                            selectCtrl.unselectAll();
                                        }
                                    });
                                }
                            });
                            
                            // close climate station popover
                            if (feature.popoverpopup != null) {
                                map.removePopup(feature.popoverpopup);
                                feature.popoverpopup.destroy();
                                feature.popoverpopup = null;
                            }
                        },
                        featureunselected: function(evt) { // when feature popup is closed
                            // destroy dialog
                            //console.log('featureunselected');
                            var feature = evt.feature;
                            
                            dialog.dialog('destroy').remove();
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
                            var stationName = feature.data.station;
                            var stat_id = feature.data.stat_id;
                            
                            var longName = false;
                            var popoverBody = '';
                            
                            if (stationName.length > 14) {
                                stationName = stationName.substring(0,13) + '...';
                                longName = true;
                            }
                            
                            var popoverWidth = 180;
                            var popoverHeight = 140;
                            
                            if (longName) {
                                popoverHeight = 160;
                                popoverWidth = 240;
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
            });
        </script>
    </body>
</html>