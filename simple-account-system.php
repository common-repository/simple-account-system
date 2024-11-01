<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
 * Plugin Name:       Simple Account System
 * Plugin URI: https://github.com/UlisesFreitas/simple-account-system
 * Description:       A plugin to replace the default user flow, for login, logout, account(profile), with extra contact info fields, phone, address, country, city, zip code.
This is a replacement for bring users a better experience on their accounts settings.
 * Version:           1.0.3
 * Author:            Ulises Freitas
 * Author URI: https://disenialia.com/
 * License:           GPL-2.0+
 * Text Domain:       simple-account-system
 * Thanks to http://code.tutsplus.com/series/build-a-custom-wordpress-user-flow--cms-816 for implementation of most of the functions
 */

add_action('load_textdomain', 'load_sas_language_files', 10, 2);

function load_sas_language_files($domain, $mofile)
{
    // Note: the plugin directory check is needed to prevent endless function nesting
    // since the new load_textdomain() call will apply the same hooks again.
    if ('simple-account-system' === $domain && plugin_dir_path($mofile) === WP_PLUGIN_DIR.'/simple-account-system/languages/')
    {
        load_textdomain('simple-account-system', WP_LANG_DIR.'/simple-account-system/'.$domain.'-'.get_locale().'.mo');
    }
}

add_action('plugins_loaded', 'sas_load_textdomain');
function sas_load_textdomain() {
	load_plugin_textdomain( 'simple-account-system', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

class Simple_Account_System {

	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {



		// Redirects
		add_action( 'login_form_login', array( $this, 'sas_redirect_to_sas_login' ) );
		add_filter( 'authenticate', array( $this, 'sas_maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'sas_redirect_after_login' ), 10, 3 );
		add_action( 'wp_logout', array( $this, 'sas_redirect_after_logout' ) );

		//add_action ( 'login_form_logout' , array( $this, 'sas_logout' ) );

		add_action( 'login_form_register', array( $this, 'sas_redirect_to_sas_register' ) );

		//add_action( 'sas_update_account', array( $this, 'sas_redirect_to_sas_account' ) );

		add_action( 'login_form_lostpassword', array( $this, 'sas_redirect_to_sas_lostpassword' ) );
		add_action( 'login_form_rp', array( $this, 'sas_redirect_to_sas_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'sas_redirect_to_sas_password_reset' ) );

		// Handlers for form posting actions
		add_action( 'login_form_register', array( $this, 'sas_do_sas_register_user' ) );
		add_action( 'login_form_lostpassword', array( $this, 'sas_do_password_lost' ) );
		add_action( 'login_form_rp', array( $this, 'sas_do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'sas_do_password_reset' ) );

		//add_action( 'sas_update_account', array( $this, 'sas_do_sas_account_user' ) );
		add_action( 'wp_loaded', array( $this, 'sas_do_sas_account_user' ) );

		// Other customizations
		add_filter( 'retrieve_password_message', array( $this, 'sas_replace_retrieve_password_message' ), 10, 4 );

		// Setup
		add_action( 'wp_print_footer_scripts', array( $this, 'sas_add_captcha_js_to_footer' ) );


		// Shortcodes
		add_shortcode( 'sas-login-form', array( $this, 'sas_render_login_form' ) );
		add_shortcode( 'sas-register-form', array( $this, 'sas_render_register_form' ) );
		add_shortcode( 'sas-password-lost-form', array( $this, 'sas_render_password_lost_form' ) );
		add_shortcode( 'sas-password-reset-form', array( $this, 'sas_render_password_reset_form' ) );
		add_shortcode( 'sas-user-profile-form', array( $this, 'sas_render_user_profile_form' ) );

		// Menu actions
		add_action( 'admin_menu', array( $this, 'sas_dashboard_menu' ) );
		add_action( 'wp_loaded', array( $this, 'sas_add_menu' ) );

		// Menu filters
		add_filter('wp_get_nav_menu_items', array($this, 'sas_custom_menu' ), 10, 2);
		add_filter( 'wp_nav_menu_items', array($this,'sas_add_login_out_item_to_menu'), 50, 2 );

		// User contact info
		add_filter('user_contactmethods', array($this,'sas_add_contact_methods'), 10, 2 );


		add_action( 'wp_enqueue_scripts', array($this, 'sas_stylesheet' ) );

	}


	/* bypass wordpress are you sure you want to logout screen when logging out of an already logged out account. */
	public function sas_logout() {
		if (!is_user_logged_in()) {
			$sas_redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '/';
			wp_safe_redirect( $sas_redirect_to );
			exit();
		} else {
			check_admin_referer('log-out');
			wp_logout();
			$sas_redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '/';
			wp_safe_redirect( $sas_redirect_to );
			exit();
		}
	}

	public function sas_stylesheet(){
		wp_enqueue_style( 'simple_account_system_style', plugins_url( 'styles.css', __FILE__ ) );
	}
	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function sas_plugin_activated() {
		// Information needed for creating the plugin's pages
		$page_definitions = array(
			'sas-login' => array(
				'title' => __( 'Sign In', 'simple-account-system' ),
				'content' => '[sas-login-form]'
			),
			'sas-password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'simple-account-system' ),
				'content' => '[sas-password-lost-form]'
			),
			'sas-password-reset' => array(
				'title' => __( 'Pick a New Password', 'simple-account-system' ),
				'content' => '[sas-password-reset-form]'
			),
			'sas-register' => array(
				'title' => __( 'Sign Up', 'simple-account-system' ),
				'content' => '[sas-register-form]'
			),
			'sas-account' => array(
				'title' => __( 'Your Account', 'simple-account-system' ),
				'content' => '[sas-user-profile-form]'
			)
		);

		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already
			$query = new WP_Query( 'pagename=' . $slug );
			if ( ! $query->have_posts() ) {
				// Add the page using the data from the array above
				wp_insert_post(
					array(
						'post_content'   => $page['content'],
						'post_name'      => $slug,
						'post_title'     => $page['title'],
						'post_status'    => 'publish',
						'post_type'      => 'page',
						'ping_status'    => 'closed',
						'comment_status' => 'closed',

					)
				);
			}
		}

		flush_rewrite_rules();
	}

	/**
	 * Add Profile Fields
	*/
	public function sas_add_contact_methods($profile_fields) {

		// Add new fields
		$profile_fields['sas_phone'] = __('Phone','simple-account-system');
		$profile_fields['sas_address'] = __('Address','simple-account-system');
		$profile_fields['sas_country'] = __( 'Country', 'simple-account-system' );
		$profile_fields['sas_city'] = __('City','simple-account-system');
		$profile_fields['sas_zipcode'] = __('Zip code','simple-account-system');


		return $profile_fields;
	}

	//
	// REDIRECT FUNCTIONS
	//

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function sas_redirect_to_sas_login() {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if ( is_user_logged_in() ) {
				$this->sas_redirect_logged_in_user();
				exit;
			}

			// The rest are redirected to the login page
			$login_url = home_url( 'sas-login' );
			if ( ! empty( $_REQUEST['redirect_to'] ) ) {
				$login_url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $login_url );
			}

			if ( ! empty( $_REQUEST['checkemail'] ) ) {
				$login_url = add_query_arg( 'checkemail', $_REQUEST['checkemail'], $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
	 * @param string            $username   The user name used to log in.
	 * @param string            $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function sas_maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );

				$login_url = home_url( 'sas-login' );
				$login_url = add_query_arg( 'login', $error_codes, $login_url );

				wp_redirect( $login_url );
				exit;
			}
		}

		return $user;
	}

	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL
	 */
	public function sas_redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = home_url();

		if ( ! isset( $user->ID ) ) {
			return $redirect_url;
		}

		if ( user_can( $user, 'manage_options' ) ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $redirect_to;
			}
		} else {
			// Non-admin users always go to their account page after login
			$redirect_url = home_url( 'sas-account' );
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */

	public function sas_redirect_after_logout() {

		$redirect_url = home_url( 'sas-login?logged_out=true' );
		wp_redirect( home_url() );

		exit;

	}

	/**
	 * Redirects the user to the custom registration page instead
	 * of wp-login.php?action=register.
	 */
	public function sas_redirect_to_sas_register() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->sas_redirect_logged_in_user();
			} else {
				wp_redirect( home_url( 'sas-register' ) );
			}
			exit;
		}
	}

	/**
	 * Redirects the user to the custom account page instead
	 * of wp-admin.php?action=profile.
	 */
	public function sas_redirect_to_sas_account() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				wp_redirect( home_url( 'sas-account' ) );
			}
			exit;
		}
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function sas_redirect_to_sas_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->sas_redirect_logged_in_user();
				exit;
			}

			wp_redirect( home_url( 'sas-password-lost' ) );
			exit;
		}
	}

	/**
	 * Redirects to the custom password reset page, or the login page
	 * if there are errors.
	 */
	public function sas_redirect_to_sas_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'sas-login?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'sas-login?login=invalidkey' ) );
				}
				exit;
			}

			$redirect_url = home_url( 'sas-password-reset' );
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

			wp_redirect( $redirect_url );
			exit;
		}
	}


	//
	// FORM RENDERING SHORTCODES
	//

	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
     * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function sas_render_login_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'simple-account-system' );
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );

			foreach ( $error_codes as $code ) {
				$errors []= $this->sas_get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;

		// Check if the user just registered
		$attributes['registered'] = isset( $_REQUEST['registered'] );

		// Check if the user just requested a new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

		// Check if user just updated password
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';

		// Render the login form using an external template
		return $this->sas_get_template_html( 'login_form', $attributes );
	}

	/**
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function sas_render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		$check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha');

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'simple-account-system' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'simple-account-system' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['register-errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['register-errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->sas_get_error_message( $error_code );
				}
			}
			if($check_simple_account_system_recaptcha == 1){
				// Retrieve recaptcha key
				$attributes['recaptcha_site_key'] = get_option( 'simple_account_system_recaptcha_site_key', null );
			}

			return $this->sas_get_template_html( 'register_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function sas_render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'simple-account-system' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->sas_get_error_message( $error_code );
				}
			}

			return $this->sas_get_template_html( 'password_lost_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function sas_render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'simple-account-system' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );

					foreach ( $error_codes as $code ) {
						$errors []= $this->sas_get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return $this->sas_get_template_html( 'password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'simple-account-system' );
			}
		}
	}

	/**
	 * A shortcode for rendering the user profile form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function sas_render_user_profile_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {

			$user_ID = get_current_user_id();
			$user_data = get_userdata($user_ID);

			$all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user_ID ) );
			$attributes['usermeta'] =  $all_meta_for_user;
			$attributes['userdata'] = $user_data;

			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['register-errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['register-errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->sas_get_error_message( $error_code );
				}
			}

			return $this->sas_get_template_html( 'user_profile_form', $attributes );
		}else{
			$this->sas_redirect_to_sas_login();
		}
	}
	/**
	 * Handles the user account updates.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 */
	public function sas_do_sas_account_user() {

		//echo $_SERVER['REQUEST_URI'];


		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_URI'] === '/sas-account/') {

			$redirect_url = home_url( 'sas-account' );

			$email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
			$first_name = sanitize_text_field( $_POST['first_name'] );
			$last_name = sanitize_text_field( $_POST['last_name'] );

			$phone = sanitize_text_field( $_POST['sas_phone'] );
			$address = sanitize_text_field( $_POST['sas_address'] );
			$country = sanitize_text_field( $_POST['sas_country'] );
			$city = sanitize_text_field( $_POST['sas_city'] );
			$zipcode = sanitize_text_field( $_POST['sas_zipcode'] );

			$user = get_user_by( 'email', $email );

			$result = $this->sas_update_account_user( $user->ID, $email, $first_name, $last_name, $phone, $address, $country, $city, $zipcode );

			if ( is_wp_error( $result ) ) {
				$errors = join( ',', $result->get_error_codes() );
				$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
			} else {

				$redirect_url = home_url( 'sas-account' );
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}
	/**
	 * An action function used to include the reCAPTCHA JavaScript file
	 * at the end of the page.
	 */
	public function sas_add_captcha_js_to_footer() {
		echo "<script src='https://www.google.com/recaptcha/api.js?hl=en'></script>";
	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param array  $attributes    The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	private function sas_get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		ob_start();

		do_action( 'simple_account_system_before_' . $template_name );

		require( 'templates/' . $template_name . '.php');

		do_action( 'simple_account_system_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	//
	// ACTION HANDLERS FOR FORMS IN FLOW
	//

	/**
	 * Handles the registration of a new user.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 */
	public function sas_do_sas_register_user() {

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$redirect_url = home_url( 'sas-register' );
			$check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha');

			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif ( $check_simple_account_system_recaptcha == 1 && ! $this->sas_verify_recaptcha() ) {
				// Recaptcha check failed, display error
				$redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
			} else {
				$email = $_POST['email'];
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );

				$phone = sanitize_text_field( $_POST['sas_phone'] );
				$address = sanitize_text_field( $_POST['sas_address'] );
				$country = sanitize_text_field( $_POST['sas_country'] );
				$city = sanitize_text_field( $_POST['sas_city'] );
				$zipcode = sanitize_text_field( $_POST['sas_zipcode'] );

				$result = $this->sas_register_user( $email, $first_name, $last_name );

				if ( is_wp_error( $result ) ) {
					// Parse errors into a string and append as parameter to redirect
					$errors = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
				} else {
					// Success, redirect to login page.

					update_usermeta( $result, 'sas_phone', $phone );
					update_usermeta( $result, 'sas_address', $address );
					update_usermeta( $result, 'sas_country', $city );
					update_usermeta( $result, 'sas_city', $city );
					update_usermeta( $result, 'sas_zipcode', $zipcode );


					$redirect_url = home_url( 'sas-login' );
					$redirect_url = add_query_arg( 'registered', $email, $redirect_url );
				}
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Validates and then completes the user account data
	 *
	 * @param string $email         The new user's email address
	 * @param string $first_name    The new user's first name
	 * @param string $last_name     The new user's last name
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function sas_update_account_user( $user_ID, $email, $first_name, $last_name, $sas_phone, $sas_address, $sas_country, $sas_city, $sas_zipcode ) {
		$errors = new WP_Error();


		// Email address is used as both username and email. It is also the only
		// parameter we need to validate
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->sas_get_error_message( 'email' ) );
			return $errors;
		}



		if ( username_exists( $email ) || email_exists( $email ) ) {

			$user_data = array(
			'ID'            => $user_ID,
			'user_login'    => $email,
			'user_email'    => $email,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'nickname'      => $first_name,
			'sas_phone'     => $sas_phone,
			'sas_address'   => $sas_address,
			'sas_country'   => $sas_country,
			'sas_city'      => $sas_city,
			'sas_zipcode'   => $sas_zipcode,
		);

		$user_id = wp_update_user( $user_data );

		}





		return $user_id;
	}
	/**
	 * Initiates password reset.
	 */
	public function sas_do_password_lost() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = home_url( 'sas-password-lost' );
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				$redirect_url = home_url( 'sas-login' );
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_url = $_REQUEST['redirect_to'];
				}
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function sas_do_password_reset() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'sas-login?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'sas-login?login=invalidkey' ) );
				}
				exit;
			}

			if ( isset( $_POST['pass1'] ) ) {
				if ( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match
					$redirect_url = home_url( 'sas-password-reset' );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				if ( empty( $_POST['pass1'] ) ) {
					// Password is empty
					$redirect_url = home_url( 'sas-password-reset' );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;

				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['pass1'] );
				wp_redirect( home_url( 'sas-login?password=changed' ) );
			} else {
				echo "Invalid request.";
			}

			exit;
		}
	}

	//
	// OTHER CUSTOMIZATIONS
	//

	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail message to send.
	 */
	public function sas_replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		// Create new message
		$msg  = __( 'Hello!', 'simple-account-system' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'simple-account-system' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'simple-account-system' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'simple-account-system' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'simple-account-system' ) . "\r\n";

		return $msg;
	}

	//
	// HELPER FUNCTIONS
	//

	/**
	 * Validates and then completes the new user signup process if all went well.
	 *
	 * @param string $email         The new user's email address
	 * @param string $first_name    The new user's first name
	 * @param string $last_name     The new user's last name
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function sas_register_user( $email, $first_name, $last_name ) {
		$errors = new WP_Error();

		// Email address is used as both username and email. It is also the only
		// parameter we need to validate
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->sas_get_error_message( 'email' ) );
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->sas_get_error_message( 'email_exists') );
			return $errors;
		}

		// Generate the password so that the subscriber will have to check email...
		$password = wp_generate_password( 12, false );

		$user_data = array(
			'user_login'    => $email,
			'user_email'    => $email,
			'user_pass'     => $password,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'nickname'      => $first_name,
		);

		$user_id = wp_insert_user( $user_data );
		wp_new_user_notification( $user_id, $password );

		return $user_id;
	}

	/**
	 * Checks that the reCAPTCHA parameter sent with the registration
	 * request is valid.
	 *
	 * @return bool True if the CAPTCHA is OK, otherwise false.
	 */
	private function sas_verify_recaptcha() {
		// This field is set by the recaptcha widget if check is successful
		if ( isset ( $_POST['g-recaptcha-response'] ) ) {
			$captcha_response = $_POST['g-recaptcha-response'];
		} else {
			return false;
		}

		// Verify the captcha response from Google
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret' => get_option( 'simple_account_system_recaptcha_secret_key' ),
					'response' => $captcha_response
				)
			)
		);

		$success = false;
		if ( $response && is_array( $response ) ) {
			$decoded_response = json_decode( $response['body'] );
			$success = $decoded_response->success;
		}

		return $success;
	}

	/**
	 * Redirects the user to the correct page depending on whether he / she
	 * is an admin or not.
	 *
	 * @param string $redirect_to   An optional redirect_to URL for admin users
	 */
	private function sas_redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			wp_redirect( home_url( 'sas-account' ) );
		}
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	private function sas_get_error_message( $error_code ) {
		switch ( $error_code ) {
			// Login errors

			case 'empty_username':
				return __( 'You do have an email address, right?', 'simple-account-system' );

			case 'empty_password':
				return __( 'You need to enter a password to login.', 'simple-account-system' );

			case 'invalid_username':
				return __(
					"We don't have any users with that email address. Maybe you used a different one when signing up?",
					'simple-account-system'
				);

			case 'incorrect_password':
				$err = __(
					"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?",
					'simple-account-system'
				);
				return sprintf( $err, wp_lostpassword_url() );

			// Registration errors

			case 'email':
				return __( 'The email address you entered is not valid.', 'simple-account-system' );

			case 'email_exists':
				return __( 'An account exists with this email address.', 'simple-account-system' );

			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'simple-account-system' );

			case 'captcha':
				return __( 'The Google reCAPTCHA check failed. Are you a robot?', 'simple-account-system' );

			// Lost password

			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'simple-account-system' );

			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'simple-account-system' );

			// Reset password

			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'simple-account-system' );

			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'simple-account-system' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'simple-account-system' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'simple-account-system' );

			default:
				break;
		}

		return __( 'An unknown error occurred. Please try again later.', 'simple-account-system' );
	}


	//
	// PLUGIN SETUP
	//

	public function sas_render_recaptcha_site_key_field() {
		$value = get_option( 'simple_account_system_recaptcha_site_key', '' );
		echo '<input type="text" id="simple_account_system_recaptcha_site_key" name="simple_account_system_recaptcha_site_key" value="' . esc_attr( $value ) . '" />';
	}

	public function sas_render_recaptcha_secret_key_field() {
		$value = get_option( 'simple_account_system_recaptcha_secret_key', '' );
		echo '<input type="text" id="simple_account_system_recaptcha_secret_key" name="simple_account_system_recaptcha_secret_key" value="' . esc_attr( $value ) . '" />';
	}

	public function sas_dashboard_menu(){
		add_menu_page( 'SAS. Login', 'SAS. Login', 'manage_options', 'simple-account-system/admin/settings.php', '', 'dashicons-admin-users', 7 );

	}

	public function sas_add_login_out_item_to_menu( $items = null, $args = null ){


			$redirect = ( is_home() ) ? false : get_permalink();
			if( is_user_logged_in( ) ){
				$link = '<a href="' . wp_logout_url( esc_url( home_url( '/' ) ) ) . '" title="' .  __( 'Sign Out', 'simple-account-system' ) .'">' . __( 'Sign Out', 'simple-account-system' ) . '</a>';
			}else{
				$link = '<a href="' . wp_login_url(   ) . '" title="' .  __( 'Sign In','simple-account-system' ) .'">' . __( 'Sign In','simple-account-system' ) . '</a>';
			}

			return $items.= '<li id="log-in-out-link" class="menu-item menu-type-link">'. $link . '</li>';

		return false;
	}

	public function sas_add_menu(){
		// Check if the menu exists
		$menu_name = 'Sign Up/Sign In/Sign Out';
		$menu_exists = wp_get_nav_menu_object( $menu_name );

		if( !$menu_exists ){
		    $menu_id = wp_create_nav_menu( $menu_name );

			/*
			// Set up default menu items
		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Sign In','simple-account-system'),
		        'menu-item-classes' => 'sas-sign-in',
		        'menu-item-url' => home_url( 'sas-login' ),
		        'menu-item-status' => 'publish'));

		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Sign Out','simple-account-system'),
		        'menu-item-classes' => 'sas-sign-out',
		        'menu-item-url' => wp_logout_url(),
		        'menu-item-status' => 'publish'));
			*/
		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Your Account','simple-account-system'),
		        'menu-item-classes' => 'sas-your-account',
		        'menu-item-url' => home_url( 'sas-account' ),
		        'menu-item-status' => 'publish'));

		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Sign Up','simple-account-system'),
		        'menu-item-classes' => 'sas-sign-up',
		        'menu-item-url' => home_url( 'sas-register' ),
		        'menu-item-status' => 'publish'));
		    /*
		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Forgot Your Password?','simple-account-system'),
		        'menu-item-classes' => 'sas-password-lost',
		        'menu-item-url' => home_url( 'sas-password-lost' ),
		        'menu-item-status' => 'publish'));

		    wp_update_nav_menu_item($menu_id, 0, array(
		        'menu-item-title' =>  __('Pick a New Password','simple-account-system'),
		        'menu-item-url' => home_url( 'sas-password-reset' ),
		        'menu-item-status' => 'publish'));
		    */

		}
	}

	public function sas_custom_menu($items, $menu){

		foreach((array)$items as $key => $item){
			/*
			 *  sign-in
			 	sign-out
			 	your-account
			 	forgot-your-password
			 	pick-a-new-password
			 	sign-up
			 */
			if ( is_user_logged_in() ){
				if($item->post_name == 'sign-up'){
					unset($items[$key]);
				}
				if($item->post_name == 'sign-in'){
					unset($items[$key]);
				}
				if($item->post_name == 'forgot-your-password'){
					unset($items[$key]);
				}
				if($item->post_name == 'pick-a-new-password'){
					unset($items[$key]);
				}

			}else{
				if($item->post_name == 'your-account'){
					unset($items[$key]);
				}
				if($item->post_name == 'sign-out'){
					unset($items[$key]);
				}
			}
		}
		return $items;

	}


}

add_filter('plugin_action_links', 'sas_action_links', 10, 2);
function sas_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {

        $settings_link = '<a href="' . get_admin_url() . 'admin.php?page=simple-account-system%2Fadmin%2Fsettings.php">'. __('Settings','simple-account-system') . '</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

add_action('init', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}

add_action ('init' , 'sas_prevent_profile_access');
function sas_prevent_profile_access(){
   		if (current_user_can('manage_options')) return '';

   		if (strpos ($_SERVER ['REQUEST_URI'] , 'wp-admin/profile.php' )){
      		wp_redirect (home_url('sas-account'));
 		 }

}

function sas_deactivation() {

	$menu_name = 'Sign Up/Sign In/Sign Out';
	$menu_exists = wp_get_nav_menu_object( $menu_name );
	wp_delete_nav_menu($menu_name);

	$page_definitions = array(
			'sas-login' => array(
				'title' => __( 'Sign In', 'simple-account-system' ),
				'content' => '[sas-login-form]'
			),
			'sas-password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'simple-account-system' ),
				'content' => '[sas-password-lost-form]'
			),
			'sas-password-reset' => array(
				'title' => __( 'Pick a New Password', 'simple-account-system' ),
				'content' => '[sas-password-reset-form]'
			),
			'sas-register' => array(
				'title' => __( 'Sign Up', 'simple-account-system' ),
				'content' => '[sas-register-form]'
			),
			'sas-account' => array(
				'title' => __( 'Your Account', 'simple-account-system' ),
				'content' => '[sas-user-profile-form]'
			)
		);

		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already
			$query = new WP_Query( 'pagename=' . $slug );
			if ( $query->have_posts() ) {
				while($query->have_posts()){
	        		$query->the_post();
					wp_delete_post(get_the_ID(), true);
				}
			}
		}


    // Clear the permalinks to remove our post type's rules
    flush_rewrite_rules();

}
register_deactivation_hook( __FILE__, 'sas_deactivation' );

$simple_account_system_pages_plugin = new Simple_Account_System();
register_activation_hook( __FILE__, array( 'Simple_Account_System', 'sas_plugin_activated' ) );