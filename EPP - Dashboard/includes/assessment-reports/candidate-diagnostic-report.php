<?php

		global $wpdb;
        $courseids = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : '';
        $courseid = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';
		$canvas_user_id = isset($_REQUEST['canvas_user_ids']) ? $_REQUEST['canvas_user_ids'] : '';


		$userid = get_current_user_id();
		$billing_company = get_user_meta($userid,'billing_company',true);
		$user_meta_query = "SELECT user_id  FROM {$wpdb->prefix}usermeta WHERE meta_key='billing_company' and meta_value = '$billing_company' ";
		$user_meta_results = $wpdb->get_results($user_meta_query);
		
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
		$canvas_course_id = get_post_meta($courseid,'course_id',true);
		$canvas_course_ids = explode(',',$canvas_course_id);
		if(count($canvas_course_ids) > 1){ 
			$is_bundle = true; 
		}else{ 
			$is_bundle = false; 
		}
		// echo'<button class="course_practice_back">Back</button><br>';
       
        
		foreach($canvas_course_ids as $canvas_course_single_id){
			$show_diagnostic_report_inner = true;
			
			$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data_latest
			WHERE course_user_id = $canvas_user_id
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
				$catGroups[$submitted][$mcateory]['possible'][] = $find['possible'];
                $catGroups[$submitted][$mcateory]['score'][] = $find['score'];

			}
		

			$attemptGroup = array_values($catGroups);
		
			ksort($timeGroup);

			if($show_diagnostic_report_inner){
			
				if($is_bundle){
				// $product_course_related = $wpdb->get_results( "select post_id, meta_key from $wpdb->postmeta where meta_key = 'course_id' and meta_value = ".$canvas_course_single_id, ARRAY_A );
				// $course_post_id = $product_course_related[0]['post_id'];
				$product_course_related = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, meta_key
						 FROM $wpdb->postmeta AS pm
						 LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID
						 WHERE meta_key = 'course_id'
						 AND p.post_status = 'publish'
						 AND meta_value = %d",
						$canvas_course_single_id
					),
					ARRAY_A
				);
				if ( ! empty( $product_course_related ) ) {
					foreach ( $product_course_related as $result ) {
						$course_post_id = $result['post_id'];
					}
				}
				echo '<h3 class="top_header_sub">Course : '.get_the_title($course_post_id).'</h3>';
				}
				if(empty($findresults)){
					echo '<div style="text-align:center;color:#ed6c47;margin-bottom:30px;border:1px solid #ccc;">'.$error_msg.'</div>';
				}
				else{
					echo' 
					
                    <div class="diagnostic-attempt">';
						foreach($timeGroup as $timekey => $timevalue){
							$percentageTotal = array();
								foreach($catGroups[$timekey] as $res){                
									$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
								}
							$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
						}
						echo '
						<div class="candi-diagnostic-container">
							<div class="diagno_mark_report">
									<p class="diagno_mark">Correct</p>';
									$domainScores = array();
									$domainTotalScore = 0;
    								$domainTotalPossible = 0;
										foreach ($catGroups[$timekey] as $res) { 
											$domainTotalScore = 0;
											$domainTotalPossible = 0;
											// $percentage = array_sum($res['percentage']) / count($res['percentage']); 
											if (isset($res['subcats'])) {
												foreach ($res['subcats'] as $subcategory) {
													$subcategoryScore = $subcategory['score'];
													$subcategoryPossible = $subcategory['possible']; 
													// echo '<p class="diagno_mark>XX/40 Correct</p>';
													$domainTotalPossible += $subcategoryPossible;
													$domainTotalScore += $subcategoryScore;
													// echo '<p class="charu">' .$subcategoryScore.'/'.$subcategoryPossible . '</p>';
												}
												}
												$domainScores[] = array('score' => $domainTotalScore, 'possible' => $domainTotalPossible);
												}
												foreach ($domainScores as $domainInfo) {
													$domainScore = $domainInfo['score'];
													$domainPossible = $domainInfo['possible'];
													echo '<p class="charu">' . $domainScore . '/' . $domainPossible . '</p>';
												}
							echo '</div>';
						
							echo'
									<div class="percentage">
										<p class="diagno_mark">% Correct</p>';
										//foreach ($catGroups[$timekey] as $res) { 
											//$percentage = array_sum($res['percentage']) / count($res['percentage']); 
											echo '<div class="digno_percent">';
											// echo '<p class="first_pr">' . $res['name'] . '</p>';
											echo '<p class="dig_percent">' . round($percentageTotalDisplay) . '%</p>';
											echo '</div>';
										//}
								echo'</div>';
							
								echo '
								<div class="report_link">
									<p class="diagno_mark">Diagnostic Assessment Report</p>
									<a href="/diagnostic-report?course=' . $courseid . '" class="woocommerce-button button view" target="_blank">' . esc_html_x('Diagnostic Report', 'view a subscription', 'woocommerce-subscriptions') . '</a>
								</div>
							';
							echo '
								</div>
							';
				}

					echo '
                    </div>';
			}
			else{
				if(!$is_bundle){ echo '<div style="text-align:center;color:#ed6c47;">'.$error_msg.'</div>'; }
			}
		}
		?>
		