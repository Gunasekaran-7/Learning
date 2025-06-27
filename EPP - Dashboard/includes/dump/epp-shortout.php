<?php
	global $wpdb;
	$user_ID = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
	if (!empty($user_ID)) { 
		$user_ids_string = implode(',', $user_ID);
		$usernames = $wpdb->get_results("SELECT user_login, user_registered, ID FROM {$wpdb->prefix}users WHERE ID IN ($user_ids_string)");
		$dataPoints = [];
		$years = [];
		$practice_average_val = $diagnostic_average_val = $module_average_val = $check_average_val = array();
		error_log(print_r(['start of finding average' => date('d-m-Y H:i:s')], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
		foreach ($usernames as $usernames_array_value) {
		$years[] = date('Y', strtotime($usernames_array_value->user_registered));
			$get_username_search_val = (array)$usernames_array_value;
			$candidate_search_value = $get_username_search_val['user_login'];
			if ($candidate_search_value != '') {
				$course_search_val = $get_username_search_val['user_login'];
				$ID = $get_username_search_val['ID'];
				$query_results = $wpdb->get_results("SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = '_canvas_user_id' AND user_id = $ID");
				//$query_results = $wpdb->get_results("SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = '_canvas_user_id' AND user_id IN ($user_ids_string)");
				$canvas_user_ids = array();
				if(!empty($query_results)){
				foreach ($query_results as $result) {
					$canvas_user_ids[] = $result->meta_value;
				} }
				$canvas_user_id = implode(',', $canvas_user_ids);
				$product_ids = array();
				$customer_orders = wc_get_orders(array(
					'customer' => $ID,
					// 'status' => array('completed', 'processing'),
					//'status' => array_keys(wc_get_order_statuses()), 
					'status' => array_diff( array_keys( wc_get_order_statuses() ), array('wc-failed') ),
				));
				$practice_average = '';
				$diagnostic_average = '';
				$module_average = '';
				$check_average = '';
				foreach ($customer_orders as $order) {
					$items = $order->get_items();
					foreach ($items as $item) {
						$product_id = $item->get_product_id();
						$product = wc_get_product($product_id);
						$canvas_course_id = get_post_meta($product_id, 'course_id', true);
						$canvas_course_ids = explode(',', $canvas_course_id);
						$is_bundle = count($canvas_course_ids) > 1;
						foreach ($canvas_course_ids as $canvas_course_single_id) {
							$show_diagnostic_report_inner = true;
							$findquery = $wpdb->prepare(
								"SELECT * FROM {$wpdb->prefix}canvas_data 
								WHERE course_user_id = %s
								AND course_id = %d 
								AND quiz_type in ('Diagnostic', 'Practice', 'Module', 'Check') 
								ORDER BY title ASC",
								$canvas_user_id,
								$canvas_course_single_id
							);
							$findresults = $wpdb->get_results($findquery, ARRAY_A);
							if (empty($findresults)) {
								if (!$is_bundle) {
									$show_diagnostic_report_inner = false;
								}
								$error_msg = 'Data not available.';
							}
							$catGroups = $timeGroup = array();
							foreach ($findresults as $find) {
								$mcateory = trim($find['main_category']);
								$submitted = strtotime($find['submitted_or_assessed_at']);
								$timeGroup[$submitted] = $find['submitted_or_assessed_at'];
								$catGroups[$submitted][$mcateory]['name'] = $mcateory;
								$catGroups[$submitted][$mcateory]['percentage'][] = $find['percent'] * 100;
								$catGroups[$submitted][$mcateory]['subcats'][] = $find;
								$catGroups[$submitted][$mcateory]['submitted_or_assessed_at'] = $find['submitted_or_assessed_at'];
								$catGroups[$submitted][$mcateory]['display_name'] = $find['display_name'];
								$catGroups[$submitted][$mcateory]['quiz_type'] = $find['quiz_type'];
							}
							$attemptGroup = array_values($catGroups);
							ksort($timeGroup);
							$first_module_attempt_found = false;
							$first_check_attempt_found = false;
							foreach ($timeGroup as $timekey => $timevalue) {
								$diagnostic_data_total = $practice_data_total = $module_data_total = $check_data_total = array();
								foreach ($catGroups[$timekey] as $res) {
									if ($res['quiz_type'] == 'Diagnostic') {
										$diagnostic_data_total[] = array_sum($res['percentage']) / count($res['percentage']);
									} elseif ($res['quiz_type'] == 'Practice') {
										$practice_data_total[] = array_sum($res['percentage']) / count($res['percentage']);

									} elseif ($res['quiz_type'] == 'Check' && !$first_check_attempt_found) {
										$check_data_total[] = array_sum($res['percentage']) / count($res['percentage']);
										$check_average = round(array_sum($check_data_total) / count($check_data_total));
										$first_check_attempt_found = true;
									} elseif ($res['quiz_type'] == 'Module' && !$first_module_attempt_found) {
										$module_data_total[] = array_sum($res['percentage']) / count($res['percentage']);
										$module_average = round(array_sum($module_data_total) / count($module_data_total));
										$first_module_attempt_found = true;
									}
								}
								if (count($diagnostic_data_total) > 0) {
									$diagnostic_average = round(array_sum($diagnostic_data_total) / count($diagnostic_data_total));
								}
								if (count($practice_data_total) > 0) {
									$practice_average = round(array_sum($practice_data_total) / count($practice_data_total));
								}
								if ($first_module_attempt_found && $first_check_attempt_found) {
									break; // Exit loop if both first attempts are found
								}
							}
						}
					}
				}
				if (!empty($diagnostic_average)) {
					$diagnostic_average_val[] = $diagnostic_average;
				}
				if (!empty($practice_average)) {
					$practice_average_val[] = $practice_average;
				}
				if (!empty($module_average)) {
					$module_average_val[] = $module_average;
				}
				if (!empty($check_average)) {
					$check_average_val[] = $check_average;
				}
			}
		}

		error_log(print_r(['End of finding average' => date('d-m-Y H:i:s')], true), 3, WP_CONTENT_DIR . '/uploads/error.log');

		if (!empty($diagnostic_average_val)) {
			$diagnostic_average_value = round(array_sum($diagnostic_average_val) / count($diagnostic_average_val)) . '%';
		} else {
			$diagnostic_average_value = 'N/A';
		}
		if (!empty($practice_average_val)) {
			$practice_average_value = round(array_sum($practice_average_val) / count($practice_average_val)) . '%';
		} else {
			$practice_average_value = 'N/A';
		}
		if (!empty($module_average_val)) {
			$module_average_value = round(array_sum($module_average_val) / count($module_average_val)) . '%';
		} else {
			$module_average_value = 'N/A';
		}
		if (!empty($check_average_val)) {
			$check_average_value = round(array_sum($check_average_val) / count($check_average_val)) . '%';
		} else {
			$check_average_value = 'N/A';
		}
		$year_counts = array_count_values($years);
		$user_counts = count($years);
		$start_year = min($years);
		$end_year = max($years);
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

	
  $htmlout	= '<div class="perform_div_sub_div">
						<div class="cust_candi_perform_div_sub_div">
							<p class="cust_candi_perform_div_sub_div_lable"> Diagnostic Test</p>
							<p id="diagnostic_average_value" class="cust_candi_perform_div_sub_div_val"><?php echo $diagnostic_average_value; ?></p>
						</div>
					</div>
					<div class="perform_div_sub_div">
						<div class="cust_candi_perform_div_sub_div">
							<p class="cust_candi_perform_div_sub_div_lable">Module Quizzes - First Attempt</p>
							<p id="module_average_value" class="cust_candi_perform_div_sub_div_val" ><?php echo $module_average_value; ?></p>
						</div>
					</div>
					<div class="perform_div_sub_div">
						<div class="cust_candi_perform_div_sub_div">
							<p class="cust_candi_perform_div_sub_div_lable">Practice Test</p>
							<p id="practice_average_value" class="cust_candi_perform_div_sub_div_val" ><?php echo $practice_average_value; ?></p>
						</div>
					</div>
					<div class="perform_div_sub_div">
						<div class="cust_candi_perform_div_sub_div">
							<p class="cust_candi_perform_div_sub_div_lable">Check for Understanding - First Attempt</p>
							<p id="check_average_value" class="cust_candi_perform_div_sub_div_val" ><?php echo $check_average_value; ?></p>
						</div>
					</div>';
					$response = array(
						'htmlouts' => $diagnostic_average_value,
						'dataPoints' => $dataPoints,
					);				
	 echo json_encode($response);
    wp_die(); // Required to terminate immediately and return a proper response
