<?php
	global $wpdb;

	$user_id = get_current_user_id();

	//$course_id = isset($_REQUEST['course_name']) ? $_REQUEST['course_name'] : '';
	$range_from = isset($_REQUEST['range_from']) ? $_REQUEST['range_from'] : '';    
	$range_to = isset($_REQUEST['rang_to']) ? $_REQUEST['rang_to'] : '';    
	
	$where_clause = "";

	if (!empty($range_from) && !empty($range_to)) {
		$where_clause = $wpdb->prepare("WHERE DATE(redeemed_date) BETWEEN %s AND %s", $range_from, $range_to);
	} elseif (!empty($range_from)) {
		$where_clause = $wpdb->prepare("WHERE DATE(redeemed_date) >= %s", $range_from);
	} elseif (!empty($range_to)) {
		$where_clause = $wpdb->prepare("WHERE DATE(redeemed_date) <= %s", $range_to);
	}

	// To retrieve the package code based on the start and end date in Voucher_list table
	
	$query = "SELECT id, package_code FROM {$wpdb->prefix}voucher_list $where_clause";
	$get_course_search = $wpdb->get_results($query, ARRAY_A);
	
	// To retrieve the package code based on the start and end date of the current user orders

	$query = "
	SELECT DISTINCT 
		GROUP_CONCAT(DISTINCT oim.meta_value) AS product_ids,
		GROUP_CONCAT(DISTINCT pm2.meta_value) AS pearson_product_ids
	FROM {$wpdb->prefix}postmeta AS pm
	INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = pm.post_id 
		AND p.post_status NOT IN ('wc-failed', 'wc-pending')
	LEFT JOIN {$wpdb->prefix}woocommerce_order_items o ON o.order_id = p.ID 
		AND o.order_item_type = 'line_item'
	LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON o.order_item_id = oim.order_item_id 
		AND oim.meta_key = '_product_id'
	LEFT JOIN {$wpdb->prefix}postmeta AS pm2 ON pm2.post_id = oim.meta_value 
		AND pm2.meta_key = 'pearson_product_id'
	WHERE pm.meta_key = '_customer_user'
	AND pm.meta_value = %d
	AND pm.meta_value IS NOT NULL
	";
	
	if (!empty($range_from) && !empty($range_to)) {
		$query .= " AND DATE(p.post_date) BETWEEN %s AND %s";
		$find_orders_query = $wpdb->prepare($query, $user_id, $range_from, $range_to);
	} elseif (!empty($range_from)) {
		$query .= " AND DATE(p.post_date) >= %s";
		$find_orders_query = $wpdb->prepare($query, $user_id, $range_from);
	} elseif (!empty($range_to)) {
		$query .= " AND DATE(p.post_date) <= %s";
		$find_orders_query = $wpdb->prepare($query, $user_id, $range_to);
	} else {
		$find_orders_query = $wpdb->prepare($query, $user_id);
	}
	
	$results = $wpdb->get_results($find_orders_query);

	 

	$all_pearson_ids = implode(',', array_column($results, 'pearson_product_ids'));
 
    $package_code_lists = array_unique(array_filter(explode(',', $all_pearson_ids)));

	$package_code_list = array_column($get_course_search, 'package_code');
 
	$merged_package_codes = array_filter(array_unique(array_merge($package_code_list, $package_code_lists)));
	

    // To Retrieves the post ID based on the package code.
	if (!empty($merged_package_codes)) {

		$placeholders = implode(',', array_fill(0, count($merged_package_codes), '%s'));
	
		$product_package_code = 'pearson_product_id';
		$query = $wpdb->prepare(
			"SELECT pm.post_id 
			FROM {$wpdb->postmeta} pm 
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
			WHERE pm.meta_key = %s 
			AND pm.meta_value IN ($placeholders) 
			AND p.post_status = 'publish'",
			array_merge([$product_package_code], $merged_package_codes)
		);
	
		$results_product = $wpdb->get_results($query);
	
		$product_names = [];
		foreach ($results_product as $product) {
			$product_obj = wc_get_product($product->post_id);
			if ($product_obj) {
				$product_names[$product->post_id] = $product_obj->get_name();
			}
		}
	
		if (!empty($product_names)) { ?>
		<div class='container_covers_ajax'>
			<table id="container_covers" class="container_covers">
				<thead>
					<tr>
						<th>S.No</th>
						<th>Course Name</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					foreach ($product_names as $course_key => $course_search_array_value) { ?>
						<tr>
							<td><?php echo $i; ?></td>
							<td>
								<a class="custom_test_list" 
								   data-course_name="<?php echo esc_attr($course_search_array_value); ?>" 
								   data-course_id="<?php echo esc_attr($course_key); ?>" 
								   style="cursor:pointer;">
									<?php echo esc_html($course_search_array_value); ?>
								</a>
							</td>
						</tr>
					<?php
						$i++;
					} ?>
				</tbody>
			</table>
			</div>

		<?php } else { ?>
			<p> No courses found. </p>
		<?php }  
	}
	?>
	