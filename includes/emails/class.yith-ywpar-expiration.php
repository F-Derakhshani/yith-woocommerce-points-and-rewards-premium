<?php
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of YITH WooCommerce Points and Rewards
 *
 * @class   YITH_WC_Points_Rewards
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( ! class_exists( 'YITH_YWPAR_Expiration' ) ) {

	/**
	 * YITH_YWPAR_Expiration
	 *
	 * @since 1.0.0
	 */
	class YITH_YWPAR_Expiration extends WC_Email {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @return \YITH_YWPAR_Expiration
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id          = 'ywpar_expiration';
			$this->title       = __( 'Expiring Points', 'yith-woocommerce-points-and-rewards' );
			$this->description = __( 'This email is sent to users some days before their points expire.', 'yith-woocommerce-points-and-rewards' );

			$this->heading = __( 'Points and Rewards', 'yith-woocommerce-points-and-rewards' );
			$this->subject = __( '[Expiring points]', 'yith-woocommerce-points-and-rewards' );

			$this->template_html  = 'emails/expired-points.php';
			$this->template_plain = 'emails/plain/expired-points.php';

			// Triggers for this email
			add_action( 'expired_points_mail_notification', array( $this, 'trigger' ), 15, 1 );

			// Call parent constructor
			parent::__construct();

			// Other settings
			$this->recipient = $this->get_option( 'recipient' );

			if ( ! $this->recipient ) {
				$this->recipient = get_option( 'admin_email' );
			}

			$this->enable_cc = $this->get_option( 'enable_cc' );
			$this->enable_cc = $this->enable_cc == 'yes';
		}

		/**
		 * Method triggered to send email
		 *
		 * @param int $args
		 *
		 * @return void
		 * @since  1.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function trigger( $args ) {

			if ( ! $this->is_enabled() || YITH_WC_Points_Rewards()->get_option( 'send_email_before_expiration_date' ) != 'yes' ) {
				return;
			}

			$expoint_sent = get_user_meta( $args['user_id'], 'points_exp_sent', true );

			if ( is_array( $expoint_sent ) && in_array( $args['item_id'], $expoint_sent ) ) {
				return;
			}

			$this->email_content = $args['email_content'];
			$this->recipient     = $args['user_email'];

			$result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			if ( $result ) {
				$expoint_sent[] = $args['item_id'];
				update_user_meta( $args['user_id'], 'points_exp_sent', $expoint_sent );
			}

		}


		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 * @since  1.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function get_content_html() {
			ob_start();

			wc_get_template( $this->template_html, array(
				'email_content' => $this->email_content,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this
			) );

			return ob_get_clean();
		}

		/**
		 * Get plain text content of the mail
		 *
		 * @return string Plain text content of the mail
		 * @since  1.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function get_content_plain() {
			ob_start();
			wc_get_template( $this->template_plain, array(
				'email_content' => $this->email_content,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this
			) );

			return ob_get_clean();
		}


		public function get_attachments() {
			$attachments = array();
			if ( ! empty( $file ) && file_exists( $file['file'] ) ) {
				$attachments[] = $file['file'];
			}

			return $attachments;
		}

		/**
		 * Get from name for email.
		 *
		 * @return string
		 */
		public function get_from_name() {
			$email_from_name = ( isset( $this->email_from_name ) && $this->email_from_name != '' ) ? $this->email_from_name : get_option( 'woocommerce_email_from_name' );

			return wp_specialchars_decode( esc_html( $email_from_name ), ENT_QUOTES );
		}

		/**
		 * Get from email address.
		 *
		 * @return string
		 */
		public function get_from_address() {
			$email_from_email = ( isset( $this->email_from_email ) && $this->email_from_email != '' ) ? $this->email_from_email : get_option( 'woocommerce_email_from_address' );

			return sanitize_email( $email_from_email );
		}

		/**
		 * Init form fields to display in WC admin pages
		 *
		 * @return void
		 * @since  1.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'          => array(
					'title'   => __( 'Enable/Disable', 'yith-woocommerce-points-and-rewards' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable notification for this type of emails', 'yith-woocommerce-points-and-rewards' ),
					'default' => 'yes',
				),
				'email_from_name'  => array(
					'title'       => __( 'From (Name)', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_name' )
				),
				'email_from_email' => array(
					'title'       => __( 'From (Email Address)', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_address' )
				),
				'subject'          => array(
					'title'       => __( 'Subject', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'text',
					'description' => sprintf( __( 'This field lets you edit email subject line. Leave it blank to use default subject text: <code>%s</code>.', 'yith-woocommerce-points-and-rewards' ), $this->subject ),
					'placeholder' => '',
					'default'     => ''
				),
				'recipient'        => array(
					'title'       => __( 'Recipient(s)', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Enter recipients (separated by commas) for this email. Defaults to <code>%s</code>', 'yith-woocommerce-points-and-rewards' ), esc_attr( get_option( 'admin_email' ) ) ),
					'placeholder' => '',
					'default'     => ''
				),
				'enable_cc'        => array(
					'title'       => __( 'Send CC copy', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'checkbox',
					'description' => __( 'Send a carbon copy to the user', 'yith-woocommerce-points-and-rewards' ),
					'default'     => 'no'
				),
				'heading'          => array(
					'title'       => __( 'Email Heading', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'text',
					'description' => sprintf( __( 'This field lets you change the main heading in email notification. Leave it blank to use default heading type: <code>%s</code>.', 'yith-woocommerce-points-and-rewards' ), $this->heading ),
					'placeholder' => '',
					'default'     => ''
				),

				'email-description' => array(
					'title'       => __( 'Email Description', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'textarea',
					'placeholder' => '',
					'default'     => '',
				),

				'email_type' => array(
					'title'       => __( 'Email type', 'yith-woocommerce-points-and-rewards' ),
					'type'        => 'select',
					'description' => __( 'Choose format for the email that has to be sent.', 'yith-woocommerce-points-and-rewards' ),
					'default'     => 'html',
					'class'       => 'email_type',
					'options'     => array(
						'plain'     => __( 'Plain text', 'yith-woocommerce-points-and-rewards' ),
						'html'      => __( 'HTML', 'yith-woocommerce-points-and-rewards' ),
						'multipart' => __( 'Multipart', 'yith-woocommerce-points-and-rewards' ),
					)
				)
			);
		}
	}
}


// returns instance of the mail on file include
return new YITH_YWPAR_Expiration();