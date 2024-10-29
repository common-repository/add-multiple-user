<?php
/**
 * Add Multiple User
 *
 * @package    Add Multiple User
 * @subpackage AddMultipleUserAdmin Management screen
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$addmultipleuseradmin = new AddMultipleUserAdmin();

/** ==================================================
 * Management screen
 */
class AddMultipleUserAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'notices' ) );

	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'add-multiple-user/addmultipleuser.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=addmultipleuser' ) . '">' . __( 'Settings' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_options_page( 'Add Multiple User Options', 'Add Multiple User', 'manage_options', 'addmultipleuser', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * For only plugin admin page
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'settings_page_addmultipleuser' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$scriptname   = admin_url( 'options-general.php?page=addmultipleuser' );
		$amu_settings = get_option( 'addmultipleuser' );

		?>

		<div class="wrap">
		<h2>Add Multiple User</h2>

			<details>
			<summary><strong><?php esc_html_e( 'Various links of this plugin', 'add-multiple-user' ); ?></strong></summary>
			<?php $this->credit(); ?>
			</details>

			<details style="margin-bottom: 5px;" open>
			<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php echo 'WordPress ' . esc_html__( 'Settings' ); ?></strong></summary>

				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'amu_set', 'addmultipleuser_set' ); ?>

				<div style="margin: 5px; padding: 5px;">
					<h3></h3>
					<div style="display: block;padding:5px 5px">
					<?php esc_html_e( 'Membership' ); ?> : 
					<input name="users_can_register" type="checkbox" value="1" <?php checked( '1', get_option( 'users_can_register' ) ); ?> />
					<?php esc_html_e( 'Anyone can register' ); ?>
				</div>
				<div style="display: block;padding:5px 5px">
					<?php esc_html_e( 'New User Default Role' ); ?> : 
					<select name="default_role">
					<?php wp_dropdown_roles( get_option( 'default_role' ) ); ?>
					</select>
					</div>
				</div>
			</details>

			<details style="margin-bottom: 5px;" open>
			<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'User roles and additional number', 'add-multiple-user' ); ?></strong></summary>

				<div style="margin: 5px; padding: 5px;">
					<table border=1 cellspacing="0" cellpadding="5" bordercolor="#000000" style="border-collapse: collapse">
					<tr>
					<td rowspan="2" align="center"><?php esc_html_e( 'User' ); ?></td>
					<td rowspan="2" align="center"><?php esc_html_e( 'Role' ); ?></td>
					<td colspan="2" align="center"><?php esc_html_e( 'Additional number', 'add-multiple-user' ); ?></td>
					</tr>
					<tr>
					<td align="center"><?php esc_html_e( 'Temporary registered', 'add-multiple-user' ); ?></td>
					<td align="center"><?php esc_html_e( 'Registered', 'add-multiple-user' ); ?></td>
					</tr>
					<tr>
					<td align="right"><?php esc_html_e( 'Parent user', 'add-multiple-user' ); ?></td>
					<td align="center">
					<select name="def_role">
					<?php wp_dropdown_roles( $amu_settings['def_role'] ); ?>
					</select>
					</td>
					<td colspan="2"></td>
					</tr>
					<tr>
					<td align="right"><?php esc_html_e( 'Child users', 'add-multiple-user' ); ?></td>
					<td align="center">
					<select name="add_role">
					<?php wp_dropdown_roles( $amu_settings['add_role'] ); ?>
					</select>
					</td>
					<td align="center">
					<input type="number" name="def_number" min="1" max="98" style="width: 60px;" value="<?php echo intval( $amu_settings['def_number'] ); ?>"> <?php esc_html_e( '1 to 98', 'add-multiple-user' ); ?>
					</td>
					<td align="center">
					<input type="number" name="paid_number" min="2" max="99" style="width: 60px;" value="<?php echo intval( $amu_settings['paid_number'] ); ?>"> <?php esc_html_e( '2 to 99', 'add-multiple-user' ); ?>
					</td>
					</tr>
					</table>
					<p class="description">
					<?php esc_html_e( 'Note : In order to add Child users, the "New User Default Role" in WordPress Settings must match the "Role" of the Parent user.', 'add-multiple-user' ); ?>
					</p>
				</div>

				<?php submit_button( __( 'Save Changes' ), 'large', 'Manageset', false ); ?>

				</form>
			</details>

			<details style="margin-bottom: 5px;">
			<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Hook', 'add-multiple-user' ); ?></strong></summary>
				<div style="margin: 5px; padding: 5px;">
					<h3><?php esc_html_e( 'The following action hook are provided.', 'add-multiple-user' ); ?></h3>
					<div style="margin: 5px; padding: 5px;">
						<h4>amu_paid_add_user</h4>
						<div><?php esc_html_e( "Process of user's payment and send email to user.", 'add-multiple-user' ); ?></div>
						<h4>amu_paid_add_user_admin</h4>
						<div><?php esc_html_e( "Process of user's payment and send email to admin.", 'add-multiple-user' ); ?></div>
						<p>
						<div><strong><?php esc_html_e( 'Sample code', 'add-multiple-user' ); ?></strong></div>
<textarea rows="6" cols="80" readonly>
/* Payname : add-multiple-user */
do_action( 'amu_paid_add_user', 'add-multiple-user' );

/* Payname : add-multiple-user , User ID : $userid */
do_action( 'amu_paid_add_user_admin', 'add-multiple-user', $userid );
</textarea>
					</div>
				</div>
				<div style="margin: 5px; padding: 5px;">
					<h3><?php esc_html_e( 'The following filter hook are provided.', 'add-multiple-user' ); ?></h3>
					<div style="margin: 5px; padding: 5px;">
						<h4>amu_regist_mail_message</h4>
						<h4>amu_regist_mail_subject</h4>
						<p>
						<div><strong><?php esc_html_e( 'Sample code', 'add-multiple-user' ); ?></strong></div>
<textarea rows="26" cols="80" readonly>
/** ======================================
 * Add Mulitiple User mail message
 *
 * @param string $message  message.
 * @param string $thanks  thanks.
 * @param string $unm  User name.
 * @param string $upass  User Password.
 */
function regist_message( $message, $thanks, $unm, $upass ) {

	return $message . 'This is test !' . $unm;

}
add_filter( 'amu_regist_mail_message', 'regist_message', 10, 4 );

/** ======================================
 * Add Mulitiple User mail subject
 *
 * @param string $subject  subject.
 */
function regist_subject( $subject ) {

	return $subject . 'This is test !';

}
add_filter( 'amu_regist_mail_subject', 'regist_subject', 10, 1 );
</textarea>
					</div>
				</div>
			</details>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'add-multiple-user' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'add-multiple-user' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'add-multiple-user' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php

	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( isset( $_POST['Manageset'] ) && ! empty( $_POST['Manageset'] ) ) {
			if ( check_admin_referer( 'amu_set', 'addmultipleuser_set' ) ) {
				if ( isset( $_POST['users_can_register'] ) && ! empty( $_POST['users_can_register'] ) ) {
					update_option( 'users_can_register', true );
				} else {
					update_option( 'users_can_register', false );
				}
				if ( isset( $_POST['default_role'] ) && ! empty( $_POST['default_role'] ) ) {
					update_option( 'default_role', sanitize_text_field( wp_unslash( $_POST['default_role'] ) ) );
				}
				if ( isset( $_POST['def_number'] ) && ! empty( $_POST['def_number'] ) ) {
					$def_number = intval( $_POST['def_number'] );
				}
				if ( isset( $_POST['paid_number'] ) && ! empty( $_POST['paid_number'] ) ) {
					$paid_number = intval( $_POST['paid_number'] );
				}
				$amu_settings = get_option( 'addmultipleuser' );
				if ( $def_number > $paid_number ) {
					/* translators: %1$s: paid %2$s: default */
					echo '<div class="notice notice-error is-dismissible"><ul><li>' . sprintf( esc_html__( '%1$s must be a number greater than %2$s.', 'add-multiple-user' ), esc_html__( 'Paid additional number', 'add-multiple-user' ), esc_html__( 'Default additional number', 'add-multiple-user' ) ) . '</li></ul></div>';
					return;
				} else {
					$amu_settings['def_number']  = $def_number;
					$amu_settings['paid_number'] = $paid_number;
				}
				if ( isset( $_POST['def_role'] ) && ! empty( $_POST['def_role'] ) ) {
					$amu_settings['def_role'] = sanitize_text_field( wp_unslash( $_POST['def_role'] ) );
				}
				if ( isset( $_POST['add_role'] ) && ! empty( $_POST['add_role'] ) ) {
					$amu_settings['add_role'] = sanitize_text_field( wp_unslash( $_POST['add_role'] ) );
				}
				update_option( 'addmultipleuser', $amu_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Settings' ) . ' --> ' . esc_html__( 'Settings saved.' ) . '</li></ul></div>';
			}
		}

	}

	/** ==================================================
	 * Notices
	 *
	 * @since 1.00
	 */
	public function notices() {

		if ( $this->is_my_plugin_screen() ) {
			if ( is_multisite() ) {
				$umor_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=user-mail-only-register' );
				$slmc_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager-client' );
			} else {
				$umor_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=user-mail-only-register' );
				$slmc_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager-client' );
			}
			$umor_install_html = '<a href="' . $umor_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">User Mail Only Register</a>';
			$slmc_install_html = '<a href="' . $slmc_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">Software License Manager Client</a>';
			if ( ! class_exists( 'UserMailOnlyRegister' ) ) {
				/* translators: %1$s: User Mail Only Register */
				echo '<div class="notice notice-warning is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'If you wish to make the registration form mail only, Please use the %1$s.', 'add-multiple-user' ), $umor_install_html ) ) . '</li></ul></div>';
			}
			if ( ! class_exists( 'SlmClient' ) ) {
				/* translators: %1$s: Software License Manager Client */
				echo '<div class="notice notice-warning is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'If you want registered users to charge with %1$s, Please use the %2$s.', 'add-multiple-user' ), __( 'License Key', 'add-multiple-user' ), $slmc_install_html ) ) . '</li></ul></div>';
			}
		}

	}

}


