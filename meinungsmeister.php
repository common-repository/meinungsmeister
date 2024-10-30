<?php
/**
 * @package Meinungsmeister
 */
/*
Plugin Name: Meinungsmeister
Plugin URI: https://www.meinungsmeister.de/
Description: Zeige Kundenbewertungen automatisch auf Deiner Homepage und in den Google Suchergebnissen an. Verbreite mit nur einem Klick Deinen guten Ruf im Netz.
Author: Meinungsmeister
Author URI: https://www.meinungsmeister.de
License: GPLv2 or later
Text Domain: meinungsmeister
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2015-2016 Meinungsmeister
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'MEINUNGSMEISTER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEINUNGSMEISTER__BASENAME', plugin_basename(__FILE__) );
define( 'MEINUNGSMEISTER__BOOTFILE', __FILE__ );
define( 'MEINUNGSMEISTER__VERSION', '1.0.1' );

require_once( MEINUNGSMEISTER__PLUGIN_DIR . 'class.mm.php' );

new Meinungsmeister();
