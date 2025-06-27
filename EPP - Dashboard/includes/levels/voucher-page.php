<?php 

    global$wpdb;

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
 
 

    $get_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, voucher_code, order_id, assinged_to, redeemed_to, redeemed_date, status
            FROM `{$wpdb->prefix}voucher_list` 
            WHERE user_id = %d",
            $user_id
        ),
        ARRAY_A
    );

    $unassigned = [];
    $assigned = [];
    $redeemed = [];
    $voucher_code = [];

    foreach ($get_data as $voucher) {
        $order_id = $voucher['order_id'];
        $voucher_code[] = $voucher['voucher_code'];
    
        if ($voucher['assinged_to'] === 'empty' && $voucher['redeemed_to'] === 'empty') {
            $unassigned[] = $voucher_code;
        }

        if ($voucher['assinged_to'] !== 'empty') {
            $assigned[] = $voucher_code;
    
        } 
        if ($voucher['redeemed_to'] !== 'empty') {
            $redeemed[] = $voucher_code;
        
        }
    

    }

 
    ?>

    <div class="voucher_main_div">
        <div class="main_dash_voucher_div">
            <p class="voucher_head">Voucher</p>
            <div class="voucher-dash-back-contain">
                <a href="<?php echo $url . '/portal/'; ?>" class="voucher_dashboard_back">Back</a>
            </div>
        </div>
        <div class="four_div">
            <div class="total_box">
                <i class='fluent-mdl2--contact-list voucher_icons'></i>
                <p class="box-head">Total</p>
                <p class="box-child"><?php echo count($voucher_code); ?></p>
            </div>
            <div class="total_box">
                <i class='mdi--account-check-outline voucher_icons'></i>
                <p class="box-head">Assigned</p>
                <p class="box-child"><?php echo count($assigned); ?></p>
            </div>
            <div class="total_box">
                <i class='mdi--account-cancel-outline voucher_icons'></i>
                <p class="box-head">Unassigned</p>
                <p class="box-child"><?php echo count($unassigned);  ?></p>
            </div>
            <div class="total_box">
                <i class='bx bx-check-circle voucher_icons'></i>
                <p class="box-head">Redeemed</p>
                <p class="box-child"><?php echo count($redeemed);  ?></p>
            </div>
        </div>
        <div class="select_stage">
            <div class="cust_voucher_label">
                <label>Status:</label>
                <select name="study" id="study">
                    <option value="All">All</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Unassigned">Unassigned</option>
                    <option value="Redeemed">Redeemed</option>
                </select>
            </div>
            <div class="cust_voucher_label cust_voucher_label_new">
                <button class="voucher-filter"><i class='bx bx-filter'></i> Filter</button>
            </div>
        </div>
    </div>

    <!-- <div class="voucher_table">
        <table class="table table-striped table-condensed" id="tblData-old">
            <thead>
                <tr>
                    <th>Order Id</th>
                    <th>Voucher number</th>
                    <th>Candidate First name</th>
                    <th>Status</th>
                    <th>Redemption Date</th>
                    <th>Candidate Last name</th>
                    <th>Candidate email</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php //foreach ($get_data as $voucher) : 
                
                    //$status = $voucher['status'];

                    //$order_date = $voucher['redeemed_date'];
                    //$redeem_date = date('Y-m-d', strtotime($order_date));

                    

                    
                    //if( $voucher['assinged_to'] == 'empty' && $voucher['redeemed_to'] == 'empty'){

                        //$email = 'N/A';
                        //$first_name = 'N/A';
                        //$last_name = 'N/A';

                    //}elseif($voucher['redeemed_to'] == 'empty'){

                        //$email_to = $voucher['assinged_to'];

                        //if( $email_to == 'empty'){
                        // $email = 'N/A';
                        //}else{
                            //$email = $email_to;

                            //$get_user_id = email_exists($email);

                            //$first_name = get_user_meta($get_user_id,'first_name',true);
                            //$last_name = get_user_meta($get_user_id,'last_name',true);
                        
                        //}

                    //}elseif($voucher['redeemed_to'] != 'empty' && $voucher['redeemed_to'] != 'empty'){

                        //$email = $voucher['redeemed_to'];

                        //$first_name = get_user_meta($email,'first_name',true);
                        //$last_name = get_user_meta($email,'last_name',true);


                    //}else{
                        //$emails = $voucher['redeemed_to'];
                        //if( $emails == 'empty'){
                            //$email = 'N/A';
                    // }else{
                            //$email = $emails;

                            //$first_name = get_user_meta($email,'first_name',true);
                        // $last_name = get_user_meta($email,'last_name',true);
                        //}
                    //}

                    //if($status == 'pending'){
                        //$status = 'Unassinged';
                    //}else{
                        //$status = $voucher['status'];
                    //}
                    
                    
                    ?>
                    
                    <tr>
                        <td><?php //echo $voucher['order_id']; ?></td>
                        <td><?php //echo $voucher['voucher_code']; ?></td>
                        <td><?php //echo $first_name ? $first_name : 'N/A'; ?></td>
                        <td><?php //echo $status; ?></td>
                        <td><?php //echo isset($voucher['redeemed_to']) && $status == 'Redeemed' ? $redeem_date : 'N/A'; ?></td>
                        <td><?php //echo $last_name ? $last_name : 'N/A'; ?></td>
                        <td><?php //echo $email; ?></td>
                        
                    </tr>
                <?php //endforeach; ?>
            </tbody>
        </table>
    </div> -->
    <div class="voucher_table">
        <table class="table table-striped table-condensed" id="tblData">
            <thead>
                <tr>
                    
                    <th>Voucher number</th>
                    
                    <th>Status</th>
                    <th>Redemption Date</th>
                    <th>Candidate First name</th>
                    <th>Candidate Last name</th>
                    <th>Candidate email</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php foreach ($get_data as $voucher) : 
                
                    $status = $voucher['status'];

                    $order_date = $voucher['redeemed_date'];
                    $redeem_date = date('Y-m-d', strtotime($order_date));
                    $assigned_to = $voucher['assinged_to'];
                    

                    
                    if( $voucher['assinged_to'] == 'empty' && $voucher['redeemed_to'] == 'empty'){

                        $email = 'N/A';
                        $first_name = 'N/A';
                        $last_name = 'N/A';

                    }elseif($voucher['redeemed_to'] == 'empty'){

                        $email_to = $voucher['assinged_to'];

                        if( $email_to == 'empty'){
                            $email = 'N/A';
                        }else{
                            $email = $email_to;

                            $get_user_id = email_exists($email);

                            $first_name = get_user_meta($get_user_id,'first_name',true);
                            $last_name = get_user_meta($get_user_id,'last_name',true);
                        
                        }

                    }elseif($voucher['redeemed_to'] != 'empty' && $voucher['redeemed_to'] != 'empty'){

                        $email_id = $voucher['redeemed_to'];

                        $first_name = get_user_meta($email_id,'first_name',true);
                        $last_name = get_user_meta($email_id,'last_name',true);

                        $user_info = get_userdata($voucher['redeemed_to']);

                        $user_email = $user_info->user_email;
                            
                        if ($user_email) {
                            $email = $voucher['redeemed_to'];
                        }else{
                            $email = $assigned_to;
                        }


                    }else{
                        //$emails = $voucher['redeemed_to'];
                        if( $voucher['redeemed_to'] == 'empty'){
                            $email = 'N/A';
                        }else{
                            $email = $voucher['redeemed_to'];

                            $first_name = get_user_meta($email,'first_name',true);
                            $last_name = get_user_meta($email,'last_name',true);
                        }

                        $user_info = get_userdata($voucher['redeemed_to']);

                        $user_email = $user_info->user_email;
                            
                        if ($user_email) {
                            $email = $voucher['redeemed_to'];
                        }else{
                            $email = $assigned_to;
                        }
                    }

                    if($status == 'pending'){
                        $status = 'Unassinged';
                    }else{
                        $status = $voucher['status'];
                    }
                    
                    
                    ?>
                    
                    <tr>
                        
                        <td><?php echo $voucher['voucher_code']; ?></td>
                        <td><?php echo $status; ?></td>
                        <td><?php echo isset($voucher['redeemed_to']) && $status == 'Redeemed' ? $redeem_date : 'N/A'; ?></td>
                        <td><?php echo $first_name ? $first_name : 'N/A'; ?></td>
                        
                        <td><?php echo $last_name ? $last_name : 'N/A'; ?></td>
                        <td><?php echo $email; ?></td>
                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

		 
	<style>

	#tblData_wrapper .dt-info {
    display: inline-block;
    vertical-align: middle;
    margin-right: 20px; 
    margin-top:8px;
    }

    #tblData_wrapper  .dt-paging {
        display: inline-block;
        vertical-align: middle;
        margin-top:10px;
    }

    .voucher_dashboard_back{text-decoration:none !important;}
	.dt-search{
		position:sticky;
		z-index: 10;
	}					  

			
			.dt-length {
				position: sticky;
			
				z-index: 10;
				
			}
			.set-draft-button{
				background:#043753;
				color:#fff;
				border-radius:20px;
				padding: 5px 7px 5px 7px;
				border:0px !important;
				cursor: pointer;
				}
				.set-draft-button:hover{
				background:#f0c144;
				/* border: 1px solid #f0c144!important; */
				}
				
                
			</style>
			 
		<script>
           
			jQuery(document).ready(function() {
				jQuery('#tblData').DataTable({
					colReorder: true,
					//responsive: true,
					scrollX: true,
					searching: true,
	 			    pagingType: 'simple_numbers',
					language: { paginate: { previous: 'Previous', next: 'Next' } },
					dom: 'Blfrtip',
					buttons: [
						{ extend: 'csvHtml5', text: 'CSV' },
						'colvis'
					]
				});
			  
			});

 </script>