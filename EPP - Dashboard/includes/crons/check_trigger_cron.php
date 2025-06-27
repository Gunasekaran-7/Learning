<?php

//The process of inserting the canvas outcomes from the api_results table into the final table by categorizing them by type.

//created_by:Guna 11-03-2025
 
 
 
   global $wpdb;

    $main_query = "SELECT * FROM wp_canvas_check_trigger 
    WHERE status = 'pending' 
    LIMIT 30";
    $get_all_results = $wpdb->get_results($main_query, ARRAY_A);
    if(empty($get_all_results)){
        return;
    }
	 $find_check_name = "SELECT assignment_name,course_id FROM wp_canvas_assignments WHERE assignment_id = $assignment_id_instert AND course_id = $course_id AND type = 'Check' ";
     $get_check_results = $wpdb->get_results($find_check_name, ARRAY_A);
	 $get_all_assignments = array_column($get_check_results,'assignment_name','course_id');
    try{
        $id = [];
        foreach($get_all_results as $result){
		
            $canvas_user_id = $result['canvas_user_id'];
            $course_id = $result['canvas_course_id'];
            $assignment_id_instert = $result['assignment_id'];
//             $find_check_name = "SELECT assignment_name FROM wp_canvas_assignments WHERE assignment_id = $assignment_id_instert AND course_id = $course_id AND type = 'Check' ";
//             $get_check_results = $wpdb->get_results($find_check_name, ARRAY_A);
            $id[] = $result['id'];

            $find_check_exist_query = "SELECT id FROM wp_canvas_check_test WHERE assignment_id = $assignment_id_instert AND canvas_user_id = $canvas_user_id AND course_id = $course_id ";
            $find_check_exist = $wpdb->get_results($find_check_exist_query, ARRAY_A);
            if(empty($find_check_exist)){
                $insertdata[] = "(".$canvas_user_id.",".$course_id.",".$assignment_id_instert.",'".$get_all_assignments[$course_id]."')";
            }
        }
        if(!empty($insertdata)){
            $insert_all = $wpdb->query("INSERT INTO wp_canvas_check_test
                        (canvas_user_id, course_id, assignment_id, assignment_name)
                        VALUES ".implode(',',$insertdata));
           if($insert_all){
                 $wpdb->query("UPDATE wp_canvas_check_trigger SET status = 'processed' where id IN (".implode(',',$id).")");
            }else{
                //echo 'Not processed';
            }
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
 