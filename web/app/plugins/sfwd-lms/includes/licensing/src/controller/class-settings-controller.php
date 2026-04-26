<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Framework\Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

defined( 'ABSPATH' ) || exit;

/**
 * Settings controllers
 */
class Settings_Controller extends Controller {
	use Permission;
	use License;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->register_page(
			__( 'Settings', 'learndash' ),
			'learndash-hub-settings',
			array( $this, 'display' ),
			'learndash-hub'
		);

		add_action( 'wp_ajax_ld_hub_search_admins', array( $this, 'search_admins' ) );
		add_action( 'wp_ajax_ld_hub_update_permissions', array( $this, 'update_permissions' ) );
		add_action( 'wp_ajax_ld_hub_reset_permissions', array( $this, 'reset_permissions' ) );
		add_action( 'wp_ajax_ld_hub_sign_out', array( $this, 'sign_out' ) );
	}

	/**
	 * Ajax endpoint for sign out.
	 */
	public function sign_out() {
		if ( ! $this->verify_nonce( 'ld_hub_sign_out' ) ) {
			return;
		}

		if ( ! $this->check_permission() ) {
			return;
		}

		$ret = $this->get_api()->remove_domain();
		$this->clear_auth();

		// we need to remove all projects cache too.
		delete_site_option( 'learndash_hub_fetch_projects' );
		delete_site_option( 'learndash_hub_update_plugins_cache' );
		$this->cleanup_access_list();

		/**
			 * Fires after the license logout.
			 *
			 * @since 4.18.0
			 */
		do_action( 'learndash_licensing_management_license_logout' );

		wp_send_json_success();
	}

	/**
	 * Ajax endpoint for add/edit user in the admin list.
	 */
	public function update_permissions() {
		if ( ! $this->verify_nonce( 'ld_hub_update_permissions' ) ) {
			return;
		}
		if ( ! $this->check_permission() ) {
			return;
		}

		$intention = isset( $_POST['intention'] ) ? sanitize_title( $_POST['intention'] ) : false;
		$user_id   = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : false;
		if ( false === $intention || false === $user_id ) {
			return;
		}
		switch ( $intention ) {
			case 'add':
				$permissions = $_POST['allow'] ?? array();
				$permissions = array_map( 'sanitize_title', $permissions );
				$this->allow_user( $user_id, $permissions, false );
				break;
			case 'remove':
				$this->disallow_user( $user_id );
				break;
		}
		wp_send_json_success( $this->get_users_list() );
	}

	/**
	 * Ajax endpoint for reset the admin list.
	 */
	public function reset_permissions() {
		if ( ! $this->verify_nonce( 'ld_hub_reset_permissions' ) ) {
			return;
		}

		if ( ! $this->check_permission() ) {
			return;
		}

		$this->populate_access_list();

		wp_send_json_success( $this->get_users_list() );
	}

	/**
	 * Ajax endpoint for search admins.
	 */
	public function search_admins() {
		if ( ! $this->verify_nonce( 'ld_hub_search_admins' ) ) {
			return;
		}
		if ( ! $this->check_permission() ) {
			return;
		}

		$search  = isset( $_GET['search'] ) ? sanitize_user( $_GET['search'] ) : '';
		$list    = $this->get_users_list();
		$exclude = array();
		foreach ( $list as $item ) {
			$exclude[] = $item['id'];
		}
		$result = get_users(
			array(
				'role'    => 'Administrator',
				'search'  => "*$search*",
				'exclude' => $exclude,
			)
		);
		$users  = array();
		foreach ( $result as $user ) {
			$users[] = array(
				'id'   => $user->ID,
				'text' => $user->user_login,
			);
		}
		wp_send_json_success( $users );
	}

	/**
	 * Display the content.
	 */
	public function display() {
		$this->render(
			'root'
		);
	}

	/**
	 * @return array
	 */
	public function make_data(): array {
		$email       = $this->get_hub_email();
		$license_key = $this->get_license_key();

		return array(
			'adminUrl'    => is_multisite() ? network_admin_url( '/admin.php?page=learndash-hub-settings' ) : admin_url( '/admin.php?page=learndash-hub-settings' ),
			'rootUrl'     => is_multisite() ? network_admin_url( '/admin.php?page=learndash-hub' ) : admin_url( '/admin.php?page=learndash-hub' ),
			'nonces'      => array(
				'verify_license'     => wp_create_nonce( 'ld_hub_verify_license' ),
				'search_admins'      => wp_create_nonce( 'ld_hub_search_admins' ),
				'update_permissions' => wp_create_nonce( 'ld_hub_update_permissions' ),
				'sign_out'           => wp_create_nonce( 'ld_hub_sign_out' ),
			),
			'email'       => $email,
			'license_key' => $license_key,
			'signed'      => $email && $license_key ? 1 : 0,
			'list'        => $this->get_users_list(),
		);
	}

	/**
	 * Get the users permissions list, for frontend display
	 *
	 * @return array
	 */
	private function get_users_list() {
		$lists = $this->get_allowed_users();

		$data = array();
		// need to fetch the users' data.
		foreach ( $lists as $user_id => $permissions ) {
			$user = get_user_by( 'id', $user_id );
			if ( is_object( $user ) ) {
				$data[] = array(
					'id'      => $user_id,
					'name'    => $user->display_name,
					'email'   => $user->user_email,
					'avatar'  => get_avatar_url( $user->ID ),
					'is_self' => get_current_user_id() === $user_id,
				);
			}
		}

		return $data;
	}
}
