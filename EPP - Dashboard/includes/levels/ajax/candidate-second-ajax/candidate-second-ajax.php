<?php
 	global $wpdb;


	//$userid = get_current_user_id();
	$userid = 2656;

	$canvas_user_ids = isset($_REQUEST['canvas_user_ids']) ? $_REQUEST['canvas_user_ids'] : '';	
	$product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';
	$course_id = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : '';
	$product_name = isset($_REQUEST['product_name']) ? $_REQUEST['product_name'] : '';
	$candidate_name = isset($_REQUEST['candidate_name']) ? $_REQUEST['candidate_name'] : '';
	$userid_new =isset($_REQUEST['userid_new']) ? $_REQUEST['userid_new'] : '';

	$courseid = $product_id;
	$canvas_user_id = $canvas_user_ids;


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
	$first_name_val = get_user_meta($userid_new,'first_name',true);	 
	
		?>
		<div class="main_candi_report">
			<div class ="canditit" >
				<div class="name_sub1">
					<lable  class="lable_sub1">Candidate :</lable>
					<p class="candidate_name"><?php echo $first_name_val; ?></p>
				</div>
			<!-- <p class="cus-divider">>></p> -->
				<div class="name_sub2">
					<lable class="lable_sub1">Course</lable>
					<lable class="lable_sub2">:</lable>
					<p class="candi_report_head"><?php echo $product_name; ?></p>
				</div>
	        </div>
			<div class ="canditit_back" >
			<button class = "reports_back candi_r1"> Back</button>
			<button class="reports_back1"><a href="<?php echo $url.'/portal/' ;?>" >Back to Portal</a></button>
	        </div>
		</div>
		<div class ="assesment_reports">
			
			<h3 class="diagnostic" data-canvas_user_ids="<?php echo($canvas_user_ids) ?>" data-product_id="<?php echo($product_id) ?>" data-course_id="<?php echo($course_id) ?>">Diagnostic Report <i class="bx bxs-down-arrow"></i></h3>

			<div class ="custom_diagnostic_container_reports">
				<?php
				     $overallScore = 0;
					 $overallPossible = 0;
					 $overallPercentageTotal = [];
					 $percentageTotalDisplay = array();
				foreach($canvas_course_ids as $canvas_course_single_id){

					$show_diagnostic_report_inner = true;
					
					$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data 
					WHERE course_user_id = $canvas_user_id
					AND course_id = %d 
					AND quiz_type = %s 
					ORDER BY title ASC",  $canvas_course_single_id, 'Diagnostic');
					$findresults = $wpdb->get_results( $findquery, ARRAY_A );

					if(empty($findresults)){
						if(!$is_bundle){
							$show_diagnostic_report_inner = false; 
							$error_msg = 'Data not available.';
						}
						
					}
					$catGroups = $timeGroup = array();
					// foreach($findresults as $find){
					
					// 	$mcateory = trim($find['main_category']);
					// 	$submitted = strtotime($find['submitted_or_assessed_at']);
					// 	$timeGroup[$submitted] = $find['submitted_or_assessed_at'];
					// 	$catGroups[$submitted][$mcateory]['name'] = $mcateory;
					// 	$catGroups[$submitted][$mcateory]['percentage'][] = $find['percent']*100;
					// 	$catGroups[$submitted][$mcateory]['subcats'][] = $find;
					// 	$catGroups[$submitted][$mcateory]['submitted_or_assessed_at'] = $find['submitted_or_assessed_at'];
					// 	$catGroups[$submitted][$mcateory]['display_name'] = $find['display_name'];
					// 	$catGroups[$submitted][$mcateory]['possible'][] = $find['possible'];
					// 	$catGroups[$submitted][$mcateory]['score'][] = $find['score'];

					// }
					// $attemptGroup = array_values($catGroups);
					// ksort($timeGroup);

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

							 
						}
						 
						foreach ($findresults as $find) {
							$subcategoryScore = $find['score'];
							$subcategoryPossible = $find['possible'];
							$percentage = $find['percent'] * 100;
			
							$overallScore += $subcategoryScore;
							$overallPossible += $subcategoryPossible;
							$overallPercentageTotal[] = $percentage;

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
						foreach($timeGroup as $timekey => $timevalue){
							$percentageTotal = array();
							foreach($catGroups[$timekey] as $res){                
								$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
							}
							$percentageTotalDisplay[] = round(array_sum($percentageTotal)/count($percentageTotal));	
						}			
							 				
					}
				}
				
				if (!empty($percentageTotalDisplay)) {
					$overallPercentageTotalDisplay = array_sum($percentageTotalDisplay) / count($percentageTotalDisplay);
					$displayPercentage = round($overallPercentageTotalDisplay) . '%';
				} else {
					$displayPercentage = 'N/A';  
				}
				 
				echo' 
							
				<div class="diagnostic-attempt">
					<div class="candi-diagnostic-container">
						<div class="diagno_mark_report">
							<div class="diagno_sub_mark_report">
								<p class="diagno_mark">Correct</p>
								<p class="diagno_mark_percent">' . $overallScore . '/' . $overallPossible . '</p>
							</div>
						</div>
						<div class="percentage">
							<div class="percentage_sub_div">
								<p class="diagno_mark">% Correct</p>
								<div class="digno_percent">
									<p class="dig_percent">' . $displayPercentage.'</p>
								</div>
							</div>
						</div>
						<div class="report_link">
							<div class="report_link_sub_div">
								<p class="diagno_mark">Diagnostic Assessment Report</p>
								<a class="views1" href="/diagnosticreports/?productid=' . $courseid . '&canvas_user_id=' . $canvas_user_id . '" class="woocommerce-button button view" target="_blank">' . esc_html_x('View', 'view a subscription', 'woocommerce-subscriptions') . '</a>
								
							</div>
						</div>
					</div>
				</div>';
				?>
			</div>
			<h3 class="diagnostic" data-canvas_user_ids="<?php echo($canvas_user_ids) ?>" data-product_id="<?php echo($product_id) ?>" data-course_id="<?php echo($course_id) ?>"> Practice Report <i class="bx bxs-down-arrow"></i></h3>
			<div class="custom_diagnostic_container_reports" >
				<?php
				foreach($canvas_course_ids as $canvas_course_single_id){
					$show_diagnostic_report_inner = true;
					
					$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data
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
                            //     $course_post_id = $result['post_id'];
								 
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
							echo' <div class="practice-attempt">';
								foreach($timeGroup as $timekey => $timevalue){
									$percentageTotal = array();
										foreach($catGroups[$timekey] as $res){                
											$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
										}
									$percentageTotalDisplay = round(array_sum($percentageTotal)/count($percentageTotal));
							
									echo'
										<div class="candidate-attempt">
											<h3>Attempt - '. $ratt .'</h3>
											<div class="cus-practice-sub-container">
												<p>Total Practice Assessment :</p>
												<p>'.$percentageTotalDisplay.'%</p>
											</div>
											<i class="bx bxs-down-arrow candidate-attempt_icon"></i>
										</div>
										<div class="candidate-practice-container">';		
											foreach ($catGroups[$timekey] as $res) { 
												$percentage = array_sum($res['percentage']) / count($res['percentage']); 
												echo '<div class="candidate-practice-sub-container">';
												echo '<p class="first_pr1">' . html_entity_decode($res['name']). '</p>';
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
			</div>
			<h3 class="diagnostic" data-course_id="<?php echo esc_attr($course_name_id);?>" style="cursor:pointer;">Module Quiz Scores<i class="bx bxs-down-arrow"></i></h3>
			<div class='custom_diagnostic_container_reports'>
				<?php
				foreach($canvas_course_ids as $canvas_course_single_id){
					$show_diagnostic_report_inner = true;
					$findquery = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}canvas_data_latest
					WHERE course_user_id = $canvas_user_id
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
							//$product_course_related = $wpdb->get_results( "select post_id, meta_key from $wpdb->postmeta where meta_key = 'course_id' and meta_value = ".$canvas_course_single_id, ARRAY_A );
							//$course_post_id = $product_course_related[0]['post_id'];
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
							echo' <div class="quizz-attempt1">';
							foreach($timeGroup as $timekey => $timevalue){
								//if($ratt == 1){
									$percentageTotal = array();
									foreach($catGroups[$timekey] as $res){                
										$percentageTotal[] = array_sum($res['percentage'])/count($res['percentage']); 
										$DisplayName = $res['name'];
									}
									$percentageTotalDisplay = array_sum($percentageTotal)/count($percentageTotal);
									echo'
										<div class="cus_module_practice_sub_container1">
											<div class="cus_module_practice_inner_sub_container1">
												<p class="module_first_td">'. html_entity_decode($DisplayName) . '</p>
												<p class="module-text-center">'.round($percentageTotalDisplay).'%</p>
											</div>
										</div>
									';
								// }
								// else{
								// 	break;
								// }
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
			<script>
				jQuery(document).ready(function(){
					jQuery(".assesment_reports .custom_diagnostic_container_reports").hide();
					jQuery(".assesment_reports .diagnostic").click(function(){
					
						jQuery(this).next().slideToggle()
						.siblings(".custom_diagnostic_container_reports:visible").slideUp();

					});
					jQuery(".practice-attempt .candidate-practice-container").hide();
					jQuery(".practice-attempt .candidate-attempt").click(function(){
						jQuery(this).next().slideToggle()
						.siblings(".candidate-practice-container:visible").slideUp();

					});
				});
			</script>
		<?php
		wp_die();
 