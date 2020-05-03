<?php
/**
 * WP Dev WP Query
 *
 * @link https://github.com/maheshwaghmare/wp-dev-wp-query.git
 *
 * How to use?
 * 
 * $data = wp_dev_wp_query( array(
 * 		'post_type'      => 'post',
 * 		'fields'         => 'ids',
 * 		'no_found_rows'  => true,
 * 		'posts_per_page' => -1,
 * 	) );
 *
 * @package WP Dev WP Query
 * @since 1.0.0
 */

if ( ! class_exists( 'WP_Dev_WP_Query' ) ) :

	/**
	 * WP Dev Remote Request API
	 *
	 * @since 1.0.0
	 */
	class WP_Dev_WP_Query {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.0.0
		 */
		private static $instance;
		private $max_request_limit = 3;

		private $default_request_args = array(
			'post_type'      => 'post',
		
			// Query performance optimization.
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
		}

		/**
		 * Remote GET API Request
		 *
		 * @since x.x.x
		 *
		 * @param  mixed  $args    Request URL/Array of arguments for the API request.
		 * @return mixed            Return the API request result.
		 */
		public function query( $args = array() ) {

			$args = wp_parse_args( $args, $this->default_request_args );

			$force = isset( $args['force'] ) ? $args['force'] : false;
			$expiration = isset( $args['expiration'] ) ? sanitize_key( $args['expiration'] ) : MONTH_IN_SECONDS;


			$unique_request_key = md5( json_encode( $args ) );
			$transient_key = 'wp-dev-wp-query-' . $unique_request_key;

			/**
			 * If `force` is not set then check request maximum requests count. If it reach the maximum requests the return transient output.
			 */
			if( false === $force ) {
				// Check in transient and return its cached transient data.			
				// delete_transient( $transient_key );
				$transient_flag = get_transient( $transient_key );

				// Check Max Request Limit.
				// Avoid multiple requests and serve data from the transient.
				$request_limit_key = 'wp-dev-wp-query-limit-' . $unique_request_key;
				// delete_transient( $request_limit_key );
				$request_limit = (int) get_transient( $request_limit_key );
				if( $request_limit >= $this->max_request_limit ) {
					return array(
						'data' => $transient_flag,
						'message' => __( 'Reached MAX requests. Response from transient.', 'wp-dev-remote-request' ),
						'expiration'  => $expiration,
					);
				}
				set_transient( $request_limit_key, ($request_limit + 1), $expiration );

				// Serve response from the transient if transient data is not empty.
				if( false !== $transient_flag ) {
					return array(
						'data' => $transient_flag,
						'message' => 'Response from transient request.',
						'expiration'  => $expiration,
					);
				}
			}

			$query = new WP_Query( $args );
			$response = ! empty( $query->posts ) ? $query->posts : array();
			$result = array(
				'data' => $response,
				'message' => 'Response from live request.',
				'expiration'  => $expiration,
			);

			set_transient( $transient_key, $response, $expiration );

			return $result;
		}

	}

	// Initialize class object with 'get_instance()' method.
	WP_Dev_WP_Query::get_instance();

endif;

if( ! function_exists( 'wp_dev_wp_query' ) ) :
	function wp_dev_wp_query( $args = array() ) {
		return WP_Dev_WP_Query::get_instance()->query( $args );
	}
endif;
