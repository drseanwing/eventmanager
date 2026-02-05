<?php
/**
 * Sponsor Onboarding Shortcode
 *
 * Provides the [ems_sponsor_onboarding] shortcode that renders a 7-step
 * multi-step registration form for new sponsor organisations.
 *
 * Steps:
 *  1. Organisation Details
 *  2. Primary Contact & Signatory
 *  3. Industry Classification
 *  4. Products, Services & Scope
 *  5. Legal, Insurance & Compliance
 *  6. Conflict of Interest
 *  7. Review & Submit
 *
 * @package    EventManagementSystem
 * @subpackage Public/Shortcodes
 * @since      1.5.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sponsor Onboarding Shortcode class.
 *
 * Registers the [ems_sponsor_onboarding] shortcode, configures the multi-step
 * form engine with seven steps, handles form submission including duplicate
 * detection, user/post creation, and spam protection.
 *
 * @since 1.5.0
 */
class EMS_Sponsor_Onboarding_Shortcode {

	/**
	 * Form ID used by the multi-step form engine.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    string
	 */
	private $form_id = 'sponsor_onboarding';

	/**
	 * Logger instance.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    EMS_Logger
	 */
	private $logger;

	/**
	 * Multi-step form engine instance.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    EMS_Multi_Step_Form
	 */
	private $form;

	/**
	 * Constructor.
	 *
	 * Registers the shortcode.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();

		add_shortcode( 'ems_sponsor_onboarding', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the [ems_sponsor_onboarding] shortcode.
	 *
	 * If the visitor is already logged in with the ems_sponsor role and has a
	 * linked sponsor profile, they are redirected to the portal page instead.
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes (unused).
	 * @return string Rendered HTML.
	 */
	public function render_shortcode( $atts ) {
		// Redirect logged-in sponsors to the portal
		if ( is_user_logged_in() && current_user_can( 'access_ems_sponsor_portal' ) ) {
			$sponsor_id = get_user_meta( get_current_user_id(), '_ems_sponsor_id', true );
			if ( $sponsor_id ) {
				$portal_url = $this->get_portal_url();
				if ( $portal_url ) {
					return '<div class="ems-notice ems-notice-info">'
						. sprintf(
							/* translators: %s: URL to sponsor portal */
							__( 'You are already registered as a sponsor. <a href="%s">Go to your Sponsor Portal</a>.', 'event-management-system' ),
							esc_url( $portal_url )
						)
						. '</div>';
				}
			}
		}

		// Build and configure the form engine
		$this->form = new EMS_Multi_Step_Form( $this->form_id );
		$this->register_steps();

		// Load any saved state (draft progress)
		$this->form->load_state();

		// Process POST submission if this is a step navigation
		$html = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && $this->is_our_form() ) {
			$html .= $this->process_step();
		}

		// Render the form (or review step)
		$current = $this->form->get_current_step();
		$steps   = $this->form->get_steps();

		if ( isset( $steps[ $current ] ) && 'review' === $steps[ $current ]['slug'] ) {
			// Render review step with custom review renderer
			$html .= $this->render_review_form();
		} else {
			$html .= $this->form->render();
		}

		// Add honeypot field via inline JS (injected into the form)
		$html .= $this->render_honeypot_script();

		return $html;
	}

	// =========================================================================
	// Step Registration
	// =========================================================================

	/**
	 * Register all seven form steps with the form engine.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_steps() {
		$this->register_step_organisation();
		$this->register_step_contacts();
		$this->register_step_industry();
		$this->register_step_products();
		$this->register_step_legal();
		$this->register_step_conflict();
		$this->register_step_review();
	}

	/**
	 * Step 1: Organisation Details.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_organisation() {
		$fields = array(
			array(
				'name'        => 'legal_name',
				'type'        => 'text',
				'label'       => __( 'Legal Entity Name', 'event-management-system' ),
				'required'    => true,
				'placeholder' => __( 'e.g. Acme Pty Ltd', 'event-management-system' ),
			),
			array(
				'name'        => 'trading_name',
				'type'        => 'text',
				'label'       => __( 'Trading Name', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'If different from the legal entity name.', 'event-management-system' ),
			),
			array(
				'name'        => 'abn',
				'type'        => 'text',
				'label'       => __( 'ABN', 'event-management-system' ),
				'required'    => false,
				'placeholder' => __( 'XX XXX XXX XXX', 'event-management-system' ),
				'help_text'   => __( 'Australian Business Number (11 digits).', 'event-management-system' ),
			),
			array(
				'name'        => 'acn',
				'type'        => 'text',
				'label'       => __( 'ACN', 'event-management-system' ),
				'required'    => false,
				'placeholder' => __( 'XXX XXX XXX', 'event-management-system' ),
				'help_text'   => __( 'Australian Company Number (9 digits).', 'event-management-system' ),
			),
			array(
				'name'     => 'registered_address',
				'type'     => 'textarea',
				'label'    => __( 'Registered Address', 'event-management-system' ),
				'required' => false,
				'rows'     => 3,
			),
			array(
				'name'        => 'website_url',
				'type'        => 'url',
				'label'       => __( 'Website URL', 'event-management-system' ),
				'required'    => false,
				'placeholder' => 'https://',
			),
			array(
				'name'        => 'country',
				'type'        => 'text',
				'label'       => __( 'Country', 'event-management-system' ),
				'required'    => false,
				'placeholder' => __( 'Australia', 'event-management-system' ),
			),
			array(
				'name'     => 'parent_company',
				'type'     => 'text',
				'label'    => __( 'Parent Company', 'event-management-system' ),
				'required' => false,
				'help_text' => __( 'If this organisation is a subsidiary.', 'event-management-system' ),
			),
		);

		$rules = array(
			'legal_name' => array( 'required', 'min:2', 'max:200' ),
			'abn'        => array( 'pattern:/^\d{2}\s?\d{3}\s?\d{3}\s?\d{3}$/' ),
			'acn'        => array( 'pattern:/^\d{3}\s?\d{3}\s?\d{3}$/' ),
			'website_url' => array( 'url' ),
		);

		$this->form->register_step(
			'organisation',
			__( 'Organisation Details', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 2: Primary Contact & Signatory.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_contacts() {
		$fields = array(
			array(
				'name'     => 'contact_name',
				'type'     => 'text',
				'label'    => __( 'Primary Contact Name', 'event-management-system' ),
				'required' => true,
			),
			array(
				'name'     => 'contact_role',
				'type'     => 'text',
				'label'    => __( 'Contact Role / Title', 'event-management-system' ),
				'required' => false,
			),
			array(
				'name'     => 'contact_email',
				'type'     => 'email',
				'label'    => __( 'Contact Email', 'event-management-system' ),
				'required' => true,
			),
			array(
				'name'        => 'contact_phone',
				'type'        => 'phone',
				'label'       => __( 'Contact Phone', 'event-management-system' ),
				'required'    => false,
				'placeholder' => __( '+61 4XX XXX XXX', 'event-management-system' ),
			),
			array(
				'name'     => 'signatory_name',
				'type'     => 'text',
				'label'    => __( 'Authorised Signatory Name', 'event-management-system' ),
				'required' => true,
				'help_text' => __( 'Person authorised to sign agreements on behalf of the organisation.', 'event-management-system' ),
			),
			array(
				'name'     => 'signatory_title',
				'type'     => 'text',
				'label'    => __( 'Signatory Title', 'event-management-system' ),
				'required' => false,
			),
			array(
				'name'     => 'signatory_email',
				'type'     => 'email',
				'label'    => __( 'Signatory Email', 'event-management-system' ),
				'required' => false,
			),
			array(
				'name'     => 'marketing_contact_name',
				'type'     => 'text',
				'label'    => __( 'Marketing Contact Name', 'event-management-system' ),
				'required' => false,
				'help_text' => __( 'Optional. Person to contact for marketing materials.', 'event-management-system' ),
			),
			array(
				'name'     => 'marketing_contact_email',
				'type'     => 'email',
				'label'    => __( 'Marketing Contact Email', 'event-management-system' ),
				'required' => false,
			),
		);

		$rules = array(
			'contact_name'           => array( 'required', 'min:2' ),
			'contact_email'          => array( 'required', 'email' ),
			'contact_phone'          => array( 'phone' ),
			'signatory_name'         => array( 'required', 'min:2' ),
			'signatory_email'        => array( 'email' ),
			'marketing_contact_email' => array( 'email' ),
		);

		$this->form->register_step(
			'contacts',
			__( 'Contact & Signatory', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 3: Industry Classification.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_industry() {
		$fields = array(
			array(
				'name'     => 'industry_sectors',
				'type'     => 'multiselect',
				'label'    => __( 'Industry Sectors', 'event-management-system' ),
				'required' => true,
				'options'  => EMS_Sponsor_Meta::INDUSTRY_SECTORS,
				'help_text' => __( 'Select all that apply.', 'event-management-system' ),
			),
			array(
				'name'     => 'ma_member',
				'type'     => 'checkbox',
				'label'    => __( 'Medicines Australia member', 'event-management-system' ),
				'required' => false,
			),
			array(
				'name'     => 'mtaa_member',
				'type'     => 'checkbox',
				'label'    => __( 'MTAA member', 'event-management-system' ),
				'required' => false,
			),
			array(
				'name'        => 'other_codes',
				'type'        => 'textarea',
				'label'       => __( 'Other Industry Codes / Details', 'event-management-system' ),
				'required'    => false,
				'rows'        => 3,
				'help_text'   => __( 'Please specify if you selected "Other" above.', 'event-management-system' ),
				'conditional' => array(
					'field'    => 'industry_sectors',
					'value'    => 'other',
					'operator' => 'contains',
				),
			),
			array(
				'name'        => 'tga_number',
				'type'        => 'text',
				'label'       => __( 'TGA Sponsor Number', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'Required for pharmaceutical or medical device sponsors.', 'event-management-system' ),
				'conditional' => array(
					'field'    => 'industry_sectors',
					'value'    => 'pharmaceutical_prescription',
					'operator' => 'contains',
				),
			),
		);

		$rules = array(
			'industry_sectors' => array( 'required' ),
		);

		$this->form->register_step(
			'industry',
			__( 'Industry Classification', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 4: Products, Services & Scope.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_products() {
		$fields = array(
			array(
				'name'      => 'products',
				'type'      => 'textarea',
				'label'     => __( 'Products and/or Services', 'event-management-system' ),
				'required'  => true,
				'rows'      => 4,
				'help_text' => __( 'Briefly describe the products and/or services your organisation provides.', 'event-management-system' ),
			),
			array(
				'name'        => 'artg_listings',
				'type'        => 'textarea',
				'label'       => __( 'ARTG Listings', 'event-management-system' ),
				'required'    => false,
				'rows'        => 3,
				'help_text'   => __( 'Relevant Australian Register of Therapeutic Goods listings.', 'event-management-system' ),
			),
			array(
				'name'     => 'scheduling_class',
				'type'     => 'select',
				'label'    => __( 'Scheduling Classification', 'event-management-system' ),
				'required' => false,
				'options'  => EMS_Sponsor_Meta::SCHEDULING_CLASSES,
			),
			array(
				'name'        => 'device_class',
				'type'        => 'select',
				'label'       => __( 'Medical Device Class', 'event-management-system' ),
				'required'    => false,
				'options'     => EMS_Sponsor_Meta::DEVICE_CLASSES,
				'conditional' => array(
					'field'    => 'industry_sectors',
					'value'    => 'medical_devices',
					'operator' => 'contains',
				),
			),
			array(
				'name'      => 'non_artg_product',
				'type'      => 'checkbox',
				'label'     => __( 'Product is NOT listed on the ARTG', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'Warning: Products not listed on the ARTG may require additional compliance review.', 'event-management-system' ),
			),
			array(
				'name'      => 'off_label',
				'type'      => 'checkbox',
				'label'     => __( 'Product includes off-label use information', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'Warning: Off-label promotion is subject to strict regulatory requirements.', 'event-management-system' ),
			),
			array(
				'name'      => 'tga_safety_alert',
				'type'      => 'checkbox',
				'label'     => __( 'Product is subject to a current TGA safety alert', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'Warning: Products under TGA safety alert require additional disclosure.', 'event-management-system' ),
			),
			array(
				'name'      => 'educational_relation',
				'type'      => 'textarea',
				'label'     => __( 'Relationship to Educational Content', 'event-management-system' ),
				'required'  => false,
				'rows'      => 3,
				'help_text' => __( 'Describe how your product/service relates to the educational content of events you wish to sponsor.', 'event-management-system' ),
			),
		);

		$rules = array(
			'products' => array( 'required', 'min:10' ),
		);

		$this->form->register_step(
			'products',
			__( 'Products & Scope', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 5: Legal, Insurance & Compliance.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_legal() {
		$yes_no = array(
			'yes' => __( 'Yes', 'event-management-system' ),
			'no'  => __( 'No', 'event-management-system' ),
		);

		$fields = array(
			array(
				'name'     => 'public_liability_confirmed',
				'type'     => 'radio',
				'label'    => __( 'Does your organisation hold current Public Liability Insurance?', 'event-management-system' ),
				'required' => true,
				'options'  => $yes_no,
			),
			array(
				'name'        => 'public_liability_insurer',
				'type'        => 'text',
				'label'       => __( 'Public Liability Insurer', 'event-management-system' ),
				'required'    => false,
				'conditional' => array(
					'field' => 'public_liability_confirmed',
					'value' => 'yes',
				),
			),
			array(
				'name'        => 'public_liability_policy',
				'type'        => 'text',
				'label'       => __( 'Public Liability Policy Number', 'event-management-system' ),
				'required'    => false,
				'conditional' => array(
					'field' => 'public_liability_confirmed',
					'value' => 'yes',
				),
			),
			array(
				'name'     => 'product_liability_confirmed',
				'type'     => 'radio',
				'label'    => __( 'Does your organisation hold current Product Liability Insurance?', 'event-management-system' ),
				'required' => false,
				'options'  => $yes_no,
			),
			array(
				'name'     => 'professional_indemnity',
				'type'     => 'radio',
				'label'    => __( 'Does your organisation hold Professional Indemnity Insurance?', 'event-management-system' ),
				'required' => false,
				'options'  => $yes_no,
			),
			array(
				'name'     => 'workers_comp_confirmed',
				'type'     => 'radio',
				'label'    => __( 'Does your organisation hold current Workers\' Compensation Insurance?', 'event-management-system' ),
				'required' => true,
				'options'  => $yes_no,
			),
			array(
				'name'      => 'code_compliance_agreed',
				'type'      => 'checkbox',
				'label'     => __( 'I agree to comply with all applicable industry codes of conduct (Medicines Australia Code, MTAA Code, TGA regulations)', 'event-management-system' ),
				'required'  => true,
			),
			array(
				'name'     => 'compliance_process',
				'type'     => 'radio',
				'label'    => __( 'Does your organisation have a documented compliance process?', 'event-management-system' ),
				'required' => false,
				'options'  => $yes_no,
			),
			array(
				'name'        => 'adverse_findings',
				'type'        => 'textarea',
				'label'       => __( 'Adverse Findings', 'event-management-system' ),
				'required'    => false,
				'rows'        => 3,
				'help_text'   => __( 'Disclose any adverse findings from regulatory bodies in the past 5 years.', 'event-management-system' ),
			),
			array(
				'name'        => 'legal_proceedings',
				'type'        => 'textarea',
				'label'       => __( 'Legal Proceedings', 'event-management-system' ),
				'required'    => false,
				'rows'        => 3,
				'help_text'   => __( 'Disclose any current or pending legal proceedings relevant to your products/services.', 'event-management-system' ),
			),
			array(
				'name'      => 'transparency_obligations',
				'type'      => 'textarea',
				'label'     => __( 'Transparency Obligations', 'event-management-system' ),
				'required'  => false,
				'rows'      => 3,
				'help_text' => __( 'Describe any transparency reporting obligations your organisation is subject to.', 'event-management-system' ),
			),
		);

		$rules = array(
			'public_liability_confirmed' => array( 'required' ),
			'workers_comp_confirmed'     => array( 'required' ),
			'code_compliance_agreed'     => array( 'required' ),
		);

		$this->form->register_step(
			'legal',
			__( 'Legal & Compliance', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 6: Conflict of Interest.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_conflict() {
		$fields = array(
			array(
				'name'      => 'qld_health_aware',
				'type'      => 'checkbox',
				'label'     => __( 'I acknowledge that Queensland Health requires all sponsors to comply with applicable policies regarding gifts, benefits, and hospitality provided to health service employees', 'event-management-system' ),
				'required'  => true,
				'help_text' => __( 'This acknowledgement is required to proceed.', 'event-management-system' ),
			),
			array(
				'name'      => 'personal_relationships',
				'type'      => 'textarea',
				'label'     => __( 'Personal Relationships', 'event-management-system' ),
				'required'  => false,
				'rows'      => 3,
				'help_text' => __( 'Disclose any personal relationships between your staff and event organisers, speakers, or health professionals involved in events you wish to sponsor.', 'event-management-system' ),
			),
			array(
				'name'      => 'procurement_conflicts',
				'type'      => 'textarea',
				'label'     => __( 'Procurement Conflicts', 'event-management-system' ),
				'required'  => false,
				'rows'      => 3,
				'help_text' => __( 'Disclose any involvement in procurement processes that may relate to sponsored events.', 'event-management-system' ),
			),
			array(
				'name'      => 'transfers_of_value',
				'type'      => 'textarea',
				'label'     => __( 'Transfers of Value', 'event-management-system' ),
				'required'  => false,
				'rows'      => 3,
				'help_text' => __( 'Disclose any transfers of value (payments, gifts, hospitality) made to healthcare professionals in the past 12 months.', 'event-management-system' ),
			),
			array(
				'name'     => 'preferential_treatment_agreed',
				'type'     => 'checkbox',
				'label'    => __( 'I confirm that this sponsorship does not seek and will not result in preferential treatment for our products or services', 'event-management-system' ),
				'required' => true,
			),
		);

		$rules = array(
			'qld_health_aware'              => array( 'required' ),
			'preferential_treatment_agreed' => array( 'required' ),
		);

		$this->form->register_step(
			'conflict',
			__( 'Conflict of Interest', 'event-management-system' ),
			$fields,
			$rules
		);
	}

	/**
	 * Step 7: Review & Submit.
	 *
	 * Registered with an empty field set; content is rendered via render_review().
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function register_step_review() {
		$this->form->register_step(
			'review',
			__( 'Review & Submit', 'event-management-system' ),
			array(), // No editable fields; uses review renderer
			array()
		);
	}

	// =========================================================================
	// Step Processing
	// =========================================================================

	/**
	 * Check whether the current POST request belongs to this form.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	private function is_our_form() {
		return isset( $_POST['ems_form_id'] )
			&& $this->form_id === sanitize_text_field( wp_unslash( $_POST['ems_form_id'] ) );
	}

	/**
	 * Process the current step on POST.
	 *
	 * Validates nonce, validates step data, stores data, advances or
	 * rewinds the step pointer, and persists state.
	 *
	 * @since 1.5.0
	 * @return string HTML notices (errors or success).
	 */
	private function process_step() {
		// Verify nonce
		if ( ! isset( $_POST['ems_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ems_form_nonce'] ) ), 'ems_form_' . $this->form_id ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'Security verification failed. Please try again.', 'event-management-system' )
				. '</div>';
		}

		$current_step = isset( $_POST['ems_current_step'] ) ? absint( $_POST['ems_current_step'] ) : 0;
		$this->form->set_current_step( $current_step );
		$steps = $this->form->get_steps();

		// Handle previous button
		if ( isset( $_POST['ems_direction'] ) && 'prev' === sanitize_text_field( wp_unslash( $_POST['ems_direction'] ) ) ) {
			if ( $current_step > 0 ) {
				$this->form->set_current_step( $current_step - 1 );
				$this->form->save_state();
			}
			return '';
		}

		// Get current step slug
		$step_slug = isset( $steps[ $current_step ] ) ? $steps[ $current_step ]['slug'] : '';

		// Review step: handle final submission
		if ( 'review' === $step_slug ) {
			return $this->process_final_submission();
		}

		// Sanitize and collect step data
		$step_data = $this->sanitize_step_data( $step_slug );

		// Validate
		$validation = $this->form->validate_step( $step_slug, $step_data );
		if ( is_wp_error( $validation ) ) {
			// Store what we have so far (even if invalid) so fields are populated
			$this->form->set_form_data( $step_slug, $step_data );
			$this->form->save_state();

			$html = '<div class="ems-notice ems-notice-error"><ul>';
			foreach ( $validation->get_error_messages() as $msg ) {
				$html .= '<li>' . esc_html( $msg ) . '</li>';
			}
			$html .= '</ul></div>';
			return $html;
		}

		// Store validated data and advance
		$this->form->set_form_data( $step_slug, $step_data );

		$next = $current_step + 1;
		if ( $next < $this->form->get_step_count() ) {
			$this->form->set_current_step( $next );
		}

		$this->form->save_state();
		return '';
	}

	/**
	 * Sanitize submitted data for a specific step.
	 *
	 * @since 1.5.0
	 * @param string $step_slug Step slug.
	 * @return array Sanitized data.
	 */
	private function sanitize_step_data( $step_slug ) {
		$data = array();

		switch ( $step_slug ) {
			case 'organisation':
				$data['legal_name']         = isset( $_POST['legal_name'] ) ? sanitize_text_field( wp_unslash( $_POST['legal_name'] ) ) : '';
				$data['trading_name']       = isset( $_POST['trading_name'] ) ? sanitize_text_field( wp_unslash( $_POST['trading_name'] ) ) : '';
				$data['abn']                = isset( $_POST['abn'] ) ? sanitize_text_field( wp_unslash( $_POST['abn'] ) ) : '';
				$data['acn']                = isset( $_POST['acn'] ) ? sanitize_text_field( wp_unslash( $_POST['acn'] ) ) : '';
				$data['registered_address'] = isset( $_POST['registered_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['registered_address'] ) ) : '';
				$data['website_url']        = isset( $_POST['website_url'] ) ? esc_url_raw( wp_unslash( $_POST['website_url'] ) ) : '';
				$data['country']            = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
				$data['parent_company']     = isset( $_POST['parent_company'] ) ? sanitize_text_field( wp_unslash( $_POST['parent_company'] ) ) : '';
				break;

			case 'contacts':
				$data['contact_name']           = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
				$data['contact_role']           = isset( $_POST['contact_role'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_role'] ) ) : '';
				$data['contact_email']          = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
				$data['contact_phone']          = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
				$data['signatory_name']         = isset( $_POST['signatory_name'] ) ? sanitize_text_field( wp_unslash( $_POST['signatory_name'] ) ) : '';
				$data['signatory_title']        = isset( $_POST['signatory_title'] ) ? sanitize_text_field( wp_unslash( $_POST['signatory_title'] ) ) : '';
				$data['signatory_email']        = isset( $_POST['signatory_email'] ) ? sanitize_email( wp_unslash( $_POST['signatory_email'] ) ) : '';
				$data['marketing_contact_name'] = isset( $_POST['marketing_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['marketing_contact_name'] ) ) : '';
				$data['marketing_contact_email'] = isset( $_POST['marketing_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['marketing_contact_email'] ) ) : '';
				break;

			case 'industry':
				$data['industry_sectors'] = isset( $_POST['industry_sectors'] ) && is_array( $_POST['industry_sectors'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['industry_sectors'] ) )
					: array();
				$data['ma_member']   = isset( $_POST['ma_member'] ) ? '1' : '';
				$data['mtaa_member'] = isset( $_POST['mtaa_member'] ) ? '1' : '';
				$data['other_codes'] = isset( $_POST['other_codes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['other_codes'] ) ) : '';
				$data['tga_number']  = isset( $_POST['tga_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tga_number'] ) ) : '';
				break;

			case 'products':
				$data['products']              = isset( $_POST['products'] ) ? sanitize_textarea_field( wp_unslash( $_POST['products'] ) ) : '';
				$data['artg_listings']         = isset( $_POST['artg_listings'] ) ? sanitize_textarea_field( wp_unslash( $_POST['artg_listings'] ) ) : '';
				$data['scheduling_class']      = isset( $_POST['scheduling_class'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduling_class'] ) ) : '';
				$data['device_class']          = isset( $_POST['device_class'] ) ? sanitize_text_field( wp_unslash( $_POST['device_class'] ) ) : '';
				$data['non_artg_product']      = isset( $_POST['non_artg_product'] ) ? '1' : '';
				$data['off_label']             = isset( $_POST['off_label'] ) ? '1' : '';
				$data['tga_safety_alert']      = isset( $_POST['tga_safety_alert'] ) ? '1' : '';
				$data['educational_relation']  = isset( $_POST['educational_relation'] ) ? sanitize_textarea_field( wp_unslash( $_POST['educational_relation'] ) ) : '';
				break;

			case 'legal':
				$data['public_liability_confirmed'] = isset( $_POST['public_liability_confirmed'] ) ? sanitize_text_field( wp_unslash( $_POST['public_liability_confirmed'] ) ) : '';
				$data['public_liability_insurer']   = isset( $_POST['public_liability_insurer'] ) ? sanitize_text_field( wp_unslash( $_POST['public_liability_insurer'] ) ) : '';
				$data['public_liability_policy']    = isset( $_POST['public_liability_policy'] ) ? sanitize_text_field( wp_unslash( $_POST['public_liability_policy'] ) ) : '';
				$data['product_liability_confirmed'] = isset( $_POST['product_liability_confirmed'] ) ? sanitize_text_field( wp_unslash( $_POST['product_liability_confirmed'] ) ) : '';
				$data['professional_indemnity']     = isset( $_POST['professional_indemnity'] ) ? sanitize_text_field( wp_unslash( $_POST['professional_indemnity'] ) ) : '';
				$data['workers_comp_confirmed']     = isset( $_POST['workers_comp_confirmed'] ) ? sanitize_text_field( wp_unslash( $_POST['workers_comp_confirmed'] ) ) : '';
				$data['code_compliance_agreed']     = isset( $_POST['code_compliance_agreed'] ) ? '1' : '';
				$data['compliance_process']         = isset( $_POST['compliance_process'] ) ? sanitize_text_field( wp_unslash( $_POST['compliance_process'] ) ) : '';
				$data['adverse_findings']           = isset( $_POST['adverse_findings'] ) ? sanitize_textarea_field( wp_unslash( $_POST['adverse_findings'] ) ) : '';
				$data['legal_proceedings']          = isset( $_POST['legal_proceedings'] ) ? sanitize_textarea_field( wp_unslash( $_POST['legal_proceedings'] ) ) : '';
				$data['transparency_obligations']   = isset( $_POST['transparency_obligations'] ) ? sanitize_textarea_field( wp_unslash( $_POST['transparency_obligations'] ) ) : '';
				break;

			case 'conflict':
				$data['qld_health_aware']              = isset( $_POST['qld_health_aware'] ) ? '1' : '';
				$data['personal_relationships']        = isset( $_POST['personal_relationships'] ) ? sanitize_textarea_field( wp_unslash( $_POST['personal_relationships'] ) ) : '';
				$data['procurement_conflicts']         = isset( $_POST['procurement_conflicts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['procurement_conflicts'] ) ) : '';
				$data['transfers_of_value']            = isset( $_POST['transfers_of_value'] ) ? sanitize_textarea_field( wp_unslash( $_POST['transfers_of_value'] ) ) : '';
				$data['preferential_treatment_agreed'] = isset( $_POST['preferential_treatment_agreed'] ) ? '1' : '';
				break;
		}

		return $data;
	}

	// =========================================================================
	// Review Step Rendering
	// =========================================================================

	/**
	 * Render the review step with the form engine's built-in review renderer.
	 *
	 * Wraps the review content inside a full form element so the submit
	 * button and nonce are present.
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	private function render_review_form() {
		$html  = '<div class="ems-multi-step-form" data-form-id="' . esc_attr( $this->form_id ) . '">';

		// Progress indicator
		$html .= $this->render_review_progress();

		$html .= '<form method="post" class="ems-form ems-step-form" id="ems-form-' . esc_attr( $this->form_id ) . '" novalidate>';
		$html .= wp_nonce_field( 'ems_form_' . $this->form_id, 'ems_form_nonce', true, false );
		$html .= '<input type="hidden" name="ems_form_id" value="' . esc_attr( $this->form_id ) . '" />';
		$html .= '<input type="hidden" name="ems_current_step" value="' . esc_attr( $this->form->get_current_step() ) . '" />';

		// Honeypot field
		$html .= '<div class="ems-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;"><label for="ems-hp-website">'
			. esc_html__( 'Website', 'event-management-system' )
			. '</label><input type="text" name="ems_hp_website" id="ems-hp-website" value="" tabindex="-1" autocomplete="off" /></div>';

		// Review content
		$html .= $this->form->render_review();

		// Navigation
		$html .= '<div class="ems-form-navigation">';
		$html .= '<button type="submit" name="ems_direction" value="prev" class="ems-btn ems-btn-secondary ems-form-prev">';
		$html .= esc_html__( 'Previous', 'event-management-system' );
		$html .= '</button>';
		$html .= '<button type="submit" class="ems-btn ems-btn-primary ems-form-submit">';
		$html .= esc_html__( 'Submit Application', 'event-management-system' );
		$html .= '</button>';
		$html .= '</div>';

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render progress indicator for the review step.
	 *
	 * Replicates the progress bar from the form engine with all previous
	 * steps marked complete and the review step active.
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	private function render_review_progress() {
		$progress = $this->form->get_progress();
		$steps    = $this->form->get_steps();

		$html = '<div class="ems-form-progress" role="navigation" aria-label="' . esc_attr__( 'Form progress', 'event-management-system' ) . '">';
		$html .= '<ol class="ems-form-progress__list">';

		foreach ( $steps as $index => $step ) {
			$state = isset( $progress[ $index ] ) ? $progress[ $index ]['state'] : 'pending';

			$step_class = 'ems-form-progress__step ems-form-progress__step--' . $state;
			$aria       = ( 'active' === $state ) ? ' aria-current="step"' : '';

			$html .= '<li class="' . esc_attr( $step_class ) . '"' . $aria . '>';
			$html .= '<span>';
			$html .= '<span class="ems-form-progress__number">';
			if ( 'complete' === $state ) {
				$html .= '<span class="ems-form-progress__check" aria-hidden="true">&#10003;</span>';
			} else {
				$html .= esc_html( $index + 1 );
			}
			$html .= '</span>';
			$html .= '<span class="ems-form-progress__title" title="' . esc_attr( $step['title'] ) . '">' . esc_html( $step['title'] ) . '</span>';
			$html .= '</span>';
			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}

	// =========================================================================
	// Final Submission Handler
	// =========================================================================

	/**
	 * Process the final form submission from the review step.
	 *
	 * Performs spam protection checks, duplicate detection, creates the
	 * WordPress user and sponsor post, sets all meta fields, sends
	 * notification emails, clears form state, and returns a success message.
	 *
	 * @since 1.5.0
	 * @return string HTML notice (success or error).
	 */
	private function process_final_submission() {
		// Verify nonce
		if ( ! isset( $_POST['ems_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ems_form_nonce'] ) ), 'ems_form_' . $this->form_id ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'Security verification failed. Please try again.', 'event-management-system' )
				. '</div>';
		}

		// Handle "Previous" button on review step
		if ( isset( $_POST['ems_direction'] ) && 'prev' === sanitize_text_field( wp_unslash( $_POST['ems_direction'] ) ) ) {
			$current = $this->form->get_current_step();
			if ( $current > 0 ) {
				$this->form->set_current_step( $current - 1 );
				$this->form->save_state();
			}
			return '';
		}

		// Confirm checkbox
		if ( empty( $_POST['ems_confirm_submission'] ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'Please confirm that the information is correct before submitting.', 'event-management-system' )
				. '</div>';
		}

		// --- Spam Protection ---

		// Honeypot check
		if ( ! empty( $_POST['ems_hp_website'] ) ) {
			$this->logger->warning( 'Honeypot triggered on sponsor onboarding form', EMS_Logger::CONTEXT_GENERAL );
			// Silently fail to not reveal the protection mechanism
			return '<div class="ems-notice ems-notice-success">'
				. esc_html__( 'Thank you! Your application has been submitted.', 'event-management-system' )
				. '</div>';
		}

		// Rate limiting: max 3 submissions per IP per hour
		$rate_limit = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html( $rate_limit->get_error_message() )
				. '</div>';
		}

		// --- Gather all form data ---
		$form_data = $this->form->get_form_data();

		$org_data      = isset( $form_data['organisation'] ) ? $form_data['organisation'] : array();
		$contact_data  = isset( $form_data['contacts'] ) ? $form_data['contacts'] : array();
		$industry_data = isset( $form_data['industry'] ) ? $form_data['industry'] : array();
		$product_data  = isset( $form_data['products'] ) ? $form_data['products'] : array();
		$legal_data    = isset( $form_data['legal'] ) ? $form_data['legal'] : array();
		$conflict_data = isset( $form_data['conflict'] ) ? $form_data['conflict'] : array();

		$email      = isset( $contact_data['contact_email'] ) ? $contact_data['contact_email'] : '';
		$legal_name = isset( $org_data['legal_name'] ) ? $org_data['legal_name'] : '';
		$abn        = isset( $org_data['abn'] ) ? $org_data['abn'] : '';

		// --- Duplicate Detection (Task 5.12) ---
		$duplicate = $this->check_duplicates( $abn, $email );
		if ( is_wp_error( $duplicate ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html( $duplicate->get_error_message() )
				. '</div>';
		}

		// --- Create WordPress User ---
		$password = wp_generate_password( 16, true, true );
		$user_id  = wp_create_user( $email, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			$this->logger->error(
				'Failed to create sponsor user: ' . $user_id->get_error_message(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'An error occurred while creating your account. Please try again or contact the administrator.', 'event-management-system' )
				. '</div>';
		}

		// Set user role
		$user = new WP_User( $user_id );
		$user->set_role( 'ems_sponsor' );

		// Set display name
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $legal_name,
			'first_name'   => isset( $contact_data['contact_name'] ) ? $contact_data['contact_name'] : '',
		) );

		// Generate password reset key
		$reset_key = get_password_reset_key( $user );
		if ( is_wp_error( $reset_key ) ) {
			$this->logger->error(
				'Failed to generate password reset key: ' . $reset_key->get_error_message(),
				EMS_Logger::CONTEXT_GENERAL
			);
			// Fallback: still send welcome email but without reset link
			$reset_key = '';
		}

		// --- Create Sponsor Post ---
		$post_id = wp_insert_post( array(
			'post_type'   => 'ems_sponsor',
			'post_title'  => $legal_name,
			'post_status' => 'pending',
			'post_author' => $user_id,
		) );

		if ( is_wp_error( $post_id ) ) {
			// Clean up the user we just created
			wp_delete_user( $user_id );
			$this->logger->error(
				'Failed to create sponsor post: ' . $post_id->get_error_message(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'An error occurred while creating your sponsor profile. Please try again or contact the administrator.', 'event-management-system' )
				. '</div>';
		}

		// Link user to sponsor post
		update_user_meta( $user_id, '_ems_sponsor_id', $post_id );

		// --- Set All Meta Fields ---
		$this->save_sponsor_meta( $post_id, $org_data, $contact_data, $industry_data, $product_data, $legal_data, $conflict_data );

		// --- Clear Form State ---
		$this->form->clear_state();

		// --- Record rate limit ---
		$this->record_submission();

		// --- Send Notification Emails ---
		$this->send_welcome_email( $email, $legal_name, $reset_key );
		$this->send_admin_notification( $legal_name, $email, $post_id );

		// --- Log Success ---
		$this->logger->info(
			sprintf( 'Sponsor onboarding completed: %s (user: %d, post: %d)', $legal_name, $user_id, $post_id ),
			EMS_Logger::CONTEXT_GENERAL
		);

		// --- Return Success ---
		$portal_url = $this->get_portal_url();
		$login_url  = wp_login_url( $portal_url ? $portal_url : home_url() );

		return '<div class="ems-notice ems-notice-success">'
			. '<h3>' . esc_html__( 'Application Submitted Successfully!', 'event-management-system' ) . '</h3>'
			. '<p>' . esc_html__( 'Thank you for registering as a sponsor. Your application is now under review.', 'event-management-system' ) . '</p>'
			. '<p>' . esc_html__( 'A confirmation email has been sent to your contact email address with a link to set your password.', 'event-management-system' ) . '</p>'
			. '<p><a href="' . esc_url( $login_url ) . '" class="ems-btn ems-btn-primary">'
			. esc_html__( 'Log In to Your Sponsor Portal', 'event-management-system' )
			. '</a></p>'
			. '</div>';
	}

	// =========================================================================
	// Meta Field Persistence
	// =========================================================================

	/**
	 * Save all sponsor meta fields from form data.
	 *
	 * Maps form field names to _ems_sponsor_* post meta keys and persists
	 * each value using update_post_meta with appropriate sanitization.
	 *
	 * @since 1.5.0
	 * @param int   $post_id       Sponsor post ID.
	 * @param array $org_data      Organisation step data.
	 * @param array $contact_data  Contact step data.
	 * @param array $industry_data Industry step data.
	 * @param array $product_data  Products step data.
	 * @param array $legal_data    Legal step data.
	 * @param array $conflict_data Conflict step data.
	 * @return void
	 */
	private function save_sponsor_meta( $post_id, $org_data, $contact_data, $industry_data, $product_data, $legal_data, $conflict_data ) {
		// Section 1: Organisation Details
		$this->set_meta( $post_id, '_ems_sponsor_legal_name', $org_data, 'legal_name', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_trading_name', $org_data, 'trading_name', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_abn', $org_data, 'abn', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_acn', $org_data, 'acn', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_registered_address', $org_data, 'registered_address', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_website_url', $org_data, 'website_url', 'url' );
		$this->set_meta( $post_id, '_ems_sponsor_country', $org_data, 'country', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_parent_company', $org_data, 'parent_company', 'text' );

		// Section 2: Contact & Signatory
		$this->set_meta( $post_id, '_ems_sponsor_contact_name', $contact_data, 'contact_name', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_contact_role', $contact_data, 'contact_role', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_contact_email', $contact_data, 'contact_email', 'email' );
		$this->set_meta( $post_id, '_ems_sponsor_contact_phone', $contact_data, 'contact_phone', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_signatory_name', $contact_data, 'signatory_name', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_signatory_title', $contact_data, 'signatory_title', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_signatory_email', $contact_data, 'signatory_email', 'email' );
		$this->set_meta( $post_id, '_ems_sponsor_marketing_contact_name', $contact_data, 'marketing_contact_name', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_marketing_contact_email', $contact_data, 'marketing_contact_email', 'email' );

		// Section 3: Industry Classification
		$sectors = isset( $industry_data['industry_sectors'] ) ? $industry_data['industry_sectors'] : array();
		if ( ! is_array( $sectors ) ) {
			$sectors = array();
		}
		update_post_meta( $post_id, '_ems_sponsor_industry_sectors', array_map( 'sanitize_text_field', $sectors ) );

		$this->set_meta( $post_id, '_ems_sponsor_ma_member', $industry_data, 'ma_member', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_mtaa_member', $industry_data, 'mtaa_member', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_other_codes', $industry_data, 'other_codes', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_tga_number', $industry_data, 'tga_number', 'text' );

		// Section 4: Products & Scope
		$this->set_meta( $post_id, '_ems_sponsor_products', $product_data, 'products', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_artg_listings', $product_data, 'artg_listings', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_scheduling_class', $product_data, 'scheduling_class', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_device_class', $product_data, 'device_class', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_non_artg_product', $product_data, 'non_artg_product', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_off_label', $product_data, 'off_label', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_tga_safety_alert', $product_data, 'tga_safety_alert', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_educational_relation', $product_data, 'educational_relation', 'textarea' );

		// Section 5: Legal, Insurance & Compliance
		$this->set_meta( $post_id, '_ems_sponsor_public_liability_confirmed', $legal_data, 'public_liability_confirmed', 'bool_radio' );
		$this->set_meta( $post_id, '_ems_sponsor_public_liability_insurer', $legal_data, 'public_liability_insurer', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_public_liability_policy', $legal_data, 'public_liability_policy', 'text' );
		$this->set_meta( $post_id, '_ems_sponsor_product_liability_confirmed', $legal_data, 'product_liability_confirmed', 'bool_radio' );
		$this->set_meta( $post_id, '_ems_sponsor_professional_indemnity', $legal_data, 'professional_indemnity', 'bool_radio' );
		$this->set_meta( $post_id, '_ems_sponsor_workers_comp_confirmed', $legal_data, 'workers_comp_confirmed', 'bool_radio' );
		$this->set_meta( $post_id, '_ems_sponsor_code_compliance_agreed', $legal_data, 'code_compliance_agreed', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_compliance_process', $legal_data, 'compliance_process', 'bool_radio' );
		$this->set_meta( $post_id, '_ems_sponsor_adverse_findings', $legal_data, 'adverse_findings', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_legal_proceedings', $legal_data, 'legal_proceedings', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_transparency_obligations', $legal_data, 'transparency_obligations', 'textarea' );

		// Section 6: Conflict of Interest
		$this->set_meta( $post_id, '_ems_sponsor_qld_health_aware', $conflict_data, 'qld_health_aware', 'bool' );
		$this->set_meta( $post_id, '_ems_sponsor_personal_relationships', $conflict_data, 'personal_relationships', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_procurement_conflicts', $conflict_data, 'procurement_conflicts', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_transfers_of_value', $conflict_data, 'transfers_of_value', 'textarea' );
		$this->set_meta( $post_id, '_ems_sponsor_preferential_treatment_agreed', $conflict_data, 'preferential_treatment_agreed', 'bool' );

		// Record submission timestamp
		update_post_meta( $post_id, '_ems_sponsor_onboarding_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_ems_sponsor_onboarding_ip', $this->get_client_ip() );
	}

	/**
	 * Set a single post meta value with type-appropriate sanitization.
	 *
	 * @since 1.5.0
	 * @param int    $post_id   Post ID.
	 * @param string $meta_key  Meta key.
	 * @param array  $data      Data array.
	 * @param string $field_key Key within $data.
	 * @param string $type      Sanitization type: text, textarea, email, url, bool, bool_radio.
	 * @return void
	 */
	private function set_meta( $post_id, $meta_key, $data, $field_key, $type ) {
		$value = isset( $data[ $field_key ] ) ? $data[ $field_key ] : '';

		switch ( $type ) {
			case 'email':
				$value = sanitize_email( $value );
				break;
			case 'url':
				$value = esc_url_raw( $value );
				break;
			case 'textarea':
				$value = sanitize_textarea_field( $value );
				break;
			case 'bool':
				$value = ( '1' === (string) $value || 'yes' === $value ) ? true : false;
				break;
			case 'bool_radio':
				$value = ( 'yes' === $value ) ? true : false;
				break;
			default:
				$value = sanitize_text_field( $value );
				break;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}

	// =========================================================================
	// Duplicate Detection (Task 5.12)
	// =========================================================================

	/**
	 * Check for duplicate sponsor registrations.
	 *
	 * Searches for existing sponsor posts with the same ABN and existing
	 * WordPress users with the same email address.
	 *
	 * @since 1.5.0
	 * @param string $abn   ABN to check.
	 * @param string $email Email to check.
	 * @return true|WP_Error True if no duplicates found, WP_Error otherwise.
	 */
	private function check_duplicates( $abn, $email ) {
		// Check for existing user with same email
		if ( ! empty( $email ) && email_exists( $email ) ) {
			$this->logger->warning(
				sprintf( 'Duplicate email detected during sponsor onboarding: %s', $email ),
				EMS_Logger::CONTEXT_GENERAL
			);
			$login_url = wp_login_url();
			return new WP_Error(
				'duplicate_email',
				sprintf(
					/* translators: %s: login URL */
					__( 'An account with this email address already exists. If you already have an account, please log in instead: %s', 'event-management-system' ),
					esc_url( $login_url )
				)
			);
		}

		// Check for existing sponsor post with same ABN
		if ( ! empty( $abn ) ) {
			$normalised_abn = preg_replace( '/\s+/', '', $abn );

			$existing = new WP_Query( array(
				'post_type'      => 'ems_sponsor',
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'meta_query'     => array(
					array(
						'key'     => '_ems_sponsor_abn',
						'value'   => $normalised_abn,
						'compare' => '=',
					),
				),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( $existing->have_posts() ) {
				$this->logger->warning(
					sprintf( 'Duplicate ABN detected during sponsor onboarding: %s', $abn ),
					EMS_Logger::CONTEXT_GENERAL
				);
				return new WP_Error(
					'duplicate_abn',
					__( 'A sponsor organisation with this ABN is already registered. If you believe this is an error, please contact the administrator.', 'event-management-system' )
				);
			}

			wp_reset_postdata();
		}

		return true;
	}

	// =========================================================================
	// Spam Protection (Task 5.13)
	// =========================================================================

	/**
	 * Check the rate limit for the current IP address.
	 *
	 * Allows a maximum of 3 submissions per IP per hour.
	 *
	 * @since 1.5.0
	 * @return true|WP_Error True if within limit, WP_Error if exceeded.
	 */
	private function check_rate_limit() {
		$ip  = $this->get_client_ip();
		$key = 'ems_onboard_rl_' . md5( $ip );

		$attempts = get_transient( $key );

		if ( false !== $attempts && (int) $attempts >= 3 ) {
			$this->logger->warning(
				sprintf( 'Rate limit exceeded for sponsor onboarding from IP: %s', $ip ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Too many submission attempts. Please try again later.', 'event-management-system' )
			);
		}

		return true;
	}

	/**
	 * Record a submission attempt for rate limiting.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function record_submission() {
		$ip  = $this->get_client_ip();
		$key = 'ems_onboard_rl_' . md5( $ip );

		$attempts = get_transient( $key );
		if ( false === $attempts ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $key, (int) $attempts + 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Render inline JavaScript to inject the honeypot field into the form.
	 *
	 * The honeypot is a hidden text field that should remain empty. Bots
	 * that auto-fill it will be silently rejected.
	 *
	 * @since 1.5.0
	 * @return string Script tag HTML.
	 */
	private function render_honeypot_script() {
		ob_start();
		?>
		<script type="text/javascript">
		(function() {
			var form = document.getElementById('ems-form-<?php echo esc_js( $this->form_id ); ?>');
			if (form && !form.querySelector('[name="ems_hp_website"]')) {
				var div = document.createElement('div');
				div.setAttribute('aria-hidden', 'true');
				div.style.cssText = 'position:absolute;left:-9999px;';
				div.innerHTML = '<label for="ems-hp-website"><?php echo esc_js( __( 'Website', 'event-management-system' ) ); ?></label>' +
					'<input type="text" name="ems_hp_website" id="ems-hp-website" value="" tabindex="-1" autocomplete="off" />';
				form.insertBefore(div, form.firstChild);
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Email Notifications
	// =========================================================================

	/**
	 * Send welcome email to the new sponsor.
	 *
	 * Contains password reset link and next steps.
	 *
	 * @since 1.5.0
	 * @param string $email      Recipient email.
	 * @param string $org_name   Organisation name.
	 * @param string $reset_key  Password reset key.
	 * @return bool True if sent successfully.
	 */
	private function send_welcome_email( $email, $org_name, $reset_key ) {
		$site_name = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: 1: site name */
			__( 'Welcome to %s - Sponsor Registration Confirmed', 'event-management-system' ),
			$site_name
		);

		// Build password reset URL
		$reset_url = '';
		if ( ! empty( $reset_key ) ) {
			$reset_url = network_site_url( 'wp-login.php?action=rp&key=' . rawurlencode( $reset_key ) . '&login=' . rawurlencode( $email ), 'login' );
		}

		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;"><?php esc_html_e( 'Sponsor Registration Confirmed', 'event-management-system' ); ?></h2>

			<p><?php echo esc_html( sprintf(
				/* translators: %s: organisation name */
				__( 'Dear %s,', 'event-management-system' ),
				$org_name
			) ); ?></p>

			<p><?php echo esc_html( sprintf(
				/* translators: %s: site name */
				__( 'Thank you for registering as a sponsor with %s. Your application has been submitted and is now under review.', 'event-management-system' ),
				$site_name
			) ); ?></p>

			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Your Login Details:', 'event-management-system' ); ?></strong></p>
				<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Username:', 'event-management-system' ); ?></strong> <?php echo esc_html( $email ); ?></p>
				<?php if ( ! empty( $reset_url ) ) : ?>
					<p style="margin: 15px 0;">
						<a href="<?php echo esc_url( $reset_url ); ?>" style="display:inline-block;padding:10px 20px;background-color:#007bff;color:#fff;text-decoration:none;border-radius:4px;"><?php esc_html_e( 'Set Your Password', 'event-management-system' ); ?></a>
					</p>
				<?php endif; ?>
			</div>

			<p><strong><?php esc_html_e( 'Next Steps:', 'event-management-system' ); ?></strong></p>
			<ul>
				<li><?php esc_html_e( 'Click the "Set Your Password" button above to create your password.', 'event-management-system' ); ?></li>
				<li><?php esc_html_e( 'Your application will be reviewed by the event management team.', 'event-management-system' ); ?></li>
				<li><?php esc_html_e( 'Once approved, your sponsor profile will be activated.', 'event-management-system' ); ?></li>
				<li><?php esc_html_e( 'You can then access the Sponsor Portal to manage your events and files.', 'event-management-system' ); ?></li>
			</ul>

			<p><?php esc_html_e( 'If you have any questions, please contact us.', 'event-management-system' ); ?></p>

			<p><?php esc_html_e( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( $site_name ); ?> <?php esc_html_e( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Send admin notification about the new sponsor application.
	 *
	 * @since 1.5.0
	 * @param string $org_name Organisation name.
	 * @param string $email    Sponsor contact email.
	 * @param int    $post_id  Sponsor post ID.
	 * @return bool True if sent successfully.
	 */
	private function send_admin_notification( $org_name, $email, $post_id ) {
		$admin_email = get_option( 'admin_email' );
		$edit_url    = admin_url( 'post.php?post=' . absint( $post_id ) . '&action=edit' );

		$subject = sprintf(
			/* translators: %s: organisation name */
			__( 'New Sponsor Application: %s', 'event-management-system' ),
			$org_name
		);

		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;"><?php esc_html_e( 'New Sponsor Application', 'event-management-system' ); ?></h2>

			<p><?php esc_html_e( 'A new sponsor organisation has submitted an onboarding application:', 'event-management-system' ); ?></p>

			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Organisation:', 'event-management-system' ); ?></strong> <?php echo esc_html( $org_name ); ?></p>
				<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Contact Email:', 'event-management-system' ); ?></strong> <?php echo esc_html( $email ); ?></p>
				<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Status:', 'event-management-system' ); ?></strong> <?php esc_html_e( 'Pending Review', 'event-management-system' ); ?></p>
			</div>

			<p><a href="<?php echo esc_url( $edit_url ); ?>" style="display:inline-block;padding:10px 20px;background-color:#007bff;color:#fff;text-decoration:none;border-radius:4px;"><?php esc_html_e( 'Review Application', 'event-management-system' ); ?></a></p>
		</div>
		<?php
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $admin_email, $subject, $message, $headers );
	}

	// =========================================================================
	// Utility Methods
	// =========================================================================

	/**
	 * Get the URL to the sponsor portal page.
	 *
	 * Searches for a published page containing the [ems_sponsor_portal] shortcode.
	 *
	 * @since 1.5.0
	 * @return string|false Portal URL or false if not found.
	 */
	private function get_portal_url() {
		$portal_page = get_option( 'ems_sponsor_portal_page' );

		if ( $portal_page ) {
			return get_permalink( $portal_page );
		}

		// Fallback: search for page with the shortcode
		global $wpdb;
		$page_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'page'
			AND post_status = 'publish'
			AND post_content LIKE '%[ems_sponsor_portal%'
			LIMIT 1"
		);

		if ( $page_id ) {
			return get_permalink( $page_id );
		}

		return false;
	}

	/**
	 * Get the client IP address.
	 *
	 * Checks common proxy headers before falling back to REMOTE_ADDR.
	 *
	 * @since 1.5.0
	 * @return string IP address.
	 */
	private function get_client_ip() {
		/**
		 * Filter whether to trust proxy headers for IP detection.
		 *
		 * @since 1.5.0
		 * @param bool $trust_proxy Whether to trust proxy headers. Default false.
		 */
		$trust_proxy = apply_filters( 'ems_trust_proxy_headers', false );

		if ( ! $trust_proxy ) {
			return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		}

		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// HTTP_X_FORWARDED_FOR can contain multiple IPs; take the first one
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
