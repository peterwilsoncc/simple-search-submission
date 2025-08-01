<?php
/**
 * No Fuss IndexNow
 *
 * @package           NoFussIndexNow
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson, FAIR Contributors
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: No Fuss IndexNow
 * Description: No Fuss IndexNow
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Peter Wilson
 * Author URI: https://peterwilson.cc
 * License: GPL-2.0-or-later
 * Text Domain: no-fuss-indexnow
 *
 * This plugin is based on the IndexNow code from the FAIR plugin, copyright
 * 2025 FAIR contributors, and licensed under the GNU General Public License.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace PWCC\NoFussIndexNow;

require_once __DIR__ . '/inc/namespace.php';

// Run late to give themes and plugins a chance to set up asynchronous pings.
add_action( 'after_setup_theme', __NAMESPACE__ . '\\bootstrap', 20 );
