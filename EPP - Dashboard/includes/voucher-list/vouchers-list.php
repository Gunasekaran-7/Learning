<?php
if (!is_user_logged_in()) {
    
	wp_redirect(site_url('/account/'));
    exit;  
}
/* Vouchers List */
 global $wpdb;
   
    $user_id = get_current_user_id();

    $get_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, voucher_code, order_id, assinged_to, redeemed_to, order_date
            FROM `{$wpdb->prefix}voucher_list` 
            WHERE user_id = %d",
            $user_id
        ),
        ARRAY_A
    );

    $unassigned = [];
    $assigned = [];
    $redeemed = [];
    $total_vouchers = [];

    $order_dates = [];
    
    foreach ($get_data as $voucher) {

        $order_id = $voucher['order_id'];

        $order_date = $voucher['order_date'];

        if (!isset($order_dates[$order_id])) {
            $order_dates[$order_id] = $order_date;
        }

        $voucher_code = $voucher['voucher_code'];
        if ($voucher['assinged_to'] === 'empty' && $voucher['redeemed_to'] === 'empty') {
            $unassigned[$order_id][] = $voucher_code;
          
        }
        if ($voucher['assinged_to'] !== 'empty') {
            $assigned[$order_id][] = $voucher_code;
        } 
        if ($voucher['redeemed_to'] !== 'empty') {
            $redeemed[$order_id][] = $voucher_code;
        }
    }
 ?>
 

    <div style="width:100%;display:none;">
        <button class="btn btn-primary" style="float:right; margin-bottom:10px;">Assign</button>
   </div>

     <!-- <input type="search" class="search_id" placeholder="search"> -->
    <div class="">
    

    <?php
   // echo "<div class='t-response'><table id='voucher_orders' class='display table-responsive'>
    // <thead>
    //     <tr>
    //         <th>Order ID</th>
    //         <th>Order Date</th>
    //         <th>Status</th>
    //         <th>View</th>
    //     </tr>
    // </thead>
    // <tbody>";
    
    // $order_ids = array_unique(array_merge(array_keys($unassigned), array_keys($assigned), array_keys($redeemed)));
    
    // foreach ($order_ids as $order_id) {
    
    //     $order_date = isset($order_dates[$order_id]) ? $order_dates[$order_id] : 'N/A'; 
    
    //     if ($order_date !== 'N/A') {
    //         $formatted_date = date('m-d-Y', strtotime($order_date));  
    //     } else {
    //         $formatted_date = 'N/A';
    //     }
        
    //     $all_vouchers = array_merge(
    //         $unassigned[$order_id] ?? [], 
    //         $assigned[$order_id] ?? []
    //     );
    
        // Count unique vouchers per order
        // $total_count = count(array_unique($all_vouchers));
        // $total_unassigned = isset($unassigned[$order_id]) ? count($unassigned[$order_id]) : 0;
        // $total_assigned = isset($assigned[$order_id]) ? count($assigned[$order_id]) : 0;
        // $total_redeemed = isset($redeemed[$order_id]) ? count($redeemed[$order_id]) : 0;
    
        // Bootstrap row layout inside Status column
        // $status_info = '
        //     <div class="row">
        //         <div class="col-lg-3"><b>Total:</b> <span class="total_count">'.$total_count.'</span></div>
        //         <div class="col-lg-3"><b>Assigned:</b> <span class="assign_count">'.$total_assigned.'</span></div>
        //         <div class="col-lg-3"><b>Unassigned:</b> <span class="unassign_count">'.$total_unassigned.'</span></div>
        //         <div class="col-lg-3"><b>Redeemed:</b> <span class="redeemed_count">'.$total_redeemed.'</span></div>
        //     </div>';
    
            // $view_link = "<a href='https://whitelabeledp.wpengine.com/voucher-assing-page?order_id={$order_id}' class='btn btn-primary button-view'>View</a>";
    
    
        //echo "<tr>
        //     <td>{$order_id}</td>
        //     <td>{$formatted_date}</td>
        //     <td class='counts-container'>{$status_info}</td>
        //     <td>{$view_link}</td>
        //   </tr>";
    // }
    
    //echo "</tbody></table></div>";
    echo"<a href='/account/' class='voucher_back'><button class='voucher_to_dashboard btn'>Back to Dashboard</button></a>";
echo "<div class='t-response'><table id='voucher_orders' class='display table-responsive'>
<thead>
    <tr>
      
        <th>Order Date</th>
        <th>Status</th>
        <th>View</th>
    </tr>
</thead>
<tbody>";

$order_ids = array_unique(array_merge(array_keys($unassigned), array_keys($assigned), array_keys($redeemed)));

foreach ($order_ids as $order_id) {

    $order_date = isset($order_dates[$order_id]) ? $order_dates[$order_id] : 'N/A'; 

    if ($order_date !== 'N/A') {
        $formatted_date = date('m-d-Y', strtotime($order_date));  
    } else {
        $formatted_date = 'N/A';
    }
    
    $all_vouchers = array_merge(
        $unassigned[$order_id] ?? [], 
        $assigned[$order_id] ?? []
    );

    // Count unique vouchers per order
    $total_count = count(array_unique($all_vouchers));
    $total_unassigned = isset($unassigned[$order_id]) ? count($unassigned[$order_id]) : 0;
    $total_assigned = isset($assigned[$order_id]) ? count($assigned[$order_id]) : 0;
    $total_redeemed = isset($redeemed[$order_id]) ? count($redeemed[$order_id]) : 0;

    // Bootstrap row layout inside Status column
    $status_info = '
        <div class="row">
            <div class="col-lg-3"><b>Total:</b> <span class="total_count">'.$total_count.'</span></div>
            <div class="col-lg-3"><b>Assigned:</b> <span class="assign_count">'.$total_assigned.'</span></div>
            <div class="col-lg-3"><b>Unassigned:</b> <span class="unassign_count">'.$total_unassigned.'</span></div>
            <div class="col-lg-3"><b>Redeemed:</b> <span class="redeemed_count">'.$total_redeemed.'</span></div>
        </div>';

        $view_link = "<a href='https://whitelabeledp.wpengine.com/voucher-assing-page?order_id={$order_id}' class='btn btn-primary button-view'>View</a>";


    echo "<tr>
       
        <td>{$formatted_date}</td>
        <td class='counts-container'>{$status_info}</td>
        <td>{$view_link}</td>
      </tr>";
}

echo "</tbody></table></div>";





?>
      
 
</div>

    
<style>
    .voucher_to_dashboard{
        background: #008eb3 !important;
        padding: 8px 20px;
        margin-top:10px;
    }
    .voucher_to_dashboard:hover{
    background: #f0c144 !important;
}
      table.dataTable thead th{
        text-align:center !important;
      }

        #voucher_orders_paginate .paginate_button {
        margin:0px!important;
       
        height: 28px;
        }
        #voucher_orders_paginate,#voucher_orders_info{
        margin:10px 0px;
        }
        #voucher_orders_paginate .previous,#voucher_orders_paginate .next{
        padding-top:4px!important;
        margin:0px 10px!important;
        }

        #voucher_orders_paginate .paginate_button.previous,
        #voucher_orders_paginate .paginate_button.next {
            background-color: #043753 !important;
            color: white !important; 
        
            /* border-radius: 2px; */
        
        
        }


        #voucher_orders_paginate .paginate_button.current {
            background-color: #f0c144 !important;
            color: white; 
        }


        #voucher_orders_paginate .paginate_button.previous:active,
        #voucher_orders_paginate .paginate_button.next:active {
            background-color: #f0c144 !important;
            color: white; 
        }


        #voucher_orders_paginate .paginate_button:hover {
            background-color: #f0c144 !important;
            color: white;
        }



        .button-view {
            background: #008eb3 !important;
            padding: 10px;
            text-decoration: none !important; 
            border-radius: 5px; 
            color: white; 
            
        }

        .button-view:active {
            background: #f0c144 !important; 
            color: white; 
        }
        .button-view:hover {
            color:white;
            background: #f0c144 !important; 
        }
       
            .counts-container {
                display: flex;
                justify-content: space-evenly; 
                /* width: 100%; */
                border-bottom: none !important; 
            }

            .counts-container div {
                padding: 5px 10px; 
                border: none; 
            }

            .status { text-align:right !important; }
            .action { text-align:center !important; }
            /* .button-view { background:#008eb3 !important; padding:10px; } */
            .total_count { background:#043753 !important; color:#fff !important; padding:2px 5px !important; }
            .assign_count { background:#f0c144 !important; color:#fff !important; padding:2px 5px !important; }
            .unassign_count { background:#ed6d46 !important; color:#fff !important; padding:2px 5px !important; }
            .redeemed_count { background:#699fb6 !important; color:#fff !important; padding:2px 5px !important; }

            .search_id {
                margin-bottom: -6.5% !important;
                padding: 5px !important;
                z-index: 9 !important;
                border:1px solid #043753 !important;
                width:20%;
                float:right;
                position: relative;
                top: 35px !important;
            }
            /* #voucher_orders_length label {
            display: flex;
            align-items: center;
            gap: 10px; 
            float: right !important;
        } */

        #voucher_orders_length select {
            margin: 0 5px;
            padding: 5px;
            padding-right: 15px;
        }
/* 
        #voucher_orders_filter {
            display: none !important;
        } */

.dataTables_length, .dataTables_filter {
    display: inline-block;
    margin-right: 10px; 
}


.dataTables_length label, .dataTables_filter label {
    display: inline-block;
    margin-right: 5px; 

}
.dataTables_filter label{
    padding-top:27px;
}
select, input[type="search"] {
    display: inline-block;
}



                #voucher_orders_length select {
                    height: 30px !important;
                    margin: 30px 0px !important;
                }

                #voucher_orders .odd {
                    background: #fff !important;
                }
                .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0 !important; /* Remove padding */
            }
                #voucher_orders .odd,
                #voucher_orders .odd td,
                #voucher_orders .even td {
                    box-shadow: 0px 0px 0px 0px #fff !important;
                    background: #fff !important;
                    padding: 20px !important;
                }
                /* @media (max-width: 475px) {
                    .search_id {
                        margin-bottom: -19% !important;
                }
            }
            @media (min-width: 768px) and (max-width: 1023px)  {
                    .search_id {
                        margin-bottom: -9% !important;
                }
            }
            @media (max-width: 767px) {
            #voucher_orders_length label {
                float: left !important;
                margin-top: 37px;
            padding-left: 2px;}
            } */
</style>


   
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <script>
       


        $(document).ready(function() {
                var table = $('#voucher_orders').DataTable();
                $('.search_id').on('keyup', function() {
                    table.search(this.value).draw();
                });
        });

    </script> 

<?php //endif; ?>


 
 
