<?php

 
    global $wpdb;
    // $main_query = "SELECT * FROM wp_canvas_course_submission 
    // WHERE status = 'pending' 
    // LIMIT 40";

    $main_query = "SELECT * FROM wp_canvas_course_submission 
    WHERE canvas_user_id = 206570
    LIMIT 40";
    $get_all_results = $wpdb->get_results($main_query, ARRAY_A);
    if(empty($get_all_results)){
        return;
    }
    $all_ids = array_column($get_all_results, 'id');
    $requests = array();
    $original_courses = array();
    foreach($get_all_results as $gfm){
        $id_token = '22540~yyGL4TZ9MVpqAzZ56kjWxdQjQXm60dfygh7RcMg25Z34iJYAoitIgA5cOfrWzBkV'; 
        $args = array(
            'headers'     => array(
                'Authorization' => 'Bearer ' . $id_token,
            ),
        );
        $course_ids = $gfm['canvas_course_id'];
        $userids = $gfm['canvas_user_id'];
		$assignment_id = $gfm['assignment_id'];
        
        $remote_url = 'https://learnersedge.instructure.com/api/v1/courses/'.$course_ids.'/outcome_results?per_page=100&include[]=outcomes&user_ids[]='.$userids;
        $requests[] = array(
                    'url'  => $remote_url,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $id_token,
                    ),
                    'type' => 'GET',
                );

        $original_courses['courses'][] = $course_ids;
        $original_courses['users'][] = $userids;
		$original_courses['$assignment_id'][] = $assignment_id;
        
    }

    try{

        $result = Requests::request_multiple( $requests );

        $all_courses = $original_courses['courses'];
        $all_canvas_users = $original_courses['users'];
		$assignment_ids = $original_courses['$assignment_id'];

        $insertdata = array();
        $process_403 = array();

        foreach($result as $rk => $req){
            $canvas_user_id = $all_canvas_users[$rk];
            $course_id = $all_courses[$rk];
			$assignment_id = $assignment_ids[$rk];
            $response = str_replace("'", "\'", $req->body);
            $response_decode = json_decode($req->body, true);

            $outcome_results = $response_decode['outcome_results'];
            $code = $req->status_code;
            $api_url = $req->url;
            $insert = true;

            if($code == 200){
                $status = 'pending';
				$check = true;
                if(empty($outcome_results)){
                    $status = 'no outcomes';
                }
            }elseif($code == 403){
                $process_403[] = $all_ids[$rk];
                unset($all_ids[$rk]);
                $insert = false;
            }else{
                $status = 'no action required';
            }
			
			if($check){
				 $status = 'pending';
			}

            if($insert){
                $insertdata[] = "(".$canvas_user_id.",".$course_id.",'".$response."',".$code.",'".$api_url."','".$status."')";
				$insertdata_check_trigger[] = "(".$course_id.",".$canvas_user_id.",'".$assignment_id."','".$status."')";
            }
        }

        if(!empty($insertdata)){

            $insert_all = $wpdb->query("INSERT INTO wp_canvas_course_api_results
                        (canvas_id, course_id, response, code, api_url, status)
                        VALUES ".implode(',',$insertdata));
			
			        $wpdb->query("INSERT INTO wp_canvas_check_trigger
                        (canvas_course_id, canvas_user_id, assignment_id, status)
                        VALUES ".implode(',',$insertdata_check_trigger));

            if($insert_all){
                $wpdb->query("UPDATE wp_canvas_course_submission SET status = 'processed' where id IN (".implode(',',$all_ids).")");
            }else{
                //echo 'Not processed';
            }

        }

        if(!empty($process_403)){
            $wpdb->query("UPDATE wp_canvas_course_submission SET status = 'pending' where id IN (".implode(',',$process_403).")");
        }

    

    }catch(Exception $e){

        $message = $e->getMessage();
        $to = array( 'ari@fronseye.com' );
        $subject = 'Passage Preparation - Process failed to insert records';
        $body = '<p>Date: '.date('m-d-Y H:i:s').'</p>';
        $body .= '<pError : '.$$message.'</p>';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail( $to, $subject, $body, $headers );

    }

 