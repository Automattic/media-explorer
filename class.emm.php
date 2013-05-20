<?php
/*
Copyright © 2013 Code for the People Ltd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class Extended_Media_Manager extends \EMM\Plugin {

	public $services = array();

	public function __construct( $file ) {

		# Filters:
		add_filter( 'mce_external_plugins',  array( $this, 'filter_mce_plugins' ) );

		# Actions:
		add_action( 'init',                  array( $this, 'action_init' ) );
		add_action( 'wp_enqueue_media',      array( $this, 'action_enqueue_media' ) );
		add_action( 'print_media_templates', array( $this, 'action_print_media_templates' ) );

		# AJAX actions:
		add_action( 'wp_ajax_emm_request',   array( $this, 'ajax_request' ) );

		# Parent setup:
		parent::__construct( $file );

		# Go!
		# @TODO populate services on-demand instead
		$this->load_services();

	}

	public function load_services() {

		foreach ( apply_filters( 'emm_services', array() ) as $service_id => $service ) {
			if ( is_a( $service, '\EMM\Service' ) )
				$this->services[$service_id] = $service;
		}

	}

	public function get_service( $service_id ) {

		if ( isset( $this->services[$service_id] ) )
			return $this->services[$service_id];
		else
			return false; # @TODO wp_error

	}

	public function get_services() {
		return $this->services;
	}

	public function action_print_media_templates() {

		foreach ( $this->get_services() as $service_id => $service ) {

			if ( ! $template = $service->get_template() )
				continue;

			# @TODO this list of templates should be somewhere else. where?
			foreach ( array( 'search', 'item', 'thumbnail', 'first_time' ) as $t ) {
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

	public function ajax_request() {

		# @TODO nonce check to prevent privilege escalation

		$sid = stripslashes( $_POST['service'] );

		if ( ! $service = $this->get_service( $sid ) ) {
			wp_send_json_error( array(
				'error_code'    => 'invalid_service',
				'error_message' => sprintf( __( 'Media service "%s" was not found', 'emm' ), esc_html( $sid ) )
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
			'before'  => null,
			'since'   => null,
			'page'    => 1,
		) );

		$response = $service->request( array(
			'params'  => $request['params'],
			'before'  => $request['before'],
			'since'   => $request['since'],
			'page'    => absint( $request['page'] ),
			'user_id' => absint( get_current_user_id() ),
		) );

		if ( is_wp_error( $response ) ) {

			wp_send_json_error( array(
				'error_code'    => $response->get_error_code(),
				'error_message' => $response->get_error_message()
			) );

		} else if ( is_a( $response, '\EMM\Response' ) ) {

			wp_send_json_success( $response->output() );

		} else {

			wp_send_json_success( false );

		}

	}

	public function filter_mce_plugins( $plugins ) {

		foreach ( $this->get_services() as $service_id => $service ) {
			$f = sprintf( 'services/%s/editor_plugin.js', $service_id );
			if ( file_exists( $this->plugin_path( $f ) ) )
				$plugins['emm-' . $service_id] = $this->plugin_url( $f );
		}

		return $plugins;

	}

	public function action_enqueue_media() {

		$emm = array(
			# @TODO nonce
			'labels'  => array(
				'insert' => __( 'Insert', 'emm' )
			),
			'base_url' => untrailingslashit( $this->plugin_url() )
		);

		foreach ( $this->get_services() as $service_id => $service ) {
			$emm['services'][$service_id] = array(
				'id'     => $service_id,
				'labels' => $service->get_labels()
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

	public function action_init() {

		load_plugin_textdomain( 'emm', false, dirname( $this->plugin_base() ) . '/languages/' );

	}

}

