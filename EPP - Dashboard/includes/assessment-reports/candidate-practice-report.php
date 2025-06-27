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
                $catGroups[$submitted][$mcateory]['display_name'] = $find['display_name'];

			}
		

			$attemptGroup = array_values($catGroups);
		
			ksort($timeGroup);

			if($show_diagnostic_report_inner){
			
				if($is_bundle){
				//$product_course_related = $wpdb->get_results( "select post_id, meta_key from $wpdb->postmeta where meta_key = 'course_id' and meta_value = ".$canvas_course_single_id, ARRAY_A );
				//$course_post_id = $product_course_related[0]['post_id'];
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

					$ratt = 1;
					echo' <div class="practice-attempt">';
						foreach($timeGroup as $timekey => $timevalue){
							$percentageTotal = array();
								foreach($catGroups[$timekey] as $res){                
									$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
								}
							$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
					
							echo'
								<div class="candidate-attempt">
									<h3>Attempt - '. $ratt .'</h3>
									<div class="cus-practice-sub-container">
										<p>Total Practice Assessment:</p>
										<p>'.round($percentageTotalDisplay).'%</p>
									</div>
									<i class="bx bxs-down-arrow"></i>
								</div>
								<div class="candidate-practice-container">';

									foreach ($catGroups[$timekey] as $res) { 
										$percentage = array_sum($res['percentage']) / count($res['percentage']); 
										echo '<div class="candidate-practice-sub-container">';
										echo '<p class="first_pr1">' .html_entity_decode($res['name']). '</p>';
										echo '<p class="text-pr1">' . round($percentage) . '%</p>';
										echo '</div>';
			
										if (isset($res['subcats'])) {
											foreach ($res['subcats'] as $subcategory) {
												$subcategoryDisplayName = $subcategory['display_name'];
												$subcategoryPercentage = $subcategory['percent'] * 100;
												echo '<div class="candidate-practice-sub1-container">';
												echo '<p class="first_pr">' . $subcategoryDisplayName . '</p>';
												echo '<p class="text-pr">' . round($subcategoryPercentage) . '%</p>';
												echo '</div>';
											}
										}
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
		<script>
			jQuery(document).ready(function() {
				jQuery(".practice-attempt .candidate-practice-container").hide();
				jQuery(".practice-attempt .candidate-attempt").click(function(){
					jQuery(this).next().slideToggle()
					.siblings(".candidate-practice-container:visible").slideUp();

				});
			});
			
		</script>