<?php
if (!is_user_logged_in()) {
    
	wp_redirect(site_url('/account/'));
    exit;  
}
/* Vouchers List Assign */
global $wpdb;  

get_header();

 



if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {

    //$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
 

    $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
 

    /*
    $get_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, voucher_code, assinged_to, redeemed_to, status FROM `{$wpdb->prefix}voucher_list` WHERE order_id = %d", 
            $order_id
        ),
        ARRAY_A
    ); */

    //newly added_by : Guna (18-03-2025)

    // $get_data = $wpdb->get_results(
    //     $wpdb->prepare(
    //         "SELECT id, voucher_code, assinged_to, redeemed_to, status 
    //          FROM `{$wpdb->prefix}voucher_list` 
    //          WHERE order_id = %d 
    //          ORDER BY 
    //             CASE 
    //                 WHEN status = 'pending' THEN 1
    //                 WHEN status = 'Assigned' THEN 2
    //                 WHEN status = 'Redeemed' THEN 3
    //                 ELSE 4
    //             END",
    //         $order_id
    //     ),
    //     ARRAY_A
    // );


    //newly added_by : Guna (20-03-2025)
    
    $get_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, voucher_code, assinged_to, redeemed_to, status 
            FROM `{$wpdb->prefix}voucher_list` 
            WHERE order_id = %s 
            ORDER BY 
                CASE 
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'Assigned' THEN 2
                    WHEN status = 'Redeemed' THEN 3
                    ELSE 4
                END",
            $order_id
        ),
        ARRAY_A
    );

 

    echo "<div class=''><div class='container-txt'>
    <h1 class='text-assign' >Voucher Assign</h1></div>";

    echo"<div id='refresh'>";
    echo"<a href='/voucher/' class='voucher_back'><button class='voucher_btn_back btn btn-danger'>Back</button></a>";
    echo"<button class='voucher_btn_refresh btn btn-danger ms-2' style='display:none;'>Refresh</button>";
    echo"</div>";
    echo "<div class='table-space'><table id='voucher-assign' class='display_pot'>
        <thead>
            <tr>
                <th>Voucher</th>
                <th>Status</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>";
    
    if (!empty($get_data)) {
        foreach ($get_data as $voucher) {
            $voucher_id = esc_html($voucher['id']);
            $voucher_code = esc_html($voucher['voucher_code']);
            $assigned_to = $voucher['assinged_to'];
            $redeemed_to = $voucher['redeemed_to'];

             

            if($voucher['status'] == 'pending'){
                $status = 'Unassigned';
            }elseif($voucher['status'] == 'Assigned'){
                $status = 'Assigned';
            }elseif($voucher['status'] == 'Redeemed'){
                $status = 'Redeemed';
            }


            echo "<tr>
                <td class='voucher-td'>{$voucher_code}</td>
                 
                <td>{$status}</td>
            "; 
                
           
            if ($assigned_to == 'empty' && $redeemed_to == 'empty') {
                echo "<td><input type='email' name='email_{$voucher_id}' class='voucher-email' placeholder='Enter Email' required ></td>
                      <td><button class='assign-voucher' data-voucher='{$voucher_id}'>Assign</button></td>";
            }
            
          
            elseif ($assigned_to != 'empty' && $redeemed_to == 'empty') {
                echo "<td><input type='email' name='email_{$voucher_id}' class='voucher-email' value='{$assigned_to}' required readonly></td>
                      <td><button class='reassign-voucher' data-voucher='{$voucher_id}'>Reassign</button></td>";
            }
          
            elseif($assigned_to != 'empty' && $redeemed_to != 'empty'){

                //newly added_by : Guna 
                // reason : If there is no redeemed user email , we use assinged user email. 

                $user_info = get_userdata($redeemed_to);

                $user_email = $user_info->user_email;
                    
                if ($user_email) {
                    $redeemed_to = $voucher['redeemed_to'];
                }else{
                    $redeemed_to = $assigned_to;
                }
                echo "<td readonly>{$redeemed_to}</td>
                      <td><span class='redeemed' disabled></span></td>";
            }
    
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No vouchers found for this order.</td></tr>";
    }
    
    echo "</tbody></table></div></div>";

  echo" <div id='success_message' style='display: none;'></div>";
  echo" <div id='success_message_out' style='display: none;'></div>";

    
    ?>


        <div id="reassign_modal" style="display: none;">
            <div class="reassign_modal_body">
                <span class="close_btn">&times;</span><br>
                <label class="email_lable_new">Email:</label>
                <input data-row="" type="email" id="email_reassing">
                <div class="modal-buttons">
                    <button id="confirm_button">Yes Re-Assign</button>
                    <button id="close_button">Close</button>
                </div>
                <p class="error_redeemed_message" style="display:none;"></p>
            </div>
        </div>
        
    <?php

} else {
    //wp_redirect(home_url('/account')); 
    //exit;
}


?>
<style>
    /* #voucher-assign tr {
    padding: 0;
} */

   .voucher_btn_back, .assign-voucher, .reassign-voucher {
    background: #008eb3 !important;
    padding: 6px 20px;
}

.voucher_btn_back:hover, .assign-voucher:hover, .reassign-voucher:hover {
    background: #f0c144 !important;
}

 #voucher-assign input.voucher-email{
    border:none;
    padding:0px!important;
     
 }
 /* #voucher-assign td {
    color: #000!important;
} */
 
 #voucher-assign input.voucher-email:placeholder-shown {
    border: 1px solid gray; 
    border-radius:3px;
    padding:8px;
}
    #voucher-assign th, #voucher-assign td {
    text-align: center;
}
    .table-space{margin:60px; margin-top:10px;}
    .container-txt{text-align: center;
    padding: 50px;
    width: 100%;
    background: #043753;
    border-top: 7px solid #F0C144;
    color:white;}
.text-assign{text-align:center;color:white;}
.pg-voucher-assing-page .ast-container{
  display:block;
}
#voucher-assing{
 margin:0px;
  width:100%!important;
}
/* Modal Background */
#reassign_modal {
    display: none; /* Initially hidden */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Dark overlay */
}

/* Modal Body */
.reassign_modal_body {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    width: 350px;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    text-align: center;
}

/* Close Button (X) */
.close_btn {
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    color: #333;
    position: absolute;
    top: 10px;
    right: 15px;
}

/* Label Styling */
.email_lable_new {
    font-size: 16px;
    font-weight: bold;
    display: block;
    margin-bottom: 10px;
}

/* Buttons */
.modal-buttons {
    margin-top: 20px;
}

.modal-buttons button {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    margin: 5px;
}

/* Confirm Button */
#confirm_button {
    background-color: #248aa9;
    color: white;
}

/* Close Button */
#close_button {
    background-color: #f0c144;
    color: #000;
}

/* Error Message */
.error_redeemed_message {
    color: red;
    font-size: 14px;
    margin-top: 10px;
}
#refresh {
    display: flex;
    gap: 2%;
    padding: 3% 0px 0px 4.9%;
 
}

#voucher-assign .voucher-email:focus, 
#voucher-assign .voucher-email:focus-visible {
  border-color: #808080 !important;
  outline: none;
}
#success_message {
    position: fixed;
    top: 61px;
    right: 20px;
    background-color: #28a745; /* Green background */
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    font-weight: bold;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.5s ease-in-out;
}
#success_message_out {
    position: fixed;
    top: 61px;
    right: 20px;
    background-color: #28a745; /* Green background */
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    font-weight: bold;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.5s ease-in-out;
}
.redeemed{
    background-color: white !important;         
        
}
.redeemed:hover{
    background-color: white !important;         
        
}
</style>


<?php get_footer(); ?>


 