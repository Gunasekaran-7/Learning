<?php

//The process of inserting the canvas outcomes from the api_results table into the final table by categorizing them by type.

//created_by:Guna 11-03-2025
 

    global $wpdb;
    // $main_query = "SELECT *
    // FROM wp_canvas_course_api_results
    // WHERE code = 200
    // and status = 'pending'
    // LIMIT 50";


    $main_query = "SELECT *
    FROM wp_canvas_course_api_results
    WHERE code = 200
    and canvas_id = 206570
    and status = 'pending'
    LIMIT 30";

    $table_name = 'wp_canvas_data'; 
    $results = $wpdb->get_results($main_query, ARRAY_A);
    if(empty($results)){
        return;
    }
    $assignment_list = get_all_canvas_course_assignments();
    $insertdata = array();
    foreach($results as $result){

        $d_process =  '['.$result['response'].']';
        $d_process = str_replace(array('u003c','u003e','u0026','\n','&nbsp;','\r'),array('<','>','&','','',''),$d_process);
        $d_process = str_replace(array('&amp;'), array('&'), $d_process);
        $d_process = str_replace(array("\n", "\r"), '', $d_process);
        $d_process = strip_tags($d_process);        
        $response_data = json_decode($d_process, true);
        $courseid = $result['course_id'];
        $userids =  $result['canvas_id'];

		if(function_exists('check_canvas_uid_in_wp')){
			$check_user = check_canvas_uid_in_wp($userids);
			if(empty($check_user)){ 
				$row_id = $result['id'];
            	$wpdb->query("UPDATE wp_canvas_course_api_results SET status = 'user not exists' WHERE id = ".$row_id);
            	continue;
			 }
		}

        $outcome_results = $response_data[0]['outcome_results'];
        if(empty($outcome_results)){
            $row_id = $result['id'];
            $wpdb->query("UPDATE wp_canvas_course_api_results SET status = 'processed' WHERE id = ".$row_id);
            continue;
        }
        $outcome_list = $response_data[0]['linked']['outcomes'];
        $newoutcome = $newresult = array();
        foreach($outcome_list as $outcomes){
            $newoutcome[$outcomes['id']] = array(
                'display_name'=>htmlentities(str_replace("'", "\'", $outcomes['display_name'])),
                'title'=>htmlentities(str_replace("'", "\'", $outcomes['title']))
            );
        }
        foreach($outcome_results as $or_lt){
            $assignment_id = explode('_',$or_lt['links']['alignment'])[1];
            $assignment_name = isset($assignment_list[$assignment_id]) ? str_replace("'", "\'", $assignment_list[$assignment_id]) : '';
            if(empty($assignment_name)){
                continue;
            }
            $quiz_type = '';
            if(strpos($assignment_name, 'Diagnostic') !== false){
                $quiz_type = 'Diagnostic';
            }elseif(strpos($assignment_name, 'Practice') !== false){
                $quiz_type = 'Practice';
            }
            elseif(strpos($assignment_name,'Quiz') !== false){
                $quiz_type = 'Module';
            }
            if($quiz_type == ''){
                continue;
            }
            $main_category_find = explode(' - ',$newoutcome[$or_lt['links']['learning_outcome']]['title']);
            $main_category_name = $main_category_find[1];
            $main_category_name = htmlentities(str_replace("'", "\'", $main_category_name));
            $submitted_time = str_replace(array('T','Z'),array(' ',''),$or_lt['submitted_or_assessed_at']);
            $newresult[$or_lt['links']['learning_outcome']] = array(
                'course_user_id' => $or_lt['links']['user'],
                'course_id' => $courseid,
                'quiz_id' => $assignment_id,
                'quiz_name' => $assignment_name,
                'quiz_type' => $quiz_type,
                'outcome_id' => $or_lt['links']['learning_outcome'],
                'display_name' => $newoutcome[$or_lt['links']['learning_outcome']]['display_name'],
                'title' =>  $newoutcome[$or_lt['links']['learning_outcome']]['title'],
                'main_category' => $main_category_name,
                'score' => $or_lt['score'],
                'possible'=> $or_lt['possible'],
                'percent' => $or_lt['percent'],
                'submitted_or_assessed_at' => $submitted_time
            );
            $find_row = $wpdb->query('SELECT id FROM '.$table_name.'
                                            WHERE course_user_id = '.$or_lt['links']['user'].'
                                            AND course_id = '.$courseid.'
                                            AND quiz_id = '.explode('_',$or_lt['links']['alignment'])[1].'
                                            AND outcome_id = '.$or_lt['links']['learning_outcome'].'
                                            AND submitted_or_assessed_at = "'.$submitted_time.'"');
            if(empty($find_row)){
                $insertdata[] = "(
                ".$or_lt['links']['user'].",
                ".$courseid.",
                ".explode('_',$or_lt['links']['alignment'])[1].",
                '".$assignment_name."',
                '".$quiz_type."',
                ".$or_lt['links']['learning_outcome'].",
                '".$newoutcome[$or_lt['links']['learning_outcome']]['display_name']."',
                '".$newoutcome[$or_lt['links']['learning_outcome']]['title']."',
                '".$main_category_name."',
                ".$or_lt['score'].",
                ".$or_lt['possible'].",
                ".$or_lt['percent'].",
                '".$submitted_time."'
                )";
            }else{
                $wpdb->query(
                    "UPDATE ".$table_name."
                    SET
                    display_name = '".$newoutcome[$or_lt['links']['learning_outcome']]['display_name']."' AND
                    title = '".$newoutcome[$or_lt['links']['learning_outcome']]['title']."' AND
                    main_category = '".$main_category_name."' AND
                    score = ".$or_lt['score']." AND
                    possible = ".$or_lt['possible']." AND
                    percent = ".$or_lt['percent']."
                    WHERE
                    course_user_id = ".$or_lt['links']['user']." AND
                    course_id = ".$courseid." AND
                    quiz_id = ".explode('_',$or_lt['links']['alignment'])[1]." AND
                    outcome_id = ".$or_lt['links']['learning_outcome']." AND
                    submitted_or_assessed_at = '".$submitted_time."'"
                );
            }
        }
        $row_id = $result['id'];
        $wpdb->query("UPDATE wp_canvas_course_api_results SET status = 'processed' WHERE id = ".$row_id);
    }
    if(!empty($insertdata)){
        $wpdb->query("INSERT INTO ".$table_name." (course_user_id,course_id,quiz_id,quiz_name,quiz_type,outcome_id,display_name,title,main_category,score,possible,percent,submitted_or_assessed_at) VALUES ".implode(',',$insertdata));
    }
 

// We will take the total assignment name and store it in a table

function get_all_canvas_course_assignments($c=''){
    global $wpdb;
    if(empty($c)){
        $query = 'SELECT * FROM wp_canvas_assignments';
    }else{
        $query = 'SELECT * FROM wp_canvas_assignments WHERE course_id = '.$c;
    }
    $results = $wpdb->get_results($query, ARRAY_A);
    $course_list = array();
    if(!empty($results)){
        $course_list = array_column($results, 'assignment_name', 'assignment_id');
    }
    return $course_list;
}

// get canvas user id from usermeta

function check_canvas_uid_in_wp($cuid){
    if(empty($cuid)){ return; }
	global $wpdb;
	$query = "SELECT 
				um.meta_value AS cuid, 
				u.ID as wpuid
				FROM wp_usermeta um
				LEFT JOIN wp_users u on u.ID = um.user_id
				WHERE um.meta_key = '_canvas_user_id'
				AND um.meta_value = ".$cuid;
    $result = $wpdb->get_results($query, ARRAY_A);
	if(empty($result)){ return;}
    return $result[0]['wpuid'];
}
