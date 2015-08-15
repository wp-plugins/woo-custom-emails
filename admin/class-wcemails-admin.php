<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WCEmails_Admin' ) ) {

	/**
	 * Admin WooCommerce Custom Emails Class
	 *
	 * @class WCE_Admin
	 * @version	0.1
	 */
	class WCEmails_Admin {

		/**
		 * @var WCEmails_Admin The single instance of the class
		 * @since 0.1
		 */
		protected static $_instance = null;

		/**
		 * Main WCEmails_Admin Instance
		 *
		 * Ensures only one instance of WCEmails_Admin is loaded or can be loaded.
		 *
		 * @since 0.1
		 * @static
		 * @return WCEmails_Admin - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'wcemails_settings_menu' ) );

			add_action( 'admin_init', array( $this, 'wcemails_email_actions_details' ) );

			add_filter( 'woocommerce_email_classes', array( $this, 'wcemails_custom_woocommerce_emails' ) );

			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'wcemails_change_action_emails' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'wcemails_enqueue_scripts' ) );

		}

		function wcemails_enqueue_scripts() {
			wp_register_script( 'jquery-cloneya', WCEmails_PLUGIN_URL . 'js/jquery-cloneya.min.js', array( 'jquery' ) );
			wp_register_script( 'wcemails-custom-scripts', WCEmails_PLUGIN_URL . 'js/wcemails-custom-scripts.js', array( 'jquery' ), WCEmails_VERSION );
		}

		function wcemails_settings_menu() {

			add_options_page( __( 'WC Emails', WCEmails_TEXT_DOMAIN ), 'WC Emails', 'manage_options', 'wcemails-settings', array( $this, 'wcemails_settings_callback' ) );

		}

		function wcemails_settings_callback() {

			?>
			<div class="wrap">
				<h2><?php _e( 'Woocommerce Custom Emails Settings', WCEmails_TEXT_DOMAIN ); ?></h2>
				<?php
				if ( ! isset( $_REQUEST['type'] ) ) {
					$type = 'today';
				} else {
					$type = $_REQUEST['type'];
				}
				$all_types = array( 'add-email', 'view-email' );
				if ( ! in_array( $type, $all_types ) ) {
					$type = 'add-email';
				}
				?>
				<ul class="subsubsub">
					<li class="today"><a class ="<?php echo ( 'add-email' == $type ) ? 'current' : ''; ?>" href="<?php echo add_query_arg( array( 'type' => 'add-email' ), admin_url( 'admin.php?page=wcemails-settings' ) ); ?>"><?php _e( 'Add Custom Emails', WCEmails_TEXT_DOMAIN ); ?></a> |</li>
					<li class="today"><a class ="<?php echo ( 'view-email' == $type ) ? 'current' : ''; ?>" href="<?php echo add_query_arg( array( 'type' => 'view-email' ), admin_url( 'admin.php?page=wcemails-settings' ) ); ?>"><?php _e( 'View Your Custom Emails', WCEmails_TEXT_DOMAIN ); ?></a></li>
				</ul>
				<?php $this->wcemails_render_sections( $type ); ?>
			</div>
			<?php

		}

		function wcemails_render_sections( $type ) {

			if ( 'add-email' == $type ) {
				$this->wcemails_render_add_email_section();
			} else if ( 'view-email' == $type ) {
				$this->wcemails_render_view_email_section();
			} else {
				$this->wcemails_render_add_email_section();
			}

		}

		function wcemails_render_add_email_section() {

			$wcemails_detail = array();
			if ( isset( $_REQUEST['wcemails_edit'] ) ) {
				$wcemails_email_details = get_option( 'wcemails_email_details', array() );
				if ( ! empty( $wcemails_email_details ) ) {
					foreach ( $wcemails_email_details as $key => $details ) {
						if ( $_REQUEST['wcemails_edit'] == $key ) {
							$wcemails_detail = $details;
						}
					}
				}
			}

			$wc_statuses = wc_get_order_statuses();
			if ( ! empty( $wc_statuses ) ) {
				foreach ( $wc_statuses as $k => $status ) {
					$key = ( 'wc-' === substr( $k, 0, 3 ) ) ? substr( $k, 3 ) : $k;
					$wc_statuses[ $key ] = $status;
					unset( $wc_statuses[ $k ] );
				}
			}

			wp_enqueue_script( 'jquery-cloneya' );
			wp_enqueue_script( 'wcemails-custom-scripts' );

			?>
			<form method="post" action="">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<?php _e( 'Title', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Title of the Email. )' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_title" id="wcemails_title" type="text" required value="<?php echo isset( $wcemails_detail['title'] ) ? $wcemails_detail['title'] : ''; ?>" placeholder="<?php _e( 'Title', WCEmails_TEXT_DOMAIN ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Description', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Email Description to display at Woocommerce Email Setting. )' ); ?>
								</span>
						</th>
						<td>
							<textarea name="wcemails_description" id="wcemails_description" required placeholder="<?php _e( 'Description', WCEmails_TEXT_DOMAIN ); ?>" ><?php echo isset( $wcemails_detail['description'] ) ? $wcemails_detail['description'] : ''; ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Subject', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Email Subject <br/>[Try this placeholders : <i>{site_title}, {order_number}, {order_date}</i>] )' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_subject" id="wcemails_subject" type="text" required value="<?php echo isset( $wcemails_detail['subject'] ) ? $wcemails_detail['subject'] : ''; ?>" placeholder="<?php _e( 'Subject', WCEmails_TEXT_DOMAIN ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Recipients', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( 'Recipients email addresses separated with comma' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_recipients" id="wcemails_recipients" type="text" required value="<?php echo isset( $wcemails_detail['recipients'] ) ? $wcemails_detail['recipients'] : ''; ?>" placeholder="<?php _e( 'Recipients', WCEmails_TEXT_DOMAIN ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Heading', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Email Default Heading )' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_heading" id="wcemails_heading" type="text" required value="<?php echo isset( $wcemails_detail['heading'] ) ? $wcemails_detail['heading'] : ''; ?>" placeholder="<?php _e( 'Heading', WCEmails_TEXT_DOMAIN ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Choose Order Status', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Choose order statuses when changed this email should fire. )' ); ?>
								</span>
						</th>
						<td>
							<div class="status-clone-wrapper">
								<?php
								if ( ! empty( $wc_statuses ) ) {
									if ( ! empty( $wcemails_detail['from_status'] ) ) {
										foreach ( $wcemails_detail['from_status'] as $key => $status ) {
											?>
											<div class="toclone">
												<select name="wcemails_from_status[]" required>
													<option value=""><?php _e( 'Select From Status', WCEmails_TEXT_DOMAIN ); ?></option>
													<?php
													$status_options = '';
													foreach ( $wc_statuses as $k => $wc_status ) {
														$selected = '';
														if ( $k == $status ) {
															$selected = 'selected="selected"';
														}
														$status_options .= '<option value="' . $k . '" ' . $selected . '>' . $wc_status . '</option>';
													}
													echo $status_options;
													?>
												</select>
												<select name="wcemails_to_status[]" required>
													<option value=""><?php _e( 'Select To Status', WCEmails_TEXT_DOMAIN ); ?></option>
													<?php
													$status_options = '';
													foreach ( $wc_statuses as $k => $wc_status ) {
														$selected = '';
														if ( $k == $wcemails_detail['to_status'][ $key ] ) {
															$selected = 'selected="selected"';
														}
														$status_options .= '<option value="' . $k . '" ' . $selected . '>' . $wc_status . '</option>';
													}
													echo $status_options;
													?>
												</select>
												<a href="#" class="clone" title="<?php _e( 'Add Another', WCEmails_TEXT_DOMAIN ) ?>">+</a>
												<a href="#" class="delete" title="<?php _e( 'Delete', WCEmails_TEXT_DOMAIN ) ?>">-</a>
											</div>
											<?php
										}
									} else {
										$status_options = '';
										foreach ( $wc_statuses as $k => $wc_status ) {
											$status_options .= '<option value="' . $k . '">' . $wc_status . '</option>';
										}
										?>
										<div class="toclone">
											<select name="wcemails_from_status[]" required>
												<option value=""><?php _e( 'Select From Status', WCEmails_TEXT_DOMAIN ); ?></option>
												<?php echo $status_options; ?>
											</select>
											<select name="wcemails_to_status[]" required>
												<option value=""><?php _e( 'Select To Status', WCEmails_TEXT_DOMAIN ); ?></option>
												<?php echo $status_options; ?>
											</select>
											<a href="#" class="clone" title="<?php _e( 'Add Another', WCEmails_TEXT_DOMAIN ) ?>">+</a>
											<a href="#" class="delete" title="<?php _e( 'Delete', WCEmails_TEXT_DOMAIN ) ?>">-</a>
										</div>
										<?php
									}
								}
								?>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Template', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Use these tags to to print them in email. - <br/>
										<i>{order_date},
										{order_number},
										{woocommerce_email_order_meta},
										{order_billing_name},
										{email_order_items_table},
										{email_order_total_footer},
										{order_billing_email},
										{order_billing_phone},
										{email_addresses}</i> )' ); ?>
								</span>
						</th>
						<td>
							<?php
							$settings = array(
								'textarea_name' => 'wcemails_template',
							);
							wp_editor( html_entity_decode( isset( $wcemails_detail['template'] ) ? $wcemails_detail['template'] : '' ), 'ezway_custom_email_new_order', $settings );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Put It In Order Actions?', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Order Edit screen at backend will have this email as order action. )' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_order_action" id="wcemails_order_action" type="checkbox" <?php echo ( isset( $wcemails_detail['order_action'] ) && 'on' == $wcemails_detail['order_action'] ) ? 'checked="checked"' : ''; ?> />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Enable?', WCEmails_TEXT_DOMAIN ); ?>
							<span style="display: block; font-size: 12px; font-weight: 300;">
							<?php _e( '( Enable this email here. )' ); ?>
								</span>
						</th>
						<td>
							<input name="wcemails_enable" id="wcemails_enable" type="checkbox" <?php echo ( isset( $wcemails_detail['enable'] ) && 'on' == $wcemails_detail['enable'] ) ? 'checked="checked"' : ''; ?> />
						</td>
					</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="wcemails_submit" id="wcemails_submit" class="button button-primary" value="Save Changes">
				</p>
				<?php
				if ( isset( $_REQUEST['wcemails_edit'] ) ) {
					?>
					<input type="hidden" name="wcemails_update" id="wcemails_update" value="<?php echo $_REQUEST['wcemails_edit']; ?>" />
					<?php
				}
				?>
			</form>
			<?php

		}

		function wcemails_render_view_email_section() {
			include_once( 'class-wcemails-list.php' );
			$wcemails_list = new WCEmails_List();
			$wcemails_list->prepare_items();
			$wcemails_list->display();
		}

		function wcemails_email_actions_details() {

			if ( isset( $_POST['wcemails_submit'] ) ) {

				$title        = filter_input( INPUT_POST, 'wcemails_title', FILTER_SANITIZE_STRING );
				$description  = filter_input( INPUT_POST, 'wcemails_description', FILTER_SANITIZE_STRING );
				$subject      = filter_input( INPUT_POST, 'wcemails_subject', FILTER_SANITIZE_STRING );
				$recipients   = filter_input( INPUT_POST, 'wcemails_recipients', FILTER_SANITIZE_STRING );
				$heading      = filter_input( INPUT_POST, 'wcemails_heading', FILTER_SANITIZE_STRING );
				$from_status  = isset( $_POST['wcemails_from_status'] ) ? $_POST['wcemails_from_status'] : '';
				$to_status    = isset( $_POST['wcemails_to_status'] ) ? $_POST['wcemails_to_status'] : '';
				$template     = isset( $_POST['wcemails_template'] ) ? $_POST['wcemails_template'] : '';
				$order_action = filter_input( INPUT_POST, 'wcemails_order_action', FILTER_SANITIZE_STRING );
				$order_action = empty( $order_action ) ? 'off' : $order_action;
				$enable       = filter_input( INPUT_POST, 'wcemails_enable', FILTER_SANITIZE_STRING );
				$enable       = empty( $enable ) ? 'off' : $enable;

				$wcemails_email_details = get_option( 'wcemails_email_details', array() );

				$data = array(
					'title'        => $title,
					'description'  => $description,
					'subject'      => $subject,
					'recipients'   => $recipients,
					'heading'      => $heading,
					'from_status'  => $from_status,
					'to_status'    => $to_status,
					'template'     => $template,
					'order_action' => $order_action,
					'enable'       => $enable,
				);

				if ( isset( $_POST['wcemails_update'] ) ) {
					if ( ! empty( $wcemails_email_details ) ) {
						foreach ( $wcemails_email_details as $key => $details ) {
							if ( $key == $_POST['wcemails_update'] ) {
								$data['id'] = $details['id'];
								$wcemails_email_details[ $key ] = $data;
							}
						}
					}
				} else {
					$id = uniqid( 'wcemails' );
					$data['id'] = $id;
					array_push( $wcemails_email_details, $data );
				}

				update_option( 'wcemails_email_details', $wcemails_email_details );

				add_settings_error( 'wcemails-settings', 'error_code', $title.' is saved and if you have enabled it then you can see it in Woocommerce Email Settings Now', 'success' );

			} else if ( isset( $_REQUEST['wcemails_delete'] ) ) {

				$wcemails_email_details = get_option( 'wcemails_email_details', array() );

				$delete_key = $_REQUEST['wcemails_delete'];

				if ( ! empty( $wcemails_email_details ) ) {
					foreach ( $wcemails_email_details as $key => $details ) {
						if ( $key == $delete_key ) {
							unset( $wcemails_email_details[ $key ] );
						}
					}
				}

				update_option( 'wcemails_email_details', $wcemails_email_details );

				add_settings_error( 'wcemails-settings', 'error_code', 'Email settings deleted!', 'success' );

			}

		}

		function wcemails_custom_woocommerce_emails( $email_classes ) {

			include_once( 'class-wcemails-instance.php' );

			$wcemails_email_details = get_option( 'wcemails_email_details', array() );

			if ( ! empty( $wcemails_email_details ) ) {

				foreach ( $wcemails_email_details as $key => $details ) {

					$enable = $details['enable'];

					if ( 'on' == $enable ) {

						$title       = isset( $details['title'] ) ? $details['title'] : '';
						$id          = isset( $details['id'] ) ? $details['id'] : '';
						$description = isset( $details['description'] ) ? $details['description'] : '';
						$subject     = isset( $details['subject'] ) ? $details['subject'] : '';
						$recipients  = isset( $details['recipients'] ) ? $details['recipients'] : '';
						$heading     = isset( $details['heading'] ) ? $details['heading'] : '';
						$from_status = isset( $details['from_status'] ) ? $details['from_status'] : array();
						$to_status   = isset( $details['to_status'] ) ? $details['to_status'] : array();
						$template    = html_entity_decode( isset( $details['template'] ) ? $details['template'] : '' );

						$wcemails_instance = new WCEmails_Instance( $id, $title, $description, $subject, $recipients, $heading, $from_status, $to_status, $template );

						$email_classes[ 'WCustom_Emails_'.$id.'_Email' ] = $wcemails_instance;

					}
				}
			}

			return $email_classes;

		}

		function wcemails_change_action_emails( $emails ) {

			$wcemails_email_details = get_option( 'wcemails_email_details', array() );

			if ( ! empty( $wcemails_email_details ) ) {

				foreach ( $wcemails_email_details as $key => $details ) {

					$enable = $details['enable'];
					$order_action = $details['order_action'];

					if ( 'on' == $enable && 'on' == $order_action ) {

						$id             = $details['id'];

						array_push( $emails, $id );

					}
				}
			}

			return $emails;

		}

	}

}

/**
 * Returns the main instance of WCEmails_Admin to prevent the need to use globals.
 *
 * @since  0.1
 * @return WCEmails_Admin
 */
function woo_custom_emails_admin() {
	return WCEmails_Admin::instance();
}
woo_custom_emails_admin();
