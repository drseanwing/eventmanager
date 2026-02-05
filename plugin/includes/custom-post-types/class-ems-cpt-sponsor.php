<?php
/**
 * Sponsor Custom Post Type
 *
 * Registers the sponsor CPT, meta boxes for organisation details,
 * and custom admin list table columns.
 *
 * @package    EventManagementSystem
 * @subpackage CustomPostTypes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_CPT_Sponsor {

	const POST_TYPE = 'ems_sponsor';
	const TAXONOMY_LEVEL = 'ems_sponsor_level';
	private $logger;

	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Register the sponsor post type and associated hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Sponsors', 'Post Type General Name', 'event-management-system' ),
			'singular_name'      => _x( 'Sponsor', 'Post Type Singular Name', 'event-management-system' ),
			'menu_name'          => __( 'Sponsors', 'event-management-system' ),
			'all_items'          => __( 'All Sponsors', 'event-management-system' ),
			'add_new_item'       => __( 'Add New Sponsor', 'event-management-system' ),
			'add_new'            => __( 'Add New', 'event-management-system' ),
			'new_item'           => __( 'New Sponsor', 'event-management-system' ),
			'edit_item'          => __( 'Edit Sponsor', 'event-management-system' ),
			'update_item'        => __( 'Update Sponsor', 'event-management-system' ),
			'view_item'          => __( 'View Sponsor', 'event-management-system' ),
			'search_items'       => __( 'Search Sponsors', 'event-management-system' ),
			'not_found'          => __( 'Not found', 'event-management-system' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'event-management-system' ),
		);

		$args = array(
			'label'               => __( 'Sponsor', 'event-management-system' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'ems-dashboard',
			'capability_type'     => array( 'ems_sponsor', 'ems_sponsors' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
		);

		register_post_type( self::POST_TYPE, $args );

		// Meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Status change logging
		add_action( 'transition_post_status', array( $this, 'log_status_change' ), 10, 3 );

		// Admin list table columns
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_custom_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'set_sortable_columns' ) );

		$this->logger->debug( 'Sponsor post type registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Register taxonomies.
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {
		register_taxonomy( self::TAXONOMY_LEVEL, array( self::POST_TYPE ), array(
			'labels'       => array( 'name' => __( 'Sponsor Levels', 'event-management-system' ) ),
			'hierarchical' => true,
			'public'       => true,
		) );
	}

	// ==========================================
	// META BOXES
	// ==========================================

	/**
	 * Register meta boxes for the sponsor edit screen.
	 *
	 * @since 1.5.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ems_sponsor_organisation',
			__( 'Organisation Details', 'event-management-system' ),
			array( $this, 'render_organisation_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_sponsor_contacts',
			__( 'Primary Contact & Signatory', 'event-management-system' ),
			array( $this, 'render_contacts_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_sponsor_industry',
			__( 'Industry Classification', 'event-management-system' ),
			array( $this, 'render_industry_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'ems_sponsor_products',
			__( 'Products & Scope', 'event-management-system' ),
			array( $this, 'render_products_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'ems_sponsor_legal',
			__( 'Legal, Insurance & Compliance', 'event-management-system' ),
			array( $this, 'render_legal_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'ems_sponsor_conflict',
			__( 'Conflict of Interest', 'event-management-system' ),
			array( $this, 'render_conflict_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Render the Organisation Details meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_organisation_meta_box( $post ) {
		wp_nonce_field( 'ems_sponsor_meta_nonce', 'ems_sponsor_meta_nonce' );

		$legal_name         = get_post_meta( $post->ID, '_ems_sponsor_legal_name', true );
		$trading_name       = get_post_meta( $post->ID, '_ems_sponsor_trading_name', true );
		$abn                = get_post_meta( $post->ID, '_ems_sponsor_abn', true );
		$acn                = get_post_meta( $post->ID, '_ems_sponsor_acn', true );
		$registered_address = get_post_meta( $post->ID, '_ems_sponsor_registered_address', true );
		$website_url        = get_post_meta( $post->ID, '_ems_sponsor_website_url', true );
		$country            = get_post_meta( $post->ID, '_ems_sponsor_country', true );
		$parent_company     = get_post_meta( $post->ID, '_ems_sponsor_parent_company', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ems_sponsor_legal_name"><?php esc_html_e( 'Legal Name', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_legal_name" name="legal_name" value="<?php echo esc_attr( $legal_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_trading_name"><?php esc_html_e( 'Trading Name', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_trading_name" name="trading_name" value="<?php echo esc_attr( $trading_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_abn"><?php esc_html_e( 'ABN', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_abn" name="abn" value="<?php echo esc_attr( $abn ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_acn"><?php esc_html_e( 'ACN', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_acn" name="acn" value="<?php echo esc_attr( $acn ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_registered_address"><?php esc_html_e( 'Registered Address', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_registered_address" name="registered_address" rows="3" class="large-text"><?php echo esc_textarea( $registered_address ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_website_url"><?php esc_html_e( 'Website URL', 'event-management-system' ); ?></label></th>
				<td><input type="url" id="ems_sponsor_website_url" name="website_url" value="<?php echo esc_url( $website_url ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_country"><?php esc_html_e( 'Country', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_country" name="country" value="<?php echo esc_attr( $country ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_parent_company"><?php esc_html_e( 'Parent Company', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_parent_company" name="parent_company" value="<?php echo esc_attr( $parent_company ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Primary Contact & Signatory meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_contacts_meta_box( $post ) {
		$contact_name            = get_post_meta( $post->ID, '_ems_sponsor_contact_name', true );
		$contact_role            = get_post_meta( $post->ID, '_ems_sponsor_contact_role', true );
		$contact_email           = get_post_meta( $post->ID, '_ems_sponsor_contact_email', true );
		$contact_phone           = get_post_meta( $post->ID, '_ems_sponsor_contact_phone', true );
		$signatory_name          = get_post_meta( $post->ID, '_ems_sponsor_signatory_name', true );
		$signatory_title         = get_post_meta( $post->ID, '_ems_sponsor_signatory_title', true );
		$signatory_email         = get_post_meta( $post->ID, '_ems_sponsor_signatory_email', true );
		$marketing_contact_name  = get_post_meta( $post->ID, '_ems_sponsor_marketing_contact_name', true );
		$marketing_contact_email = get_post_meta( $post->ID, '_ems_sponsor_marketing_contact_email', true );
		?>
		<table class="form-table">
			<tr>
				<th colspan="2"><h3 style="margin: 0;"><?php esc_html_e( 'Primary Contact', 'event-management-system' ); ?></h3></th>
			</tr>
			<tr>
				<th><label for="ems_sponsor_contact_name"><?php esc_html_e( 'Contact Name', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_contact_name" name="contact_name" value="<?php echo esc_attr( $contact_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_contact_role"><?php esc_html_e( 'Contact Role', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_contact_role" name="contact_role" value="<?php echo esc_attr( $contact_role ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_contact_email"><?php esc_html_e( 'Contact Email', 'event-management-system' ); ?></label></th>
				<td><input type="email" id="ems_sponsor_contact_email" name="contact_email" value="<?php echo esc_attr( $contact_email ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_contact_phone"><?php esc_html_e( 'Contact Phone', 'event-management-system' ); ?></label></th>
				<td><input type="tel" id="ems_sponsor_contact_phone" name="contact_phone" value="<?php echo esc_attr( $contact_phone ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th colspan="2"><h3 style="margin: 0;"><?php esc_html_e( 'Signatory', 'event-management-system' ); ?></h3></th>
			</tr>
			<tr>
				<th><label for="ems_sponsor_signatory_name"><?php esc_html_e( 'Signatory Name', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_signatory_name" name="signatory_name" value="<?php echo esc_attr( $signatory_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_signatory_title"><?php esc_html_e( 'Signatory Title', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_signatory_title" name="signatory_title" value="<?php echo esc_attr( $signatory_title ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_signatory_email"><?php esc_html_e( 'Signatory Email', 'event-management-system' ); ?></label></th>
				<td><input type="email" id="ems_sponsor_signatory_email" name="signatory_email" value="<?php echo esc_attr( $signatory_email ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th colspan="2"><h3 style="margin: 0;"><?php esc_html_e( 'Marketing Contact', 'event-management-system' ); ?></h3></th>
			</tr>
			<tr>
				<th><label for="ems_sponsor_marketing_contact_name"><?php esc_html_e( 'Marketing Contact Name', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_marketing_contact_name" name="marketing_contact_name" value="<?php echo esc_attr( $marketing_contact_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_marketing_contact_email"><?php esc_html_e( 'Marketing Contact Email', 'event-management-system' ); ?></label></th>
				<td><input type="email" id="ems_sponsor_marketing_contact_email" name="marketing_contact_email" value="<?php echo esc_attr( $marketing_contact_email ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Industry Classification meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_industry_meta_box( $post ) {
		$industry_sectors = get_post_meta( $post->ID, '_ems_sponsor_industry_sectors', true );
		$ma_member        = get_post_meta( $post->ID, '_ems_sponsor_ma_member', true );
		$mtaa_member      = get_post_meta( $post->ID, '_ems_sponsor_mtaa_member', true );
		$other_codes      = get_post_meta( $post->ID, '_ems_sponsor_other_codes', true );
		$tga_number       = get_post_meta( $post->ID, '_ems_sponsor_tga_number', true );

		if ( ! is_array( $industry_sectors ) ) {
			$industry_sectors = array();
		}
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Industry Sectors', 'event-management-system' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( EMS_Sponsor_Meta::INDUSTRY_SECTORS as $key => $label ) : ?>
							<label style="display: block; margin-bottom: 5px;">
								<input type="checkbox" name="industry_sectors[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $industry_sectors, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_ma_member"><?php esc_html_e( 'Medicines Australia Member', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_ma_member" name="ma_member" value="1" <?php checked( $ma_member ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_mtaa_member"><?php esc_html_e( 'MTAA Member', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_mtaa_member" name="mtaa_member" value="1" <?php checked( $mtaa_member ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_other_codes"><?php esc_html_e( 'Other Industry Codes', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_other_codes" name="other_codes" rows="3" class="large-text"><?php echo esc_textarea( $other_codes ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_tga_number"><?php esc_html_e( 'TGA Sponsor Number', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_tga_number" name="tga_number" value="<?php echo esc_attr( $tga_number ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Products & Scope meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_products_meta_box( $post ) {
		$products             = get_post_meta( $post->ID, '_ems_sponsor_products', true );
		$artg_listings        = get_post_meta( $post->ID, '_ems_sponsor_artg_listings', true );
		$scheduling_class     = get_post_meta( $post->ID, '_ems_sponsor_scheduling_class', true );
		$device_class         = get_post_meta( $post->ID, '_ems_sponsor_device_class', true );
		$non_artg_product     = get_post_meta( $post->ID, '_ems_sponsor_non_artg_product', true );
		$off_label            = get_post_meta( $post->ID, '_ems_sponsor_off_label', true );
		$tga_safety_alert     = get_post_meta( $post->ID, '_ems_sponsor_tga_safety_alert', true );
		$educational_relation = get_post_meta( $post->ID, '_ems_sponsor_educational_relation', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ems_sponsor_products"><?php esc_html_e( 'Products / Services', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_products" name="products" rows="4" class="large-text"><?php echo esc_textarea( $products ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_artg_listings"><?php esc_html_e( 'ARTG Listings', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_artg_listings" name="artg_listings" rows="3" class="large-text"><?php echo esc_textarea( $artg_listings ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_scheduling_class"><?php esc_html_e( 'Scheduling Class', 'event-management-system' ); ?></label></th>
				<td>
					<select id="ems_sponsor_scheduling_class" name="scheduling_class">
						<option value=""><?php esc_html_e( '-- Select --', 'event-management-system' ); ?></option>
						<?php foreach ( EMS_Sponsor_Meta::SCHEDULING_CLASSES as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $scheduling_class, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_device_class"><?php esc_html_e( 'Device Class', 'event-management-system' ); ?></label></th>
				<td>
					<select id="ems_sponsor_device_class" name="device_class">
						<option value=""><?php esc_html_e( '-- Select --', 'event-management-system' ); ?></option>
						<?php foreach ( EMS_Sponsor_Meta::DEVICE_CLASSES as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $device_class, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_non_artg_product"><?php esc_html_e( 'Non-ARTG Product', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_non_artg_product" name="non_artg_product" value="1" <?php checked( $non_artg_product ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_off_label"><?php esc_html_e( 'Off-Label Use', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_off_label" name="off_label" value="1" <?php checked( $off_label ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_tga_safety_alert"><?php esc_html_e( 'TGA Safety Alert', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_tga_safety_alert" name="tga_safety_alert" value="1" <?php checked( $tga_safety_alert ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_educational_relation"><?php esc_html_e( 'Educational Relation', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_educational_relation" name="educational_relation" rows="3" class="large-text"><?php echo esc_textarea( $educational_relation ); ?></textarea></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Legal, Insurance & Compliance meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_legal_meta_box( $post ) {
		$public_liability_confirmed  = get_post_meta( $post->ID, '_ems_sponsor_public_liability_confirmed', true );
		$public_liability_insurer    = get_post_meta( $post->ID, '_ems_sponsor_public_liability_insurer', true );
		$public_liability_policy     = get_post_meta( $post->ID, '_ems_sponsor_public_liability_policy', true );
		$product_liability_confirmed = get_post_meta( $post->ID, '_ems_sponsor_product_liability_confirmed', true );
		$professional_indemnity      = get_post_meta( $post->ID, '_ems_sponsor_professional_indemnity', true );
		$workers_comp_confirmed      = get_post_meta( $post->ID, '_ems_sponsor_workers_comp_confirmed', true );
		$code_compliance_agreed      = get_post_meta( $post->ID, '_ems_sponsor_code_compliance_agreed', true );
		$compliance_process          = get_post_meta( $post->ID, '_ems_sponsor_compliance_process', true );
		$adverse_findings            = get_post_meta( $post->ID, '_ems_sponsor_adverse_findings', true );
		$legal_proceedings           = get_post_meta( $post->ID, '_ems_sponsor_legal_proceedings', true );
		$transparency_obligations    = get_post_meta( $post->ID, '_ems_sponsor_transparency_obligations', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ems_sponsor_public_liability_confirmed"><?php esc_html_e( 'Public Liability Insurance Confirmed', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_public_liability_confirmed" name="public_liability_confirmed" value="1" <?php checked( $public_liability_confirmed ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_public_liability_insurer"><?php esc_html_e( 'Public Liability Insurer', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_public_liability_insurer" name="public_liability_insurer" value="<?php echo esc_attr( $public_liability_insurer ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_public_liability_policy"><?php esc_html_e( 'Public Liability Policy Number', 'event-management-system' ); ?></label></th>
				<td><input type="text" id="ems_sponsor_public_liability_policy" name="public_liability_policy" value="<?php echo esc_attr( $public_liability_policy ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_product_liability_confirmed"><?php esc_html_e( 'Product Liability Insurance Confirmed', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_product_liability_confirmed" name="product_liability_confirmed" value="1" <?php checked( $product_liability_confirmed ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_professional_indemnity"><?php esc_html_e( 'Professional Indemnity Insurance', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_professional_indemnity" name="professional_indemnity" value="1" <?php checked( $professional_indemnity ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_workers_comp_confirmed"><?php esc_html_e( 'Workers Compensation Confirmed', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_workers_comp_confirmed" name="workers_comp_confirmed" value="1" <?php checked( $workers_comp_confirmed ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_code_compliance_agreed"><?php esc_html_e( 'Code of Conduct Compliance Agreed', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_code_compliance_agreed" name="code_compliance_agreed" value="1" <?php checked( $code_compliance_agreed ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_compliance_process"><?php esc_html_e( 'Compliance Process in Place', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_compliance_process" name="compliance_process" value="1" <?php checked( $compliance_process ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_adverse_findings"><?php esc_html_e( 'Adverse Findings', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_adverse_findings" name="adverse_findings" rows="3" class="large-text"><?php echo esc_textarea( $adverse_findings ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_legal_proceedings"><?php esc_html_e( 'Legal Proceedings', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_legal_proceedings" name="legal_proceedings" rows="3" class="large-text"><?php echo esc_textarea( $legal_proceedings ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_transparency_obligations"><?php esc_html_e( 'Transparency Obligations', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_transparency_obligations" name="transparency_obligations" rows="3" class="large-text"><?php echo esc_textarea( $transparency_obligations ); ?></textarea></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Conflict of Interest meta box.
	 *
	 * @since 1.5.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_conflict_meta_box( $post ) {
		$qld_health_aware              = get_post_meta( $post->ID, '_ems_sponsor_qld_health_aware', true );
		$personal_relationships        = get_post_meta( $post->ID, '_ems_sponsor_personal_relationships', true );
		$procurement_conflicts         = get_post_meta( $post->ID, '_ems_sponsor_procurement_conflicts', true );
		$transfers_of_value            = get_post_meta( $post->ID, '_ems_sponsor_transfers_of_value', true );
		$preferential_treatment_agreed = get_post_meta( $post->ID, '_ems_sponsor_preferential_treatment_agreed', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ems_sponsor_qld_health_aware"><?php esc_html_e( 'QLD Health Gifting Policy Aware', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_qld_health_aware" name="qld_health_aware" value="1" <?php checked( $qld_health_aware ); ?>></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_personal_relationships"><?php esc_html_e( 'Personal Relationships', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_personal_relationships" name="personal_relationships" rows="3" class="large-text"><?php echo esc_textarea( $personal_relationships ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_procurement_conflicts"><?php esc_html_e( 'Procurement Conflicts', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_procurement_conflicts" name="procurement_conflicts" rows="3" class="large-text"><?php echo esc_textarea( $procurement_conflicts ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_transfers_of_value"><?php esc_html_e( 'Transfers of Value', 'event-management-system' ); ?></label></th>
				<td><textarea id="ems_sponsor_transfers_of_value" name="transfers_of_value" rows="3" class="large-text"><?php echo esc_textarea( $transfers_of_value ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ems_sponsor_preferential_treatment_agreed"><?php esc_html_e( 'No Preferential Treatment Agreed', 'event-management-system' ); ?></label></th>
				<td><input type="checkbox" id="ems_sponsor_preferential_treatment_agreed" name="preferential_treatment_agreed" value="1" <?php checked( $preferential_treatment_agreed ); ?>></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data when the sponsor post is saved.
	 *
	 * @since 1.5.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['ems_sponsor_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ems_sponsor_meta_nonce'], 'ems_sponsor_meta_nonce' ) ) {
			return;
		}

		// Check for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Use EMS_Sponsor_Meta to save all fields
		$data = wp_unslash( $_POST );
		EMS_Sponsor_Meta::update_sponsor_meta( $post_id, $data );

		$this->logger->info(
			sprintf( 'Sponsor #%d meta saved by user #%d', $post_id, get_current_user_id() ),
			EMS_Logger::CONTEXT_GENERAL
		);
	}

	/**
	 * Log sponsor status changes.
	 *
	 * @since 1.5.0
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function log_status_change( $new_status, $old_status, $post ) {
		// Only log for sponsor posts
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Don't log if status hasn't changed
		if ( $new_status === $old_status ) {
			return;
		}

		// Don't log auto-drafts or new posts
		if ( 'auto-draft' === $old_status || 'new' === $old_status || 'inherit' === $new_status ) {
			return;
		}

		$this->logger->info(
			sprintf(
				'Sponsor #%d status changed from "%s" to "%s" by user #%d',
				$post->ID,
				$old_status,
				$new_status,
				get_current_user_id()
			),
			EMS_Logger::CONTEXT_GENERAL,
			array(
				'sponsor_id' => $post->ID,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'user_id'    => get_current_user_id(),
			)
		);
	}

	// ==========================================
	// ADMIN LIST TABLE COLUMNS
	// ==========================================

	/**
	 * Set custom columns for the sponsor list table.
	 *
	 * @since 1.5.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function set_custom_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb']             = $columns['cb'];
		$new_columns['title']          = __( 'Sponsor', 'event-management-system' );
		$new_columns['trading_name']   = __( 'Trading Name', 'event-management-system' );
		$new_columns['abn']            = __( 'ABN', 'event-management-system' );
		$new_columns['linked_events']  = __( 'Linked Events', 'event-management-system' );
		$new_columns['status']         = __( 'Status', 'event-management-system' );
		$new_columns['date']           = $columns['date'];
		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @since 1.5.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'trading_name':
				$trading_name = get_post_meta( $post_id, '_ems_sponsor_trading_name', true );
				echo esc_html( $trading_name ?: '—' );
				break;

			case 'abn':
				$abn = get_post_meta( $post_id, '_ems_sponsor_abn', true );
				echo esc_html( $abn ?: '—' );
				break;

			case 'linked_events':
				$linked_events = get_post_meta( $post_id, '_ems_linked_events', true );
				if ( is_array( $linked_events ) && ! empty( $linked_events ) ) {
					echo absint( count( $linked_events ) );
				} else {
					echo '0';
				}
				break;

			case 'status':
				$post_status = get_post_status( $post_id );
				$status_label = get_post_status_object( $post_status );
				$label = $status_label ? $status_label->label : ucfirst( $post_status );
				echo '<span class="ems-status-badge ems-status-' . esc_attr( $post_status ) . '">' . esc_html( $label ) . '</span>';
				break;
		}
	}

	/**
	 * Define sortable columns.
	 *
	 * @since 1.5.0
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function set_sortable_columns( $columns ) {
		$columns['trading_name'] = 'trading_name';
		$columns['abn']          = 'abn';
		return $columns;
	}
}
