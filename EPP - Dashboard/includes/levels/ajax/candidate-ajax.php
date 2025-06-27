<?php

		global $wpdb;

       $user_id - get_current_user_id();

		$ID = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : '';
		$canvas_user_id = get_user_meta($ID, '_canvas_user_id', true);
 
		$candidate_name = isset($_REQUEST['candidate_name'])?$_REQUEST['candidate_name']:'';
		$candi_first_name = get_user_meta($ID,'first_name',true);
		$product_ids = array();
		

        if( $ID == $user_id ){

			$find_orders_query = $wpdb->prepare("SELECT DISTINCT oim.meta_value AS product_id, p2.post_title AS product_name
			FROM {$wpdb->prefix}postmeta AS pm
			INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = pm.post_id 
				AND p.post_status NOT IN ('wc-failed', 'wc-pending')
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items o 
				ON o.order_id = p.ID AND o.order_item_type = 'line_item'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim 
				ON o.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
			LEFT JOIN {$wpdb->prefix}posts AS p2 
				ON p2.ID = oim.meta_value 
			WHERE pm.meta_key = '_customer_user'
			AND pm.meta_value = %d
			AND pm.meta_value IS NOT NULL
			", $ID);
		
			$order_results = $wpdb->get_results($find_orders_query);
			
			if (!empty($order_results)) {
				foreach ($order_results as $row) {
					$product_names[$row->product_id] = $row->product_name; 
				}
			}

	    }else{
			$get_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id,redeemed_date, package_code, status
					FROM {$wpdb->prefix}voucher_list
					WHERE redeemed_to = %d
					AND status = 'Redeemed'",
					$ID
				),
				ARRAY_A
			);
	    }
 

		$package_code = [];
	
		foreach ($get_data as $data) {
			if (!empty($data['package_code'])) {
				$package_code[] = $data['package_code'];
			}
		}
	
		$product_package_code = 'pearson_product_id';
	
		if (!empty($package_code)) {
			$placeholders = implode(',', array_fill(0, count($package_code), '%s'));
			$query = $wpdb->prepare(
				"SELECT pm.post_id 
				FROM {$wpdb->postmeta} pm 
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
				WHERE pm.meta_key = %s 
				AND pm.meta_value IN ($placeholders) 
				AND p.post_status = 'publish'",
				array_merge([$product_package_code], $package_code)
			);
	
			$results_product = $wpdb->get_results($query);
		}  
	
		$all_assignment_query = "SELECT course_id, COUNT(id) AS total_count FROM wp_canvas_assignments WHERE type = 'Check' GROUP BY course_id";
		$all_assignment_result = $wpdb->get_results($all_assignment_query, ARRAY_A);
        
		$all_assignments = array();
		if(!empty($all_assignment_result)){
			$all_assignments = array_column($all_assignment_result, 'total_count', 'course_id');
		}

		?>
		<div class="main_candi_course_name">
			<div class="sub_candidate_name">
				<label class="lable_sub1">Candidate:</label>
				<p class="main_candi_head"><?php echo $candi_first_name; ?></p>
			</div>
			<div>
				<button class = "coursename_back"> Back </button>
				<button class="reports_back1"><a class="txt-deco" href="<?php echo $url.'/portal/' ;?>" >Back to Portal</a></button>
			</div>
		</div>
		<?php
	    $product_names =  array();

		
		foreach ($results_product as $product) {
			$product_obj = wc_get_product($product->post_id);
			$product_names[$product->post_id] = $product_obj ? $product_obj->get_name() : '';
		}
	 
		 

		$check_data_total = array();
		foreach($product_names as $product_id_key => $product_id_val){
			
			$canvas_course_id = get_post_meta($product_id_key, 'canvas_course_id', true);
			$canvas_course_ids = explode(',', $canvas_course_id);
			foreach ($canvas_course_ids as $canvas_course_single_id) {

				$show_diagnostic_report_inner = true;
				$findquery = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}canvas_data
					WHERE course_user_id = %d
					AND course_id = %d 
					AND quiz_type in ('Diagnostic','Practice','Module','Check') 
					ORDER BY title ASC",
					$canvas_user_id,
					$canvas_course_single_id
				);
			
				$findresults = $wpdb->get_results($findquery, ARRAY_A);
				if (empty($findresults)) {
		
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
					$catGroups[$submitted][$mcateory]['quiz_name'] = $find['quiz_name'];
				}
				$attemptGroup = array_values($catGroups);
				ksort($timeGroup);
				$first_module_attempt_found = false;
				$first_check_attempt_found = false;
                 
 
					foreach ($timeGroup as $timekey => $timevalue) {
					$diagnostic_data_total = $practice_data_total = $module_data_total = array();
					foreach ($catGroups[$timekey] as $res) {
						 if ($res['quiz_type'] == 'Module') {
							$check_data_total[$product_id_key][$res['quiz_name']][$res['name']] = array_sum($res['percentage']) / count($res['percentage']);
							//$check_data_total2[$product_id_key][$res['quiz_name']][$res['name']] = array_sum($res['percentage']) / count($res['percentage']);
							
						}
					}

				}
			}
			
		}

		$check_data_total_all = array();
		foreach ($check_data_total as $check_data_total_key => $check_data_total_value) {
			foreach ($check_data_total_value as $section_key => $section_value) {
				$check_data_total_all[$check_data_total_key][$section_key] = round(array_sum($section_value)/count($section_value));
			}
		}
		 
		if (!empty($product_names)) {
			 
				
			?>
		 
			<?php
		 
				echo '<table id="fixed_column_table" class="table table-striped table-condensed">';
				echo '<thead>';
				echo '<tr>';
				echo "<th>Course Name</th>";
				echo "<th>Check</th>";

				$allHeaders = array();
				foreach ($check_data_total_all as $domains) {
					$allHeaders = array_unique(array_merge($allHeaders, array_keys($domains)));
				}

				foreach ($allHeaders as $header) {
					echo "<th>$header</th>";
				}
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
			 
				foreach ($product_names as $courseId => $courseName) {

					$canvas_course_key = get_post_meta($courseId, 'canvas_course_id', true);
					 
					 
			        $canvas_course_keys = explode(',', $canvas_course_key);
					$titles_course = array();
					$assignment_counts = [];
					$each_course_plus = $total_assignments = 0;
					 
					foreach ($canvas_course_keys as $canvas_course_single_key) {
						 
			           
 
							$finddata = "SELECT course_id, COUNT(id) as count FROM wp_canvas_check_test WHERE canvas_user_id = ".$canvas_user_id." GROUP BY course_id";
							$finddb = $wpdb->get_results($finddata, ARRAY_A);

							 
							$each_course_check = array();
							if(!empty($finddb)){
								$each_course_check = array_column($finddb, 'count', 'course_id');
								$each_course_plus += $each_course_check[$canvas_course_single_key];
							}
							//$course_assignment_counts = array();
							$total_assignments += isset($all_assignments[$canvas_course_single_key]) ? $all_assignments[$canvas_course_single_key] : 0;

							 
				    }	
				 

				 
					 
					echo '<tr>';
					echo '<td>
						<a class="c_course" value="' . esc_attr($courseName) . '" data-userid_new="' . esc_attr($ID) . '" data-product-id="' . esc_attr($courseId) . '" data-user-id="' . esc_attr($canvas_user_id) . '" data-candidate_name="' . esc_html($candidate_name) . '" data-product_name="' . esc_html($courseName) . '" data-course_id="' . esc_attr($canvas_course_key) . '">' . esc_html($courseName) . '</a>
					</td>';
					echo '<td>';
					 
					echo $each_course_plus.'/'.$total_assignments;
					 
			
					echo '</td>';
					 
					foreach ($allHeaders as $header) {
						if (isset($check_data_total_all[$courseId][$header])) {
							echo "<td>{$check_data_total_all[$courseId][$header]}</td>";
						} else {
							echo "<td><i class='bx bx-minus candidate_c_course_icon'></i></td>";
						}
					}

					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
		}
		else{
			$error_msg = 'Data not available. ';
			echo '<div style="text-align:center;color:#ed6c47;margin-bottom:30px;border:1px solid #ccc;">'.$error_msg.'</div>';
		}
		 
		?>
		<script>
			jQuery(document).ready(function() {
				

				jQuery('#fixed_column_table').DataTable({
					fixedColumns: true,
					paging: false,
					scrollCollapse: true,
					scrollX: true,
					scrollY: 300
				});
			});
		</script>
		<?php
		// include('candidate-course-name.php ');
		wp_die();
	
