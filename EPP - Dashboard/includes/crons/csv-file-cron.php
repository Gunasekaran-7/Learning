<?php

/* The process involves separating and storing data from order creation and order updates into a table.*/

//created_by : Guna 11-03-2025
    

 
    global $wpdb;

    $get_results = $wpdb->get_results(
        "SELECT id, user_id, order_id, voucher_code, order_date FROM `wp_voucher_file` WHERE status = 'pending'", 
        ARRAY_A
    );
    $datas = [];  
    foreach ($get_results as $results) {
        $id = $results['id'];
        $user_id = $results['user_id'];
        $voucher_code = $results['voucher_code'];
        $order_id = $results['order_id'];
        $date = $results['order_date'];


        $csv_file_path = ABSPATH . 'vouchers/' . $voucher_code;

        if (!file_exists($csv_file_path)) {
            $wpdb->update(
                $wpdb->prefix . 'voucher_file',
                array('comment' => 'file does not exist'),
                array('id' => $id),
                array('%s'),
                array('%d')
            );
            continue;
        }

     

        if (($handle = fopen($csv_file_path, 'r')) !== FALSE) {
            //fgetcsv($handle); // Skip header row

            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $voucher_code_csv = $data[0];
                $datas[] = array(
                    'order_date'      => $date,
                    'file_id'        => $id,
                    'user_id'        => $user_id,
                    'order_id'       => $order_id,
                    'package_code'   =>'',
                    'course_id'       =>'',
                    'customer_order_id'=>'',
                    'voucher_code'   => $voucher_code_csv,
                    'assinged_to'    => 'empty',
                    'assinged_date'  => '',
                    'redeemed_to'    => 'empty',
                    'redeemed_date'  => '',
                    'status'         => 'pending'
                );
            }
            fclose($handle);
        }

        $wpdb->update(
            $wpdb->prefix . 'voucher_file',
            array('status'  => 'processed'),
            array('id' => $id),  
            array('%s'),
            array('%d')
        );
    }

    if (!empty($datas)) {
        foreach ($datas as $data) {
            $wpdb->insert(
                $wpdb->prefix . 'voucher_list',
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')  
            );
        }
    }
 
