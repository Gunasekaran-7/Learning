<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://fronseye.com
 * @since      1.0.0
 *
 * @package    EPP - Dashboard
 * @subpackage EPP - Dashboard/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    EPP - Dashboard
 * @subpackage EPP - Dashboard/includes
 * @author     Developer <info@fronseye.com>
 */
class Passage_Customization {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Passage_Customization_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $apikey;
	public $emailid;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PASSAGE_CUSTOMIZATION_VERSION' ) ) {
			$this->version = PASSAGE_CUSTOMIZATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'EPP - Dashboard';
		$this->apikey = 'pat-na1-28abe1f2-1eaf-476a-827e-a2a824843a1a';
		$this->emailid = 159608759116;
		$this->init();
		
	}

	public function init(){

		//shortcodes

		add_shortcode('pearson_portal_shortcode', array($this, 'pearson_epp_dashbord')); // All levels are displayed. ( candidate , course , vouchers )
				 
		add_shortcode('portal_candidate_shortcode', array($this, 'portal_candidate_page')); //  All users showing  in Portal candidate page

		add_shortcode('portal_course_shortcode', array($this, 'portal_course_page')); //  All users purchased courses showing in portal course page

		add_shortcode('portal_voucher_shortcode',array($this, 'portal_voucher_page') ); // Epp shows the total vouchers purchased by the user, and also shows their status
		
		add_shortcode('portal_voucher_list', array($this, 'voucher_list_page')); // Total vouchers list showing based on the orders
		 
		// Add filters

		add_filter('template_include',array($this, 'custom_template_redirect')); // For voucher assing page  

		add_filter( 'body_class',array($this, 'my_body_classes') ); // This function adds the pg class before any page name.



        // Add Actions 

      	//crons
		add_action('csv_file_cron_process', array($this, 'csv_file_cron_process')); // Get the file in voucher_file table and process.
		add_action('individual_code_cron_process', array($this, 'individual_code_cron_process')); // Get the voucher code in voucher_individual table and process.

		// submission crons
		add_action('pearson_process_course_submission_results', array($this, 'pearson_process_course_submission_results')); // Get submission from canvas and import tha data into the canvas_course_submission_table.
		add_action('pearson_process_course_api_results', array($this, 'pearson_process_course_api_results')); // Get the data in the canvas_course_submission table and import the data into the canvas_data table. 
		add_action('pearson_process_check_trigger_cron',array($this, 'pearson_process_check_trigger_cron'));



		 


		// enqueue function
	    add_action('wp_enqueue_scripts', array($this, 'callback_for_setting_up_scripts'));
		add_action('wp_enqueue_scripts',array($this, 'localize_script'));


		//ajax for vouchers list page
		add_action('wp_ajax_voucher_list_ajax', array($this,'voucher_list_ajax'));
		add_action('wp_ajax_nopriv_voucher_list_ajax', array($this,'voucher_list_ajax'));

	    //For voucher Ajax filters on the voucher page
		add_action('wp_ajax_voucher_filter_ajax', array($this,'voucher_filter_ajax'));
		add_action('wp_ajax_nopriv_voucher_filter_ajax', array($this,'voucher_filter_ajax'));

		//For Courses Ajax 
		add_action('wp_ajax_course_test_list',array($this,'course_test_list'));
        add_action('wp_ajax_nopriv_course_test_list',array($this,'course_test_list'));

		//For course Ajax filters on the voucher page
		add_action('wp_ajax_course_search_filter',array($this,'course_search_filter'));
        add_action('wp_ajax_nopriv_course_search_filter',array($this,'course_search_filter'));

		//For Candidate Ajax 
		add_action('wp_ajax_candidate_course_name', array($this,'candidate_course_name'));
		add_action('wp_ajax_nopriv_candidate_course_name', array($this,'candidate_course_name'));

		add_action('wp_ajax_candidate_filter', array($this,'candidate_filter'));
		add_action('wp_ajax_nopriv_candidate_filter', array($this,'candidate_filter'));

		add_action('wp_ajax_candidate_inner_ajax', array($this,'candidate_inner_ajax'));
		add_action('wp_ajax_nopriv_candidate_inner_ajax', array($this,'candidate_inner_ajax'));

	}
	public function callback_for_setting_up_scripts() {
		
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/style.css',__FILE__),array(), '1.0.6', 'all');
		wp_enqueue_style('custom-style',plugins_url('/../assets/css/custom-icons.css',__FILE__),array(), '1.0.4', 'all');
		wp_enqueue_style('custom-dataTables',plugins_url('/../assets/css/dataTables.css',__FILE__),array(), '1.0.4', 'all');
		wp_enqueue_style('custom-jquery-dataTables-min',plugins_url('/../assets/css/jquery_dataTables_min.css',__FILE__),array(), '1.0.0', 'all');
		wp_enqueue_style('custom-colReorder-dataTables-min',plugins_url('/../assets/css/colReorder_dataTables_min.css',__FILE__));
		wp_enqueue_style('custom-responsive-dataTables-min',plugins_url('/../assets/css/responsive_dataTables_min.css',__FILE__));
		wp_enqueue_style('custom-editor-dataTables-min',plugins_url('/../assets/css/editor_dataTables_min.css',__FILE__));
		wp_enqueue_style('fixedColumns_dataTables_css',plugins_url('/../assets/css/fixedColumns_dataTables.css',__FILE__));

		//vouchers
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/voucher-list/voucher-assign.css',__FILE__),array(), '1.0.6', 'all');
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/voucher-list/voucher-list.css',__FILE__),array(), '1.0.6', 'all');
		//levels
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/candidate.css',__FILE__),array(), '1.0.6', 'all');
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/courses.css',__FILE__),array(), '1.0.6', 'all');
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/epp-portal.css',__FILE__),array(), '1.0.6', 'all');
		wp_enqueue_style('stylesheet',plugins_url('/../assets/css/pearson-css/voucher.css',__FILE__),array(), '1.0.6', 'all');

		
		wp_enqueue_script('jquery'); 

    	if(!is_page('registration')){

  			 // Register scripts
			wp_register_script('basic-dataTables-js', plugins_url('/../assets/js/dataTables.js', __FILE__), array('jquery'), '1.0.1');
			wp_register_script('canvasjs-min-js', plugins_url('/../assets/js/canvasjs_min.js', __FILE__), array('jquery'), '1.0.0');
			wp_register_script('jszip-min-js', plugins_url('/../assets/js/jszip_min.js', __FILE__), array('jquery'));
			wp_register_script('bootstrap-min-js', plugins_url('/../assets/js/bootstrap_min.js', __FILE__), array('jquery'));
			wp_register_script('pdfmake-min-js', plugins_url('/../assets/js/pdfmake_min.js', __FILE__), array('jquery'));
			wp_register_script('dataTables-editor-min-js', plugins_url('/../assets/js/dataTables-editor-min.js', __FILE__), array('jquery'));
			wp_register_script('dataTables-colReorder-min-js', plugins_url('/../assets/js/dataTables_colReorder_min.js', __FILE__), array('jquery'), '1.0.1');
			wp_register_script('dataTables-responsive-min-js', plugins_url('/../assets/js/dataTables_responsive_min.js', __FILE__), array('jquery'), '1.0.0');
			wp_register_script('vfs-fonts-js', plugins_url('/../assets/js/vfs_fonts.js', __FILE__), array('jquery'));
			wp_register_script('dataTables-buttons-js', plugins_url('/../assets/js/dataTables_buttons.js', __FILE__), array('jquery'));
			wp_register_script('buttons-dataTables-js', plugins_url('/../assets/js/buttons_dataTables.js', __FILE__), array('jquery'));
			wp_register_script('dataTables-fixedColumns-js', plugins_url('/../assets/js/dataTables-fixedColumns.js', __FILE__), array('jquery'));
			wp_register_script('fixedColumns-dataTables-js', plugins_url('/../assets/js/fixedColumns-dataTables.js', __FILE__), array('jquery'));
			wp_register_script('custom-dataTables-button-print-js', plugins_url('/../assets/js/buttons_print_min.js', __FILE__), array('jquery'));
			wp_register_script('custom-dataTables-buttons-html5-min-js', plugins_url('/../assets/js/buttons_html5_min.js', __FILE__), array('jquery'), '1.0.0');
			wp_register_script('dataTables-select-js', plugins_url('/../assets/js/dataTables_select.js', __FILE__), array('jquery'));
			wp_register_script('select-dataTables-js', plugins_url('/../assets/js/select_dataTables.js', __FILE__), array('jquery'));
			wp_register_script('dataTables-own-js', plugins_url('/../assets/js/voucher-js/dataTables-own.js', __FILE__), array('jquery'));

			// Enqueue scripts
			wp_enqueue_script('basic-dataTables-js'); // For basic DataTables functionality
			wp_enqueue_script('canvasjs-min-js'); // For charting (CanvasJS library)
			wp_enqueue_script('jszip-min-js'); // For zip file support (JSZip library)
			wp_enqueue_script('bootstrap-min-js'); // For Bootstrap JavaScript components
			wp_enqueue_script('pdfmake-min-js'); // For PDF generation support (pdfmake library)
			wp_enqueue_script('dataTables-editor-min-js'); // For DataTables editor functionality
			wp_enqueue_script('dataTables-colReorder-min-js'); // For column reordering in DataTables
			wp_enqueue_script('dataTables-responsive-min-js'); // For responsive tables in DataTables
			wp_enqueue_script('vfs-fonts-js'); // For font support in PDF generation (vfs_fonts)
			wp_enqueue_script('dataTables-buttons-js'); // For DataTables button controls (export, print, etc.)
			wp_enqueue_script('buttons-dataTables-js'); // For handling buttons in DataTables
			wp_enqueue_script('dataTables-fixedColumns-js'); // For fixing columns in DataTables
			wp_enqueue_script('fixedColumns-dataTables-js'); // For handling fixed columns in DataTables
			wp_enqueue_script('custom-dataTables-button-print-js'); // For custom print button in DataTables
			wp_enqueue_script('custom-dataTables-buttons-html5-min-js'); // For HTML5 export buttons in DataTables
			wp_enqueue_script('dataTables-select-js'); // For DataTables row selection functionality
			wp_enqueue_script('select-dataTables-js'); // For handling row selection in DataTables
			wp_enqueue_script('dataTables-own-js'); // For voucher list - Custom DataTables functionality

		}
			
		$is_logged_in = is_user_logged_in();
		$is_my_account_page = is_page('account');
		wp_register_script('basic-action-js', plugins_url('/../assets/js/basic_action_value.js', __FILE__),array("jquery"),'1.0.27'); 
		
			wp_enqueue_script('basic-action-js');
			wp_localize_script( 'basic-action-js', 'my_ajax_object', 
			array( 	
				'admin_ajax_url' => admin_url( 'admin-ajax.php' ),		
				'is_logged_in' => $is_logged_in,
        		'is_my_account_page' => $is_my_account_page
			) 
		);
	}
 

	// Newly added
	//added_by : Bharathi and Guna

	
	public function course_search_filter(){
		include('filters/search-course-filter.php');
		wp_die();
	}

	public function candidate_course_name() {
		include('levels/ajax/candidate-ajax.php');
		wp_die();
	}

	public function course_test_list(){
		include('levels/ajax/course-list-ajax.php');
		wp_die();
	}
	public function my_body_classes( $classes ) {
		global $post;
		
		$classes[] = 'pg-'.$post->post_name;
		$classes[] = 'bread_crumb';
		
		return $classes;
			
	}

	public function include_plugin_file_on_specific_page() {

		if(is_user_logged_in() && strpos($_SERVER['REQUEST_URI'], 'voucher-list/vouchers-list-assign') !== false){
           include('vouchers-list-assign.php');
            exit; 

        } 
    }

	public function custom_template_redirect($template) {
		global $wp_query;
 
		if (is_page('voucher-assing-page')) {
			$new_template = plugin_dir_path( __FILE__ ) . 'voucher-list/vouchers-list-assign.php';
 
			if (file_exists($new_template)) {
				return $new_template;
			}else{
				echo"test";
				exit;
			}
		}
		return $template;
	}

	 
    public function localize_script(){
        $site_data = array(
            'siteUrl' => get_site_url(),
            'ajaxUrl' =>get_site_url().'/wp-admin/admin-ajax.php',
        );
        echo '<script>var site_data = ' . json_encode($site_data) . ';</script>';
    }
	
	
	public function portal_voucher_page() {
		include('levels/voucher-page.php');
	}

	public function portal_course_page() {
		include('levels/course-page.php');
	}

	public function portal_candidate_page(){
		include('levels/candidate-page.php');
	}

	public function pearson_epp_dashbord(){
		include('levels/epp-dashboard.php');
	}

	public function voucher_list_page(){
		
		include('voucher-list/vouchers-list.php');
	}

	public function csv_file_cron_process(){
		include('crons/csv-file-cron.php');
	}

	 
	public function individual_code_cron_process(){
		include('crons/individual-code-cron.php');
	}

	public function pearson_process_course_api_results(){
		include('crons/api_results.php');
	}

	public function pearson_process_course_submission_results(){
		include('crons/submission_api.php');
	}

	public function pearson_process_check_trigger_cron(){
		include('crons/check_trigger_cron.php');
	}

	public function voucher_list_ajax(){
		include('voucher-list/vouchers-list-assing-ajax.php');
		die();
	}

	public function voucher_filter_ajax(){
		include('filters/voucher-filter.php');
		die();
	}

	public function candidate_filter(){
		include('filters/search-candidate-filter.php');
		die();
	}
	 

	public function candidate_inner_ajax(){
		include('levels/ajax/candidate-second-ajax/candidate-second-ajax.php');
		die();
	}
	
	public function find_percentage_from_canvas_data($c_users, $c_courses, $module = '', $extra_group = ''){

		global $wpdb;
	 
			if(empty($c_courses)){
				return array();
			}
			if(!empty($module)){
				$query = "SELECT quiz_type, SUM(score) as Score, SUM(percent) as Percent, AVG(score) as score_avg, ROUND(AVG(percent)*100, 0) as percent_score FROM wp_canvas_data WHERE quiz_type = '".$module."' AND course_id IN (".implode(',',array_unique($c_courses)).") and course_user_id IN (".implode(',',$c_users).") group by quiz_type";
			}else{
				$query = "SELECT quiz_type, SUM(score) as Score, SUM(percent) as Percent, AVG(score) as score_avg, ROUND(AVG(percent)*100, 0) as percent_score FROM wp_canvas_data WHERE course_id IN (".implode(',',array_unique($c_courses)).") and course_user_id IN (".implode(',',$c_users).") group by quiz_type";
			}
			if(!empty($extra_group)){
				$query = $query.' ,'.$extra_group;
			}
			$results = $wpdb->get_results($query, ARRAY_A);
			return $results;
	
	} 
 
}
 






 