<?php
global $wpdb;

$email = sanitize_email($_POST['email']);
$rn_email = sanitize_email($_POST['rn_email']);  
$voucher_id = intval($_POST['id']);

$vouchervalue = isset($_POST['vouchervalue']) ? sanitize_text_field($_POST['vouchervalue']) : '';

 


$rn_vouchervalue = isset($_POST['rn_vouchervalue']) ? sanitize_text_field($_POST['rn_vouchervalue']) : '';
 
//echo $rn_vouchervalue;

$table_name = $wpdb->prefix . "voucher_list";  
$date = date('Y-m-d H:i:s');  
$status = !empty($rn_email) ? 'Assigned' : (!empty($email) ? 'Assigned' : 'pending');

if ($rn_vouchervalue) {
    $to = $rn_email;
    $subject = 'Voucher Re-assign';
    $message = '<html><body>';
    $message .= '<p>This voucher <strong>' . $rn_vouchervalue . '</strong> has been assigned to you.</p>';
    $message .= '</body></html>';
    $headers = "From: Right Start Voucher Issue \r\n";
    $headers .= "Reply-To: support@fronseye.com\r\n";
    $headers .= "Return-Path: no-reply@yourdomain.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";  
    
    mail($to, $subject, $message, $headers);
}


if ($vouchervalue) {

    $to = $email;
    $subject = 'Voucher Re-assign';
    $message = '<html><body>';
    $message .= '<p>This voucher <strong>' . $rn_vouchervalue . '</strong> has been Re-assigned to you.</p>';
    $message .= '</body></html>';
    
    $headers = "From: Right Start canvas issue <no-reply@fronseye.com>\r\n";
    $headers .= "Reply-To: support@fronseye.com\r\n";
    $headers .= "Return-Path: no-reply@yourdomain.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";  
    
    mail($to, $subject, $message, $headers);
    
}

 


 
$final_email = !empty($rn_email) ? $rn_email : $email;

$updated = $wpdb->update(
    $table_name,
    [
        'assinged_to' => $final_email,  
        'assinged_date' => $date,  
        'status' => $status
    ],
    ['id' => $voucher_id],  
    ['%s', '%s', '%s'],  
    ['%d']  
);

if ($updated === false) {
    error_log("DB Error: " . $wpdb->last_error);  
    wp_send_json_error(['message' => 'Failed to update voucher', 'error' => $wpdb->last_error]);
} else {
    wp_send_json_success(['message' => 'Voucher updated successfully!', 'email' => $final_email]);
}




