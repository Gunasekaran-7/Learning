<?php


    global $wpdb;

	
    $user_id = get_current_user_id();

	$user_type = get_user_meta($user_id,'user_type',true);

	if (!is_user_logged_in()) {
		
		wp_redirect(site_url('/account/'));
		exit;  
	}
 
    if( $user_type != 'EPP' ){
        wp_redirect(site_url('/account/'));
		exit; 
    }
 
 

	$get_data = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, redeemed_to, redeemed_date, package_code, status
			FROM {$wpdb->prefix}voucher_list
			WHERE user_id = %d
			AND status = 'Redeemed'",
			$user_id
		),
		ARRAY_A
	);

	$redeem_user_count = [];
	$package_code = [];

	foreach ($get_data as $data) {
		if (!empty($data['package_code'])) {
			$package_code[] = $data['package_code'];
		}
		if (!empty($data['redeemed_to'])) {
			$redeem_user_count[] = $data['redeemed_to'];
		}
	}

	$redeem_user_count[] = $user_id;

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
	} else {
		$results_product = [];
	}

	$product_names = [];

	foreach ($results_product as $product) {
		$product_obj = wc_get_product($product->post_id);
		$product_names[$product->post_id] = $product_obj ? $product_obj->get_name() : '';
	}


	$find_orders_query = $wpdb->prepare("
		SELECT DISTINCT oim.meta_value AS product_id, p2.post_title AS product_name
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
	", $user_id);
	
	$order_results = $wpdb->get_results($find_orders_query);
	
	if (!empty($order_results)) {
		foreach ($order_results as $row) {
			$product_names[$row->product_id] = $row->product_name; 
		}
	}
	

	?>
	<div  class="search_loc_cover">
		<div class="main_dash_div" >
			<p class="course_head">Courses List</p>
			<div class="dash-back-contain">
				<a href="<?php echo $url.'/portal/' ;?>"  class="course_dashboard_back">Back</a>		
			</div>
		</div>
		<div class ="text-center1">
			<div class="form-control-search">
				<div class="custom-input-search search_input_loc">
					<label>From Date:</label><br>
					<input type="date" id="rang_from" name="rang_from">
				</div>
				<div class="custom-input-search search_input_loc">
					<label>To Date:</label><br>
					<input type="date" id="rang_to" name="rang_to">
				</div>
				<div class="custom-input-search search_input_loc" style="display:none;">
					<label>Course:</label><br>
					<select name="" id="custom_course_data" >
						<option  class="course_option" value="" >All</option>
						<?php
						foreach ($product_names as $key=>$name) {
							?>	 
							<div>
								<option class="course_option" value="<?php echo $key;?>"> <?php echo $name;?></option>
							</div>
						<?php
					 
						}
						?>		
					</select>
				  </div>
				<div class="search_icon_add">
					<button id="search-course-button-click" name="Search"  class="Buttoncreate">
						<i class='bx bx-search'></i>	
						Search
					</button>
				</div>
			</div>
		</div>
	</div>
    <div class="loader_ol"></div>  
	<div class="container_covers" ></div>  
	<div class="container_cover">
		<table id="container_cover">
			<thead>
				<tr>
					<th>S.No</th>
					<th>Course Name</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$i = 1;
				foreach($product_names as $key => $name) {
					?>
					<tr>
						<td><?php echo $i; ?></td>
						<td>
							<a class="custom_test_list" data-course_name="<?php echo esc_attr($name); ?>" data-course_id="<?php echo esc_attr($key); ?>" style="cursor:pointer;">
								<?php echo $name; ?>
							</a>
						</td>
					</tr>
					<?php
					$i++;
				}
				?>
			</tbody>
		</table>
	</div>



	<div class='course_test_container' style="display:none;"></div>
	<style>
		.course_dashboard_back{text-decoration:none !important;}

#container_cover_paginate{
	padding-top:12.8px !important;
}
#container_cover_paginate a {
    display: inline-block; 
    padding: 8px 15px; 
    font-size: 14px;
    color: #fff !important; 
    text-align: center;
    border-radius: 4px; 
    margin: 0 5px; 
}


#container_cover_previous, 
#container_cover_next {
    background-color: #043753 !important; 
}





#container_cover_paginate .paginate_button.current {
    background-color: #f0c144 !important; 
    color: black !important; 
	padding:10px;
}
#container_cover_paginate .paginate_button:not(.current) {
    background-color: #043753 !important; 
    color: white !important;
    padding: 10px;
    border-radius: 4px;
}

#container_cover_paginate .paginate_button {
	background-color: #043753 !important; 
    color: white !important; 
	padding:4px 10px  !important;
	border-radius:3px !important;
}

#container_cover_paginate .paginate_button {
    all: unset;
    display: inline-block; 
    padding: 5px 10px; 
    text-decoration: none !important; 
    color: white !important; 
    cursor: pointer;
    margin: 0 4px; 
}

#container_cover_paginate .paginate_button:hover {
    background-color: #f0c144 !important; 
   
}
.txt-align{text-align:center !important;}


	</style>

 
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

		
	<script>

	 
		jQuery(document).ready(function ($) {
			jQuery('#container_cover').DataTable({
				"paging": true,      
				"searching": true,    
				"ordering": true,    
				"info": true,        
				"lengthMenu": [10, 25, 50, 100]  
			});
		});

		jQuery(document).ready(function(){

	 
			jQuery(document).on('click', '#search-course-button-click', function () {
				var course_name = jQuery('.course_option:selected').val();
				var range_from = jQuery('#rang_from').val();
				var rang_to = jQuery('#rang_to').val();
				if (range_from === '' && rang_to === '') {
		
		return false; 
	}

				jQuery('.loader_ol').html('<div style="width:100%;height:200px;text-align:center;"><img class="loading-gif" src="/wp-content/uploads/2025/03/loading-gif.gif"></div>');
				jQuery(".container_cover").hide();
				jQuery(".container_covers").hide();
				jQuery.ajax({
					type: 'POST',
					url: my_ajax_object.admin_ajax_url,
					data: {
						'action': 'course_search_filter',
						'course_name': course_name,
						'range_from': range_from,
						'rang_to': rang_to,
					},
					dataType: 'html',
					success: function (data) {
						jQuery(".container_covers").show(); 
						if (data.trim() === '') {
							jQuery('.container_covers').html('<p class="txt-align">No courses found.</p>');
						} else {
							jQuery('.container_covers').html(data);
						}
								
						jQuery(".container_cover").hide(); // Hide old table
						jQuery('.loader_ol').html(''); // Remove loader

						if ($.fn.DataTable.isDataTable('#container_covers')) {
							$('#container_covers').DataTable().destroy();
						}
						jQuery('#container_covers').DataTable({
        scrollX: true,
		// responsive: true,
        pagingType: 'simple_numbers',
        paging: true,
        language: {
            paginate: {
                previous: 'Previous',
                next: 'Next'
            }
        },
        autoWidth: false,  
		
    });
					},
					error: function (request, errorThrown) {
						console.log(errorThrown);
						jQuery('.loader_ol').html('<p style="color:red;">Error loading data.</p>');
					}
				});
			});

			jQuery(document).on('click','.custom_test_list',function(){
				jQuery('.container_cover').css('display','none');
				jQuery('.search_loc_cover ').css('display','none');
				var course_name_id = jQuery(this).data('course_id');
				var course_name = jQuery(this).data('course_name');
				
				jQuery('.course_test_container').css('display','block');
				jQuery('.course_test_container').html('<div style="width:100%;height:200px;text-align:center;"><img  class="loading-gif" src="/wp-content/uploads/2025/03/loading-gif.gif"></div>');
				console.log("AJAX Request Triggered"); 
				jQuery.ajax({
					type:'POST',
					url: site_data.ajaxUrl,
					data:{
						'action':'course_test_list',
						'product_id': course_name_id,
						'course_name':course_name,
					},
					dataType:'html',
					success: function(data) {
						console.log("Success: Data loaded successfully!");
						jQuery('.course_test_container').html(data);
					},
					error: function(xhr, status, error) {
						console.error("Error: ", error);
						console.error("Status: ", status);
						console.error("Response: ", xhr.responseText);
					}
				});
				console.log("Course ID:", course_name_id); 
				console.log("Course Name:", course_name);
			});

			
			jQuery(document).on('click','.course_report_back',function(){
				jQuery('.course_test_container').css('display','none');
				jQuery('.container_cover').css('display','block');
				jQuery('.search_loc_cover ').css('display','block');
				
			});
			jQuery(document).on('click','.course_list_dia_back',function(){
				jQuery('.course_test_container').css('display','block');
				jQuery('.course_diagnostic_report_container').css('display','none');
			});
			jQuery(document).on('click','.course_list_practice_back',function(){
				jQuery('.course_test_container').css('display','block');
				jQuery('.course_practice_report_container').css('display','none');
			});
			
		});


	</script>
<?php
				 