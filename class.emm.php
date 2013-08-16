<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class Extended_Media_Manager extends EMM_Plugin {

	/**
	 * Array of Service objects.
	 */
	public $services = array();

	/**
	 * Class constructor. Set up some actions and filters.
	 *
	 * @return null
	 */
	protected function __construct( $file ) {

		# Filters:
		# (none)

		# Actions:
		add_action( 'init',                  array( $this, 'action_init' ) );
		add_action( 'wp_enqueue_media',      array( $this, 'action_enqueue_media' ) );
		add_action( 'print_media_templates', array( $this, 'action_print_media_templates' ) );

		# AJAX actions:
		add_action( 'wp_ajax_emm_request',   array( $this, 'ajax_request' ) );

		# Parent setup:
		parent::__construct( $file );

		# Go!
		$this->load_services();

	}

	/**
	 * Populate the array of Service objects.
	 *
	 * @return null
	 */
	protected function load_services() {

		foreach ( apply_filters( 'emm_services', array() ) as $service_id => $service ) {
			if ( is_a( $service, 'EMM_Service' ) )
				$this->services[$service_id] = $service;
		}

	}

	/**
	 * Retrieve a registered Service object by its ID.
	 *
	 * @param string $service_id A service ID.
	 * @return Service|WP_Error A Service object on success, a WP_Error object on failure.
	 */
	public function get_service( $service_id ) {

		if ( isset( $this->services[$service_id] ) )
			return $this->services[$service_id];

		return new WP_Error(
			'invalid_service',
			sprintf( __( 'Media service "%s" was not found', 'emm' ), esc_html( $service_id ) )
		);

	}

	/**
	 * Retrieve all the registered services.
	 *
	 * @return array An array of registered Service objects.
	 */
	public function get_services() {
		return $this->services;
	}

	/**
	 * Load the Backbone templates for each of our registered services.
	 *
	 * @action print_media_templates
	 * @return null
	 */
	public function action_print_media_templates() {

		foreach ( $this->get_services() as $service_id => $service ) {

			if ( ! $template = $service->get_template() )
				continue;

			# @TODO this list of templates should be somewhere else. where?
			foreach ( array( 'search', 'item' ) as $t ) {

				foreach ( $service->get_tabs() as $tab_id => $tab ) {

					$id = sprintf( 'emm-%s-%s-%s',
						esc_attr( $service_id ),
						esc_attr( $t ),
						esc_attr( $tab_id )
					);

					$template->before_template( $id, $tab_id );
					call_user_func( array( $template, $t ), $id, $tab_id );
					$template->after_template( $id, $tab_id );

				}

			}

			foreach ( array( 'thumbnail' ) as $t ) {

				$id = sprintf( 'emm-%s-%s',
					esc_attr( $service_id ),
					esc_attr( $t )
				);

				$template->before_template( $id );
				call_user_func( array( $template, $t ), $id );
				$template->after_template( $id );

			}

		}

	}

	/**
	 * Process an AJAX request and output the resulting JSON.
	 *
	 * @action wp_ajax_emm_request
	 * @return null
	 */
	public function ajax_request() {

		if ( !isset( $_POST['_nonce'] ) or !wp_verify_nonce( $_POST['_nonce'], 'emm_request' ) )
			die( '-1' );

		$service = $this->get_service( stripslashes( $_POST['service'] ) );

		if ( is_wp_error( $service ) ) {
			wp_send_json_error( array(
				'error_code'    => $service->get_error_code(),
				'error_message' => $service->get_error_message()
			) );
		}

		foreach ( $service->requires() as $file => $class ) {

			if ( class_exists( $class ) )
				continue;

			require_once sprintf( '%s/class.%s.php',
				dirname( __FILE__ ),
				$file
			);

		}

		$request = wp_parse_args( stripslashes_deep( $_POST ), array(
			'params'  => array(),
			'tab'     => null,
			'min_id'  => null,
			'max_id'  => null,
			'page'    => 1,
		) );

		$response = $service->request( array(
			'params'  => $request['params'],
			'tab'     => $request['tab'],
			'min_id'  => $request['min_id'],
			'max_id'  => $request['max_id'],
			'page'    => absint( $request['page'] ),
			'user_id' => absint( get_current_user_id() ),
		) );

		if ( is_wp_error( $response ) ) {

			wp_send_json_error( array(
				'error_code'    => $response->get_error_code(),
				'error_message' => $response->get_error_message()
			) );

		} else if ( is_a( $response, 'EMM_Response' ) ) {

			wp_send_json_success( $response->output() );

		} else {

			wp_send_json_success( false );

		}

	}

	/**
	 * Enqueue and localise the JS and CSS we need for the media manager.
	 *
	 * @action enqueue_media
	 * @return null
	 */
	public function action_enqueue_media() {

		$emm = array(
			'_nonce'    => wp_create_nonce( 'emm_request' ),
			'labels'    => array(
				'insert' => __( 'Insert', 'emm' )
			),
			'base_url'  => untrailingslashit( $this->plugin_url() ),
			'admin_url' => untrailingslashit( admin_url() ),
		);

		foreach ( $this->get_services() as $service_id => $service ) {
			$service->load();
			$emm['services'][$service_id] = array(
				'id'     => $service_id,
				'labels' => $service->get_labels(),
				'tabs'   => $service->get_tabs(),
			);
		}

		wp_enqueue_script(
			'emm',
			$this->plugin_url( 'js/emm.js' ),
			array( 'jquery', 'media-views' ),
			$this->plugin_ver( 'js/emm.js' )
		);

		wp_localize_script(
			'emm',
			'emm',
			$emm
		);

		wp_enqueue_style(
			'emm',
			$this->plugin_url( 'css/emm.css' ),
			array( /*'wp-admin'*/ ),
			$this->plugin_ver( 'css/emm.css' )
		);

	}

	/**
	 * Load text domain and localisation files.
	 *
	 * @action init
	 * @return null
	 */
	public function action_init() {

		load_plugin_textdomain( 'emm', false, dirname( $this->plugin_base() ) . '/languages/' );

	}

	/**
	 * Singleton instantiator.
	 *
	 * @param string $file The plugin file (usually __FILE__) (optional)
	 * @return Extended_Media_Manager
	 */
	public static function init( $file = null ) {

		static $instance = null;

		if ( !$instance )
			$instance = new Extended_Media_Manager( $file );

		return $instance;

	}

}
