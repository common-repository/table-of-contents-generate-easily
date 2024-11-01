<?php
/*
 * Plugin Name: Table of Contents Generate Easily
 * Version: 1.2
 * Plugin URI: https://wordpress.org/plugins/table-of-contents-generate-easily/
 * Description: This plugin automatically generate table of contents from headers(H1,H2,H3...). It supports pages, posts and custom posts.
 * Author: 4Games
 * Author URI: https://www.sockscap64.com/
 * Requires at least: 4.0
 * Tested up to: 5.0.3
 *
 * Text Domain: table-of-content-generate-easily
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author 4Games
 * @since 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/table-of-content-generate-easily.php' );

/**
 * Returns the main instance of Table_of_Contents_Generate_Easily to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Table_of_Contents_Generate_Easily
 */
function Table_of_Contents_Generate_Easily () {
	$instance = Table_of_Contents_Generate_Easily::instance( __FILE__, '1.0.0' );

	return $instance;
}

Table_of_Contents_Generate_Easily();