<?php

   /* The process retrieves individual users and checks for matching vouchers in the CSV bulk table. If a matching voucher exists, it updates the assigned and redeemed columns accordingly.*/
   //created_by : Guna 11-03-2025

   global $wpdb;
 
//    $get_results = $wpdb->get_results(
//        "SELECT 
//                i.id, 
//                i.user_id AS individual_user_id, 
//                i.order_date,
//                i.package_code,
//                i.course_id, 
//                i.voucher_code, 
//                'individual' AS source
//            FROM wp_voucher_individual i
//            WHERE i.status = 'pending'

//            UNION ALL

//            SELECT 
//                l.id, 
//                COALESCE(i.user_id, NULL) AS user_id,  
//                NULL AS order_date, 
//                l.voucher_code, 
//                'list' AS source
//            FROM wp_voucher_list l
//            LEFT JOIN wp_voucher_individual i 
//                ON l.voucher_code = i.voucher_code AND i.status = 'pending'
//            WHERE l.status = 'Assigned';
//        ",
//        ARRAY_A
//    );

    //newly added_by : Guna 
    // date : 15-03-2025

    $get_results = $wpdb->get_results(
        "SELECT 
                i.id, 
                i.user_id AS individual_user_id, 
                i.order_date,
                i.package_code,
                i.course_id, 
                i.customer_order_id,
                i.voucher_code, 
                'individual' AS source
            FROM wp_voucher_individual i
            WHERE i.status = 'pending'
    
        UNION ALL
    
        SELECT 
                l.id, 
                COALESCE(i.user_id, NULL) AS user_id,  
                NULL AS order_date, 
                Null AS package_code,     
                NULL AS course_id,
                NULL AS customer_order_id,                  
                l.voucher_code,                    
                'list' AS source
        FROM wp_voucher_list l
        LEFT JOIN wp_voucher_individual i 
            ON l.voucher_code = i.voucher_code AND i.status = 'pending'
        WHERE l.status = 'Assigned';",
        ARRAY_A
    );

   $voucher_codes = $ids = [];
   foreach ($get_results as $row) {



       if ($row['source'] === 'list') {
           $voucher_codes[$row['voucher_code']] = $row['id'];
           $voucher_codes['user_id'] = $row['individual_user_id'];
       }
   }

   foreach ($get_results as $results) {

       $source = $results['source'];
     
       if ($source == 'list') {

           $id = $results['id'];
           
           $user_id = $results['individual_user_id'];

           $user_info = get_userdata($user_id);

           $user = $user_info->user_email ? $user_info->user_email : $user_id;
       }

       if ($source == 'individual') {
           $ids[] = $results['id'];
        
           $order_date = $results['order_date'];
           $course_id = $results['course_id'];
           $package_code = $results['package_code'];
           $customer_order_id = $results['customer_order_id'];
       }

        

    //    if (!is_null($user_id) && $source == 'list') {

           
    //        $wpdb->update(
    //            "{$wpdb->prefix}voucher_list",
    //            [
    //                'status' => 'Redeemed',
    //                'package_code' =>,
    //                'course_id' =>,
    //                'redeemed_to' => $user,
    //                'redeemed_date' => $order_date,
    //            ],
    //            ['id' => $id],
    //            ['%s', '%s', '%s'],
    //            ['%d']
    //        );

    //        if (!empty($ids)) {
    //            $ids_list = implode(',', $ids);  
    //            $wpdb->query(
    //                $wpdb->prepare(
    //                    "UPDATE {$wpdb->prefix}voucher_individual 
    //                     SET status = %s 
    //                     WHERE id IN ($ids_list)",
    //                    'processed'
    //                )
    //            );
    //        }
    //    }


    //newly added_by : Guna 
    // date : 15-03-2025

    if (!is_null($user_id) && $source == 'list') {
      
            
        $wpdb->update(
            "{$wpdb->prefix}voucher_list",
            [
                'status' => 'Redeemed',
                'package_code'=> $package_code,
                'course_id' => $course_id,
                'customer_order_id' => $customer_order_id,
                'redeemed_to' => $user,
                'redeemed_date' => $order_date,
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if (!empty($ids)) {
            $ids_list = implode(',', $ids);  
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}voucher_individual 
                     SET status = %s 
                     WHERE id IN ($ids_list)",
                    'processed'
                )
            );
        }
    }


   }
