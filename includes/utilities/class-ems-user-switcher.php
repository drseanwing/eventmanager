<?php
/**
 * User Switcher for Admin Testing
 *
 * Allows administrators to temporarily switch to another user account
 * to test dashboards (sponsor portal, presenter dashboard, etc.)
 * without needing separate login credentials.
 *
 * @package    Event_Management_System
 * @subpackage Utilities
 * @since      1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EMS_User_Switcher {

	/**
	 * Cookie name for storing the original admin user ID.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'ems_switch_olduser';

	/**
	 * Query parameter for initiating a switch.
	 *
	 * @var string
	 */
	const SWITCH_ACTION = 'ems_switch_to_user';

	/**
	 * Query parameter for switching back.
	 *
	 * @var string
	 */
	const SWITCH_BACK_ACTION = 'ems_switch_back';

	/**
	 * Logger instance.
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Register hooks.
	 */
	public function init_hooks() {
		// Handle switch actions early (before headers).
		add_action( 'init', array( $this, 'handle_switch_action' ), 1 );

		// Add "Switch To" link in admin user list.
		add_filter( 'user_row_actions', array( $this, 'add_switch_link' ), 10, 2 );

		// Add "Switch To" link on single sponsor admin edit page.
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_sponsor_switch_link' ) );

		// Show admin notice bar when switched.
		add_action( 'wp_footer', array( $this, 'render_switch_back_bar' ) );
		add_action( 'admin_footer', array( $this, 'render_switch_back_bar' ) );

		// Override admin bar hiding when admin is switched to an EMS user.
		add_action( 'after_setup_theme', array( $this, 'maybe_show_admin_bar_for_switched' ), 20 );
	}

	/**
	 * Check if the current session is a switched user.
	 *
	 * @return int|false Original admin user ID, or false if not switched.
	 */
	public function get_old_user_id() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}

		$data = $this->verify_switch_cookie( $_COOKIE[ self::COOKIE_NAME ] );
		if ( ! $data ) {
			return false;
		}

		return absint( $data['user_id'] );
	}

	/**
	 * Generate a switch-to URL for a given user.
	 *
	 * @param int $user_id Target user ID.
	 * @return string Nonced URL.
	 */
	public function get_switch_url( $user_id ) {
		return wp_nonce_url(
			add_query_arg( array(
				self::SWITCH_ACTION => $user_id,
			), admin_url() ),
			'ems_switch_to_' . $user_id,
			'_ems_switch_nonce'
		);
	}

	/**
	 * Generate a switch-back URL.
	 *
	 * @return string Nonced URL.
	 */
	public function get_switch_back_url() {
		$old_user = $this->get_old_user_id();
		if ( ! $old_user ) {
			return '';
		}

		return wp_nonce_url(
			add_query_arg( array(
				self::SWITCH_BACK_ACTION => 1,
			), admin_url() ),
			'ems_switch_back_' . $old_user,
			'_ems_switch_nonce'
		);
	}

	/**
	 * Handle switch and switch-back requests.
	 *
	 * Runs on 'init' at priority 1.
	 */
	public function handle_switch_action() {
		// Switch TO another user.
		if ( ! empty( $_GET[ self::SWITCH_ACTION ] ) && ! empty( $_GET['_ems_switch_nonce'] ) ) {
			$target_user_id = absint( $_GET[ self::SWITCH_ACTION ] );

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_ems_switch_nonce'] ) ), 'ems_switch_to_' . $target_user_id ) ) {
				wp_die( esc_html__( 'Invalid security token. Please try again.', 'event-management-system' ) );
			}

			// Only administrators can switch.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to switch users.', 'event-management-system' ) );
			}

			$target_user = get_user_by( 'ID', $target_user_id );
			if ( ! $target_user ) {
				wp_die( esc_html__( 'Target user not found.', 'event-management-system' ) );
			}

			// Store original admin ID in signed cookie.
			$current_user_id = get_current_user_id();
			$this->set_switch_cookie( $current_user_id );

			$this->logger->info(
				sprintf( 'Admin %d switching to user %d (%s)', $current_user_id, $target_user_id, $target_user->user_login ),
				EMS_Logger::CONTEXT_GENERAL
			);

			// Switch to target user.
			wp_clear_auth_cookie();
			wp_set_current_user( $target_user_id );
			wp_set_auth_cookie( $target_user_id, false );

			// Redirect to their dashboard.
			$user_helper = new EMS_User_Helper();
			$redirect    = $user_helper->get_user_dashboard_url( $target_user_id );

			wp_safe_redirect( $redirect );
			exit;
		}

		// Switch BACK to original admin.
		if ( ! empty( $_GET[ self::SWITCH_BACK_ACTION ] ) && ! empty( $_GET['_ems_switch_nonce'] ) ) {
			$old_user_id = $this->get_old_user_id();

			if ( ! $old_user_id ) {
				wp_die( esc_html__( 'No original user session found.', 'event-management-system' ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_ems_switch_nonce'] ) ), 'ems_switch_back_' . $old_user_id ) ) {
				wp_die( esc_html__( 'Invalid security token. Please try again.', 'event-management-system' ) );
			}

			$old_user = get_user_by( 'ID', $old_user_id );
			if ( ! $old_user || ! user_can( $old_user, 'manage_options' ) ) {
				wp_die( esc_html__( 'Original user not found or is no longer an administrator.', 'event-management-system' ) );
			}

			$this->logger->info(
				sprintf( 'Switching back to admin %d from user %d', $old_user_id, get_current_user_id() ),
				EMS_Logger::CONTEXT_GENERAL
			);

			// Clear the switch cookie.
			$this->clear_switch_cookie();

			// Switch back to admin.
			wp_clear_auth_cookie();
			wp_set_current_user( $old_user_id );
			wp_set_auth_cookie( $old_user_id, false );

			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Add "Switch To" link in admin Users list row actions.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_User $user    User object.
	 * @return array Modified actions.
	 */
	public function add_switch_link( $actions, $user ) {
		if ( ! current_user_can( 'manage_options' ) || $user->ID === get_current_user_id() ) {
			return $actions;
		}

		// Only show for EMS roles.
		$ems_roles = array( 'ems_sponsor', 'ems_presenter', 'ems_participant', 'ems_convenor', 'ems_reviewer' );
		$has_ems_role = false;
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $ems_roles, true ) ) {
				$has_ems_role = true;
				break;
			}
		}

		if ( ! $has_ems_role ) {
			return $actions;
		}

		$url = $this->get_switch_url( $user->ID );
		$actions['ems_switch_to'] = sprintf(
			'<a href="%s" style="color: #2271b1;">%s</a>',
			esc_url( $url ),
			esc_html__( 'Switch To', 'event-management-system' )
		);

		return $actions;
	}

	/**
	 * Add a "Switch To" link on the Sponsor CPT edit screen.
	 *
	 * Shows when viewing a sponsor post that has a linked user.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function add_sponsor_switch_link( $post ) {
		if ( ! $post || 'ems_sponsor' !== $post->post_type ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Find the user linked to this sponsor.
		$user_id = get_post_meta( $post->ID, '_ems_user_id', true );
		if ( ! $user_id ) {
			return;
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		$url = $this->get_switch_url( $user_id );
		?>
		<div class="misc-pub-section">
			<span class="dashicons dashicons-randomize" style="vertical-align: middle; color: #2271b1;"></span>
			<a href="<?php echo esc_url( $url ); ?>" style="color: #2271b1; font-weight: 600;">
				<?php echo esc_html( sprintf(
					/* translators: %s: user display name */
					__( 'View as %s', 'event-management-system' ),
					$user->display_name
				) ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render a floating bar at the top of the page when switched.
	 */
	public function render_switch_back_bar() {
		$old_user_id = $this->get_old_user_id();
		if ( ! $old_user_id ) {
			return;
		}

		$old_user = get_user_by( 'ID', $old_user_id );
		if ( ! $old_user ) {
			return;
		}

		$current_user  = wp_get_current_user();
		$switch_back_url = $this->get_switch_back_url();
		?>
		<div id="ems-user-switch-bar" style="
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 999999;
			background: #1d2327;
			color: #f0f0f1;
			padding: 8px 16px;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			font-size: 13px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 12px;
			box-shadow: 0 -2px 8px rgba(0,0,0,0.3);
		">
			<span class="dashicons dashicons-randomize" style="font-size: 16px; width: 16px; height: 16px; color: #72aee6;"></span>
			<span>
				<?php echo esc_html( sprintf(
					/* translators: 1: current user display name, 2: current user role */
					__( 'Viewing as: %1$s (%2$s)', 'event-management-system' ),
					$current_user->display_name,
					implode( ', ', $current_user->roles )
				) ); ?>
			</span>
			<a href="<?php echo esc_url( $switch_back_url ); ?>" style="
				background: #2271b1;
				color: #fff;
				padding: 4px 12px;
				border-radius: 3px;
				text-decoration: none;
				font-size: 12px;
				font-weight: 600;
			">
				<?php echo esc_html( sprintf(
					/* translators: %s: original admin display name */
					__( 'Switch Back to %s', 'event-management-system' ),
					$old_user->display_name
				) ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Keep admin bar visible when an admin is switched to an EMS user.
	 *
	 * Runs at priority 20 (after EMS_User_Helper hides it at default priority).
	 */
	public function maybe_show_admin_bar_for_switched() {
		if ( $this->get_old_user_id() ) {
			show_admin_bar( false );
		}
	}

	/**
	 * Set a signed cookie storing the original admin user ID.
	 *
	 * @param int $user_id Original admin user ID.
	 */
	private function set_switch_cookie( $user_id ) {
		$expiry = time() + ( 12 * HOUR_IN_SECONDS );
		$token  = $this->generate_cookie_token( $user_id, $expiry );

		$value = $user_id . '|' . $expiry . '|' . $token;

		setcookie(
			self::COOKIE_NAME,
			$value,
			$expiry,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		$_COOKIE[ self::COOKIE_NAME ] = $value;
	}

	/**
	 * Clear the switch cookie.
	 */
	private function clear_switch_cookie() {
		setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * Verify and parse the switch cookie value.
	 *
	 * @param string $cookie_value Raw cookie value.
	 * @return array|false Array with 'user_id' and 'expiry', or false.
	 */
	private function verify_switch_cookie( $cookie_value ) {
		$parts = explode( '|', $cookie_value );
		if ( count( $parts ) !== 3 ) {
			return false;
		}

		$user_id = absint( $parts[0] );
		$expiry  = absint( $parts[1] );
		$token   = $parts[2];

		if ( time() > $expiry ) {
			$this->clear_switch_cookie();
			return false;
		}

		$expected_token = $this->generate_cookie_token( $user_id, $expiry );
		if ( ! hash_equals( $expected_token, $token ) ) {
			$this->clear_switch_cookie();
			return false;
		}

		// Verify the user is still an admin.
		if ( ! user_can( $user_id, 'manage_options' ) ) {
			$this->clear_switch_cookie();
			return false;
		}

		return array(
			'user_id' => $user_id,
			'expiry'  => $expiry,
		);
	}

	/**
	 * Generate an HMAC token for the switch cookie.
	 *
	 * @param int $user_id User ID.
	 * @param int $expiry  Expiry timestamp.
	 * @return string HMAC hash.
	 */
	private function generate_cookie_token( $user_id, $expiry ) {
		return hash_hmac( 'sha256', $user_id . '|' . $expiry, wp_salt( 'auth' ) );
	}
}
