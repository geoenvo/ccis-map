    <style>
        table { font-size: 14px; }
        .dataTables_filter { display: none; }
        .dataTables_paginate.paging_full_numbers {
            width: 100%;
            text-align: right;
            float: none;
        }
        .demoHeaders { margin: 0 0 10px 0; }
        .ui-widget { font-family: 'Pathway Gothic One','Open Sans Condensed','Arial Narrow',sans-serif; }
        .ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button { font-size: 12px; }
        .dataTables_length, table.display thead th, .ui-datepicker th {
            text-shadow: none;
            font-weight: normal;
        }
        .dataTables_length { font-size: 14px; }
        .dataTables_wrapper { box-shadow: none; padding: 5px; }
        input.date_range_filter { width: 85px; }
        .paging_full_numbers a.paginate_button, .paging_full_numbers a.paginate_active { box-shadow: none; }

        .display-select input { vertical-align: inherit; }
        .chart { display: none; }
        .choices-container { float: right; }
        .choices { margin: 0; padding: 0; }
        .choices li { list-style: none; float: left; margin-right: 5px; }
        .choices li input { vertical-align: inherit; }
    </style>
    <!--
    <h2 class="demoHeaders"><?php echo $_GET['name'] . ' (' . $_GET['id'] . ')'; ?></h2>
    -->
    <div id="tabs">
        <ul>
            <li><a href="#tabs-1">10 Days</a></li>
            <li><a href="#tabs-2">Monthly</a></li>
            <li><a href="#tabs-3">Yearly</a></li>
            <!--
            <li><a href="#tabs-4">FKLIM</a></li>
            -->
        </ul>
        
        <div id="tabs-1" class="tab-container" data-period="10_days">
            <div class="display-select">
                <input type="radio" name="display_select_1" value="tabular" checked="checked">Tabular <input type="radio" name="display_select_1" value="chart">Chart
            </div>
            <div class="tabular">
                <table cellpadding="0" cellspacing="0" border="1" class="display">
                    <thead>
                        <tr></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="chart">
                <div class="chart-container" style="height: 500px; min-width: 500px"></div>
                <div class="choices-container">
                    <ul class="choices"></ul>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        
        <div id="tabs-2" class="tab-container" data-period="monthly">
            <div class="display-select">
                <input type="radio" name="display_select_2" value="tabular" checked="checked">Tabular <input type="radio" name="display_select_2" value="chart">Chart
            </div>
            <div class="tabular">
                <table class="display" cellpadding="0" cellspacing="0" border="1">
                    <thead>
                        <tr></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="chart">
                <div class="chart-container" style="height: 500px; min-width: 500px"></div>
                <div class="choices-container">
                    <ul class="choices"></ul>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        
        <div id="tabs-3" class="tab-container" data-period="yearly">
            <div class="display-select">
                <input type="radio" name="display_select_3" value="tabular" checked="checked">Tabular <input type="radio" name="display_select_3" value="chart">Chart
            </div>
            <div class="tabular">
                <table class="display" cellpadding="0" cellspacing="0" border="1">
                    <thead>
                        <tr></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="chart">
                <div class="chart-container" style="height: 500px; min-width: 500px"></div>
                <div class="choices-container">
                    <ul class="choices"></ul>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        
        <!--
        <div id="tabs-4" class="tab-container" data-period="monthly_fklim">
            <div class="display-select">
                <input type="radio" name="display_select_4" value="tabular" checked="checked">Tabular <input type="radio" name="display_select_4" value="chart">Chart
            </div>
            <div class="tabular">
                <table class="display" cellpadding="0" cellspacing="0" border="1">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Date</th>
                            <th>tave</th>
                            <th>tmax</th>
                            <th>tmin</th>
                            <th>rhave</th>
                            <th>slpave</th>
                            <th>pave</th>
                            <th>wsave</th>
                            <th>wdave</th>
                            <th>rain</th>
                            <th>sund</th>
                            <th>srep</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="chart">
                <div class="chart-container" style="height: 500px; min-width: 500px"></div>
                <div class="choices-container">
                    <ul class="choices"></ul>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        -->
    </div>