<?php
/**
 * User Helper Utility
 *
 * Handles automatic user creation for registrations and abstract submissions,
 * manages EMS-only user restrictions, and provides user management utilities.
 *
 * @package    EventManagementSystem
 * @subpackage Utilities
 * @since      1.2.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * EMS User Helper Class
 *
 * Manages automatic user creation and wp-admin restrictions.
 *
 * @since 1.2.0
 */
class EMS_User_Helper {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Initialize hooks for user management
	 *
	 * Should be called during plugin initialization.
	 *
	 * @since 1.2.0
	 */
	public function init_hooks() {
		// Block wp-admin access for EMS-only users
		add_action( 'admin_init', array( $this, 'maybe_redirect_from_admin' ), 1 );
		
		// Hide admin bar for EMS-only users
		add_action( 'after_setup_theme', array( $this, 'maybe_hide_admin_bar' ) );
		
		// Customize login redirect for EMS users
		add_filter( 'login_redirect', array( $this, 'custom_login_redirect' ), 10, 3 );
	}

	/**
	 * Create or get user for registration
	 *
	 * Creates a new user with ems_participant role if email doesn't exist,
	 * or returns existing user ID if found.
	 *
	 * @since 1.2.0
	 * @param array $data {
	 *     User data.
	 *
	 *     @type string $email      User email (required).
	 *     @type string $first_name First name (required).
	 *     @type string $last_name  Last name (required).
	 *     @type string $discipline Discipline (optional).
	 *     @type string $specialty  Specialty (optional).
	 * }
	 * @param bool  $send_notification Whether to send new user notification email.
	 * @return array {
	 *     Result array.
	 *
	 *     @type bool $success     Whether operation succeeded.
	 *     @type int  $user_id     User ID.
	 *     @type bool $created     Whether user was newly created.
	 *     @type string $message   Status message.
	 * }
	 */
	public function create_or_get_participant( $data, $send_notification = true ) {
		return $this->create_or_get_user( $data, 'ems_participant', $send_notification );
	}

	/**
	 * Create or get user for abstract submission
	 *
	 * Creates a new user with ems_presenter role if email doesn't exist,
	 * or returns existing user ID and upgrades to presenter if participant.
	 *
	 * @since 1.2.0
	 * @param array $data {
	 *     User data.
	 *
	 *     @type string $email      User email (required).
	 *     @type string $first_name First name (required).
	 *     @type string $last_name  Last name (required).
	 *     @type string $bio        Presenter bio (optional).
	 *     @type string $affiliation Organization/affiliation (optional).
	 * }
	 * @param bool  $send_notification Whether to send new user notification email.
	 * @return array {
	 *     Result array.
	 *
	 *     @type bool $success     Whether operation succeeded.
	 *     @type int  $user_id     User ID.
	 *     @type bool $created     Whether user was newly created.
	 *     @type string $message   Status message.
	 * }
	 */
	public function create_or_get_presenter( $data, $send_notification = true ) {
		$result = $this->create_or_get_user( $data, 'ems_presenter', $send_notification );
		
		// If user existed and was a participant, upgrade to presenter
		if ( $result['success'] && ! $result['created'] ) {
			$user = get_user_by( 'ID', $result['user_id'] );
			if ( $user && in_array( 'ems_participant', $user->roles, true ) ) {
				$user->remove_role( 'ems_participant' );
				$user->add_role( 'ems_presenter' );
				$this->logger->info(
					sprintf( 'Upgraded user %d from participant to presenter', $result['user_id'] ),
					EMS_Logger::CONTEXT_GENERAL
				);
			}
		}
		
		return $result;
	}

	/**
	 * Create or get user with specified role
	 *
	 * Core method for user creation/retrieval.
	 *
	 * @since 1.2.0
	 * @param array  $data              User data array.
	 * @param string $role              WordPress role to assign.
	 * @param bool   $send_notification Whether to send new user notification.
	 * @return array Result array.
	 */
	private function create_or_get_user( $data, $role, $send_notification = true ) {
		// Validate required fields
		if ( empty( $data['email'] ) || empty( $data['first_name'] ) || empty( $data['last_name'] ) ) {
			$this->logger->warning(
				'Missing required fields for user creation',
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'user_id' => 0,
				'created' => false,
				'message' => __( 'Email, first name, and last name are required.', 'event-management-system' ),
			);
		}

		$email = sanitize_email( $data['email'] );
		
		// Check for existing user
		$existing_user = get_user_by( 'email', $email );
		
		if ( $existing_user ) {
			$this->logger->debug(
				sprintf( 'Found existing user %d for email %s', $existing_user->ID, $email ),
				EMS_Logger::CONTEXT_GENERAL
			);
			
			// Add EMS role if user doesn't have one
			$ems_roles = array_keys( EMS_Roles::get_ems_roles() );
			$has_ems_role = false;
			foreach ( $existing_user->roles as $user_role ) {
				if ( in_array( $user_role, $ems_roles, true ) ) {
					$has_ems_role = true;
					break;
				}
			}
			
			if ( ! $has_ems_role ) {
				$existing_user->add_role( $role );
				$this->logger->info(
					sprintf( 'Added %s role to existing user %d', $role, $existing_user->ID ),
					EMS_Logger::CONTEXT_GENERAL
				);
			}
			
			return array(
				'success' => true,
				'user_id' => $existing_user->ID,
				'created' => false,
				'message' => __( 'Existing user found.', 'event-management-system' ),
			);
		}

		// Generate username from email
		$username = $this->generate_unique_username( $email );
		
		// Generate random password
		$password = wp_generate_password( 12, true, true );

		// Create user
		$user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $password,
			'first_name'   => sanitize_text_field( $data['first_name'] ),
			'last_name'    => sanitize_text_field( $data['last_name'] ),
			'display_name' => sanitize_text_field( $data['first_name'] . ' ' . $data['last_name'] ),
			'role'         => $role,
		) );

		if ( is_wp_error( $user_id ) ) {
			$this->logger->error(
				'Failed to create user: ' . $user_id->get_error_message(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'user_id' => 0,
				'created' => false,
				'message' => $user_id->get_error_message(),
			);
		}

		// Store additional meta
		if ( ! empty( $data['discipline'] ) ) {
			update_user_meta( $user_id, 'ems_discipline', sanitize_text_field( $data['discipline'] ) );
		}
		if ( ! empty( $data['specialty'] ) ) {
			update_user_meta( $user_id, 'ems_specialty', sanitize_text_field( $data['specialty'] ) );
		}
		if ( ! empty( $data['seniority'] ) ) {
			update_user_meta( $user_id, 'ems_seniority', sanitize_text_field( $data['seniority'] ) );
		}
		if ( ! empty( $data['bio'] ) ) {
			update_user_meta( $user_id, 'description', sanitize_textarea_field( $data['bio'] ) );
		}
		if ( ! empty( $data['affiliation'] ) ) {
			update_user_meta( $user_id, 'ems_affiliation', sanitize_text_field( $data['affiliation'] ) );
		}
		
		// Mark as EMS-created user
		update_user_meta( $user_id, '_ems_created_user', true );
		update_user_meta( $user_id, '_ems_created_date', current_time( 'mysql' ) );

		$this->logger->info(
			sprintf( 'Created new %s user %d for email %s', $role, $user_id, $email ),
			EMS_Logger::CONTEXT_GENERAL
		);

		// Send notification email
		if ( $send_notification ) {
			$this->send_new_user_notification( $user_id, $password, $role );
		}

		return array(
			'success' => true,
			'user_id' => $user_id,
			'created' => true,
			'message' => __( 'User account created successfully.', 'event-management-system' ),
		);
	}

	/**
	 * Generate unique username from email
	 *
	 * @since 1.2.0
	 * @param string $email Email address.
	 * @return string Unique username.
	 */
	private function generate_unique_username( $email ) {
		// Start with email prefix
		$base_username = sanitize_user( strtok( $email, '@' ), true );
		
		// Ensure minimum length
		if ( strlen( $base_username ) < 3 ) {
			$base_username = 'user_' . $base_username;
		}
		
		$username = $base_username;
		$suffix = 1;
		
		// Check for uniqueness
		while ( username_exists( $username ) ) {
			$username = $base_username . $suffix;
			$suffix++;
		}
		
		return $username;
	}

	/**
	 * Send new user notification email
	 *
	 * Sends a welcome email with login credentials.
	 *
	 * @since 1.2.0
	 * @param int    $user_id  User ID.
	 * @param string $password Plain text password.
	 * @param string $role     Role assigned.
	 */
	private function send_new_user_notification( $user_id, $password, $role ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		$role_names = EMS_Roles::get_ems_roles();
		$role_name = isset( $role_names[ $role ] ) ? $role_names[ $role ] : $role;
		
		// Get appropriate dashboard link
		$dashboard_link = home_url( '/' );
		if ( 'ems_presenter' === $role ) {
			$page_id = get_option( 'ems_page_presenter_dashboard' );
			if ( $page_id ) {
				$dashboard_link = get_permalink( $page_id );
			}
		} elseif ( 'ems_participant' === $role ) {
			$page_id = get_option( 'ems_page_my_registrations' );
			if ( $page_id ) {
				$dashboard_link = get_permalink( $page_id );
			}
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your %s Account', 'event-management-system' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			/* translators: %1$s: first name, %2$s: site name */
			__( 'Hello %1$s,

An account has been created for you on %2$s.

Your login details:
Username: %3$s
Password: %4$s

Login URL: %5$s

After logging in, you can access your dashboard here:
%6$s

You can change your password at any time by visiting your profile page after logging in.

Thank you,
%2$s', 'event-management-system' ),
			$user->first_name,
			get_bloginfo( 'name' ),
			$user->user_login,
			$password,
			wp_login_url(),
			$dashboard_link
		);

		wp_mail( $user->user_email, $subject, $message );

		$this->logger->debug(
			sprintf( 'Sent new user notification to %s', $user->user_email ),
			EMS_Logger::CONTEXT_EMAIL
		);
	}

	/**
	 * Check if user is EMS-only (no wp-admin access)
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 * @return bool True if user should be blocked from wp-admin.
	 */
	public function is_ems_only_user( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		// Administrators always have access
		if ( in_array( 'administrator', $user->roles, true ) ) {
			return false;
		}

		// Editors always have access
		if ( in_array( 'editor', $user->roles, true ) ) {
			return false;
		}

		// Check if user has any non-EMS roles that grant admin access
		$admin_roles = array( 'administrator', 'editor', 'author', 'contributor' );
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $admin_roles, true ) ) {
				return false;
			}
		}

		// EMS roles that should be blocked from wp-admin
		$restricted_roles = array( 'ems_participant', 'ems_presenter', 'ems_sponsor' );
		
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $restricted_roles, true ) ) {
				return true;
			}
		}

		// Convenors and reviewers can access wp-admin
		return false;
	}

	/**
	 * Redirect EMS-only users away from wp-admin
	 *
	 * @since 1.2.0
	 */
	public function maybe_redirect_from_admin() {
		// Don't redirect AJAX requests
		if ( wp_doing_ajax() ) {
			return;
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		
		if ( $this->is_ems_only_user( $user_id ) ) {
			// Get appropriate redirect URL
			$redirect_url = $this->get_user_dashboard_url( $user_id );
			
			$this->logger->debug(
				sprintf( 'Redirecting EMS-only user %d away from wp-admin', $user_id ),
				EMS_Logger::CONTEXT_GENERAL
			);
			
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Hide admin bar for EMS-only users
	 *
	 * @since 1.2.0
	 */
	public function maybe_hide_admin_bar() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( $this->is_ems_only_user( get_current_user_id() ) ) {
			show_admin_bar( false );
		}
	}

	/**
	 * Custom login redirect for EMS users
	 *
	 * @since 1.2.0
	 * @param string           $redirect_to           Default redirect URL.
	 * @param string           $requested_redirect_to Requested redirect URL.
	 * @param WP_User|WP_Error $user                  User object or error.
	 * @return string Redirect URL.
	 */
	public function custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		// Check if valid user
		if ( ! $user || is_wp_error( $user ) ) {
			return $redirect_to;
		}

		// If a specific redirect was requested and it's not wp-admin, use it
		if ( ! empty( $requested_redirect_to ) && strpos( $requested_redirect_to, 'wp-admin' ) === false ) {
			return $redirect_to;
		}

		// For EMS-only users, redirect to their dashboard
		if ( $this->is_ems_only_user( $user->ID ) ) {
			return $this->get_user_dashboard_url( $user->ID );
		}

		return $redirect_to;
	}

	/**
	 * Get appropriate dashboard URL for user
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 * @return string Dashboard URL.
	 */
	public function get_user_dashboard_url( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return home_url();
		}

		// Presenter dashboard
		if ( in_array( 'ems_presenter', $user->roles, true ) ) {
			$page_id = get_option( 'ems_page_presenter_dashboard' );
			if ( $page_id ) {
				return get_permalink( $page_id );
			}
		}

		// Participant dashboard (My Registrations)
		if ( in_array( 'ems_participant', $user->roles, true ) ) {
			$page_id = get_option( 'ems_page_my_registrations' );
			if ( $page_id ) {
				return get_permalink( $page_id );
			}
		}

		// Sponsor portal
		if ( in_array( 'ems_sponsor', $user->roles, true ) ) {
			$page_id = get_option( 'ems_page_sponsor_portal' );
			if ( $page_id ) {
				return get_permalink( $page_id );
			}
			return home_url();
		}

		// Default to home
		return home_url();
	}

	/**
	 * Link registration to user
	 *
	 * Updates registration record to associate with user ID.
	 *
	 * @since 1.2.0
	 * @param int $registration_id Registration ID.
	 * @param int $user_id         User ID.
	 * @return bool True on success.
	 */
	public function link_registration_to_user( $registration_id, $user_id ) {
		global $wpdb;
		
		$updated = $wpdb->update(
			$wpdb->prefix . 'ems_registrations',
			array( 'user_id' => $user_id ),
			array( 'id' => $registration_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false !== $updated ) {
			$this->logger->debug(
				sprintf( 'Linked registration %d to user %d', $registration_id, $user_id ),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return true;
		}

		return false;
	}

	/**
	 * Get or create user data from form fields
	 *
	 * Helper to extract user data from typical form submissions.
	 *
	 * @since 1.2.0
	 * @param array $form_data Form data array.
	 * @return array User data array suitable for create_or_get_* methods.
	 */
	public static function extract_user_data( $form_data ) {
		return array(
			'email'       => isset( $form_data['email'] ) ? $form_data['email'] : '',
			'first_name'  => isset( $form_data['first_name'] ) ? $form_data['first_name'] : '',
			'last_name'   => isset( $form_data['last_name'] ) ? $form_data['last_name'] : '',
			'discipline'  => isset( $form_data['discipline'] ) ? $form_data['discipline'] : '',
			'specialty'   => isset( $form_data['specialty'] ) ? $form_data['specialty'] : '',
			'seniority'   => isset( $form_data['seniority'] ) ? $form_data['seniority'] : '',
			'bio'         => isset( $form_data['presenter_bio'] ) ? $form_data['presenter_bio'] : '',
			'affiliation' => isset( $form_data['affiliation'] ) ? $form_data['affiliation'] : '',
		);
	}
}
