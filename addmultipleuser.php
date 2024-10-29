<?php
/**
 * Plugin Name: Add Multiple User
 * Plugin URI:  https://wordpress.org/plugins/add-multiple-user/
 * Description: Add multiple users in bulk.
 * Version:     2.00
 * Author:      Katsushi Kawamori
 * Author URI:  https://riverforest-wp.info/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: add-multiple-user
 *
 * @package Add Multiple User
 */

/*
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

if ( ! class_exists( 'AddMultipleUserRegist' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-addmultipleuserregist.php';
}
if ( ! class_exists( 'AddMultipleUserAdmin' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-addmultipleuseradmin.php';
}
if ( ! class_exists( 'AddMultipleUser' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-addmultipleuser.php';
}


