<?php
/**
 * Sponsor Organisation Meta Fields
 *
 * Registers and manages all metadata fields for the ems_sponsor CPT.
 * Handles organisation details, contacts, industry classification, products,
 * legal compliance, insurance, and conflict of interest declarations.
 *
 * @package    EventManagementSystem
 * @subpackage Sponsor
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Sponsor_Meta {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Industry sector options
	 */
	const INDUSTRY_SECTORS = array(
		'pharmaceutical_prescription' => 'Pharmaceutical - Prescription',
		'pharmaceutical_otc'          => 'Pharmaceutical - OTC',
		'medical_devices'             => 'Medical Devices',
		'diagnostic_equipment'        => 'Diagnostic Equipment',
		'simulation_equipment'        => 'Simulation Equipment',
		'health_it'                   => 'Health IT',
		'education_publishing'        => 'Education/Publishing',
		'professional_body'           => 'Professional Body',
		'other'                       => 'Other',
	);

	/**
	 * Scheduling class options
	 */
	const SCHEDULING_CLASSES = array(
		'unscheduled'     => 'Unscheduled',
		's2'              => 'Schedule 2',
		's3'              => 'Schedule 3',
		's4'              => 'Schedule 4',
		's8'              => 'Schedule 8',
		'not_a_medicine'  => 'Not a Medicine',
	);

	/**
	 * Device class options
	 */
	const DEVICE_CLASSES = array(
		'class_i'   => 'Class I',
		'class_iia' => 'Class IIa',
		'class_iib' => 'Class IIb',
		'class_iii' => 'Class III',
		'aimd'      => 'AIMD',
		'na'        => 'Not Applicable',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
		add_action( 'init', array( $this, 'register_meta_fields' ) );
	}

	/**
	 * Register all sponsor meta fields
	 *
	 * @since 1.0.0
	 */
	public function register_meta_fields() {
		// Section 1: Organisation Details
		$this->register_string_meta( '_ems_sponsor_legal_name' );
		$this->register_string_meta( '_ems_sponsor_trading_name' );
		$this->register_string_meta( '_ems_sponsor_abn' );
		$this->register_string_meta( '_ems_sponsor_acn' );
		$this->register_textarea_meta( '_ems_sponsor_registered_address' );
		$this->register_url_meta( '_ems_sponsor_website_url' );
		$this->register_string_meta( '_ems_sponsor_country' );
		$this->register_string_meta( '_ems_sponsor_parent_company' );

		// Section 2: Contact & Signatory
		$this->register_string_meta( '_ems_sponsor_contact_name' );
		$this->register_string_meta( '_ems_sponsor_contact_role' );
		$this->register_email_meta( '_ems_sponsor_contact_email' );
		$this->register_string_meta( '_ems_sponsor_contact_phone' );
		$this->register_string_meta( '_ems_sponsor_signatory_name' );
		$this->register_string_meta( '_ems_sponsor_signatory_title' );
		$this->register_email_meta( '_ems_sponsor_signatory_email' );
		$this->register_string_meta( '_ems_sponsor_marketing_contact_name' );
		$this->register_email_meta( '_ems_sponsor_marketing_contact_email' );

		// Section 3: Industry Classification
		$this->register_array_meta( '_ems_sponsor_industry_sectors' );
		$this->register_boolean_meta( '_ems_sponsor_ma_member' );
		$this->register_boolean_meta( '_ems_sponsor_mtaa_member' );
		$this->register_textarea_meta( '_ems_sponsor_other_codes' );
		$this->register_string_meta( '_ems_sponsor_tga_number' );

		// Section 4: Products & Scope
		$this->register_textarea_meta( '_ems_sponsor_products' );
		$this->register_textarea_meta( '_ems_sponsor_artg_listings' );
		$this->register_string_meta( '_ems_sponsor_scheduling_class' );
		$this->register_string_meta( '_ems_sponsor_device_class' );
		$this->register_boolean_meta( '_ems_sponsor_non_artg_product' );
		$this->register_boolean_meta( '_ems_sponsor_off_label' );
		$this->register_boolean_meta( '_ems_sponsor_tga_safety_alert' );
		$this->register_textarea_meta( '_ems_sponsor_educational_relation' );

		// Section 5: Legal, Insurance & Compliance
		$this->register_boolean_meta( '_ems_sponsor_public_liability_confirmed' );
		$this->register_string_meta( '_ems_sponsor_public_liability_insurer' );
		$this->register_string_meta( '_ems_sponsor_public_liability_policy' );
		$this->register_boolean_meta( '_ems_sponsor_product_liability_confirmed' );
		$this->register_boolean_meta( '_ems_sponsor_professional_indemnity' );
		$this->register_boolean_meta( '_ems_sponsor_workers_comp_confirmed' );
		$this->register_boolean_meta( '_ems_sponsor_code_compliance_agreed' );
		$this->register_boolean_meta( '_ems_sponsor_compliance_process' );
		$this->register_textarea_meta( '_ems_sponsor_adverse_findings' );
		$this->register_textarea_meta( '_ems_sponsor_legal_proceedings' );
		$this->register_textarea_meta( '_ems_sponsor_transparency_obligations' );

		// Section 6: Conflict of Interest
		$this->register_boolean_meta( '_ems_sponsor_qld_health_aware' );
		$this->register_textarea_meta( '_ems_sponsor_personal_relationships' );
		$this->register_textarea_meta( '_ems_sponsor_procurement_conflicts' );
		$this->register_textarea_meta( '_ems_sponsor_transfers_of_value' );
		$this->register_boolean_meta( '_ems_sponsor_preferential_treatment_agreed' );

		$this->logger->debug( 'Sponsor meta fields registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Register a string meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_string_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register a textarea meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_textarea_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register an email meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_email_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_email',
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register a URL meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_url_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register a boolean meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_boolean_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register an array meta field
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 */
	private function register_array_meta( $meta_key ) {
		register_post_meta(
			'ems_sponsor',
			$meta_key,
			array(
				'type'              => 'array',
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_array_field' ),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Sanitize array field
	 *
	 * @since 1.0.0
	 * @param array $value Array value to sanitize
	 * @return array Sanitized array
	 */
	public function sanitize_array_field( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Authorization callback for meta fields
	 *
	 * @since 1.0.0
	 * @param bool   $allowed  Whether the user can access the meta field
	 * @param string $meta_key The meta key being checked
	 * @param int    $post_id  The post ID
	 * @return bool Whether the user can edit this meta field
	 */
	public function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get all sponsor meta for a post
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID
	 * @return array Associative array of all sponsor meta
	 */
	public static function get_sponsor_meta( $post_id ) {
		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'ems_sponsor' ) {
			return array();
		}

		$meta = array();

		// Section 1: Organisation Details
		$meta['legal_name']           = get_post_meta( $post_id, '_ems_sponsor_legal_name', true );
		$meta['trading_name']         = get_post_meta( $post_id, '_ems_sponsor_trading_name', true );
		$meta['abn']                  = get_post_meta( $post_id, '_ems_sponsor_abn', true );
		$meta['acn']                  = get_post_meta( $post_id, '_ems_sponsor_acn', true );
		$meta['registered_address']   = get_post_meta( $post_id, '_ems_sponsor_registered_address', true );
		$meta['website_url']          = get_post_meta( $post_id, '_ems_sponsor_website_url', true );
		$meta['country']              = get_post_meta( $post_id, '_ems_sponsor_country', true );
		$meta['parent_company']       = get_post_meta( $post_id, '_ems_sponsor_parent_company', true );

		// Section 2: Contact & Signatory
		$meta['contact_name']                 = get_post_meta( $post_id, '_ems_sponsor_contact_name', true );
		$meta['contact_role']                 = get_post_meta( $post_id, '_ems_sponsor_contact_role', true );
		$meta['contact_email']                = get_post_meta( $post_id, '_ems_sponsor_contact_email', true );
		$meta['contact_phone']                = get_post_meta( $post_id, '_ems_sponsor_contact_phone', true );
		$meta['signatory_name']               = get_post_meta( $post_id, '_ems_sponsor_signatory_name', true );
		$meta['signatory_title']              = get_post_meta( $post_id, '_ems_sponsor_signatory_title', true );
		$meta['signatory_email']              = get_post_meta( $post_id, '_ems_sponsor_signatory_email', true );
		$meta['marketing_contact_name']       = get_post_meta( $post_id, '_ems_sponsor_marketing_contact_name', true );
		$meta['marketing_contact_email']      = get_post_meta( $post_id, '_ems_sponsor_marketing_contact_email', true );

		// Section 3: Industry Classification
		$meta['industry_sectors'] = get_post_meta( $post_id, '_ems_sponsor_industry_sectors', true );
		$meta['ma_member']        = (bool) get_post_meta( $post_id, '_ems_sponsor_ma_member', true );
		$meta['mtaa_member']      = (bool) get_post_meta( $post_id, '_ems_sponsor_mtaa_member', true );
		$meta['other_codes']      = get_post_meta( $post_id, '_ems_sponsor_other_codes', true );
		$meta['tga_number']       = get_post_meta( $post_id, '_ems_sponsor_tga_number', true );

		// Section 4: Products & Scope
		$meta['products']              = get_post_meta( $post_id, '_ems_sponsor_products', true );
		$meta['artg_listings']         = get_post_meta( $post_id, '_ems_sponsor_artg_listings', true );
		$meta['scheduling_class']      = get_post_meta( $post_id, '_ems_sponsor_scheduling_class', true );
		$meta['device_class']          = get_post_meta( $post_id, '_ems_sponsor_device_class', true );
		$meta['non_artg_product']      = (bool) get_post_meta( $post_id, '_ems_sponsor_non_artg_product', true );
		$meta['off_label']             = (bool) get_post_meta( $post_id, '_ems_sponsor_off_label', true );
		$meta['tga_safety_alert']      = (bool) get_post_meta( $post_id, '_ems_sponsor_tga_safety_alert', true );
		$meta['educational_relation']  = get_post_meta( $post_id, '_ems_sponsor_educational_relation', true );

		// Section 5: Legal, Insurance & Compliance
		$meta['public_liability_confirmed']    = (bool) get_post_meta( $post_id, '_ems_sponsor_public_liability_confirmed', true );
		$meta['public_liability_insurer']      = get_post_meta( $post_id, '_ems_sponsor_public_liability_insurer', true );
		$meta['public_liability_policy']       = get_post_meta( $post_id, '_ems_sponsor_public_liability_policy', true );
		$meta['product_liability_confirmed']   = (bool) get_post_meta( $post_id, '_ems_sponsor_product_liability_confirmed', true );
		$meta['professional_indemnity']        = (bool) get_post_meta( $post_id, '_ems_sponsor_professional_indemnity', true );
		$meta['workers_comp_confirmed']        = (bool) get_post_meta( $post_id, '_ems_sponsor_workers_comp_confirmed', true );
		$meta['code_compliance_agreed']        = (bool) get_post_meta( $post_id, '_ems_sponsor_code_compliance_agreed', true );
		$meta['compliance_process']            = (bool) get_post_meta( $post_id, '_ems_sponsor_compliance_process', true );
		$meta['adverse_findings']              = get_post_meta( $post_id, '_ems_sponsor_adverse_findings', true );
		$meta['legal_proceedings']             = get_post_meta( $post_id, '_ems_sponsor_legal_proceedings', true );
		$meta['transparency_obligations']      = get_post_meta( $post_id, '_ems_sponsor_transparency_obligations', true );

		// Section 6: Conflict of Interest
		$meta['qld_health_aware']                    = (bool) get_post_meta( $post_id, '_ems_sponsor_qld_health_aware', true );
		$meta['personal_relationships']              = get_post_meta( $post_id, '_ems_sponsor_personal_relationships', true );
		$meta['procurement_conflicts']               = get_post_meta( $post_id, '_ems_sponsor_procurement_conflicts', true );
		$meta['transfers_of_value']                  = get_post_meta( $post_id, '_ems_sponsor_transfers_of_value', true );
		$meta['preferential_treatment_agreed']       = (bool) get_post_meta( $post_id, '_ems_sponsor_preferential_treatment_agreed', true );

		return $meta;
	}

	/**
	 * Update sponsor meta fields
	 *
	 * @since 1.0.0
	 * @param int   $post_id Post ID
	 * @param array $data    Associative array of meta data to update
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function update_sponsor_meta( $post_id, $data ) {
		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'ems_sponsor' ) {
			return new WP_Error( 'invalid_post', 'Invalid sponsor post ID' );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to edit this sponsor' );
		}

		// Map of user-friendly keys to meta keys
		$field_map = array(
			// Section 1: Organisation Details
			'legal_name'          => '_ems_sponsor_legal_name',
			'trading_name'        => '_ems_sponsor_trading_name',
			'abn'                 => '_ems_sponsor_abn',
			'acn'                 => '_ems_sponsor_acn',
			'registered_address'  => '_ems_sponsor_registered_address',
			'website_url'         => '_ems_sponsor_website_url',
			'country'             => '_ems_sponsor_country',
			'parent_company'      => '_ems_sponsor_parent_company',

			// Section 2: Contact & Signatory
			'contact_name'                => '_ems_sponsor_contact_name',
			'contact_role'                => '_ems_sponsor_contact_role',
			'contact_email'               => '_ems_sponsor_contact_email',
			'contact_phone'               => '_ems_sponsor_contact_phone',
			'signatory_name'              => '_ems_sponsor_signatory_name',
			'signatory_title'             => '_ems_sponsor_signatory_title',
			'signatory_email'             => '_ems_sponsor_signatory_email',
			'marketing_contact_name'      => '_ems_sponsor_marketing_contact_name',
			'marketing_contact_email'     => '_ems_sponsor_marketing_contact_email',

			// Section 3: Industry Classification
			'industry_sectors'    => '_ems_sponsor_industry_sectors',
			'ma_member'           => '_ems_sponsor_ma_member',
			'mtaa_member'         => '_ems_sponsor_mtaa_member',
			'other_codes'         => '_ems_sponsor_other_codes',
			'tga_number'          => '_ems_sponsor_tga_number',

			// Section 4: Products & Scope
			'products'             => '_ems_sponsor_products',
			'artg_listings'        => '_ems_sponsor_artg_listings',
			'scheduling_class'     => '_ems_sponsor_scheduling_class',
			'device_class'         => '_ems_sponsor_device_class',
			'non_artg_product'     => '_ems_sponsor_non_artg_product',
			'off_label'            => '_ems_sponsor_off_label',
			'tga_safety_alert'     => '_ems_sponsor_tga_safety_alert',
			'educational_relation' => '_ems_sponsor_educational_relation',

			// Section 5: Legal, Insurance & Compliance
			'public_liability_confirmed'   => '_ems_sponsor_public_liability_confirmed',
			'public_liability_insurer'     => '_ems_sponsor_public_liability_insurer',
			'public_liability_policy'      => '_ems_sponsor_public_liability_policy',
			'product_liability_confirmed'  => '_ems_sponsor_product_liability_confirmed',
			'professional_indemnity'       => '_ems_sponsor_professional_indemnity',
			'workers_comp_confirmed'       => '_ems_sponsor_workers_comp_confirmed',
			'code_compliance_agreed'       => '_ems_sponsor_code_compliance_agreed',
			'compliance_process'           => '_ems_sponsor_compliance_process',
			'adverse_findings'             => '_ems_sponsor_adverse_findings',
			'legal_proceedings'            => '_ems_sponsor_legal_proceedings',
			'transparency_obligations'     => '_ems_sponsor_transparency_obligations',

			// Section 6: Conflict of Interest
			'qld_health_aware'                   => '_ems_sponsor_qld_health_aware',
			'personal_relationships'             => '_ems_sponsor_personal_relationships',
			'procurement_conflicts'              => '_ems_sponsor_procurement_conflicts',
			'transfers_of_value'                 => '_ems_sponsor_transfers_of_value',
			'preferential_treatment_agreed'      => '_ems_sponsor_preferential_treatment_agreed',
		);

		// Build whitelist of allowed keys
		$allowed_keys = array_merge( array_keys( $field_map ), array_values( $field_map ) );

		// Update each provided field
		foreach ( $data as $key => $value ) {
			// Handle both friendly keys and actual meta keys
			$meta_key = isset( $field_map[ $key ] ) ? $field_map[ $key ] : $key;

			// Validate meta key is in whitelist
			if ( ! in_array( $meta_key, array_values( $field_map ), true ) ) {
				continue;
			}

			// Skip if not a valid sponsor meta key
			if ( strpos( $meta_key, '_ems_sponsor_' ) !== 0 ) {
				continue;
			}

			// Sanitize based on field type
			$value = self::sanitize_meta_value( $meta_key, $value );

			// Update the meta
			update_post_meta( $post_id, $meta_key, $value );
		}

		return true;
	}

	/**
	 * Sanitize meta value based on field type
	 *
	 * @since 1.0.0
	 * @param string $meta_key Meta key
	 * @param mixed  $value    Value to sanitize
	 * @return mixed Sanitized value
	 */
	private static function sanitize_meta_value( $meta_key, $value ) {
		// Email fields
		if ( in_array( $meta_key, array( '_ems_sponsor_contact_email', '_ems_sponsor_signatory_email', '_ems_sponsor_marketing_contact_email' ) ) ) {
			return sanitize_email( $value );
		}

		// URL fields
		if ( $meta_key === '_ems_sponsor_website_url' ) {
			return esc_url_raw( $value );
		}

		// Textarea fields
		if ( in_array( $meta_key, array(
			'_ems_sponsor_registered_address',
			'_ems_sponsor_other_codes',
			'_ems_sponsor_products',
			'_ems_sponsor_artg_listings',
			'_ems_sponsor_educational_relation',
			'_ems_sponsor_adverse_findings',
			'_ems_sponsor_legal_proceedings',
			'_ems_sponsor_transparency_obligations',
			'_ems_sponsor_personal_relationships',
			'_ems_sponsor_procurement_conflicts',
			'_ems_sponsor_transfers_of_value',
		) ) ) {
			return sanitize_textarea_field( $value );
		}

		// Boolean fields
		if ( in_array( $meta_key, array(
			'_ems_sponsor_ma_member',
			'_ems_sponsor_mtaa_member',
			'_ems_sponsor_non_artg_product',
			'_ems_sponsor_off_label',
			'_ems_sponsor_tga_safety_alert',
			'_ems_sponsor_public_liability_confirmed',
			'_ems_sponsor_product_liability_confirmed',
			'_ems_sponsor_professional_indemnity',
			'_ems_sponsor_workers_comp_confirmed',
			'_ems_sponsor_code_compliance_agreed',
			'_ems_sponsor_compliance_process',
			'_ems_sponsor_qld_health_aware',
			'_ems_sponsor_preferential_treatment_agreed',
		) ) ) {
			return rest_sanitize_boolean( $value );
		}

		// Array fields
		if ( $meta_key === '_ems_sponsor_industry_sectors' ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			return array_map( 'sanitize_text_field', $value );
		}

		// Default: text field
		return sanitize_text_field( $value );
	}
}
