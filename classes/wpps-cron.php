<?php

if ( ! class_exists( 'WPPS_Cron' ) ) {

	/**
	 * Handles cron jobs and intervals
	 *
	 * Note: Because WP-Cron only fires hooks when HTTP requests are made, make sure that an external monitoring service pings the site regularly to ensure hooks are fired frequently
	 */
	class WPPS_Cron extends WPPS_Module {
		protected static $readable_properties  = array();
		protected static $writeable_properties = array();

		/*
		 * Magic methods
		 */

		/**
		 * Constructor
		 *
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}


		/*
		 * Static methods
		 */

		/**
		 * Adds custom intervals to the cron schedule.
		 *
		 * @mvc Model
		 *
		 * @param array $schedules
		 * @return array
		 */
		public static function add_custom_cron_intervals( $schedules ) {
			$schedules[ 'wpps_debug' ] = array(
				'interval' => 5,
				'display'  => 'Every 5 seconds'
			);

			$schedules[ 'wpps_10_minutes' ] = array(
				'interval' => 60 * 10,
				'display'  => 'Every 10 minutes'
			);

			$schedules[ 'wpps_1_hour' ] = array(
				'interval' => 60 * 60,
				'display'  => 'Every 1 hour'
			);

			$schedules[ 'wpps_6_hours' ] = array(
				'interval' => 60 * 60 * 6,
				'display'  => 'Every 6 hours'
			);

			$schedules[ 'wpps_1_day' ] = array(
				'interval' => 60 * 60 * 24,
				'display'  => 'Every 1 day'
			);

			$schedules[ 'wpps_custom_interval' ] = array(
				'interval' => 60 * $spvcmvu_job_interval,
				'display'  => 'Custom interval (minutes)'
			);

			return $schedules;
		}

		/**
		 * Example WP-Cron job
		 *
		 * @mvc Model
		 *
		 * @param array $schedules
		 * @return array
		 */
		public static function update_spvcmvu_counts() {
			// Do stuff
			add_notice( __METHOD__ . ' cron job fired.' );
			global $wpdb;
			// Needed to get post IDs
			$post_ids = $wpdb->get_results ("SELECT id FROM  $wpdb->wp_posts WHERE post_type = 'post'");
			//$query_string = ""; /* Get the option from somewhere */
			if ($spvcmcvu_query_type == 1) {
				// Post View Counter
				$query_string = "SELECT id,count FROM %SPVCMVU_WPDB_PREFIX%wp_post_views WHERE period='total';";
			} else if ($spvcmcvu_query_type == 0) {
				/*
				 * Arbitrary query
				 * To prevent malicious DB commands, immediately terminate if
				 * the qeruy doesn't begin with SELECT or contains a semicolon
				 * (possible sql command chaning).
				 */
				$res = explode(" ", $spvcmcvu_query_string, 2);
				if (strtolower($res[0]) != "select" || strpos($query_string, ';') != false) {
					return; //FIXME make warning?!!
				}
			} else {
				/* Invalid query type, do nothing */
				return;
			}
			str_replace("%SPVCMVU_WPDB_PREFIX%", $wpdb->prefix, $query_string);
			$result = $wpdb->query($query_string);
			foreach ($result as $r_elem) {
				$r_id = (int) $result["{$spvcmcvu_id_name}"];
				$r_count = (int) $result["{$spvcmcvu_count_name}"];
				update_post_meta($r_id, $spvcmvu_meta_key, $r_count);

				if (($key = array_search($r_id, $post_ids)) !== false) {
					/* We should always arrive here unless we have duplicate ID
					 * results form the DB.
					 */
					unset($post_ids[$key]);
				}
			}

			/*
			 * For those post IDs that aren't in the result, assign them a count of 0.
			 * Sometimes, plugins do not make DB entries for posts it does not edit.
			 */
			foreach ($post_ids as $r_id) {
				update_post_meta((int) $r_id, $spvcmvu_meta_key, 0);
			}
		}


		/*
		 * Instance methods
		 */

		/**
		 * Register callbacks for actions and filters
		 *
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'wpps_cron_update_spvcmvu_counts', __CLASS__ . '::update_spvcmvu_counts' );

			add_action( 'init',                  array( $this, 'init' ) );

			add_filter( 'cron_schedules',        __CLASS__ . '::add_custom_cron_intervals' );
		}

		/**
		 * Prepares site to use the plugin during activation
		 *
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
			if ( wp_next_scheduled( 'wpps_cron_update_spvcmvu_counts' ) === false ) {
				wp_schedule_event(
					current_time( 'timestamp' ),
					'wpps_1_hour',
					'wpps_cron_update_spvcmvu_counts'
				);
			}
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 *
		 * @mvc Controller
		 */
		public function deactivate() {
			wp_clear_scheduled_hook( 'wpps_cron_update_spvcmvu_counts' );
		}

		/**
		 * Initializes variables
		 *
		 * @mvc Controller
		 */
		public function init() {
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 *
		 * @mvc Model
		 *
		 * @param string $db_version
		 */
		public function upgrade( $db_version = 0 ) {
			/*
			if( version_compare( $db_version, 'x.y.z', '<' ) )
			{
				// Do stuff
			}
			*/
		}

		/**
		 * Checks that the object is in a correct state
		 *
		 * @mvc Model
		 *
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function is_valid( $property = 'all' ) {
			return true;
		}
	} // end WPPS_Cron
}