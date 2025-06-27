<?php
		global $wpdb;
		$course_product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';
		$course_name = isset($_REQUEST['course_name']) ? $_REQUEST['course_name'] : '';
		$courseid = $course_product_id;
	
        
		$user_id = get_current_user_id();

		$get_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, redeemed_to, redeemed_date, status
				FROM `{$wpdb->prefix}voucher_list` 
				WHERE user_id = %d
				AND status = 'Redeemed'",
				$user_id
			),
			ARRAY_A
		);
	
		$redeem_user_count =  [];
	
		foreach ($get_data as $data) {
		
			$order_date = $data['redeemed_date'];
			$order_status = $data['status'];
	
			if( $data['redeemed_to'] != 'empty'){
				$redeem_user = $data['redeemed_to'];
			
			} else{
				continue;
			}
	
			$redeem_user_count[] = $redeem_user;
		}
	
	
		$redeem_user_count[] = $user_id;

		
		$user_ID = [];   

		if (!empty($redeem_user_count)) {
		
			foreach ($redeem_user_count as $user_single) {
				if (!is_numeric($user_single)) {
					continue;
				}
			
				$user_canvas = get_user_meta($user_single, '_canvas_user_id', true);

				if (!empty($user_canvas)) {
					$user_ID[] = $user_canvas;
				}
			}
		}

		$error_msg = '';
		$show_diagnostic_report = true;
		$check_product = wc_get_product($courseid);
	
		if(!$check_product){
			$show_diagnostic_report = false; $error_msg .= 'Course Id is not valid in the system<br/>';
		}elseif(empty($courseid)){
			$show_diagnostic_report = false; $error_msg .= 'Course Id is not valid<br/>';
		}
		if(empty($userid)){ $show_diagnostic_report = false; $error_msg .= 'User Id is not valid'; }
		if(empty($canvas_user_id)){ $show_diagnostic_report = false; $error_msg .= 'Canvas User Id is not valid'; }
		$canvas_course_id = get_post_meta($courseid,'canvas_course_id',true);
     
		$canvas_course_ids = explode(',',$canvas_course_id);
		if(count($canvas_course_ids) > 1){
			$is_bundle = true;
		}else{
			$is_bundle = false;
		}
		$percentageTotalDisplay_round = array();
		$percentage_round = '';
		foreach($canvas_course_ids as $canvas_course_single_id){
			
			 
			$show_diagnostic_report_inner = true;
			$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data
					WHERE course_user_id IN ('".implode("','",$user_ID)."')
					AND course_id = %d 
					AND quiz_type = %s 
					ORDER BY title ASC",  $canvas_course_single_id, 'Diagnostic');
			$findresults = $wpdb->get_results( $findquery, ARRAY_A );

			 
			if(empty($findresults)){
				if(!$is_bundle){
					$show_diagnostic_report_inner = false;
				}
				$error_msg = 'Data not available.';
			}
			$catGroups = array();
            //echo'<pre>';print_r($findresults);echo'</pre>';
			foreach($findresults as $find){
				$mcateory = trim($find['main_category']);
				$catGroups[$mcateory]['name'] = $mcateory;
				$catGroups[$mcateory]['percentage'][] = $find['percent']*100;
				$catGroups[$mcateory]['subcats'][] = $find;
				$catGroups[$mcateory]['possible'][] = $find['possible'];
				$catGroups[$mcateory]['score'][] = $find['score'];
			}
			if(!empty($findresults)){
				$percentageTotal = array();
				$scoreTotal = array();
				foreach($catGroups as $res){
					$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']);
				}
				$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
				$percentageTotalDisplay_round[] = array_sum($percentageTotal)/count($percentageTotal);
			}
			else{
				$percentageTotalDisplay_round[] = '';
			}
		}
		if(!empty($percentageTotalDisplay_round)){
		$percentage_round = array_sum($percentageTotalDisplay_round)/count($percentageTotalDisplay_round);
		}


		 
		?>
		<div class="main_course_test_list">
			<div class="main_course_l1">
				<label class="lable_l1">Course :</label>
		    	<p class="div_course_test_list"><?php echo $course_name ?></p>
			</div>
			<div>
				<button class="course_report_back">Back</button> 	 
				<button class="reports_back1"><a class='txt-deco' href="<?php echo $url.'/portal/' ;?>" >Back to Portal</a></button>
			</div>
		</div>
		<div class="dia-container">
			
			<div class="course_dia_attempt">
				<div class="course_diagnostic_report cus_dia_report_sub c1">
					<h3  data-course_id="<?php echo esc_attr($course_product_id);?>" style="cursor:pointer;">Diagnostic Report </h3>
					<div class="custom-dia-sub-container1 t1">
						<p>Total Diagnostic Assessment :</p>
						<p><?php echo round($percentage_round);?> %</p>
					</div>
					<i class="bx bxs-down-arrow cus_dia_attempt_icon1 d1"></i>
				</div>
				<div class='course_diagnostic_report_container'> 
					<?php
						foreach($canvas_course_ids as $canvas_course_single_id){
							$show_diagnostic_report_inner = true;
							
							$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data
											WHERE course_user_id IN ('".implode("','",$user_ID)."')
											AND course_id = %d 
											AND quiz_type = %s 
											ORDER BY title ASC",  $canvas_course_single_id, 'Diagnostic');
									$findresults = $wpdb->get_results( $findquery, ARRAY_A );
							if(empty($findresults)){
								if(!$is_bundle){
									$show_diagnostic_report_inner = false;
								}
								$error_msg = 'Data not available.';
							}
							$catGroups = array();
							foreach($findresults as $find){
								$mcateory = trim($find['main_category']);
								$catGroups[$mcateory]['name'] = $mcateory;
								$catGroups[$mcateory]['percentage'][] = $find['percent']*100;
								$catGroups[$mcateory]['subcats'][] = $find;
								$catGroups[$mcateory]['possible'][] = $find['possible'];
								$catGroups[$mcateory]['score'][] = $find['score'];
							}
							if(!empty($findresults)){
								$percentageTotal = array();
								$scoreTotal = array();
								foreach($catGroups as $res){
									$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']);
								}
								$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
							}
							if($show_diagnostic_report_inner){
								if($is_bundle){
									
									$product_course_related = $wpdb->get_results(
										$wpdb->prepare(
											"SELECT post_id, meta_key
											 FROM $wpdb->postmeta AS pm
											 LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID
											 WHERE meta_key = 'canvas_course_id'
											 AND p.post_status = 'publish'
											 AND meta_value = %d",
											$canvas_course_single_id
										),
										ARRAY_A
									);
									if ( ! empty( $product_course_related ) ) {
										// foreach ( $product_course_related as $result ) {
										// 	$course_post_id = $result['post_id'];
										// }
										$course_post_id = $product_course_related[0]['post_id'];
									}
									echo '<h3 class="top_header_sub_course">Course : '.get_the_title($course_post_id).'</h3>';
								}
								if(empty($findresults)){
									echo '<div style="text-align:center;color:#ed6c47;margin-bottom:30px;border:1px solid #ccc;">'.$error_msg.'</div>';
								}else{
										
									echo'
									<div class="custom-dia-container">';
									foreach($catGroups as $res){
										$percentage = array_sum($res['percentage'])/count($res['percentage']);
										
										echo '
											<div class="custom-dia-sub-container">
												<div class="custom_dia_innersub_container">
													<p class="first_td_dia">'.html_entity_decode($res['name']).'</p>
													
													<p class="text-center">'.round($percentage).'%</p>
												</div>
											</div>
										';
									}
									echo '
									</div>
									';
								}
							}
							else{
								if(!$is_bundle){ echo '<div style="text-align:center;color:#ed6c47;">'.$error_msg.'</div>'; }
							}
						}
								
					?>
				</div>
				<!-- <a class="course_practice_report" data-course_id="<?php 
				// echo esc_attr($course_name_id);?>" style="cursor:pointer;">Practice Report</a> -->
				<h3 class="course_diagnostic_report p1" data-course_id="<?php echo esc_attr($course_product_id);?>" style="cursor:pointer;">Practice Report  <i class="bx bxs-down-arrow course_diagnostic_report_icon d2"></i></h3>
				<div class='course_diagnostic_report_container'>
					<?php
					foreach($canvas_course_ids as $canvas_course_single_id){
						$show_diagnostic_report_inner = true;
						// $findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data 
						// WHERE course_user_id IN ($canvas_user_id) AND course_id = %d AND quiz_type = %s ORDER BY title ASC",  $canvas_course_single_id, 'Practice');
						// $findresults = $wpdb->get_results( $findquery, ARRAY_A );

						$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data
						WHERE course_user_id IN ('".implode("','",$user_ID)."')
						AND course_id = %d 
						AND quiz_type = %s 
						ORDER BY title ASC",  $canvas_course_single_id, 'Practice');
			            $findresults = $wpdb->get_results( $findquery, ARRAY_A );
						if(empty($findresults)){
							if(!$is_bundle){
								$show_diagnostic_report_inner = false; 
							}
							$error_msg = 'Data not available.';
						}
						$catGroups = $timeGroup = array();
						foreach($findresults as $find){
						
							$mcateory = trim($find['main_category']);
							$submitted = strtotime($find['submitted_or_assessed_at']);
							$timeGroup[$submitted] = $find['submitted_or_assessed_at'];
							$catGroups[$submitted][$mcateory]['name'] = $mcateory;
							$catGroups[$submitted][$mcateory]['percentage'][] = $find['percent']*100;
							$catGroups[$submitted][$mcateory]['subcats'][] = $find;
							$catGroups[$submitted][$mcateory]['submitted_or_assessed_at'] = $find['submitted_or_assessed_at'];
						}
						$attemptGroup = array_values($catGroups);
						ksort($timeGroup);
						if($show_diagnostic_report_inner){
						
							if($is_bundle){
							$product_course_related = $wpdb->get_results(
								$wpdb->prepare(
									"SELECT post_id, meta_key
									 FROM $wpdb->postmeta AS pm
									 LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID
									 WHERE meta_key = 'canvas_course_id'
									 AND p.post_status = 'publish'
									 AND meta_value = %d",
									$canvas_course_single_id
								),
								ARRAY_A
							);
							if ( ! empty( $product_course_related ) ) {
								// foreach ( $product_course_related as $result ) {
								// 	$course_post_id = $result['post_id'];
								// }
								$course_post_id = $product_course_related[0]['post_id'];
							}
							echo '<h3 class="top_header_sub_course">Course : '.get_the_title($course_post_id).'</h3>';
							}
							if(empty($findresults)){
								echo '<div style="text-align:center;color:#ed6c47;margin-bottom:30px;border:1px solid #ccc;">'.$error_msg.'</div>';
							}
							else{

								$ratt = 1;
								echo' <div class="practice-demo">';
									foreach($timeGroup as $timekey => $timevalue){
										$tableClass = 'table_'.$canvas_course_single_id.' table_'.$timekey;
										$activeClass = $ratt == count($timeGroup) ? 'report_active' : '';

										$percentageTotal = array();
											foreach($catGroups[$timekey] as $res){                
												$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
											}
										$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
								
										echo'
											<div class="cus-attempt">
												<h3>Attempt - '.$ratt.' </h3>
											
												<div class="custom-practice-sub-container1">
												
													<p calss="total_practice">Total Practice Assessment :</p>
													<p class="mobile_cus">'.round($percentageTotalDisplay).'%</p>
											
												</div>
												<i class="bx bxs-down-arrow cus_dia_attempt_icon"></i>
											</div>
											<div class="custom-practice-container">
											
												';
												foreach($catGroups[$timekey] as $res){ 
													$percentage = array_sum($res['percentage'])/count($res['percentage']); 
													echo '
													<div class="custom-practice-sub-container">
														<div class="custom-practice-inner-sub-container">
															<p class="first_td">'.html_entity_decode($res['name']).'</p>
															<p class="text-center">'.round($percentage).'%</p>
														</div>
													</div>
													';
												}
												echo '
											</div>
										';
										$ratt++;
									}

								echo '</div>';
							}
						}
						else{
							if(!$is_bundle){ echo '<div style="text-align:center;color:#ed6c47;">'.$error_msg.'</div>'; }
						}
					}
					?>
				</div>
				<h3 class="course_diagnostic_report m1" data-course_id="<?php echo esc_attr($course_product_id);?>" style="cursor:pointer;">Module Quiz Scores<i class="bx bxs-down-arrow d3"></i></h3>
				<div class='course_diagnostic_report_container'>
					<?php
					foreach($canvas_course_ids as $canvas_course_single_id){
						$show_diagnostic_report_inner = true;
						
						// $findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data 
						// WHERE course_user_id IN ($canvas_user_id) AND course_id = %d AND quiz_type = %s ORDER BY title ASC",  $canvas_course_single_id, 'Module');
						// $findresults = $wpdb->get_results( $findquery, ARRAY_A );
 						//error_log(print_r(['findresults' => $findresults], true), 3, WP_CONTENT_DIR . '/uploads/error.log');
						//$findresults = $wpdb->get_results( $findquery, ARRAY_A );
						
						$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data
						WHERE course_user_id IN ('".implode("','",$user_ID)."')
						AND course_id = %d 
						AND quiz_type = %s 
						ORDER BY title ASC",  $canvas_course_single_id, 'Module');
			            $findresults = $wpdb->get_results( $findquery, ARRAY_A );

						if(empty($findresults)){
							if(!$is_bundle){
								$show_diagnostic_report_inner = false; 
							}
							$error_msg = 'Data not available.';
						}
						$catGroups = $timeGroup = array();
						foreach($findresults as $find){
						
							$mcateory = trim($find['main_category']);
							$submitted = strtotime($find['submitted_or_assessed_at']);
							$timeGroup[$submitted] = $find['submitted_or_assessed_at'];
							$catGroups[$submitted][$mcateory]['name'] = $mcateory;
							$catGroups[$submitted][$mcateory]['percentage'][] = $find['percent']*100;
							$catGroups[$submitted][$mcateory]['subcats'][] = $find;
							$catGroups[$submitted][$mcateory]['submitted_or_assessed_at'] = $find['submitted_or_assessed_at'];
							$catGroups[$submitted][$mcateory]['display_name'] = $find['display_name'];

						}
						$attemptGroup = array_values($catGroups);
						ksort($timeGroup);
						if($show_diagnostic_report_inner){
						
							if($is_bundle){
							$product_course_related = $wpdb->get_results(
								$wpdb->prepare(
									"SELECT post_id, meta_key
									 FROM $wpdb->postmeta AS pm
									 LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID
									 WHERE meta_key = 'canvas_course_id'
									 AND p.post_status = 'publish'
									 AND meta_value = %d",
									$canvas_course_single_id
								),
								ARRAY_A
							);
							if ( ! empty( $product_course_related ) ) {
								// foreach ( $product_course_related as $result ) {
								// 	$course_post_id = $result['post_id'];
								// }

								$course_post_id = $product_course_related[0]['post_id'];
							}
							echo '<h3 class="top_header_sub">Course : '.get_the_title($course_post_id).'</h3>';
							}
							if(empty($findresults)){
								echo '<div style="text-align:center;color:#ed6c47;margin-bottom:30px;border:1px solid #ccc;">'.$error_msg.'</div>';
							}
							else{

								$ratt = 1;
								echo' <div class="quizz-attempt">';
								$percentageTotal = array();
									foreach($timeGroup as $timekey => $timevalue){	
											foreach($catGroups[$timekey] as $res){                
												$DisplayName = $res['display_name'];
												$percentageTotal[$DisplayName][] =    array_sum($res['percentage'])/count($res['percentage']); 
											}
										$ratt++;
									}
									$percentageTotalDisplay = array();

									foreach ($percentageTotal as $DisplayName => $scores) {
										$percentageTotalDisplay = array_sum($scores) / count($scores);
										echo'
										<div class="cus_module_practice_sub_container">
										<div class="cus_module_practice_inner_sub_container">
											<p class="first_td">'. $DisplayName . '</p>
											<p class="text-center">'.round($percentageTotalDisplay).'%</p>
										</div>
										</div>
										';
									}
								echo '</div>';
							}
						}
						else{
							if(!$is_bundle){ echo '<div style="text-align:center;color:#ed6c47;">'.$error_msg.'</div>'; }
						}
					}
					?>
				</div>
			</div>
		</div>
		<!-- <a>End of Module Quizzes</a>
		<a>Time spent in Course</a> -->
		<script>
			jQuery(document).ready(function(){
				jQuery(".course_dia_attempt .course_diagnostic_report_container").hide();
				jQuery(".course_dia_attempt .course_diagnostic_report").click(function(){
					jQuery(this).next().slideToggle()
					.siblings(".course_diagnostic_report_container:visible").slideUp();

				});
				jQuery(".practice-demo .custom-practice-container").hide();
				jQuery(".practice-demo .cus-attempt").click(function(){
					
					jQuery(this).next().slideToggle()
					.siblings(".custom-practice-container:visible").slideUp();
				});
			});
		</script>
		<?php
		die();
