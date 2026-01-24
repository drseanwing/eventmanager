<?php
/**
 * Sponsor Custom Post Type
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

	public function register_post_type() {
		$labels = array(
			'name'          => _x( 'Sponsors', 'Post Type General Name', 'event-management-system' ),
			'singular_name' => _x( 'Sponsor', 'Post Type Singular Name', 'event-management-system' ),
			'menu_name'     => __( 'Sponsors', 'event-management-system' ),
		);

		$args = array(
			'label'               => __( 'Sponsor', 'event-management-system' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'public'              => true,
			'show_in_menu'        => false,
			'capability_type'     => 'post',
		);

		register_post_type( self::POST_TYPE, $args );
		$this->logger->debug( 'Sponsor post type registered', EMS_Logger::CONTEXT_GENERAL );
	}

	public function register_taxonomies() {
		register_taxonomy( self::TAXONOMY_LEVEL, array( self::POST_TYPE ), array(
			'labels'       => array( 'name' => __( 'Sponsor Levels', 'event-management-system' ) ),
			'hierarchical' => true,
			'public'       => true,
		) );
	}
}
