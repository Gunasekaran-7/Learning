<?php
global $wpdb;
 
$select_data = $_POST['select_data'];

 
$user_id = get_current_user_id();

$where_clause = "WHERE user_id = %d";  



if ($select_data === 'Assigned') {
 
    $where_clause .= " AND status = 'assigned'";

} elseif ($select_data === 'Unassigned') {
 
    $where_clause .= " AND status = 'pending'";
} elseif ($select_data === 'Redeemed') {
   
    $where_clause .= " AND status = 'redeemed'";
}
 
$get_data = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, voucher_code, order_id, assinged_to, redeemed_to, redeemed_date, status
        FROM `{$wpdb->prefix}voucher_list`
        $where_clause",  
        $user_id  
    ),
    ARRAY_A
);

?>

<!-- <div class="voucher_table">
    <table class="table table-striped table-condensed" id="tblData">
        <thead>
            <tr>
                <th>Order Id</th>
                <th>Voucher number</th>
                <th>Candidate First name</th>
                <th>Candidate Last name</th>
                <th>Candidate email</th>
                <th>Status</th>
                <th>Redemption Date</th>
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
                        //$email = 'N/A';
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
                       // $email = 'N/A';
                   // }else{
                        //$email = $emails;
                        //$first_name = get_user_meta($email,'first_name',true);
                        //$last_name = get_user_meta($email,'last_name',true);
                    //}
               // }

                //if ($status == 'pending') {
                    //$status = 'Unassigned';   
               // }
            ?>
                <tr>
                    <td><?php //echo $voucher['order_id']; ?></td>
                    <td><?php //echo $voucher['voucher_code']; ?></td>
                    <td><?php //echo $first_name ? $first_name : 'N/A'; ?></td>
                    <td><?php //echo $last_name ? $last_name : 'N/A'; ?></td>
                    <td><?php //echo $email; ?></td>
                    <td><?php //echo $status; ?></td>
                    <td><?php //echo isset($voucher['redeemed_to']) && $status == 'Redeemed' ? $redeem_date : 'N/A'; ?></td>
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

                    $email = $voucher['redeemed_to'];

                    $first_name = get_user_meta($email,'first_name',true);
                    $last_name = get_user_meta($email,'last_name',true);


                }else{
                    $emails = $voucher['redeemed_to'];
                    if( $emails == 'empty'){
                        $email = 'N/A';
                    }else{
                        $email = $emails;
                        $first_name = get_user_meta($email,'first_name',true);
                        $last_name = get_user_meta($email,'last_name',true);
                    }
                }

                if ($status == 'pending') {
                    $status = 'Unassigned';   
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

<script>
	jQuery(document).ready(function(){
	     let table = new DataTable('#tblData');
		jQuery('#tblData1').DataTable({
			colReorder: true,
			 //responsive: true,   
            scrollX: true,
			pagingType: 'simple_numbers',
			paging: true,
			language: {
				paginate: {
                    previous: 'Previous',
                    next: 'Next'
			    }
			},
            dom: 'Blfrtip',
					buttons: [
						{ extend: 'csvHtml5', text: 'CSV' },
						'colvis'
					]
		});
	});

</script>
