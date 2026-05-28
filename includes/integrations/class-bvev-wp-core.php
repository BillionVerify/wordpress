<?php
/**
 * WordPress core form integrations: registration, comment, lost password.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registration form integration.
 */
class BVEV_Integration_WP_Registration extends BVEV_Integration_Base {

	public function key() {
		return 'wp_registration';
	}

	public function label() {
		return __( 'WordPress Registration', 'billionverify-email-validator' );
	}

	public function is_available() {
		return true;
	}

	public function hooks() {
		add_filter( 'registration_errors', array( $this, 'validate' ), 10, 3 );
		// Multisite signup.
		add_filter( 'wpmu_validate_user_signup', array( $this, 'validate_ms_signup' ) );
	}

	/**
	 * Validate the registration email.
	 *
	 * @param WP_Error $errors Errors.
	 * @param string   $login  Login.
	 * @param string   $email  Email.
	 * @return WP_Error
	 */
	public function validate( $errors, $login, $email ) {
		$message = $this->block_message_for( $email );
		if ( '' !== $message ) {
			$errors->add( 'bvev_email', $message );
		}
		return $errors;
	}

	/**
	 * Validate multisite signup.
	 *
	 * @param array $result Signup result with 'user_email' and 'errors'.
	 * @return array
	 */
	public function validate_ms_signup( $result ) {
		$email = isset( $result['user_email'] ) ? $result['user_email'] : '';
		$message = $this->block_message_for( $email );
		if ( '' !== $message && isset( $result['errors'] ) && is_wp_error( $result['errors'] ) ) {
			$result['errors']->add( 'user_email', $message );
		}
		return $result;
	}
}

/**
 * Comment form integration.
 */
class BVEV_Integration_WP_Comment extends BVEV_Integration_Base {

	public function key() {
		return 'wp_comment';
	}

	public function label() {
		return __( 'WordPress Comments', 'billionverify-email-validator' );
	}

	public function is_available() {
		return true;
	}

	public function hooks() {
		add_filter( 'preprocess_comment', array( $this, 'validate' ) );
	}

	/**
	 * Validate the comment author email.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function validate( $commentdata ) {
		// Skip logged-in users and pingbacks/trackbacks.
		if ( is_user_logged_in() ) {
			return $commentdata;
		}
		$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
		if ( 'comment' !== $type && '' !== $type ) {
			return $commentdata;
		}
		$email = isset( $commentdata['comment_author_email'] ) ? $commentdata['comment_author_email'] : '';
		$message = $this->block_message_for( $email );
		if ( '' !== $message ) {
			wp_die(
				esc_html( $message ),
				esc_html__( 'Comment Submission Failure', 'billionverify-email-validator' ),
				array( 'response' => 200, 'back_link' => true )
			);
		}
		return $commentdata;
	}
}

/**
 * Lost-password form integration.
 */
class BVEV_Integration_WP_Lost_Password extends BVEV_Integration_Base {

	public function key() {
		return 'wp_lost_password';
	}

	public function label() {
		return __( 'WordPress Lost Password', 'billionverify-email-validator' );
	}

	public function is_available() {
		return true;
	}

	public function hooks() {
		add_action( 'lostpassword_post', array( $this, 'validate' ), 10, 1 );
	}

	/**
	 * Validate the lost-password email field. Only checks when an email (not a
	 * username) was entered, to avoid spending credits on usernames.
	 *
	 * @param WP_Error $errors Errors object.
	 * @return void
	 */
	public function validate( $errors ) {
		$login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $login || ! is_email( $login ) ) {
			return;
		}
		$message = $this->block_message_for( $login );
		if ( '' !== $message && is_wp_error( $errors ) ) {
			$errors->add( 'bvev_email', $message );
		}
	}
}
