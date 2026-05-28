<?php
/**
 * Admin settings screen, AJAX tools and asset loading.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the Settings → BillionVerify screen.
 */
class BVEV_Admin {

	const MENU_SLUG = 'billionverify-email-validator';
	const NONCE     = 'bvev_admin';

	/**
	 * Verifier.
	 *
	 * @var BVEV_Verifier
	 */
	private $verifier;

	/**
	 * Integration manager.
	 *
	 * @var BVEV_Integration_Manager
	 */
	private $integrations;

	/**
	 * Constructor.
	 *
	 * @param BVEV_Verifier            $verifier     Verifier.
	 * @param BVEV_Integration_Manager $integrations Integration manager.
	 */
	public function __construct( BVEV_Verifier $verifier, BVEV_Integration_Manager $integrations ) {
		$this->verifier     = $verifier;
		$this->integrations = $integrations;

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_bvev_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_bvev_test_email', array( $this, 'ajax_test_email' ) );
	}

	/**
	 * Register the menu under Settings.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			__( 'BillionVerify Email Validator', 'billionverify-email-validator' ),
			__( 'BillionVerify', 'billionverify-email-validator' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on our screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'bvev-admin', BVEV_PLUGIN_URL . 'assets/css/admin.css', array(), BVEV_VERSION );
		wp_enqueue_script( 'bvev-admin', BVEV_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), BVEV_VERSION, true );
		wp_localize_script(
			'bvev-admin',
			'bvevAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE ),
				'i18n'    => array(
					'testing'   => __( 'Testing…', 'billionverify-email-validator' ),
					'verifying' => __( 'Verifying…', 'billionverify-email-validator' ),
					'error'     => __( 'Request failed. Please try again.', 'billionverify-email-validator' ),
				),
			)
		);
	}

	/**
	 * Persist settings on POST.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! isset( $_POST['bvev_save'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( self::NONCE );

		$defaults = BVEV_Settings::defaults();
		$out      = $defaults;

		$out['api_key']       = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$out['check_smtp']    = isset( $_POST['check_smtp'] ) ? 1 : 0;
		$out['fail_open']     = isset( $_POST['fail_open'] ) ? 1 : 0;
		$out['timeout']       = isset( $_POST['timeout'] ) ? max( 1, min( 60, (int) $_POST['timeout'] ) ) : $defaults['timeout'];
		$out['cache_ttl']     = isset( $_POST['cache_ttl'] ) ? max( 0, (int) $_POST['cache_ttl'] ) : $defaults['cache_ttl'];
		$out['block_message'] = isset( $_POST['block_message'] ) ? sanitize_text_field( wp_unslash( $_POST['block_message'] ) ) : $defaults['block_message'];

		$posted_statuses = isset( $_POST['block_statuses'] ) && is_array( $_POST['block_statuses'] ) ? wp_unslash( $_POST['block_statuses'] ) : array();
		foreach ( $out['block_statuses'] as $status => $unused ) {
			$out['block_statuses'][ $status ] = isset( $posted_statuses[ $status ] ) ? 1 : 0;
		}

		$posted_integrations = isset( $_POST['integrations'] ) && is_array( $_POST['integrations'] ) ? wp_unslash( $_POST['integrations'] ) : array();
		foreach ( $out['integrations'] as $key => $unused ) {
			$out['integrations'][ $key ] = isset( $posted_integrations[ $key ] ) ? 1 : 0;
		}

		BVEV_Settings::update( $out );

		add_settings_error( 'bvev', 'bvev_saved', __( 'Settings saved.', 'billionverify-email-validator' ), 'updated' );
	}

	/**
	 * Verify the current user can run AJAX tools.
	 *
	 * @return void Dies with JSON error if unauthorized.
	 */
	private function guard_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'billionverify-email-validator' ) ), 403 );
		}
		check_ajax_referer( self::NONCE, 'nonce' );
	}

	/**
	 * AJAX: test the API key by fetching the credit balance.
	 *
	 * @return void
	 */
	public function ajax_test_connection() {
		$this->guard_ajax();

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( '' === $api_key ) {
			$api_key = (string) BVEV_Settings::get( 'api_key', '' );
		}

		$client  = new BVEV_Api_Client( $api_key, (int) BVEV_Settings::get( 'timeout', 8 ) );
		$credits = $client->get_credits();

		if ( is_wp_error( $credits ) ) {
			wp_send_json_error( array( 'message' => $credits->get_error_message() ) );
		}

		$balance = isset( $credits['credits_balance'] ) ? (int) $credits['credits_balance'] : null;
		wp_send_json_success(
			array(
				'message' => null === $balance
					? __( 'Connected successfully.', 'billionverify-email-validator' )
					/* translators: %s: formatted credit balance. */
					: sprintf( __( 'Connected. Credit balance: %s', 'billionverify-email-validator' ), number_format_i18n( $balance ) ),
				'credits' => $credits,
			)
		);
	}

	/**
	 * AJAX: verify a single test email and return the full result.
	 *
	 * @return void
	 */
	public function ajax_test_email() {
		$this->guard_ajax();

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( '' === $email || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address to test.', 'billionverify-email-validator' ) ) );
		}

		$client = new BVEV_Api_Client(
			(string) BVEV_Settings::get( 'api_key', '' ),
			(int) BVEV_Settings::get( 'timeout', 8 )
		);
		$data = $client->verify_single( $email, (bool) BVEV_Settings::get( 'check_smtp', 1 ) );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		$status     = isset( $data['status'] ) ? (string) $data['status'] : 'unknown';
		$would_block = BVEV_Settings::blocks_status( $status );

		wp_send_json_success(
			array(
				'status'      => $status,
				'would_block' => $would_block,
				'data'        => $data,
			)
		);
	}

	/**
	 * Render the settings page view.
	 *
	 * @return void
	 */
	public function render_page() {
		$settings     = BVEV_Settings::all();
		$integrations = $this->integrations->all();
		require BVEV_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
	}
}
