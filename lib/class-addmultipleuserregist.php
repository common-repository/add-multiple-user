<?php
/**
 * Add Multiple User
 *
 * @package    AddMultipleUser
 * @subpackage AddMultipleUserRegist registered in the database
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

$addmultipleuserregist = new AddMultipleUserRegist();

/** ==================================================
 * Registered in the database
 */
class AddMultipleUserRegist {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		register_activation_hook( plugin_dir_path( __DIR__ ) . 'addmultipleuser.php', array( $this, 'active' ) );
		register_deactivation_hook( plugin_dir_path( __DIR__ ) . 'addmultipleuser.php', array( $this, 'deactive' ) );
		add_action( 'init', array( $this, 'register_settings' ) );

	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_option( 'addmultipleuser' ) ) {
			$amu_tbl = array(
				'def_role'    => 'editor',
				'add_role'    => 'subscriber',
				'def_number'  => 5,
				'paid_number' => 22,
			);
			update_option( 'addmultipleuser', $amu_tbl );
		}

	}

	/** ==================================================
	 * Active
	 *
	 * @since 1.00
	 */
	public function active() {
		if ( get_option( 'addmultipleuser' ) ) {
			$amu_settings = get_option( 'addmultipleuser' );
			update_option( 'default_role', $amu_settings['def_role'] );
		}
	}

	/** ==================================================
	 * Deactive
	 *
	 * @since 1.00
	 */
	public function deactive() {
		update_option( 'default_role', 'subscriber' );
	}

}


