<?php
$id_token = '22540~6D26FpdQnxn5zTchvq0fro5Q3OV41qFv24z4OGSreftuqzXs7tvyd8mqbsRW1Y3t'; 
$args = array(
    'headers'     => array(
        'Authorization' => 'Bearer ' . $id_token,
    ),
);
global $wpdb;
$table_name = $wpdb->prefix . 'canvas_data_latest';
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// $customer_orders = get_posts(
//     apply_filters(
//         'woocommerce_my_account_my_orders_query',
//         array(
//             'numberposts' => -1,
//             'meta_key'    => '_customer_user',
//             'meta_value'  => $user_id,
//             'post_type'   => wc_get_order_types( 'view-orders' ),
//             'post_status' => array_diff( array_keys( wc_get_order_statuses() ), array('wc-failed') ),   // shorter step agust 2024
//         )
//     )
// );

// $user_ID = array();
// foreach ($customer_orders as $customer_order) {
//     $order = wc_get_order( $customer_order );
//     $items = $order->get_items();
//     foreach ($items as $order_item) {
//         $product_name = $order_item->get_name();
//         $order_id_value = $order->get_id();
//         $gift_card_coupons = $order_item->get_meta('_ph_gift_card_product__coupons');
//         if ( !empty($gift_card_coupons) ) {
//             $finals .= ',"'.implode('","',$gift_card_coupons).'"';
//         }
//     }
// }

$user_orders_only_vouchers = "SELECT 
pm.meta_value AS user_id, 
pm.post_id AS order_id,
p.post_status,
oim.meta_value AS gifts
FROM wp_posts AS p 
LEFT JOIN wp_postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = '_customer_user'
LEFT JOIN wp_woocommerce_order_items o on o.order_id = p.ID
LEFT JOIN wp_woocommerce_order_itemmeta oim on o.order_item_id = oim.order_item_id and oim.meta_key = '_ph_gift_card_product__coupons'
WHERE p.post_type = 'shop_order'
AND p.post_status NOT IN ('wc-failed','wc-pending')
AND pm.meta_key = '_customer_user'
AND pm.meta_value = ".$userid."
AND oim.meta_value IS NOT NULL
GROUP BY pm.post_id";

$user_orders_only_vouchers_result = $wpdb->get_results($user_orders_only_vouchers, ARRAY_A);

$gifts = array();
foreach($user_orders_only_vouchers_result as $new){
    foreach(unserialize($new['gifts']) as $gif){
        $gifts[] = $gif;
    }
}

$process = $wpdb->get_results("SELECT 
YEAR(p.post_date) AS yr,
LPAD(MONTH(p.post_date),2,'0') AS mon,
count(p.post_title) AS Total,
GROUP_CONCAT(pm2.meta_value) AS uids
FROM wp_posts p
LEFT JOIN wp_postmeta pm on pm.post_id = p.ID and pm.meta_key = 'is_assigned'
LEFT JOIN wp_postmeta pm1 on pm1.post_id = p.ID and pm1.meta_key = 'usage_count'
LEFT JOIN wp_postmeta pm2 on pm2.post_id = p.ID and pm2.meta_key = '_used_by'
WHERE 
p.post_type = 'shop_coupon' AND p.post_status = 'publish' AND
p.post_title IN ('".implode("','",$gifts)."')
GROUP BY YEAR(p.post_date), MONTH(p.post_date)", ARRAY_A);
	
foreach($process as $pro){
    $users_list = $pro['uids'];
    if(!empty($users_list)){
        foreach(explode(',', $users_list) as $uus){
            if(!is_numeric($uus)){ continue; }
            $user_ID[] = $uus;
        }
    }
}
	 
$user_ID[] = $user_id;
				
$user_meta_value =  implode(',', $user_ID);
        
$user_meta_list = $wpdb->get_results(
    "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE user_id IN ($user_meta_value) AND meta_key = 'user_type' AND meta_value IN ('School or District Representative', 'Individual', 'EPP Representative')"
);

$user_ids = array();
$product_name_array = array();
foreach ($user_meta_list as $user_meta) {
    $user_id = $user_meta->user_id;
    
    $canvas_user_id = get_user_meta($user_id, '_canvas_user_id', true);
    
    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        // 'status' => array('completed', 'processing'),
        //'status' => array_keys(wc_get_order_statuses()), 
        'status' => array_diff( array_keys( wc_get_order_statuses() ), array('wc-failed') ),   // shorter step agust 2024
    ));
    foreach ($customer_orders as $order) {
        $items = $order->get_items();
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            //error_log(print_r(['product_id' => $product_id], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
            $product = wc_get_product($product_id);
            if (is_a($product, 'WC_Product') && $product->get_status() === 'publish') {
                $val = $product->get_name();
                $course_id = get_post_meta($product_id, 'course_id', true);
                //error_log(print_r(['course_id' => $course_id], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
                $product_name_array[$product_id]['user_id'] = $canvas_user_id;
                $product_name_array[$product_id]['course_id'] = $course_id;
                // $categories = $product->get_category_ids();
                // foreach ($categories as $category_id) {
                // 	$category = get_term($category_id, 'product_cat');
                // 	if ($category && !is_wp_error($category)) {
                // 		$category_slug = $category->slug;

                // 		if ($category_slug == 'courses' || $category_slug == 'free-course') {
                // 			$val = $product->get_name();
                // 			$course_id = get_post_meta($product_id, 'course_id', true);
                // 		    //$product_name_array[$user_id][$val] = $course_id;
                // 		}
                // 	}
                // }
            }
        }
    }
}
		
		//$titles_courses = array();
		 
		foreach($product_name_array as $gp){
			//continue;
			$course_ids = explode(',',$gp['course_id']);
			$userids = $gp['user_id'];
			//error_log(print_r(['course_id' => $course_ids], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
			foreach($course_ids as $courseid){
				$gradebook_url = 'https://learnersedge.instructure.com/api/v1/courses/' . $courseid . '/gradebook_history/feed?user_id=' . $userids;
				$grade = wp_remote_get($gradebook_url, $args);
				$response_key = wp_remote_retrieve_response_code($grade);
				$response = json_decode(wp_remote_retrieve_body($grade), true);
				//error_log(print_r(['response' => $response], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
				if ($response_key == 200 && is_array($response)) {
					foreach ($response as $spot) {
                        if (strpos($spot['assignment_name'], 'Check') !== false && strpos($spot['assignment_name'], 'Understanding') !== false) {
						$outcome_assst_id = $spot['assignment_id'];
						$out_user = $spot['user_id'];
						error_log($out_user . '<br>', 3, WP_CONTENT_DIR . '/uploads/error.log');
						$outcome_assst = $spot['assignment_name'];
						$out_attem = $spot['attempt'];
						$out_post = str_replace(['T', 'Z'], '', $spot['posted_at']);
						$out_submit = str_replace(['T', 'Z'], '', $spot['submitted_at']);
						$table_names = $wpdb->prefix . "canvas_check";
                        if(!empty($spot['user_id']) && !empty($courseid)){
							$find_datas = $wpdb->get_results(
								$wpdb->prepare(
									"SELECT * FROM $table_names 
									WHERE canvas_user_id = %d 
									AND course_id = %d 
									AND assignment_id = %d",
									$spot['user_id'],
									$courseid,
									$spot['assignment_id']
								)
							);
					     }
						if(!empty($find_datas)){
						//do nothing
						}else{
						  if(!empty($out_user))	{
						  $wpdb->insert( $wpdb->prefix . "canvas_check", 
								array(
								'canvas_user_id' => $out_user,
								'course_id' => $courseid,
								'assignment_id' => $outcome_assst_id,
								'assignment_name' => $outcome_assst,
								'attempt' => $out_attem,
								'posted_at' => $out_post,
								'submitted_at' => $out_submit,
								), 
								array('%d', '%d', '%d', '%s', '%d', '%s', '%s')
							    );
							 }
						 } 
					}else{
						continue;
						}
						
					}
				}else{
					continue;
				}
				// print_r('cron start');
				 
				$getAllAssignment = 'https://learnersedge.instructure.com/api/v1/courses/'.$courseid.'/assignments/?per_page=100';
				$result_assignments = wp_remote_get( $getAllAssignment, $args );
				$response_code_assignment = wp_remote_retrieve_response_code( $result_assignments );
				$response_body_assignment = json_decode(wp_remote_retrieve_body( $result_assignments ), true);
				$assignment_list = array_column($response_body_assignment, 'name', 'id');
				$remote_url = 'https://learnersedge.instructure.com/api/v1/courses/'.$courseid.'/outcome_results?per_page=100&include[]=outcomes&user_ids[]='.$userids;
				$result = wp_remote_get( $remote_url, $args );
				$response_code = wp_remote_retrieve_response_code( $result );
				$response_body = json_decode(wp_remote_retrieve_body( $result ), true);

			 
				if($response_code == 200){
					$outcome_results = $response_body['outcome_results'];
					if(empty($outcome_results)){ continue; }
					$outcome_list = $response_body['linked']['outcomes']; 
					$newoutcome = $newresult = array();
					foreach($outcome_list as $outcomes){
						$newoutcome[$outcomes['id']] = array('display_name'=>$outcomes['display_name'],'title'=>$outcomes['title']);
					}
					foreach($outcome_results as $or_lt){
						$assignment_id = explode('_',$or_lt['links']['alignment'])[1];
						$assignment_name = $assignment_list[$assignment_id];
					
						$quiz_type = '';
						if(strpos($assignment_name, 'Diagnostic') !== false){
							$quiz_type = 'Diagnostic';
						}elseif(strpos($assignment_name, 'Practice') !== false){
							$quiz_type = 'Practice';
						}
						// elseif(strpos($assignment_name,'Check') !== false){
						// 	$quiz_type = 'Check';
						// }
						elseif(strpos($assignment_name,'Quiz') !== false){
							$quiz_type = 'Module';
						}
						if($quiz_type == ''){
							continue;
						}
						$main_category_find = explode(' - ',$newoutcome[$or_lt['links']['learning_outcome']]['title']);
						$main_category_name = $main_category_find[1];
						$submitted_time = str_replace(array('T','Z'),array(' ',''),$or_lt['submitted_or_assessed_at']);
						//$submitted_time = $or_lt['submitted_or_assessed_at'];
						$newresult[$or_lt['links']['learning_outcome']] = array(
							'course_user_id' => $or_lt['links']['user'],
							'course_id' => $courseid,
							'quiz_id' => $assignment_id,
							'quiz_name' => $assignment_list[$assignment_id],
							'quiz_type' => $quiz_type,
							'outcome_id' => $or_lt['links']['learning_outcome'],
							'display_name' => $newoutcome[$or_lt['links']['learning_outcome']]['display_name'],
							'title' => $newoutcome[$or_lt['links']['learning_outcome']]['title'],
							'main_category' => $main_category_name,
							'score' => $or_lt['score'],
							'possible'=> $or_lt['possible'],
							'percent' => $or_lt['percent'],
							'submitted_or_assessed_at' => $submitted_time
						);
						$find_row = $wpdb->get_results('SELECT * FROM '.$table_name.' 
														WHERE course_user_id = '.$or_lt['links']['user'].' 
														AND course_id = '.$courseid.' 
														AND quiz_id = '.explode('_',$or_lt['links']['alignment'])[1].'
														AND outcome_id = '.$or_lt['links']['learning_outcome'].'
														AND submitted_or_assessed_at = "'.$submitted_time.'"'
													);   
							                                 
						if(empty($find_row)){
							$wpdb->insert( $table_name, 
											array(
												'course_user_id'=>$or_lt['links']['user'],
												'course_id' => $courseid,
												'quiz_id' =>  explode('_',$or_lt['links']['alignment'])[1],
												'quiz_name' => $assignment_name,
												'quiz_type' => $quiz_type,
												'outcome_id' => $or_lt['links']['learning_outcome'],
												'display_name' => $newoutcome[$or_lt['links']['learning_outcome']]['display_name'],
												'title' => $newoutcome[$or_lt['links']['learning_outcome']]['title'],
												'main_category' => $main_category_name,
												'score' => $or_lt['score'],
												'possible'=> $or_lt['possible'],
												'percent' => $or_lt['percent'],
												'submitted_or_assessed_at' => $submitted_time,
												), 
											array( '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%.2f', '%s') );

											//error_log(print_r(['$findrow' => $find_row], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
											
						}else{
							//error_log(print_r(['$newresult222' => $newresult], true), 3, WP_CONTENT_DIR . '/uploads/error.log');					
							$update_column = array(
								'display_name' => $newoutcome[$or_lt['links']['learning_outcome']]['display_name'],
								'title' => $newoutcome[$or_lt['links']['learning_outcome']]['title'],
								'main_category' => $main_category_name,
								'score' => $or_lt['score'],
								'possible'=> $or_lt['possible'],
								'percent' => $or_lt['percent'],
							);
							$update_values = array(
								'course_user_id'=>$or_lt['links']['user'],
								'course_id' => $courseid,
								'quiz_id' =>  explode('_',$or_lt['links']['alignment'])[1],
								'outcome_id' => $or_lt['links']['learning_outcome'],
								'submitted_or_assessed_at' => $submitted_time
							);
							$wpdb->update( $table_name, $update_column, $update_values );
							
						}

					}
				}
				else{
					//echo 'some error  - '.$response_code;
					if(!empty($response_code)){
						$to = array(
							//'nathan.estel@passagepreepp1.wpenginepowered.com',
							//'bailey.reilly@passagepreepp1.wpenginepowered.com',
							//'maurice.hamilton@k12coalition.com',
							//'mandy.turner@passagepreepp1.wpenginepowered.com',
							'vasudevan.parthasarathy@felixsolutions.ai'
						);
						$subject = 'Passage Preparation - ERROR: Canvas Api Failed to get outcome data.';
						$body = '<p>Date: '.date('m-d-Y H:i:s').'</p>';
						$body .= '<p>User Email: '.$user->user_email.'</p>';
						$body .= '<p>Course Id: '.$courseid.'</p>';
						$body .= '<p>Response Code: '.$response_code.'</p>';
						$body .= '<p>Response: '.$response_body.'</p>';
						$headers = array('Content-Type: text/html; charset=UTF-8');
						
					}
				}
				 
			}
		}
		$response['success'] = 'testing';
		ob_clean();
		//error_log(print_r(['response' => $response], true), 3, WP_CONTENT_DIR . '/uploads/error.log');					
		wp_send_json_success($response);
		wp_die();
	