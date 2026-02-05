<?php
/**
 * User Roles Management
 *
 * Manages custom roles and capabilities for the Event Management System.
 * Provides role creation, capability management, and admin UI for user role assignment.
 *
 * @package    EventManagementSystem
 * @subpackage UserRoles
 * @since      1.0.0
 * @version    1.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * EMS Roles class.
 *
 * @since 1.0.0
 */
class EMS_Roles {

	/**
	 * Logger instance.
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Version for capability updates.
	 *
	 * @var string
	 */
	const CAPS_VERSION = '1.3.0';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Initialize roles and capabilities.
	 *
	 * Checks if capabilities need to be updated based on version.
	 *
	 * @since 1.0.0
	 */
	public function maybe_create_roles() {
		$current_version = get_option( 'ems_caps_version', '0' );
		
		// Always refresh if version changed or first install
		if ( version_compare( $current_version, self::CAPS_VERSION, '<' ) ) {
			$this->create_roles();
			$this->add_admin_capabilities();
			update_option( 'ems_caps_version', self::CAPS_VERSION );
			$this->logger->info( 'Roles and capabilities updated to version ' . self::CAPS_VERSION, EMS_Logger::CONTEXT_GENERAL );
		}
	}

	/**
	 * Force refresh all capabilities.
	 *
	 * Used when plugin is updated or capabilities need to be reset.
	 *
	 * @since 1.1.0
	 */
	public function refresh_capabilities() {
		$this->remove_all_capabilities();
		$this->create_roles();
		$this->add_admin_capabilities();
		update_option( 'ems_caps_version', self::CAPS_VERSION );
		$this->logger->info( 'Capabilities force refreshed', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Create all custom roles.
	 *
	 * @since 1.0.0
	 */
	private function create_roles() {
		$this->create_convenor_role();
		$this->create_reviewer_role();
		$this->create_presenter_role();
		$this->create_participant_role();
		$this->create_sponsor_role();
	}

	/**
	 * Create Convenor role.
	 *
	 * Event convenors can manage events, sessions, and review abstracts.
	 *
	 * @since 1.0.0
	 */
	private function create_convenor_role() {
		// Remove existing role first to update capabilities
		remove_role( 'ems_convenor' );
		
		add_role(
			'ems_convenor',
			__( 'Event Convenor', 'event-management-system' ),
			array(
				// Base WordPress
				'read'                          => true,
				'upload_files'                  => true,
				
				// Events - full access
				'edit_ems_event'                => true,
				'read_ems_event'                => true,
				'delete_ems_event'              => true,
				'edit_ems_events'               => true,
				'edit_others_ems_events'        => true,
				'publish_ems_events'            => true,
				'read_private_ems_events'       => true,
				'delete_ems_events'             => true,
				'delete_private_ems_events'     => true,
				'delete_published_ems_events'   => true,
				'delete_others_ems_events'      => true,
				'edit_private_ems_events'       => true,
				'edit_published_ems_events'     => true,
				
				// Sessions - full access
				'edit_ems_session'              => true,
				'read_ems_session'              => true,
				'delete_ems_session'            => true,
				'edit_ems_sessions'             => true,
				'edit_others_ems_sessions'      => true,
				'publish_ems_sessions'          => true,
				'read_private_ems_sessions'     => true,
				'delete_ems_sessions'           => true,
				'delete_private_ems_sessions'   => true,
				'delete_published_ems_sessions' => true,
				'delete_others_ems_sessions'    => true,
				'edit_private_ems_sessions'     => true,
				'edit_published_ems_sessions'   => true,
				
				// Abstracts - read and review
				'edit_ems_abstract'             => true,
				'read_ems_abstract'             => true,
				'edit_ems_abstracts'            => true,
				'edit_others_ems_abstracts'     => true,
				'read_private_ems_abstracts'    => true,
				
				// Custom EMS capabilities
				'view_ems_registrations'        => true,
				'export_ems_registrations'      => true,
				'add_ems_registration'          => true,
				'cancel_ems_registration'       => true,
				'review_ems_abstracts'          => true,
				'approve_ems_abstracts'         => true,
				'reject_ems_abstracts'          => true,
				'manage_ems_schedule'           => true,
				'publish_ems_schedule'          => true,
				'manage_ems_settings'           => false,
			)
		);
	}

	/**
	 * Create Reviewer role.
	 *
	 * Abstract reviewers can read and review assigned abstracts.
	 *
	 * @since 1.1.0
	 */
	private function create_reviewer_role() {
		remove_role( 'ems_reviewer' );
		
		add_role(
			'ems_reviewer',
			__( 'Abstract Reviewer', 'event-management-system' ),
			array(
				'read'                       => true,
				'read_ems_event'             => true,
				'read_ems_events'            => true,
				'read_ems_abstract'          => true,
				'read_ems_abstracts'         => true,
				'edit_ems_abstract'          => true,
				'read_private_ems_abstracts' => true,
				'review_ems_abstracts'       => true,
			)
		);
	}

	/**
	 * Create Presenter role.
	 *
	 * Presenters can submit and manage their own abstracts.
	 *
	 * @since 1.0.0
	 */
	private function create_presenter_role() {
		remove_role( 'ems_presenter' );
		
		add_role(
			'ems_presenter',
			__( 'Presenter', 'event-management-system' ),
			array(
				'read'                    => true,
				'upload_files'            => true,
				'read_ems_event'          => true,
				'read_ems_events'         => true,
				'edit_ems_abstract'       => true,
				'read_ems_abstract'       => true,
				'delete_ems_abstract'     => true,
				'edit_ems_abstracts'      => false,
				'publish_ems_abstracts'   => false,
				'submit_ems_abstract'     => true,
				'edit_own_ems_abstract'   => true,
				'delete_own_ems_abstract' => true,
				'upload_ems_files'        => true,
				'view_own_ems_sessions'   => true,
			)
		);
	}

	/**
	 * Create Participant role.
	 *
	 * Event participants can view events and register for sessions.
	 *
	 * @since 1.0.0
	 */
	private function create_participant_role() {
		remove_role( 'ems_participant' );
		
		add_role(
			'ems_participant',
			__( 'Event Participant', 'event-management-system' ),
			array(
				'read'                        => true,
				'read_ems_event'              => true,
				'read_ems_events'             => true,
				'read_ems_session'            => true,
				'read_ems_sessions'           => true,
				'read_published_ems_schedule' => true,
				'register_for_ems_event'      => true,
				'register_for_ems_session'    => true,
				'download_ems_public_files'   => true,
			)
		);
	}

	/**
	 * Create Sponsor role.
	 *
	 * Sponsors can access the sponsor portal and upload files.
	 *
	 * @since 1.0.0
	 */
	private function create_sponsor_role() {
		remove_role( 'ems_sponsor' );
		
		add_role(
			'ems_sponsor',
			__( 'Event Sponsor', 'event-management-system' ),
			array(
				'read'                       => true,
				'upload_files'               => true,
				'access_ems_sponsor_portal'  => true,
				'upload_ems_sponsor_files'   => true,
				'download_ems_sponsor_files' => true,
				'view_linked_ems_events'     => true,
			)
		);
	}

	/**
	 * Add all EMS capabilities to administrator role.
	 *
	 * @since 1.0.0
	 */
	private function add_admin_capabilities() {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			$this->logger->error( 'Administrator role not found', EMS_Logger::CONTEXT_GENERAL );
			return;
		}

		$caps = $this->get_all_capabilities();

		foreach ( $caps as $cap ) {
			$admin->add_cap( $cap );
		}

		$this->logger->debug( 'Administrator capabilities updated', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Get all EMS capabilities.
	 *
	 * @since 1.1.0
	 * @return array Array of capability names.
	 */
	public function get_all_capabilities() {
		return array(
			// Event capabilities
			'edit_ems_event',
			'read_ems_event',
			'delete_ems_event',
			'edit_ems_events',
			'edit_others_ems_events',
			'publish_ems_events',
			'read_private_ems_events',
			'delete_ems_events',
			'delete_private_ems_events',
			'delete_published_ems_events',
			'delete_others_ems_events',
			'edit_private_ems_events',
			'edit_published_ems_events',

			// Session capabilities
			'edit_ems_session',
			'read_ems_session',
			'delete_ems_session',
			'edit_ems_sessions',
			'edit_others_ems_sessions',
			'publish_ems_sessions',
			'read_private_ems_sessions',
			'delete_ems_sessions',
			'delete_private_ems_sessions',
			'delete_published_ems_sessions',
			'delete_others_ems_sessions',
			'edit_private_ems_sessions',
			'edit_published_ems_sessions',

			// Abstract capabilities
			'edit_ems_abstract',
			'read_ems_abstract',
			'delete_ems_abstract',
			'edit_ems_abstracts',
			'edit_others_ems_abstracts',
			'publish_ems_abstracts',
			'read_private_ems_abstracts',
			'delete_ems_abstracts',
			'delete_private_ems_abstracts',
			'delete_published_ems_abstracts',
			'delete_others_ems_abstracts',
			'edit_private_ems_abstracts',
			'edit_published_ems_abstracts',

			// Sponsor capabilities
			'edit_ems_sponsor',
			'read_ems_sponsor',
			'delete_ems_sponsor',
			'edit_ems_sponsors',
			'edit_others_ems_sponsors',
			'publish_ems_sponsors',
			'read_private_ems_sponsors',
			'delete_ems_sponsors',
			'delete_private_ems_sponsors',
			'delete_published_ems_sponsors',
			'delete_others_ems_sponsors',
			'edit_private_ems_sponsors',
			'edit_published_ems_sponsors',

			// Custom EMS capabilities
			'view_ems_registrations',
			'export_ems_registrations',
			'add_ems_registration',
			'cancel_ems_registration',
			'review_ems_abstracts',
			'approve_ems_abstracts',
			'reject_ems_abstracts',
			'manage_ems_schedule',
			'publish_ems_schedule',
			'manage_ems_settings',
			'manage_ems_users',
			'submit_ems_abstract',
			'upload_ems_files',
			'access_ems_sponsor_portal',
		);
	}

	/**
	 * Remove all EMS capabilities from all roles.
	 *
	 * Used for cleanup or refresh.
	 *
	 * @since 1.1.0
	 */
	private function remove_all_capabilities() {
		global $wp_roles;
		
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$caps = $this->get_all_capabilities();

		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Get all EMS roles.
	 *
	 * @since 1.1.0
	 * @return array Array of role slugs and names.
	 */
	public static function get_ems_roles() {
		return array(
			'ems_convenor'    => __( 'Event Convenor', 'event-management-system' ),
			'ems_reviewer'    => __( 'Abstract Reviewer', 'event-management-system' ),
			'ems_presenter'   => __( 'Presenter', 'event-management-system' ),
			'ems_participant' => __( 'Event Participant', 'event-management-system' ),
			'ems_sponsor'     => __( 'Event Sponsor', 'event-management-system' ),
		);
	}

	/**
	 * Assign EMS role to user.
	 *
	 * @since 1.1.0
	 * @param int    $user_id User ID.
	 * @param string $role    Role slug.
	 * @return bool True on success, false on failure.
	 */
	public static function assign_role( $user_id, $role ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		if ( ! in_array( $role, $ems_roles, true ) ) {
			return false;
		}

		// Remove any existing EMS roles
		foreach ( $ems_roles as $ems_role ) {
			$user->remove_role( $ems_role );
		}

		// Add new role
		$user->add_role( $role );

		return true;
	}

	/**
	 * Remove all EMS roles from user.
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 * @return bool True on success, false on failure.
	 */
	public static function remove_ems_roles( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		foreach ( $ems_roles as $ems_role ) {
			$user->remove_role( $ems_role );
		}

		return true;
	}

	/**
	 * Get users with specific EMS role.
	 *
	 * @since 1.1.0
	 * @param string $role Role slug.
	 * @return array Array of WP_User objects.
	 */
	public static function get_users_by_role( $role ) {
		return get_users( array(
			'role'    => $role,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		) );
	}

	/**
	 * Check if user has any EMS role.
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 * @return string|false Role slug or false if no EMS role.
	 */
	public static function get_user_ems_role( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $ems_roles, true ) ) {
				return $role;
			}
		}

		return false;
	}
}
