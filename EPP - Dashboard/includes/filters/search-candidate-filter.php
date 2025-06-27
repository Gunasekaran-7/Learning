<?php
	global $wpdb;

	$range_from = isset($_REQUEST['startdate']) ? $_REQUEST['startdate'] : '';	
	$range_to = isset($_REQUEST['enddate']) ? $_REQUEST['enddate'] : '';

 
	//$candidate_name = isset($_REQUEST['candidate_name']) ? $_REQUEST['candidate_name'] : '';
 
	
	$current_user = wp_get_current_user();

	$userid = $current_user->ID;
 
	
	
	global $wpdb;
	
	
	$get_data = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, redeemed_to, redeemed_date, course_id, status
			FROM `{$wpdb->prefix}voucher_list`
			WHERE user_id = %d
			AND status = 'Redeemed'",
			$userid
		),
		ARRAY_A
	);
	
	$redeem_user_count = $course_id = $redeem_canvas_user = $courses_by_user = $courses = [];

	
 
	
	foreach ($get_data as $data) {

	 
		$order_date = $data['redeemed_date'];
		$order_status = $data['status'];
	
		 

		$redeem_user = $data['redeemed_to'];
			 

		$courses = is_array($data['course_id']) ? $data['course_id'] : [$data['course_id']];

		$course_id[$redeem_user] = isset($course_id[$redeem_user]) 
        ? array_merge($course_id[$redeem_user], $courses) 
        : $courses;

		 

		$redeem_canvas_user[] = get_user_meta($redeem_user, '_canvas_user_id', true);
		 
	
		$redeem_user_count[] = $redeem_user;
	}
	
	$course_id = array_map(function($courses) {
		return implode(', ', array_unique(array_reduce($courses, function($carry, $course) {
			return array_merge($carry, array_map('trim', explode(',', $course)));
		}, [])));
	}, $course_id);
	
	// echo'<pre>';
	// print_r($get_data);
	// echo'</pre>';

 
	
	$redeem_user_count[] = $userid;

	$redeem_canvas_user[] = get_user_meta($userid, '_canvas_user_id', true);
	
	$newquery = $wpdb->prepare(
		"SELECT 
			GROUP_CONCAT(DISTINCT pm1.meta_value) AS course_ids
		FROM wp_posts AS p
		LEFT JOIN wp_postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = '_customer_user'
		LEFT JOIN wp_woocommerce_order_items o ON o.order_id = p.ID
		LEFT JOIN wp_woocommerce_order_itemmeta oim ON o.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
		LEFT JOIN wp_postmeta pm1 ON pm1.post_id = oim.meta_value AND pm1.meta_key = 'canvas_course_id'
		WHERE p.post_type = 'shop_order'
		AND p.post_status NOT IN ('wc-failed', 'wc-pending')
		AND pm.meta_key = '_customer_user'
		AND pm.meta_value = %d",
		$user_id
	);
	
	$result = $wpdb->get_var($newquery);
	
	if (!empty($result)) {
		$query_course_ids = explode(',', $result);
		$formatted_result = array($user_id => implode(',', $query_course_ids));
		$courses_by_user = array_replace($course_id, $formatted_result);
	}
	
	$all_pro_test = implode(',', array_unique(array_filter($courses_by_user)));

	// echo'<pre>';
	// print_r($courses_by_user);
	// echo'</pre>';
	
	$all_datas = $wpdb->get_results("SELECT 
		quiz_type, 
		course_user_id as canvas_user,
		SUM(score) as Score, 
		SUM(percent) as Percent, 
		AVG(score) as score_avg, 
		ROUND(AVG(percent)*100, 0) as percent_score 
	FROM wp_canvas_data 
	WHERE quiz_type IN ( 'Diagnostic', 'Practice', 'Module' )
	AND course_id IN (" . $all_pro_test . ") 
	AND course_user_id IN (" . implode(',',array_filter($redeem_canvas_user)) . ")
	GROUP BY course_user_id, quiz_type", ARRAY_A);
	
	$newgroup = array();
	foreach ($all_datas as $uk => $uv) {
		if (empty($uv['canvas_user'])) {
			continue;
		}
		$newgroup[$uv['canvas_user']][$uv['quiz_type']] = $uv;
	}
	
	$all_assignment_query = "SELECT course_id, COUNT(id) AS total_count
		FROM wp_canvas_assignments
		WHERE course_id IN ($all_pro_test) 
		AND type = 'Check'
		GROUP BY course_id";
	 
	
	$all_assignment_result = $wpdb->get_results($all_assignment_query);
	
	$all_assignments = array();
	if (!empty($all_assignment_result)) {
		//$all_assignments = array_column($all_assignment_result, 'total_count', 'course_id');
		$all_assignments = array_map('intval', array_column($all_assignment_result, 'total_count', 'course_id'));

	}
 

	$query_assignment_attended_query = "SELECT canvas_user_id, COUNT(DISTINCT id) AS attended_count 
	FROM wp_canvas_check_test WHERE canvas_user_id IN (" . implode(',', array_filter(array_unique($redeem_canvas_user))) . ") GROUP BY canvas_user_id";
	
	$query_assignment_attended = $wpdb->get_results($query_assignment_attended_query, ARRAY_A);
	$attended_assignment = array();
	if (!empty($query_assignment_attended)) {
		$attended_assignment = array_column($query_assignment_attended, 'attended_count', 'canvas_user_id');
	}
	

	$where_clause = "";

   if (!empty($redeem_user_count) && is_array($redeem_user_count)) {
    $user_ids_string = implode(',', array_map('intval', $redeem_user_count));
    $where_clause = "WHERE ID IN ($user_ids_string)";
   }

	if (!empty($range_from) && !empty($range_to)) {

		$range_from_start = $range_from . " 00:00:00";
		$range_to_end = $range_to . " 23:59:59";
		$where_clause .= " AND user_registered BETWEEN '$range_from_start' AND '$range_to_end'";
		
	}

 
	$usernames = $wpdb->get_results("SELECT user_login, ID FROM {$wpdb->prefix}users $where_clause");
 
 
?>
	<!-- <div class='username_container'> -->
	<table  class="table table-striped table-condensed"  id="tblData_candidate_new">
		<thead>
			<tr>
				<th>Name</th>
				<th class="tab2">Avg Diagnostic</th>
				<th class="tab2">Avg Practice</th>
				<th class="tab2">Module</th>
				<th class="tab2">check</th>
				
			</tr>
		</thead>
		<tbody>
		<?php 
		 	foreach ($usernames as $usernames_array_value) {

				$get_username_search_val = (array)$usernames_array_value;

				$candidate_search_value = $get_username_search_val['user_login'];

				if ($candidate_search_value != '') {

					$course_search_val = $get_username_search_val['user_login'];

					$ID = $get_username_search_val['ID'];

					$first_name = get_user_meta($ID, 'first_name', true);
					$last_name = get_user_meta($ID, 'last_name', true);
					$full_name = $first_name.' '.$last_name;
					if(empty($first_name && $last_name)){
						$full_name = $course_search_val;
					}
					$canvas_user_id = get_user_meta($ID, '_canvas_user_id', true);
					$product_ids = array();
					$practice_average = [];
					$diagnostic_average_val2 = [];
					$module_average = [];
					$practice_average_val2 =[];
					$check_average = array();
					$assignment_count_total_outer = array();

					$newcanvas_array = array();

			 
															 
					if (empty($diagnostic_average_val2)) {
						$diagnostic_average_val = 'N/A';
					} else {
						$diagnostic_average_val = round(array_sum($diagnostic_average_val2) / count($diagnostic_average_val2)) . '%';
					}
					if (empty($practice_average_val2)) {
						$practice_average_val = 'N/A';
					} else {
						$practice_average_val = round(array_sum($practice_average_val2) / count($practice_average_val2)) . '%';
					}
					if (empty($module_average)) {
						$module_average_val = 'N/A';
					} else {
						$module_average_val = round(array_sum($module_average) / count($module_average)) . '%';
					}

					$total_count = 0;
					$total_count_array = array();
					
						 
					if (isset($courses_by_user[$ID])) {

						$fnal_course_user_process = explode(',', $courses_by_user[$ID]);

						$final_to_process_for_user = array_filter(array_unique($fnal_course_user_process));
						 
						foreach ($final_to_process_for_user as $each) {

							$each = (int) $each;

						 
							if (!isset($all_assignments[$each])) {
								 
								continue;
							}
							 
							$assignment_count = $all_assignments[$each];

							$total_count += $assignment_count;

							$total_count_array[$each] = $assignment_count;


						}
						 
					}

					$diagnostic_average_val = 'N/A';
					$practice_average_val = 'N/A';
					$module_average_val = 'N/A';

					if (isset($newgroup[$canvas_user_id]['Diagnostic'])) {
						$diagnostic_average_val = $newgroup[$canvas_user_id]['Diagnostic']['percent_score'].'%';
					}

					if (isset($newgroup[$canvas_user_id]['Practice'])) {
						$practice_average_val =  $newgroup[$canvas_user_id]['Practice']['percent_score'].'%';
					}

					if (isset($newgroup[$canvas_user_id]['Module'])) {
						$module_average_val =  $newgroup[$canvas_user_id]['Module']['percent_score'].'%';
					}
					

					$attend_count = 0;
					if(isset($attended_assignment[$canvas_user_id])){
						$attend_count = $attended_assignment[$canvas_user_id];
					}
					
					$total_assignments = !empty($total_count_array) ? array_sum($total_count_array) : 0;

					$check_average_val = empty($attend_count) ? "0/$total_assignments" : "$attend_count/$total_assignments";

					?>
			
					<tr>
						<td>
							<a class="cus_candidate_option" data-ID="<?php echo $ID; ?>" data-candidate_name="<?php echo $course_search_val; ?>" style="cursor:pointer;"> <?php echo $full_name; ?></a>
						</td>
						<td class="tab1"><?php echo $diagnostic_average_val; ?></td>
						<td class="tab1"><?php echo $practice_average_val; ?></td>
						<td class="tab1"><?php echo $module_average_val; ?></td>
						<td class="tab1"><?php  echo $check_average_val; ?></td>

					</tr>
			
					<?php
				}
			}		
		?>	
		</tbody>
	</table>
<!-- </div> -->
<script>
	jQuery(document).ready(function(){
		jQuery('#tblData_candidate_new').DataTable({
			colReorder: true,
			responsive: true,   
			pagingType: 'simple_numbers',
			paging: true,
			language: {
				paginate: {
					previous: 'Previous',
					next: 'Next'
				}
			},
		});
	});