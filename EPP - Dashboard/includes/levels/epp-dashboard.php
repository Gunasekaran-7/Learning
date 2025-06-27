<?php

    $user_id = get_current_user_id();

	$user_type = get_user_meta($user_id,'user_type',true);

	if (!is_user_logged_in()) {
		
		wp_redirect(site_url('/account/'));
		exit;  
	}
 
    if( $user_type != 'EPP' ){
        wp_redirect(site_url('/account/'));
		exit; 
    }

    global$wpdb;



    $get_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, redeemed_to, redeemed_date, order_id, customer_order_id, package_code, status, assinged_date, created_at
            FROM {$wpdb->prefix}voucher_list
            WHERE user_id = %d",
            $user_id
        ),
        ARRAY_A
    );

 

    $redeem_user_count = $voucherData = $voucher_count = $unassigned = $assigned = $redeemed = $package_code = $customer_order_id = [];


    foreach($get_data as $data) {
        if (!empty($data['package_code'])) {
            $package_code[] = $data['package_code'];
        }

        if (!empty($data['customer_order_id'])) {
            $customer_order_id[] = $data['customer_order_id'];
        }

        $voucher_count[] = $data['id'];

        if($data['status'] == 'pending'){
            $unassigned[] = $data['id'];
        }

        if($data['status'] == 'Assigned'){
            $assigned[] = $data['id'];
        }

        if($data['status'] == 'Redeemed'){
            $redeemed[] = $data['id'];
        }

        
        $assignedMonth = (strtotime($data['assinged_date']) !== false && $data['assinged_date'] != '0000-00-00') ? date('Y-m', strtotime($data['assinged_date'])) : null;   
        $redeemedMonth = (strtotime($data['redeemed_date']) !== false && $data['redeemed_date'] != '0000-00-00') ? date('Y-m', strtotime($data['redeemed_date'])) : null;
        $unassignedMonth = (strtotime($data['created_at']) !== false && $data['created_at'] != '0000-00-00') ? date('Y-m', strtotime($data['created_at'])) : null;

        if ($data['status'] == 'pending' && $unassignedMonth !== null) {
            if (!isset($voucherData[$unassignedMonth])) {
                $voucherData[$unassignedMonth] = ['assigned' => 0, 'unassigned' => 0, 'redeemed' => 0];
            }
            $voucherData[$unassignedMonth]['unassigned']++;
        }

        if ($data['status'] == 'Assigned' && !empty($assignedMonth)) {
            if ($assignedMonth !== null) {
                if (!isset($voucherData[$assignedMonth])) {
                    $voucherData[$assignedMonth] = ['assigned' => 0, 'unassigned' => 0, 'redeemed' => 0];
                }
                $voucherData[$assignedMonth]['assigned']++;
            }
        }

        if ($data['status'] == 'Redeemed' && !empty($redeemedMonth)) {
            if ($redeemedMonth !== null) {
                if (!isset($voucherData[$redeemedMonth])) {
                    $voucherData[$redeemedMonth] = ['assigned' => 0, 'unassigned' => 0, 'redeemed' => 0];
                }
                $voucherData[$redeemedMonth]['redeemed']++;
            }
        }

        if (!empty($data['redeemed_to']) && $data['redeemed_to'] != 'empty') {
            $redeem_user_count[] = $data['redeemed_to'];
        }
    }

    $redeem_user_count[] = $user_id;

    $years_mon = [];
 

    if(!empty($voucherData)){
   
        foreach (array_keys($voucherData) as $key) {
            if (!empty($key)) {
                $year = substr($key, 0, 4); 
                if (!in_array($year, $years_mon)) {
                    $years_mon[] = $year; 
                }
            }
        }
    }


    $product_package_code = 'api_ordercode';

    if (!empty($customer_order_id)) {
    
        $placeholders = implode(',', array_fill(0, count($customer_order_id), '%s'));
        $query = $wpdb->prepare(
            "SELECT pm.post_id 
            FROM {$wpdb->postmeta} pm 
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
            WHERE pm.meta_key = %s 
            AND pm.meta_value IN ($placeholders)",
            array_merge([$product_package_code], $customer_order_id)
        );

        $results_product = $wpdb->get_results($query);


        $order_id = array_column($results_product, 'post_id');
    
    } 

    $product_package_code = 'pearson_product_id';

	if (!empty($package_code)) {
		$placeholders = implode(',', array_fill(0, count($package_code), '%s'));
		$querys = $wpdb->prepare(
			"SELECT pm.post_id 
			FROM {$wpdb->postmeta} pm 
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
			WHERE pm.meta_key = %s 
			AND pm.meta_value IN ($placeholders) 
			AND p.post_status = 'publish'",
			array_merge([$product_package_code], $package_code)
		);

		$results_products = $wpdb->get_results($querys);

        $product_id = array_column($results_products, 'post_id');
	} 

 
   

 

    $find_orders_query = $wpdb->prepare(
        "SELECT 
            GROUP_CONCAT(DISTINCT pm.post_id) AS order_ids,
            GROUP_CONCAT(DISTINCT oim.meta_value) AS product_ids
        FROM wp_postmeta AS pm
        INNER JOIN wp_posts AS p 
            ON p.ID = pm.post_id 
            AND p.post_status NOT IN ('wc-failed', 'wc-pending')   
        LEFT JOIN wp_woocommerce_order_items o
            ON o.order_id = p.ID 
            AND o.order_item_type = 'line_item'   
        LEFT JOIN wp_woocommerce_order_itemmeta oim
            ON oim.order_item_id = o.order_item_id
            AND oim.meta_key = '_product_id'   
        WHERE pm.meta_key = '_customer_user'
        AND pm.meta_value = %d
        AND pm.meta_value IS NOT NULL",
        $user_id   
    );

    
     
        $order_result = $wpdb->get_results($find_orders_query, ARRAY_A);


    if(!empty($order_result)){


        $all_orders = array_column($order_result, 'order_ids'); 
      
 
        if ($all_orders[0] !== NULL) {
                
            $order_merge = array_merge($order_id, explode(',', $all_orders[0]));
        } else {
        
            $order_merge = $order_id;
        }
 
       // $order_merge = array_merge($order_id, explode(',', $all_orders[0]));

        $merged_array_unique = array_unique($order_merge);

        $pro_order_list = array_values($merged_array_unique);

        $all_product = array_column($order_result, 'product_ids'); 
    
        $product_merge = array_merge($product_id, explode(',', $all_product[0]));
        $product_array_unique = array_unique($product_merge);
        $product_order = array_values($product_array_unique);
             
        
    }

  

    
    
    
    
    if (!empty($pro_order_list)) {
    
        $placeholders = implode(',', array_fill(0, count($pro_order_list), '%d'));
        $find_product_details_query = "
            SELECT 
                YEAR(p.post_date) AS x, COUNT(*) AS y
            FROM wp_posts p
            WHERE p.ID IN ($placeholders)
            GROUP BY YEAR(p.post_date)
        ";

    
        $course_create_data = $wpdb->get_results(
            $wpdb->prepare($find_product_details_query, ...$pro_order_list),
            ARRAY_A
        );
        $course_count = count($pro_order_list);
    } 

 
    if (!empty($redeem_user_count)) {
            
        $dataPoints = [];
        $years = [];
        $practice_average_val = $diagnostic_average_val = $module_average_val = $check_average_val = array();
        $newcanvas_array = array();

        //get all users year registered and canvas user id
        $user_query = "SELECT 
        Year(u.user_registered) AS yr,
        um.meta_value AS canvas_ids
        FROM wp_users u
        LEFT JOIN wp_usermeta um on um.user_id = u.ID and um.meta_key = '_canvas_user_id'
        WHERE u.ID IN (".implode(',',array_filter(array_unique($redeem_user_count))).")";
        $usernames = $wpdb->get_results($user_query, ARRAY_A);

        
        if(!empty($usernames)){ $years = array_column($usernames, 'yr'); }

        if($product_order != NULL ){ 

            $find_product_courses_query = "SELECT 
            DISTINCT pm.meta_value as course_ids
            FROM wp_postmeta pm
            WHERE pm.post_id IN (".implode(',',array_filter(array_unique($product_order))).")
            AND pm.meta_key = 'canvas_course_id'";
            $all_canvas_courses = $wpdb->get_results($find_product_courses_query, ARRAY_A);
            

            //get canvas course ids
            $all_canvas_course_ids = array_column($all_canvas_courses, 'course_ids');
            $all_canvas_course_ids_implode = implode(',',$all_canvas_course_ids);
            $all_canvas_course_ids_explode = explode(',',$all_canvas_course_ids_implode);
            $newcanvas_array = array_filter(array_unique($all_canvas_course_ids_explode));

        }

        //get canvas user ids
        $all_canvas_user_ids = array_column($usernames, 'canvas_ids');
        $all_canvas_user_ids_implode = implode(',',$all_canvas_user_ids);
        $all_canvas_user_ids_explode = explode(',',$all_canvas_user_ids_implode);
        $c_user_ids = array_filter(array_unique($all_canvas_user_ids_explode));

        

        //get percentage/average values
        if($newcanvas_array){
        $find_all_Val = $this->find_percentage_from_canvas_data($c_user_ids, $newcanvas_array);
        }
 

        if(!empty($find_all_Val)){
    
            $find_each = array_column($find_all_Val, 'percent_score','quiz_type');
            if (isset($find_each['Diagnostic'])) {
                $diagnostic_average_value = $find_each['Diagnostic'] . '%';
            } else {
                $diagnostic_average_value = 'N/A';
            }
            if (isset($find_each['Practice'])) {
                $practice_average_value = $find_each['Practice'] . '%';
            } else {
                $practice_average_value = 'N/A';
            }
            if (isset($find_each['Module'])) {
                $module_average_value = $find_each['Module'] . '%';
            } else {
                $module_average_value = 'N/A';
            }

        }


    
    
        $year_counts = array_count_values($years);
        $user_counts = count($years);
        $start_year = min($years);
        $end_year = max($years);
        
        //data point for chart
        $dataPoints = [];
   

        for ($year = $start_year; $year <= $end_year; $year++) {
            $dataPoints[] = ["x" => esc_html($year), "y" => 0];
        }

        foreach ($year_counts as $year => $count) {
            foreach ($dataPoints as &$dataPoint) {
                if ($dataPoint['x'] == $year) {
                    $dataPoint['y'] = $count;
                    break;
                }
            }
        }

        usort($dataPoints, function ($a, $b) {
            return $a['x'] <=> $b['x'];
        });
    }

          ?>
           
        <div class="mountian-style">    
            <div class="custom_dashboard_container">
                <div class="cus_back_design">
                    <div class="custom_dashboard_container_sub">
                        <div class="das_header">
                            <p class="das_head">Candidate </p>
                            <p class="over_all_details"><?php echo count(array_unique($redeem_user_count))?></p>
                        </div>
                        <div class="custom_dashboard_container_sub_child">
                            <div class="poster canidate"></div>
                            <a href="/candidates/" class="cus_dash_link"><i class='bx bx-right-arrow-alt'></i></a>
                        </div>
                    </div>
                </div>
                <div class="cus_back_design">
                    <div class="custom_dashboard_container_sub">
                        <div class="das_header">
                            <p class="das_head">Course</p>
                            <p class="over_all_details"> <?php  echo (is_array($pro_order_list) && $pro_order_list) ? count(array_unique($pro_order_list)) : '0';?></p>
                        </div>
                        <div class="custom_dashboard_container_sub_child">
                            <div class="poster course"></div>
                            <a  href="/courses/" class="cus_dash_link"><i class='bx bx-right-arrow-alt'></i></a>
                        </div>
                    </div>
                </div>
                <div class="cus_back_design">
                    <div class="custom_dashboard_container_sub">
                        <div class="das_header">
                            <p class="das_head">Voucher</p>
                            <p class="over_all_details"><?php echo count($voucher_count); ?></p>
                        </div>
                        <div class="custom_dashboard_container_sub_child">
                            <div class="poster voucher"></div>
                            <a href="/vouchers-level/" class="cus_dash_link"><i class='bx bx-right-arrow-alt'></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="barcharts" style="width:80%;margin:0px auto;">
                <div class="course_candidate_barchart ">
                    <div id="chartContainer" style="height: 400px; width: 49%; float:left; margin-right:1%;"></div>
                    <div id="course_chart" style="height: 400px; width: 49%; float:left;"></div>
                </div>

                <!-- <select id="yearSelector">
                    <?php //foreach ($years as $year): ?>
                        
                        <option value="<?php //echo $year; ?>" <?php //if ($year == $current_year) echo 'selected'; ?>>
                            <?php //echo $year; ?>
                        </option>
                    <?php //endforeach; ?>
                </select> -->
                
                <?php
                
                $years_mon = array_unique($years_mon);
                
                ?> <select id="yearSelector"> <?php
                
                foreach ($years_mon as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>" 
                        <?php if ($year == $current_year) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>

             


                <div class="candidate_voucher_barchart">
                
                    <div id="voucher_chart" style="height: 400px; width: 49%; float:left; margin-right:1%; z-index:1;"></div>
                    <div id="Voucher_details" style="height: 400px; width: 49%; float:left;"></div>
                </div>
                <!-- <div id="Candidate_performance" style="height: 400px; width: 49%; float:left;"></div> -->
            </div>
        </div>
        <div class="topigraphic-lines">
            <h3 class= "average_title">Average Scores</h3>
            <div class="cust_candi_perform_div">
                <div class="perform_div_sub_div">
                    <div class="cust_candi_perform_div_sub_div">
                        <p class="cust_candi_perform_div_sub_div_lable"> Diagnostic Test</p>
                        <p id="diagnostic_average_value" class="cust_candi_perform_div_sub_div_val"><?php echo !empty($diagnostic_average_value) ? $diagnostic_average_value: 'N/A'; ?></p>
                    </div>
                </div>
                <div class="perform_div_sub_div">
                    <div class="cust_candi_perform_div_sub_div">
                        <p class="cust_candi_perform_div_sub_div_lable">Module Quizzes <span>First Attempt</span></p>
                        <p id="module_average_value" class="cust_candi_perform_div_sub_div_val" ><?php echo !empty($module_average_value) ? $module_average_value : 'N/A' ; ?></p>
                    </div>
                </div>
                <div class="perform_div_sub_div">
                    <div class="cust_candi_perform_div_sub_div">
                        <p class="cust_candi_perform_div_sub_div_lable">Practice Test</p>
                        <p id="practice_average_value" class="cust_candi_perform_div_sub_div_val" ><?php echo !empty($practice_average_value) ? $practice_average_value : 'N/A'; ?></p>
                    </div>
                </div>
                <!-- <div class="perform_div_sub_div">
                    <div class="cust_candi_perform_div_sub_div">
                        <p class="cust_candi_perform_div_sub_div_lable">Check for Understanding - First Attempt</p>
                        <p id="check_average_value" class="cust_candi_perform_div_sub_div_val" ><?php //echo $check_average_value; ?></p>
                    </div>
                </div> -->
            </div>
           
           <?php 
           

  ?>
        <script>
            window.onload = function() {

            //course barchart
            var coursechart = new CanvasJS.Chart("course_chart", {
                animationEnabled: true,
                colorSet: "Shades",
                title:{
                    text: "Courses by Year",
                    fontColor: "#043753",
                    fontSize: 30,
                    fontWeight: 900,
                    fontFamily:"Open Sans",
                },
                axisX: {
                    title:'Year',
                    interval: 1,
                    // intervalType: "month",
                    tickColor:"transparent",
                    valueFormatString: "#####",
                },
                axisY: {
                    title:'Count',
                    gridColor:"white",
                    interval: 10,
                    tickColor:"transparent",
                    valueFormatString: "#####",
                },
                data: [{
                    type: "column",
                    dataPoints: <?php echo json_encode($course_create_data, JSON_NUMERIC_CHECK); ?>
                    // dataPoints: [
                    //     { x: 2022, y: 75 }
                    // ]
                    
                    
                }],
                toolTip: {
                    contentFormatter: function (e) {
                        var content = "";
                        for (var i = 0; i < e.entries.length; i++) {
                        var year = e.entries[i].dataPoint.x.toString().replace(/,/g, '');
                            var count = e.entries[i].dataPoint.y;
                            content += year + ": " + count;
                            if (i < e.entries.length - 1) {
                                content += "<br/>";
                            }
                        }
                        return content;
                    }
                }

            });
            
            coursechart.render();

            //candidate barchart
            var candidatechart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                // interactivityEnabled: false,
                title: {
                    text: "Candidates by Year",
                    fontColor: "#043753",
                    fontSize: 30,
                    fontWeight: 900,
                    fontFamily:"Open Sans",
                },
                axisX: {
                title:'Year',
                tickColor:"transparent",
                valueFormatString: "#####",
                interval: 1,
                },
                axisY: {
                title:'Count',
                gridColor:"white",
                tickColor:"transparent",
                valueFormatString: "#####",
                interval: 100,
                },
                // data: [{
                //     type: "line",
                //     color: "#0d2844",
                //     dataPoints: [
                //         { x: 2021 },
                //         { x: 2022, y: 1 },
                //         { x: 2023, }
                //     ]
                // }],
                data: [{
                    type: "line",
                    color: "#0d2844",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                }],
                toolTip: {
                            contentFormatter: function (e) {
                                var content = "";
                                for (var i = 0; i < e.entries.length; i++) {
                                    var year = e.entries[i].dataPoint.x.toString().replace(/,/g, '');
                                    var count = e.entries[i].dataPoint.y;
                                    content += year + ": " + count;
                                    if (i < e.entries.length - 1) {
                                        content += "<br/>";
                                    }
                                }
                                return content;
                            }
                        }
            });
            candidatechart.render();



            //For Vouchers by year 
            var voucherData = <?php echo json_encode($voucherData); ?>;
            console.log(voucherData);
            function updateChart() {
                // var selectedYear = 2025;  
                var selectedYear = document.getElementById("yearSelector").value;
                console.log("Year Selector:", selectedYear);
                
                var dataPointsAssigned = [];
                var dataPointsUnassigned = [];
                var dataPointsRedeemed = [];
                
            
                //if (selectedYear && voucherData[selectedYear]) {
            
                    

                    for (var month = 1; month <= 12; month++) {
                        var monthStr = month.toString().padStart(2, '0');   
                        var monthKey = selectedYear + "-" + monthStr;      

                 
                        
                        var monthData = voucherData[monthKey] || { assigned: 0, unassigned: 0, redeemed: 0 };
                        console.log("Voucher Data:", monthData);

           
                        dataPointsAssigned.push({ x: new Date(selectedYear, month - 1), y: monthData.assigned });
                        dataPointsUnassigned.push({ x: new Date(selectedYear, month - 1), y: monthData.unassigned });
                        dataPointsRedeemed.push({ x: new Date(selectedYear, month - 1), y: monthData.redeemed });
                    }

               // }
        
                var voucherchart = new CanvasJS.Chart("voucher_chart", {
                    animationEnabled: true,
                    theme: "light2",
                    title: {
                        verticalAlign: "top", 
                        horizontalAlign: "center",
                        margin: 20,
                        fontColor: "#043753",
                        fontSize: 30,
                        fontWeight: 900,
                        text: "Vouchers by Year"
                    },
                    axisX: {
                        title: 'Month',
                        valueFormatString: "MMM",
                        interval: 1,
                        intervalType: "month"
                    },
                    axisY: {
                        title: 'Count',
                        gridColor: "transparent",
                        lineThickness: 2,
                    },
                    toolTip: {
                        contentFormatter: function (e) {
                            var content = "";
                            for (var i = 0; i < e.entries.length; i++) {
                                content += e.entries[i].dataSeries.legendText + ": " + e.entries[i].dataPoint.y + "<br/>";
                            }
                            return content;
                        }
                    },
                    data: [{
                        type: "stackedColumn",
                        legendText: "Assigned",
                        color: "#f0c144",
                        showInLegend: true,
                        dataPoints: dataPointsAssigned
                    },
                    {
                        type: "stackedColumn",
                        legendText: "Unassigned",
                        color: "#315f7f",
                        showInLegend: true,
                        dataPoints: dataPointsUnassigned
                    },
                    {
                        type: "stackedColumn",
                        legendText: "Redeemed",
                        color: "#ed6d46",
                        showInLegend: true,
                        dataPoints: dataPointsRedeemed
                    }]
                });

                voucherchart.render();
            }

            document.getElementById("yearSelector").addEventListener("change", updateChart);
            
        
            updateChart();

            CanvasJS.addColorSet("Shades", ["#ed6d46", "#f0c144", "#699fb6", "#315f7f"]);
         
    
            // For voucher total count 
            var dataPoints = [
                { 
                    legendtext: "Assigned", 
                    indexLabel: "<?php echo count($assigned); ?>", 
                    y: <?php echo count($assigned); ?>,
                    color: "#f0c144"
                },
                { 
                    legendtext: "Unassigned", 
                    indexLabel: "<?php echo count($unassigned); ?>", 
                    y: <?php echo count($unassigned); ?>, 
                    color: "#315f7f"
                },
                { 
                    legendtext: "Redeemed", 
                    indexLabel: "<?php echo count($redeemed); ?>", 
                    y: <?php echo count($redeemed); ?>,
                    color: "#ed6d46"
                }
            ];
            //no data code
                var isNoData = dataPoints.every(function(point) {
                    return point.y === 0;
                });


                if (isNoData) {
                    dataPoints = [
                        {
                            legendtext: "No Data Available",
                            // indexLabel: "No Data",
                            y: 1, 
                            color: "#d3d3d3" 
                        }
                    ];
                }
                //***********===========*********** */
            var couponchart = new CanvasJS.Chart("Voucher_details", {
                animationEnabled: true,
                theme: "light2",
                height: 400,
                title: {
                    text: "Total Vouchers",
                    fontColor: "#043753",
                    fontSize: 30,
                    fontFamily: "Open Sans"
                },
                axisX: {
                    tickColor: "transparent"
                },
                axisY: {
                    tickColor: "transparent"
                },
                data: [{
                    type: "doughnut",
                    showInLegend: true,
                    legendText: "{legendtext}",
                    toolTipContent: "{legendtext} - {y}",
                    dataPoints: dataPoints,
                    indexLabelFontColor: "#043753",
                    indexLabelFontSize: 16,
                }],
                
            });

            couponchart.render();
        }

        
       /*function updateChart() {
    var selectedYear = "2025"; // or from a dropdown
    var selectedMonth = "03";  // or from a dropdown

    var dataKey = selectedYear + "-" + selectedMonth;

    var dataPointsAssigned = [];
    var dataPointsUnassigned = [];
    var dataPointsRedeemed = [];

    if (voucherData[dataKey]) {
        var monthData = voucherData[dataKey];
        
        // Add data to chart points
        dataPointsAssigned.push({ x: new Date(selectedYear, selectedMonth - 1), y: monthData.assigned });
        dataPointsUnassigned.push({ x: new Date(selectedYear, selectedMonth - 1), y: monthData.unassigned });
        dataPointsRedeemed.push({ x: new Date(selectedYear, selectedMonth - 1), y: monthData.redeemed });
    } else {
        console.log("No data for this month!");
    }

    // Assuming you have a CanvasJS chart initialized, like this:
    var voucherchart = new CanvasJS.Chart("voucher_chart", {
        animationEnabled: true,
        theme: "light2",
        title: {
            text: "Vouchers by Month"
        },
        axisX: {
            title: "Month",
            valueFormatString: "MMM"
        },
        axisY: {
            title: "Count"
        },
        data: [{
            type: "stackedColumn",
            legendText: "Assigned",
            dataPoints: dataPointsAssigned
        }, {
            type: "stackedColumn",
            legendText: "Unassigned",
            dataPoints: dataPointsUnassigned
        }, {
            type: "stackedColumn",
            legendText: "Redeemed",
            dataPoints: dataPointsRedeemed
        }]
    });

    voucherchart.render();
} */
    </script>

<?php
       

 
