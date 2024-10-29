<?php
/**
 * Add Multiple User
 *
 * @package    AddMultipleUser
 * @subpackage Add Multiple User Main function
/*  Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$addmultipleuser = new AddMultipleUser();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class AddMultipleUser {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'show_password_fields', array( $this, 'pay_form' ) );
		add_action( 'profile_update', array( $this, 'update_user_profile' ), 10, 2 );
		add_filter( 'slmclient_licensed', array( $this, 'licensekey_charge' ), 10, 2 );
		add_action( 'wp_dashboard_setup', array( $this, 'pay_form_dashboard_widgets' ) );

		add_action( 'user_register', array( $this, 'action_add_user' ), 10, 1 );
		add_filter( 'wp_new_user_notification_email', '__return_false' );
		add_filter( 'wp_new_user_notification_email_admin', array( $this, 'regist_user_notify_mail_admin' ), 10, 3 );
		add_action( 'delete_user', array( $this, 'action_delete_user' ), 10, 1 );

		/* for woocommerce*/
		add_action( 'woocommerce_created_customer', array( $this, 'regist_user_notify_mail_woo' ), 10, 3 );

		/* original action hook */
		add_action( 'amu_paid_add_user', array( $this, 'paid' ), 10, 1 );
		add_action( 'amu_paid_add_user_admin', array( $this, 'paid_admin' ), 10, 2 );

	}

	/** ==================================================
	 * Administrator Add User Hook
	 *
	 * @param int $userid The user ID.
	 * @since 1.00
	 */
	public function action_add_user( $userid ) {

		$parent_user_pswd = wp_generate_password( 12, false, false );
		global $wpdb;
		$data         = array( 'user_pass' => wp_hash_password( $parent_user_pswd ) );
		$format       = array( '%s' );
		$where        = array( 'ID' => $userid );
		$where_format = array( '%d' );
		$wpdb->update( $wpdb->users, $data, $where, $format, $where_format );

		$amu_settings = get_option( 'addmultipleuser' );
		$user         = get_userdata( $userid );
		$useremail    = $user->user_email;
		$role         = implode( ', ', $user->roles );
		if ( $role === $amu_settings['def_role'] ) {
			$amu_meta = get_user_meta( $userid, 'amu_meta', true );
			if ( empty( $amu_meta ) ) {
				$prefix1  = substr( $useremail, 0, 3 );
				if ( function_exists( 'wp_date' ) ) {
					$prefix2  = wp_date( 'ymdHi' );
				} else {
					$prefix2  = date_i18n( 'ymdHi' );
				}
				$prefix   = $prefix1 . $prefix2;
				if ( $amu_settings['paid_number'] === $amu_settings['def_number'] ) {
					$add = 'paid';
				} else {
					$add = 'def';
				}
				$amu_meta = array(
					'add'    => $add,
					'number' => $amu_settings['def_number'],
					'prefix' => $prefix,
				);
				update_user_meta( $userid, 'amu_meta', $amu_meta );

				$this->auto_insert_users( $user, $parent_user_pswd, 1, $amu_meta['number'], $prefix, $amu_settings['add_role'] );

			}
		}

	}

	/** ==================================================
	 * Notice mail when newly registering users for woocommerce
	 *
	 * @param int   $customer_id  customer_id.
	 * @param array $new_customer_data  new_customer_data.
	 * @param bool  $password_generated  password_generated.
	 * @since 2.00
	 */
	public function regist_user_notify_mail_woo( $customer_id, $new_customer_data, $password_generated ) {

		/* for admin */
		wp_new_user_notification( $customer_id, null, 'admin' );

	}

	/** ==================================================
	 * Notice mail when newly registering users for admin
	 *
	 * @param array  $wp_mail  wp_mail.
	 * @param object $user  user.
	 * @param string $blogname  blogname.
	 * @since 1.00
	 */
	public function regist_user_notify_mail_admin( $wp_mail, $user, $blogname ) {

		$amu_meta = get_user_meta( $user->ID, 'amu_meta', true );
		if ( ! empty( $amu_meta ) && array_key_exists( 'number', $amu_meta ) ) {
			/* translators: %1$s: blogname %2$s: user's count */
			$subject            = sprintf( __( '[%1$s] New User Registration [%2$s]users', 'add-multiple-user' ), $blogname, $amu_meta['number'] );
			$wp_mail['subject'] = $subject;

			/* translators: %s: blogname */
			$message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			/* translators: %s: username */
			$message .= sprintf( __( 'Username' ) . '&' . __( 'Email' ) . ': %s', $user->user_login ) . "\r\n\r\n";
			$message .= $this->member_add_users( $amu_meta );

			$wp_mail['message'] = $message;

		}

		return $wp_mail;

	}

	/** ==================================================
	 * Members add users
	 *
	 * @param array $amu_meta  amu_meta.
	 * @since 1.00
	 */
	private function member_add_users( $amu_meta ) {

		$amu_settings = get_option( 'addmultipleuser' );
		if ( 'def' === $amu_meta['add'] ) {
			$min = 1;
			$max = $amu_meta['number'];
		} else { /* paid */
			$min = $amu_settings['def_number'] + 1;
			$max = $amu_meta['number'];
		}
		$message = __( 'Multiple users added as follows.', 'add-multiple-user' ) . "\r\n\r\n";
		for ( $i = $min; $i <= $max; $i++ ) {
			$message .= sprintf( __( 'Username' ) . ': %s', $amu_meta['prefix'] . sprintf( '%02d', $i ) ) . "\n";
		}

		return $message;

	}

	/** ==================================================
	 * Create multiple user
	 *
	 * @param object $user  User info object.
	 * @param string $parent_user_pswd Parent user password.
	 * @param int    $number_min  number_min.
	 * @param int    $number_count  number_count.
	 * @param string $prefix  prefix.
	 * @param string $add_role  add_role.
	 * @since 1.00
	 */
	private function auto_insert_users( $user, $parent_user_pswd, $number_min, $number_count, $prefix, $add_role ) {

		/* translators: %1$s: blogname %2$s: user's count */
		$subject = sprintf( __( '[%1$s] login Username [%2$s]users', 'add-multiple-user' ), get_bloginfo( 'name' ), $number_count );
		$subject = apply_filters( 'amu_regist_mail_subject', $subject );
		$message = null;
		if ( ! empty( $parent_user_pswd ) ) {
			$thanks   = __( 'Thank you for registering. The login information is as follows.', 'add-multiple-user' ) . "\r\n\r\n";
			$message  = $thanks;
			$message .= sprintf( __( 'Username' ) . '&' . __( 'Email' ) . ': %s', $user->user_login ) . "\n";
			$message .= sprintf( __( 'Password' ) . ': %s', $parent_user_pswd ) . "\n";
			$message .= __( 'Login Address (URL)' ) . ':' . wp_login_url() . "\r\n\r\n";
		}
		$message .= __( 'Multiple users added as follows.', 'add-multiple-user' ) . "\r\n\r\n";

		$user_ids = array();
		for ( $i = $number_min; $i <= $number_count; $i++ ) {
			$unm = $prefix . sprintf( '%02d', $i );
			$userdata = array(
				'user_login' => $unm,
				'user_pass'  => null,
				'role'       => $add_role,
			);
			$user_ids[] = wp_insert_user( $userdata );
		}

		$amu_meta = get_user_meta( $user->ID, 'amu_meta', true );
		global $wpdb;
		foreach ( $user_ids as $userid ) {
			/* Email address and Password write */
			$child_user_pswd = wp_generate_password( 12, false, false );
			$data = array(
				'user_email' => $user->user_email,
				'user_pass' => wp_hash_password( $child_user_pswd ),
			);
			$format = array(
				'%s',
				'%s',
			);
			$where = array(
				'ID' => $userid,
				'ID' => $userid,
			);
			$where_format = array(
				'%d',
				'%d',
			);
			$wpdb->update( $wpdb->users, $data, $where, $format, $where_format );
			/* Mail message */
			$childuser = get_userdata( $userid );
			$message .= sprintf( __( 'Username' ) . ': %s', $childuser->user_login ) . "\n";
			$message .= sprintf( __( 'Password' ) . ': %s', $child_user_pswd ) . "\r\n\r\n";
			$message = apply_filters( 'amu_regist_mail_message', $message, $thanks, $childuser->user_login, $child_user_pswd );
		}

		/* send email */
		wp_mail( $user->user_email, $subject, $message );

	}

	/** ==================================================
	 * Delete User Hook
	 *
	 * @param int $userid The user ID.
	 * @since 1.00
	 */
	public function action_delete_user( $userid ) {

		$amu_settings = get_option( 'addmultipleuser' );
		$user         = get_userdata( $userid );
		$useremail    = $user->user_email;
		$role         = implode( ', ', $user->roles );
		$amu_meta     = get_user_meta( $userid, 'amu_meta', true );
		if ( ! empty( $amu_meta ) ) {
			if ( array_key_exists( 'prefix', $amu_meta ) && array_key_exists( 'number', $amu_meta ) ) {
				/* translators: %1s: blogname */
				$subject = sprintf( __( '[%s] Delete Acount', 'add-multiple-user' ), get_option( 'blogname' ) );
				$message = __( 'The created account has been deleted. Thank you for using.', 'add-multiple-user' ) . "\r\n\r\n";
				/* translators: %s: useremail  */
				$message_admin = sprintf( __( 'The created account[%s] has been deleted.', 'add-multiple-user' ), $useremail ) . "\r\n\r\n";
				wp_mail( $useremail, $subject, $message );
				@wp_mail( get_option( 'admin_email' ), $subject, $message_admin );

				/* Delete users */
				$user_prefix = $amu_meta['prefix'];
				for ( $i = 1; $i <= $amu_meta['number']; $i++ ) {
					$unm = $user_prefix . sprintf( '%02d', $i );
					if ( username_exists( $unm ) ) {
						wp_delete_user( username_exists( $unm ) );
					}
				}

				/* Deactive License Key for Software License Manager Client */
				if ( class_exists( 'SlmClient' ) ) {
					if ( get_option( 'license_key_add-multiple-user' ) ) {
						do_action( 'deactive_slm_key', $arg = array() );
					}
				}
			}
		}

	}

	/** ==================================================
	 * Pay form
	 *
	 * @param bool $bool bool.
	 * @since 1.00
	 */
	public function pay_form( $bool ) {

		$screen = get_current_screen();
		global $profileuser;
		if ( 'profile' === $screen->id || 'user-edit' === $screen->id ) {
			if ( current_user_can( 'administrator' ) ) {
				if ( isset( $_GET['user_id'] ) && ! empty( $_GET['user_id'] ) ) {
					$userid   = intval( $_GET['user_id'] );
					$amu_meta = get_user_meta( $userid, 'amu_meta', true );
					if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
						?>
						<tr>
						<th><label for="description"><?php echo 'Add Multiple User ' . esc_html__( 'Status' ); ?></label></th>
						<td>
						<?php
						if ( 'paid' === $amu_meta['add'] ) {
							?>
							<p class="description"><?php esc_html_e( 'Registered', 'add-multiple-user' ); ?></p>
							<?php
						} else {
							?>
							<p class="description"><?php esc_html_e( 'Temporary registered', 'add-multiple-user' ); ?></p>
							<input type="checkbox" name="amu_paid_admin" value="1" />
							<?php
							echo esc_html__( 'Change to registered user.', 'add-multiple-user' );
						}
						?>
						</td>
						</tr>
						<?php
					}
				}
			} else {
				$user      = wp_get_current_user();
				$userid    = $user->ID;
				$useremail = $user->user_email;
				$amu_meta  = get_user_meta( $userid, 'amu_meta', true );
				if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
					?>
					<tr>
					<th><label for="description"><?php echo 'Add Multiple User ' . esc_html__( 'Status' ); ?></label></th>
					<td>
					<?php
					if ( 'paid' === $amu_meta['add'] ) {
						?>
						<p class="description"><?php esc_html_e( 'Registered', 'add-multiple-user' ); ?></p>
						<?php
					} else {
						?>
						<p class="description"><?php esc_html_e( 'Temporary registered', 'add-multiple-user' ); ?></p>
						<?php
					}
					if ( class_exists( 'SlmClient' ) ) {
						echo do_shortcode( '[slmcl]' );
					}
					?>
					</td>
					</tr>
					<?php
				}
			}
		}

		return $bool;

	}

	/** ==================================================
	 * Pay form for Dashboard
	 *
	 * @since 1.00
	 */
	public function pay_form_dashboard_widgets() {

		$user     = wp_get_current_user();
		$userid   = $user->ID;
		$amu_meta = get_user_meta( $userid, 'amu_meta', true );
		if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
			global $wp_meta_boxes;
			wp_add_dashboard_widget( 'custom_help_widget', 'Add Multiple User ' . __( 'Status' ), array( $this, 'dashboard_text' ) );
		}

	}

	/** ==================================================
	 * Dashboard text
	 *
	 * @since 1.00
	 */
	public function dashboard_text() {

		$screen = get_current_screen();
		if ( 'dashboard' === $screen->id ) {
			if ( current_user_can( 'administrator' ) ) {
				if ( isset( $_GET['user_id'] ) && ! empty( $_GET['user_id'] ) ) {
					$userid   = intval( $_GET['user_id'] );
					$amu_meta = get_user_meta( $userid, 'amu_meta', true );
					if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
						if ( 'paid' === $amu_meta['add'] ) {
							?>
							<h3><strong><?php esc_html_e( 'Registered', 'add-multiple-user' ); ?></strong></h3>
							<?php
						} else {
							?>
							<h3><strong><?php esc_html_e( 'Temporary registered', 'add-multiple-user' ); ?></strong></h3>
							<?php
						}
					}
				}
			} else {
				$user      = wp_get_current_user();
				$userid    = $user->ID;
				$amu_meta  = get_user_meta( $userid, 'amu_meta', true );
				$useremail = $user->user_email;
				if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
					if ( 'paid' === $amu_meta['add'] ) {
						?>
						<h3><strong><?php esc_html_e( 'Registered', 'add-multiple-user' ); ?></strong></h3>
						<?php
					} else {
						?>
						<h3><strong><?php esc_html_e( 'Temporary registered', 'add-multiple-user' ); ?></strong></h3>
						<?php
					}
					if ( class_exists( 'SlmClient' ) ) {
						echo do_shortcode( '[slmcl]' );
					}
				}
			}
		}

	}

	/** ==================================================
	 * License Key Charge
	 *
	 * @param string $license_key  license_key.
	 * @param string $item_reference  item_reference.
	 * @since 1.00
	 */
	public function licensekey_charge( $license_key, $item_reference ) {
		do_action( 'amu_paid_add_user', $item_reference );
	}

	/** ==================================================
	 * Paid
	 *
	 * @param string $payname  payname.
	 * @since 1.00
	 */
	public function paid( $payname ) {

		if ( is_admin() && 'add-multiple-user' === $payname ) {
			$amu_settings = get_option( 'addmultipleuser' );
			$user         = wp_get_current_user();
			$userid       = $user->ID;
			$useremail    = $user->user_email;
			$amu_meta     = get_user_meta( $userid, 'amu_meta', true );
			if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
				if ( 'def' === $amu_meta['add'] ) {
					$def_number         = $amu_meta['number'];
					$amu_meta['add']    = 'paid';
					$amu_meta['number'] = $amu_settings['paid_number'];
					update_user_meta( $userid, 'amu_meta', $amu_meta );

					/* translators: %1$s: blogname %2$s: number for user */
					$subject = sprintf( __( '[%1$s] login Username [%2$s]users', 'add-multiple-user' ), get_option( 'blogname' ), $amu_meta['number'] );
					$message = $this->auto_insert_users( $user, null, $def_number + 1, $amu_meta['number'], $amu_meta['prefix'], $amu_settings['add_role'] );
					/* translators: %1$s: blogname %2$s: number for admin */
					$subject_admin = sprintf( __( '[%1$s] New User Registration [%2$s]users', 'add-multiple-user' ), get_option( 'blogname' ), $amu_meta['number'] );
					/* translators: %s: blogname */
					$message_admin = sprintf( __( 'New user registration on your site %s:' ), get_option( 'blogname' ) ) . "\r\n\r\n";
					/* translators: %s: username */
					$message_admin .= sprintf( __( 'Username' ) . '&' . __( 'Email' ) . ': %s', $user->user_login ) . "\r\n\r\n";
					$min            = $def_number + 1;
					$max            = $amu_meta['number'];
					$message_admin .= __( 'Multiple users added as follows.', 'add-multiple-user' ) . "\r\n\r\n";
					for ( $i = $min; $i <= $max; $i++ ) {
						$message_admin .= sprintf( __( 'Username' ) . ': %s', $amu_meta['prefix'] . sprintf( '%02d', $i ) ) . "\n";
					}

					wp_mail( $useremail, $subject, $message );
					@wp_mail( get_option( 'admin_email' ), $subject_admin, $message_admin );

				}
			}
		}

	}

	/** ==================================================
	 * Paid
	 *
	 * @param string $payname  payname.
	 * @param int    $userid  userid.
	 * @since 1.00
	 */
	public function paid_admin( $payname, $userid ) {

		if ( is_admin() && 'add-multiple-user' === $payname ) {
			$amu_settings = get_option( 'addmultipleuser' );
			$user         = get_userdata( $userid );
			$useremail    = $user->user_email;
			$amu_meta     = get_user_meta( $userid, 'amu_meta', true );
			if ( ! empty( $amu_meta ) && array_key_exists( 'add', $amu_meta ) ) {
				if ( 'def' === $amu_meta['add'] ) {
					$def_number         = $amu_meta['number'];
					$amu_meta['add']    = 'paid';
					$amu_meta['number'] = $amu_settings['paid_number'];
					update_user_meta( $userid, 'amu_meta', $amu_meta );

					/* translators: %1$s: blogname %2$s: number for user */
					$subject = sprintf( __( '[%1$s] login Username [%2$s]users', 'add-multiple-user' ), get_option( 'blogname' ), $amu_meta['number'] );
					$message = $this->auto_insert_users( $user, null, $def_number + 1, $amu_meta['number'], $amu_meta['prefix'], $amu_settings['add_role'] );
					/* translators: %1$s: blogname %2$s: number for admin */
					$subject_admin = sprintf( __( '[%1$s] New User Registration [%2$s]users', 'add-multiple-user' ), get_option( 'blogname' ), $amu_meta['number'] );
					/* translators: %s: blogname */
					$message_admin = sprintf( __( 'New user registration on your site %s:' ), get_option( 'blogname' ) ) . "\r\n\r\n";
					/* translators: %s: username */
					$message_admin .= sprintf( __( 'Username' ) . '&' . __( 'Email' ) . ': %s', $user->user_login ) . "\r\n\r\n";
					$min            = $def_number + 1;
					$max            = $amu_meta['number'];
					$message_admin .= __( 'Multiple users added as follows.', 'add-multiple-user' ) . "\r\n\r\n";
					for ( $i = $min; $i <= $max; $i++ ) {
						$message_admin .= sprintf( __( 'Username' ) . ': %s', $amu_meta['prefix'] . sprintf( '%02d', $i ) ) . "\n";
					}

					wp_mail( $useremail, $subject, $message );
					@wp_mail( get_option( 'admin_email' ), $subject_admin, $message_admin );

				}
			}
		}

	}

	/** ==================================================
	 * Update user profile
	 *
	 * @param int    $userid  userid.
	 * @param object $old_user_data  old_user_data.
	 * @since 1.00
	 */
	public function update_user_profile( $userid, $old_user_data ) {

		if ( isset( $_POST['amu_paid_admin'] ) && ! empty( $_POST['amu_paid_admin'] ) ) {
			if ( check_admin_referer( 'update-user_' . $userid ) ) {
				$amu_paid_admin = intval( $_POST['amu_paid_admin'] );
				if ( $old_user_data->amu_paid_admin !== $amu_paid_admin ) {
					do_action( 'amu_paid_add_user_admin', 'add-multiple-user', $userid );
				}
			}
		}

	}

}


