<?php
    /**
        Climate Indices Map
        2014
        
        Related source files:
            config.php
            index.php
            index_mysql_layer.php
            index_chart_query.php
            index_mongo_query.php
            index_popup.php
        
        Libraries used:
            jquery-ui
            openlayers
            datatables
            flot charts
    */
?>
<html>
    <head>
        <title>OpenLayers Map</title>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-1.10.2.js"></script>
        <script type="text/javascript" src="asset/jquery-ui/js/jquery-ui-1.10.4.custom.js"></script>
        <script type="text/javascript" src="OpenLayers.js"></script>
        <script type="text/javascript" src="asset/popover/Popover.js"></script>
        <script type="text/javascript" src="asset/datatable/js/jquery.dataTables.js"></script>
        <script type="text/javascript" src="asset/datatable/js/jquery.dataTables.columnFilter.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.time.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.selection.js"></script>
        <script type="text/javascript" src="asset/flot/jquery.flot.navigate.js"></script>
        <link rel="stylesheet" type="text/css" href="asset/datatable/css/demo_table.css">
        <link rel="stylesheet" type="text/css" href="asset/datatable/css/customStyle.css">
        <link rel="stylesheet" type="text/css" href="asset/jquery-ui/css/ccis/jquery-ui-1.10.4.custom.css">
        <link rel="stylesheet" type="text/css" href="asset/popover/openlayerspopovers.css">
        <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Pathway+Gothic+One|Open+Sans+Condensed:300">
        <link rel="stylesheet" type="text/css" href="asset/style.css">
    </head>
    <body>
        <div id="map-id"></div>
        <script type="text/javascript">
            
            // flot: add arrow buttons for navigation
            $.fn.addArrow = function(chart, dir, right, top, offset) {
                $('<img class="button" src="img/flot/arrow-' + dir + '.gif" style="position:absolute; right:' + right + 'px;top:' + top + 'px">').appendTo($(this)).click(function (e) {
                    e.preventDefault();
                    chart.pan(offset);
                });
            } // end addArrow()
            
            ///////////////////////////////////////////////////
            
            // flot: event handler for showing tooltip on flot chart hover
            $.fn.useTooltip = function () {
                var previousPoint = null;
                
                $(this).bind('plothover', function (event, pos, item) {         
                    //console.log('hovering');
                    if (item) {
                        if (previousPoint != item.dataIndex) {
                            previousPoint = item.dataIndex;

                            $('#tooltip').remove();
                            
                            var x = item.datapoint[0]; // the timestamp
                            var y = item.datapoint[1]; // the value
                            
                            var date = new Date(x);            
                            //console.log(date);
                            
                            var dd = date.getDate();
                            var mm = date.getMonth() + 1;
                            var yyyy = date.getFullYear();
                            
                            if (dd < 10) { dd = '0' + dd }
                            if (mm < 10) { mm = '0' + mm }
                            
                            var full_date = yyyy + '-' + mm + '-' + dd;
                            
                            showTooltip(
                                item.pageX,
                                item.pageY,
                                full_date  + '<br>' + item.series.label + ': ' + '<strong>' + y + '</strong>'
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
                    border: '2px solid #545454',
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
            
            // datatables: dynamically create the table heading for the datatable
            function createJSONTableHeading(dataPeriod, statID) {
                var table_heading = '';
                var jsonColumns;
                
                $.ajax({
                    type: 'GET',
                    url: mongo_query_script + 'id=' + statID + '&type=' + dataPeriod + '&columns=1',
                    async: false,
                    dataType: 'json',
                    success: function(data) {
                        jsonColumns = data;
                        //console.log(jsonColumns);
                    }
                });
                    
                for (var i = 0; i < jsonColumns.length; i++) {
                    var opentag = '<th>';
                    
                    if (jsonColumns[i] === 'No.') {
                        opentag = '<th width="5%">';
                    }
                    else if (jsonColumns[i] === 'date') {
                        opentag = '<th width="40%">';
                    }
                    
                    table_heading += opentag + jsonColumns[i] + '</th>';
                }
                
                return table_heading;
            }
            
            ///////////////////////////////////////////////////
            
            ///OpenLayers.ProxyHost = "proxy.php?url=";
            
            var charts = []; // storing initialized charts
            var mongo_query_script = './index_mongo_query.php?';
            var chart_query_script = './index_chart_query.php?';
            
            var epsg4326 = new OpenLayers.Projection('EPSG:4326');
            
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
                    style: { // marker custom styling
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
                    },
                    eventListeners: {
                        featureselected: function(evt) { // when climate station feature is clicked load popup
                            //console.log('featureselected');
                            var feature = evt.feature;
                            
                            $.ajax({
                                type: 'GET',
                                url: 'index_popup.php?id=' + feature.data.stat_id + '&name=' + feature.data.station,
                                async: false,
                                success: function(data) {
                                    var dialogContent = $('<div id="dialogContent" title=""></div>');
                                    dialogContent.html(data);
                                    dialogContent.attr('title', feature.data.station + ' (' + feature.data.stat_id + ')');
                                    $('body').append(dialogContent);
                                    dialog = $('#dialogContent').dialog({
                                        width: 1000,
                                        height: 700,
                                        open: function(event, ui)
                                        {
                                            //console.log('dialog open');
                                            
                                            $('#tabs').tabs({
                                                create: function(event, ui) {
                                                    // after tab is created initialize datatable for first tab                                    
                                                    var tableSelector = '[data-period="10_days"] table';
                                                    var tableHeading = createJSONTableHeading('10_days', feature.data.stat_id);
                                                    $(tableSelector + ' thead tr').append(tableHeading);
                                                    
                                                    if (tableHeading.length > 0) {
                                                        $('[data-period="10_days"]').addClass('hasdata');
                                                        
                                                        $(tableSelector).dataTable({
                                                            //aoColumnDefs: dtColumns, // actually don't need this since we already created the table heading dynamically
                                                            sPaginationType: 'full_numbers',
                                                            bServerSide: true,
                                                            sAjaxSource: mongo_query_script + 'id=' + feature.data.stat_id + '&type=10_days',
                                                            bDeferRender: true
                                                        }).columnFilter({
                                                            sPlaceHolder: 'head:before',
                                                            aoColumns: [null, { type: 'date-range' }]
                                                        });
                                                    }
                                                    else {
                                                        $('[data-period="10_days"]').addClass('nodata'); // add to dom for checking if tab has data
                                                        $('[data-period="10_days"] .display-select').after('<p style="text-align: center;">No data found.</p>');
                                                    }
                                                }
                                            });
                                            
                                            $('#tabs').on('tabsactivate', function(event, ui) {
                                                var tabIndex = ui.newTab.index() + 1; // panel id starts from 1
                                                var dataPeriod = $('#tabs-' + tabIndex).data('period'); // get period from data attribute
                                                var dataPeriodSelector = '[data-period="' + dataPeriod + '"]';
                                                
                                                if (dataPeriod != null && $(dataPeriodSelector).hasClass('hasdata') == false) {
                                                    // if nodata class is applied means there is no data for the period in the tab
                                                    if ($(dataPeriodSelector).hasClass('nodata') == false) { // data period tab
                                                        var dtTableSelector = dataPeriodSelector + ' .dataTables_wrapper';
                                                        var tableSelector = dataPeriodSelector + ' table';
                                                        var tableHeading = createJSONTableHeading(dataPeriod, feature.data.stat_id);
                                                        
                                                        if ($(dtTableSelector).length == 0 && tableHeading.length > 0) { // if datatable is not initialized
                                                            $(dataPeriodSelector).addClass('hasdata');
                                                            
                                                            $(tableSelector + ' thead tr').append(tableHeading);
                                                            
                                                            $(tableSelector).dataTable({
                                                                //aoColumnDefs: dtColumns,
                                                                sPaginationType: 'full_numbers',
                                                                bServerSide: true,
                                                                sAjaxSource: mongo_query_script + 'id=' + feature.data.stat_id + '&type=' + dataPeriod,
                                                                bDeferRender: true
                                                            }).columnFilter({
                                                                sPlaceHolder: 'head:before',
                                                                aoColumns: [null, { type: 'date-range' }]
                                                            });
                                                        }
                                                        else {
                                                            $(dataPeriodSelector).addClass('nodata'); // for checking when toggling tabs/displays
                                                            $(dataPeriodSelector + ' .display-select').append('<p style="text-align: center;">No data found.</p>');
                                                        }
                                                    }
                                                }
                                            });// end tabsactivate
                                            
                                            // the data display selector (tabular or chart?)
                                            $('.display-select input[type="radio"]').on('change', function() {
                                                var dataPeriod = $(this).closest('.tab-container').data('period');
                                                var dataPeriodSelector = '[data-period="' + dataPeriod + '"]';
                                                var displaySelected = $(this).val();

                                                if (displaySelected === 'tabular') {
                                                    $(dataPeriodSelector + ' .tabular').toggle(true);
                                                    $(dataPeriodSelector + ' .chart').toggle(false);
                                                }
                                                else if (displaySelected === 'chart') {
                                                    $(dataPeriodSelector + ' .tabular').toggle(false);
                                                    $(dataPeriodSelector + ' .chart').toggle(true);
                                                    
                                                    if ($(dataPeriodSelector).hasClass('nodata') == false) { // the period has data
                                                        // initialize chart here
                                                        var flotSelector = '[data-period="' + dataPeriod + '"] .flot-base';
                                                        var chartContainer = '[data-period="' + dataPeriod + '"] .chart-container';
                                                        var choiceContainer = $('[data-period="' + dataPeriod + '"] .choices');
                                                        var datasets = null;
                                                        
                                                        ///////////////////////////////////////////////////
                                                        
                                                        // plot according to selected series
                                                        function plotToChoices() {
                                                            var data = [];
                                                            
                                                            // push dataset for every checked series
                                                            choiceContainer.find('input:checked').each(function () {
                                                                var key = $(this).attr('name');
                                                                
                                                                if (key && datasets[key]) {
                                                                    data.push(datasets[key]);
                                                                }
                                                            });

                                                            if (data.length > 0) {
                                                                // set tick interval based on the data period
                                                                var tickPeriod;
                                                                var tickInterval = 1;
                                                                
                                                                var options = {
                                                                    yaxis: {
                                                                        min: 0
                                                                    },
                                                                    xaxis: {
                                                                        mode: 'time'
                                                                    },
                                                                    grid: {
                                                                        hoverable: true,
                                                                        mouseActiveRadius: 30,
                                                                    },
                                                                    pan: {
                                                                        interactive: false
                                                                    }
                                                                }
                                                                
                                                                if (dataPeriod == 'yearly') {
                                                                    tickPeriod = 'year';
                                                                    options['tickSize'] = [tickInterval, tickPeriod];
                                                                }
                                                                else if (dataPeriod == 'monthly') {
                                                                    //tickPeriod = 'month';
                                                                    //options['tickSize'] = [tickInterval, tickPeriod];
                                                                    options['xaxis']['tickLength'] = 1;
                                                                    options['selection'] = { mode: 'x' };
                                                                }
                                                                else if (dataPeriod == '10_days') {   
                                                                    options['xaxis']['tickLength'] = 10;
                                                                    options['selection'] = { mode: 'x' };
                                                                }
                                                                
                                                                var chart = $.plot(chartContainer, data, options);
                                                                
                                                                $(chartContainer).useTooltip();
                                                                
                                                                $(chartContainer).addArrow(chart, 'left', 135, 35, { left: -100 });
                                                                $(chartContainer).addArrow(chart, 'right', 105, 35, { left: 100 });
                                                                $(chartContainer).addArrow(chart, 'up', 120, 20, { top: -100 });
                                                                $(chartContainer).addArrow(chart, 'down', 120, 50, { top: 100 });
                                                                
                                                                charts.push(chart);
                                                                //console.log(chart);
                                                                //console.log(options);
                                                                //console.log(datasets);
                                                                
                                                                if (dataPeriod == '10_days' || dataPeriod == 'monthly') {
                                                                    $(chartContainer).on('plotselected', function (event, ranges) {
                                                                        // do the zooming
                                                                        //console.log('zoom');
                                                                        //console.log(options);
                                                                        var chartZoomed = $.plot($(chartContainer), data, $.extend(true, {}, options, { xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to } }));
                                                                        
                                                                        $(chartContainer).addArrow(chartZoomed, 'left', 135, 35, { left: -100 });
                                                                        $(chartContainer).addArrow(chartZoomed, 'right', 105, 35, { left: 100 });
                                                                        $(chartContainer).addArrow(chartZoomed, 'up', 120, 20, { top: -100 });
                                                                        $(chartContainer).addArrow(chartZoomed, 'down', 120, 50, { top: 100 });
                                                                    });
                                                                }
                                                            }
                                                        } // end plotToChoices()
                                                        
                                                        ///////////////////////////////////////////////////
                                                        
                                                        if ($(flotSelector).length == 0) { // if chart is not initialized
                                                            // flot chart implementation
                                                            
                                                            // load indices data for period
                                                            $.ajax({
                                                                type: 'GET',
                                                                url: chart_query_script + 'id=' + feature.data.stat_id + '&type=' + dataPeriod,
                                                                async: false,
                                                                dataType: 'json',
                                                                success: function(data) {
                                                                    datasets = data;
                                                                    //console.log(datasets);
                                                                }
                                                            });
                                                            
                                                            if (datasets != null) {
                                                                // hard-code color indices to prevent them from shifting as choices are turned on/off
                                                                var i = 0;
                                                                
                                                                $.each(datasets, function(key, val) {
                                                                    val.color = i;
                                                                    ++i;
                                                                });

                                                                // insert checkboxes
                                                                // create checkbox for each series
                                                                $.each(datasets, function(key, val) {
                                                                    choiceContainer.append(
                                                                        '<li><input type="checkbox" name="'+ key
                                                                        + '" checked="checked" id="id' + key + dataPeriod + '"></input>'
                                                                        + '<label for="id' + key + dataPeriod + '">&nbsp;&nbsp;'
                                                                        + val.label + '</label></li>'
                                                                    );
                                                                });
                                                                
                                                                plotToChoices();
                                                                choiceContainer.find('input').click(plotToChoices);
                                                            }
                                                        }
                                                    }
                                                }
                                            });
                                            
                                            $.datepicker.setDefaults({
                                                dateFormat: 'yy-mm-dd',
                                                showButtonPanel: true,
                                                changeMonth: true,
                                                changeYear: true
                                            });
                                            
                                            $('.hasDatepicker').keydown(function() {
                                                $('#ui-datepicker-div').hide();
                                            });
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
                            
                            // destroy datatable instances
                            var tables = $.fn.dataTable.fnTables();
                            $(tables).each(function () {
                                $(this).dataTable().fnDestroy();
                                //console.log('destroyed datatable');
                            });
                            
                            // destroy chart instances
                            for (var i = charts.length; i > 0; i--) {
                                charts[i - 1].shutdown();
                                //console.log('destroyed chart');
                                charts.pop();
                            }
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
                            
                            popoverpopup['dimensions']['w'] = popoverWidth;
                            popoverpopup['dimensions']['h'] = popoverHeight;
                            
                            feature.popoverpopup = popoverpopup;
                            popoverpopup.panMapIfOutOfView = true;
                            map.addPopup(popoverpopup);
                        },
                        out: function(feature) { // when mouse moves outside of marker
                            if (feature.popoverpopup != null) {
                                map.removePopup(feature.popoverpopup);
                                feature.popoverpopup.destroy();
                                feature.popoverpopup = null;
                            }
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
        
        </script>
    </body>
</html>