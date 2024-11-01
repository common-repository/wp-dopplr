<?php

/*
Plugin Name: WP-DOPPLR
Plugin URI: http://www.rodenas.org/blog/2007/10/09/wp-dopplr/
Description: WP-DOPPLR is a Wordpress plugin that displays your <a href="http://www.dopplr.com/">DOPPLR</a> travel information on your blog. <a href="options-general.php?page=wp-dopplr.php">Configure your settings here</a> and <a href="widgets.php">add the widget here</a>.
Version: 1.6
Author: Ferran Rodenas
Author URI: http://www.rodenas.org/blog/
Text Domain: wp-dopplr 
*/

/*
 Copyright 2007, 2008, 2009 Ferran Rodenas (email: frodenas@gmail.com)

 This program is free software; you can redistribute it and/or modify it under the terms
 of the GNU General Public License as published by the Free Software Foundation; either
 version 2 of the License, or any later version.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along with this program;
 if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 MA  02111-1307  USA
*/

/* Changelog
 * October 9 2007 - v1.0
   - Initial release
 * October 14 2007 - v1.1
   - Use the Dopplr AuthSub process
 * January 13 2008 - v1.2
   - Add new method future_trips_info
   - Add new method local_time
   - Add new option to display cities local time
   - Cache Dopplr query results (Thanks to Boris Anthony for this suggestion)
 * February 29 2008 - v1.3
   - Add CSS classes
 * November 5 2008 - v1.4
   - Add new method past_trips_info
   - Add new option to display past and future trips
   - Add new option to display start and finish trip dates 
   - Add new option to modify the date and time format 
   - Add new option to display city colours 
   - Add new option to display countries 
 * June 10 2009 - v1.5
   - Bug: Determine the correct wp-content directory
 * September 14 2009 - v1.6
   - Bug: Determine the correct local date & time
   - Add new option to specify cities links
   - Add new option to specify cities colour type
   - Add new option to dismiss the API key
   - Add new option to clear the cache contents
   - Enhance settings and widget menu
   - Enhance installation process (subdirectories allowed)
   - Translatable strings (internationalization)
*/

// Plugin inicialization
load_plugin_textdomain('wp-dopplr', '', dirname(plugin_basename(__FILE__)) . '/lang');
add_action('plugins_loaded', 'widget_wpdopplr_init');
add_action('admin_menu', 'add_wpdopplr_options_page');
register_activation_hook(__FILE__, 'wpdopplr_createdb');  
register_deactivation_hook(__FILE__, 'wpdopplr_dropdb');

// Plugin functions
function wpdopplr_badge($username = "") {
	$display_past_trips       = get_option('wpdopplr_display_past_trips');
	$display_future_trips     = get_option('wpdopplr_display_future_trips');
	$display_city_colour_icon = get_option('wpdopplr_display_city_colour_icon');
	$display_localtime        = get_option('wpdopplr_display_localtime');

	$status = wpdopplr_traveller_info($username);
	if ($status != null) {
		echo '<p class="wpdopplr_status">' . $status;
		if ($display_past_trips || $display_future_trips) {
			if ($display_past_trips) {
				$past_cities = wpdopplr_past_trips_info($username);
				if ($past_cities != null && count($past_cities) > 0) {
					echo '.</p>';
					echo '<p class="wpdopplr_status">' . __('Past trips', 'wp-dopplr') . ':</p>';
					if ($display_city_colour_icon) {
						echo '<ul class="wpdopplr_plannedtrips" style="list-style-type: none;">';
					} else {
						echo '<ul class="wpdopplr_plannedtrips">';
					}
					foreach ($past_cities as $city) {
						echo '<li>' . $city . '</li>';
					}
					echo '</ul>';
				}
			}
			if ($display_future_trips) {
				$future_cities = wpdopplr_future_trips_info($username);
				if ($past_cities != null && count($past_cities) > 0) {
					if ($future_cities != null && count($future_cities) > 0) {
						echo '<p class="wpdopplr_status">' . __('Planned trips', 'wp-dopplr') . ':</p>';
						if ($display_city_colour_icon) {
							echo '<ul class="wpdopplr_plannedtrips" style="list-style-type: none;">';
						} else {
							echo '<ul class="wpdopplr_plannedtrips">';
						}
						foreach ($future_cities as $city) {
							echo '<li>' . $city . '</li>';
						}
						echo '</ul>';
					}
				} elseif ($future_cities != null && count($future_cities) > 0) {
					if (count($future_cities) == 1) {
						echo __(' and has planned a trip to ', 'wp-dopplr') . $future_cities[0] . '.</p>';
					} else {
						echo __(' and has planned trips to', 'wp-dopplr') . ':</p>';
						if ($display_city_colour_icon) {
							echo '<ul class="wpdopplr_plannedtrips" style="list-style-type: none;">';
						} else {
							echo '<ul class="wpdopplr_plannedtrips">';
						}
						foreach ($future_cities as $city) {
							echo '<li>' . $city . '</li>';
						}
						echo '</ul>';
					}
				} else {
					echo '.</p>';
				}
			}
		} else {
			echo '.</p>';
		}
		if ($display_localtime) {
			echo '<p class="wpdopplr_localtime">' . wpdopplr_local_time($username) . '.</p>';			
		}
	}
}

function wpdopplr_traveller_info($username = "") {
	$cities_links      = get_option('wpdopplr_cities_links');
	$display_countries = get_option('wpdopplr_display_countries');
	
	$traveller_info = wpdopplr_traveller_methods($method = "traveller_info", $use_cache = true, $username);
	if ($traveller_info != null) {
		if ($display_countries) {
			if ($traveller_info->traveller->current_city->region) {
				$city_name = $traveller_info->traveller->current_city->name . ', ' . $traveller_info->traveller->current_city->region . ', ' . $traveller_info->traveller->current_city->country;
			} else {
				$city_name = $traveller_info->traveller->current_city->name . ', ' . $traveller_info->traveller->current_city->country;
			}
		} else {
			$city_name = $traveller_info->traveller->current_city->name;
		}
		if ($traveller_info->traveller->current_city->geoname_id == $traveller_info->traveller->home_city->geoname_id) {
			$status = __(' is at home in ', 'wp-dopplr');
		} else {
			$status = __(' is in ', 'wp-dopplr');
		}
		if ($cities_links == 'place') {
			$city_url = '<a href="' . $traveller_info->traveller->current_city->url . '">' . $city_name . '</a>';
		} elseif ($cities_links == 'gmaps') {
			$city_url = '<a href="http://maps.google.com/maps?ie=UTF8&ll=' . $traveller_info->traveller->current_city->latitude . ',' . $traveller_info->traveller->current_city->longitude . '&z=12">' . $city_name . '</a>';
		} else {
			$city_url = $city_name;
		}
		return '<a href="http://www.dopplr.com/traveller/' . $traveller_info->traveller->nick . '">' . $traveller_info->traveller->name . '</a>' . $status . $city_url;
	} else {
		return null;
	}
}

function wpdopplr_local_time($username = "") {
	$date_format = get_option('wpdopplr_date_format');
	$time_format = get_option('wpdopplr_time_format');

	$traveller_info = wpdopplr_traveller_methods($method = "traveller_info", $use_cache = true, $username);
	if ($traveller_info != null && function_exists('date_default_timezone_set')) {
		$default_timezone = date_default_timezone_get();
		date_default_timezone_set($traveller_info->traveller->current_city->timezone);
		$local_time_text = date($time_format, time());
		date_default_timezone_set($default_timezone);
		return sprintf(__('It\'s %s at %s\'s current location', 'wp-dopplr'), $local_time_text, $traveller_info->traveller->name);
	} else {
		return '';
	}
}

function wpdopplr_trips_info($username = "", $from_date = 0, $to_date = 0) {
	$display_start_date = get_option('wpdopplr_display_start_date');
	$display_finish_date      = get_option('wpdopplr_display_finish_date');
	$date_format              = get_option('wpdopplr_date_format');
	$time_format              = get_option('wpdopplr_time_format');
	$cities_links             = get_option('wpdopplr_cities_links');
	$display_city_colour_icon = get_option('wpdopplr_display_city_colour_icon');
	$display_city_colour_text = get_option('wpdopplr_display_city_colour_text');
	$display_countries        = get_option('wpdopplr_display_countries');
	$display_localtime        = get_option('wpdopplr_display_localtime');

	$trips_info = wpdopplr_traveller_methods($method = "trips_info", $use_cache = true, $username);
	if ($trips_info != null) {
		$city_list = array();
		foreach ($trips_info->trip as $trip) {
			$trip_date = explode("-", $trip->start);
			$start_date = mktime(0, 0, 0, $trip_date[1], $trip_date[2], $trip_date[0]);
			$trip_date = explode("-", $trip->finish);
			$finish_date = mktime(0, 0, 0, $trip_date[1], $trip_date[2], $trip_date[0]);
			if ($from_date > 0) {
				if ($to_date > 0) {
					if ($start_date > $from_date && $start_date < $to_date) {
						$add_city = true;
					} else {
						$add_city = false;
					}
				} else {
					if ($start_date > $from_date) {
						$add_city = true;
					} else {
						$add_city = false;
					}
				}
			} elseif ($to_date > 0) {
				if ($start_date < $to_date) {
					$add_city = true;
				} else {
					$add_city = false;
				}
			} else {
				$add_city = true;
			}
			if ($add_city) {
				if ($display_countries) {
					if ($trip->city->region) {
						$city_name = $trip->city->name . ', ' . $trip->city->region . ', ' . $trip->city->country;
					} else {
						$city_name = $trip->city->name . ', ' . $trip->city->country;
					}
				} else {
					$city_name = $trip->city->name;
				}
				if ($display_localtime && function_exists('date_default_timezone_set')) {
					$default_timezone = date_default_timezone_get();
					date_default_timezone_set($trip->city->timezone);
					$local_time_text = date($time_format, time());
					date_default_timezone_set($default_timezone);
					$city_title = ' title="' . __('It\'s ', 'wp-dopplr') . $local_time_text . __(' in ', 'wp-dopplr') . $city_name . '"';
				} else {
					$city_title = '';			
				}
				if ($display_city_colour_text) {
					$city_colour_text = ' style=\'color: #' . $trip->city->rgb . '\'';
				} else {
					$city_colour_text = '';
				}
				if ($cities_links == 'trip') {
					$city_url_start = '<a href="' . $trip->url . '" ' . $city_title . $city_colour_text . '>';
					$city_url_end = '</a>';
				} elseif ($cities_links == 'place') {
					$city_url_start = '<a href="' . $trip->city->url . '" ' . $city_title . $city_colour_text . '>';
					$city_url_end = '</a>';
				} elseif ($cities_links == 'gmaps') {
					$city_url_start = '<a href="http://maps.google.com/maps?ie=UTF8&ll=' . $trip->city->latitude . ',' . $trip->city->longitude . '&z=12" ' . $city_title . $city_colour_text . '>';
					$city_url_end = '</a>';
				} else {
					$city_url_start = '';
					$city_url_end = '';
					if ($display_city_colour_text) {
						$city_name = '<span style="color: #' . $trip->city->rgb . '">' . $city_name . '</span>';
					}
				}
				if ($display_city_colour_icon) {				
					$city_colour_img = '<img style="border: none; height: 14px; width: 14px; background-color: #' . $trip->city->rgb . '" alt="*" src="http://www.dopplr.com/images/spaceball.gif" /> ';
				} else {
					$city_colour_img = '';
				}
				if ($display_start_date) {
					if ($display_finish_date && ($start_date != $finish_date)) {
						$trip_dates = __(' from ', 'wp-dopplr') . date_i18n($date_format, $start_date) . __(' to ', 'wp-dopplr') . date_i18n($date_format, $finish_date);
					} else {
						$trip_dates = __(' in ', 'wp-dopplr') . date_i18n($date_format, $start_date);
					}
				} elseif ($display_finish_date) {
					$trip_dates = __(' in ', 'wp-dopplr') . date_i18n($date_format, $finish_date);
				} else {
					$trip_dates = '';
				}
				array_push($city_list, $city_url_start . $city_colour_img . $city_name . $city_url_end . $trip_dates);
			}
		}
		return $city_list;
	} else {
		return null;
	}
}

function wpdopplr_past_trips_info($username = "") {
	return wpdopplr_trips_info($username, 0, strtotime('now'));
}

function wpdopplr_future_trips_info($username = "") {
	return wpdopplr_trips_info($username, strtotime('now'), 0);
}

// Widget functions
function widget_wpdopplr_init() {
 	if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control'))
		return; 

	function widget_wpdopplr_control() {
		if (isset($_POST['wpdopplr-submit'])) {
			// Widget Title
			if (empty($_POST['wpdopplr_widget_title'])) {
				$wpdopplr_widget_title = '';
				delete_option('wpdopplr_widget_title');
			} else {
				$wpdopplr_widget_title = strip_tags(stripslashes($_POST['wpdopplr_widget_title']));
				update_option('wpdopplr_widget_title', $wpdopplr_widget_title);
			}
		} else { 
			$wpdopplr_widget_title = wp_specialchars(get_option('wpdopplr_widget_title'));
		}

		echo '<div style="text-align:right">';
		echo '<label for="wpdopplr_widget_title" style="line-height:35px;display:block;">' . __('Widget title', 'wp-dopplr') . ': <input id="wpdopplr_widget_title" name="wpdopplr_widget_title" type="text" value="' . $wpdopplr_widget_title . '" /></label>';
		echo '<input type="hidden" name="wpdopplr-submit" id="wpdopplr-submit" value="1" />';
		echo '</div>';
	}

	function widget_wpdopplr($args) {
		extract($args);
		$wpdopplr_widget_title = wp_specialchars(get_option('wpdopplr_widget_title'));
		if ($wpdopplr_widget_title == "") {
			$wpdopplr_widget_title = '<a href="http://www.dopplr.com">' . __('Dopplr', 'wp-dopplr') . '</a>';
		}

		echo $before_widget;
		echo '<div class="wpdopplr">';
	        echo $before_title . $wpdopplr_widget_title . $after_title;
		wpdopplr_badge();
		echo '</div>';
		echo $after_widget;
	}

	$options = array('description' => __('Displays your DOPPLR travel information', 'wp-dopplr'));
	wp_register_sidebar_widget('wp-dopplr', __('Dopplr', 'wp-dopplr'), 'widget_wpdopplr', $options);
	wp_register_widget_control('wp-dopplr', __('Dopplr', 'wp-dopplr'), 'widget_wpdopplr_control'); 
}

// DOPPLR functions
function wpdopplr_make_connection($url, $header = array()) {
	global $wp_version;
	
	// Make connection
	$conn = curl_init();
	curl_setopt($conn, CURLOPT_URL, $url);
	curl_setopt($conn, CURLOPT_HEADER, true);
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($conn, CURLOPT_USERAGENT, 'WordPress/' . $wp_version . '| WP-DOPPLR Plugin 1.6');
	if (count($header) > 0) {
		curl_setopt($conn, CURLOPT_HTTPHEADER, $header);
	}
	$response = curl_exec($conn);
	curl_close($conn);
	unset($conn);

	// Process response
	list($response_headers, $response_body) = explode("\r\n\r\n", $response, 2);
	$response_header_lines = explode("\r\n", $response_headers);
	$http_response_line = array_shift($response_header_lines);
	if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@', $http_response_line, $matches)) { 
		$response_code = $matches[1];
	}
	$response_header_array = array();
	foreach($response_header_lines as $header_line) {
		list($header, $value) = explode(': ', $header_line, 2);
		$response_header_array[$header] .= $value."\n";
	}

	// Return response
	return array("code" => $response_code, "header" => $response_header_array, "body" => $response_body); 
}

/**
 * Traveller methods
 * @See http://dopplr.pbwiki.com/API+Resource+URLs
 */
// 
function wpdopplr_traveller_methods($method = "", $use_cache = true, $username = "", $tagname = "", $date = "") {
	global $wpdb;

	if (trim($username) == "") {
		$username_parm = '';
	} else {
		$username_parm = '&traveller=' . trim($username);
	}
	if (trim($tag) == "") {
		$tag_parm = '';
	} else {
		$tag_parm = '&tag=' . trim($tag);
	}
	if (trim($date) == "") {
		$date_parm = '';
	} else {
		$date_parm = '&date=' . trim($date);
	}

	$json = new Services_JSON();
	$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
	$wpdopplr_api_token = get_option('wpdopplr_api_token');
	$wpdopplr_cache_expire_time = get_option('wpdopplr_cache_expire_time');
	if (empty($wpdopplr_cache_expire_time)) {
		$wpdopplr_cache_expire_time = 24;
	}

	$url = 'https://www.dopplr.com/api/' . trim($method) . '?' . $username_parm . $tag_parm . $date_parm . '&format=js&token=' . $wpdopplr_api_token;
	if (function_exists('mysql_real_escape_string')) {
		$url_escaped = mysql_real_escape_string($url);
	} else {
		$url_escaped = mysql_escape_string($url);
	}

	$result = $wpdb->get_row("SELECT method, data, last_update FROM $wpdb->wpdopplr WHERE method = '$url_escaped'");
	if ($result && use_cache) {
		if (strtotime($result->last_update) >= (strtotime('now') - ($wpdopplr_cache_expire_time * 3600))) {
			return $json->decode($result->data);
		} else {
			$response = wpdopplr_make_connection($url);

			if ($response["code"] == '200') {
				if (function_exists('mysql_real_escape_string')) {
					$data_escaped = mysql_real_escape_string($response["body"]);
				} else {
					$data_escaped = mysql_escape_string($response["body"]);
				}
				$wpdb->query("UPDATE $wpdb->wpdopplr SET data = '$data_escaped', last_update = NOW() WHERE method = '$url_escaped'");
				return $json->decode($response["body"]);
			} else {
				$wpdb->query("DELETE FROM $wpdb->wpdopplr WHERE method = '$url_escaped'");
				return null;
			}
		}
	} else {
		$response = wpdopplr_make_connection($url);

		if ($response["code"] == '200') {
			if (function_exists('mysql_real_escape_string')) {
				$data_escaped = mysql_real_escape_string($response["body"]);
			} else {
				$data_escaped = mysql_escape_string($response["body"]);
			}
			if ($use_cache) {
				$wpdb->query("INSERT INTO $wpdb->wpdopplr (method, data, last_update) VALUES ('$url_escaped', '$data_escaped', NOW())");
			}
			return $json->decode($response["body"]);
		} else {
			return null;
		}
	}
}

function wpdopplr_print_json_object($object) {
	print_r(get_object_vars($object));
}

// Plugin options functions
function add_wpdopplr_options_page() {
	global $wp_version;

	if (current_user_can('manage_options') && function_exists('add_options_page')) {
		$menutitle = '';
		if (version_compare($wp_version, '2.6.999', '>' )) {
	  		$menutitle = '<img src="' . plugins_url(dirname(plugin_basename(__FILE__))) . '/wp-dopplr.png" style="margin-right:4px;" />';
		}
		$menutitle .= __('Dopplr', 'wp-dopplr');

		add_filter('plugin_action_links', 'add_wpdopplr_plugin_links', 10, 2);
		add_options_page(__('DOPPLR Settings', 'wp-dopplr'), $menutitle, 8, basename(__FILE__), 'add_wpdopplr_options_panel');
	}
}

function add_wpdopplr_plugin_links($links, $file) {
	static $this_plugin;

	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=wp-dopplr.php">' . __('Settings', 'wp-dopplr') . '</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

function add_wpdopplr_options_panel() {
	global $wpdb;

	$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
	$tables = $wpdb->get_col("SHOW TABLES");
	if (!in_array($wpdb->wpdopplr, $tables)) {
		wpdopplr_createdb();
	}

	// API Token
	if (isset($_GET['token'])) {
		if (empty($_GET['token'])) {
			$wpdopplr_api_token = '';
			delete_option('wpdopplr_api_token');
		} else {
			$response = wpdopplr_make_connection('https://www.dopplr.com/api/AuthSubSessionToken', array("Authorization: AuthSub token=\"" . $_GET[token] . "\""));
			if ($response["code"] == '200') {
				$response_body_lines = explode("\n", $response["body"]);
				foreach($response_body_lines as $body_line) {
					list($body, $value) = explode('=', $body_line, 2);
					$response_body_array[$body] = $value;
				}
				$wpdopplr_api_token = $response_body_array["Token"];
				update_option('wpdopplr_api_token', $wpdopplr_api_token);
			} else {
				$wpdopplr_api_token = '';
				delete_option('wpdopplr_api_token');
			}
		}
	} else { 
		$wpdopplr_api_token = get_option('wpdopplr_api_token');
	}
	
	// Update options
	if (isset($_POST['info_update'])) {	
		// Display past trips
		if (empty($_POST['wpdopplr_display_past_trips'])) {
			$wpdopplr_display_past_trips = '';
			delete_option('wpdopplr_display_past_trips');
		} else {
			$wpdopplr_display_past_trips = 'on';
			update_option('wpdopplr_display_past_trips', $wpdopplr_display_past_trips);
		}
		// Display future trips
		if (empty($_POST['wpdopplr_display_future_trips'])) {
			$wpdopplr_display_future_trips = '';
			delete_option('wpdopplr_display_future_trips');
		} else {
			$wpdopplr_display_future_trips = 'on';
			update_option('wpdopplr_display_future_trips', $wpdopplr_display_future_trips);
		}
		// Display start date
		if (empty($_POST['wpdopplr_display_start_date'])) {
			$wpdopplr_display_start_date = '';
			delete_option('wpdopplr_display_start_date');
		} else {
			$wpdopplr_display_start_date = 'on';
			update_option('wpdopplr_display_start_date', $wpdopplr_display_start_date);
		}
		// Display finish date
		if (empty($_POST['wpdopplr_display_finish_date'])) {
			$wpdopplr_display_finish_date = '';
			delete_option('wpdopplr_display_finish_date');
		} else {
			$wpdopplr_display_finish_date = 'on';
			update_option('wpdopplr_display_finish_date', $wpdopplr_display_finish_date);
		}
		// Date format
		if (empty($_POST['wpdopplr_date_format'])) {
			$wpdopplr_date_format = '';
			delete_option('wpdopplr_date_format');
		} else {
			$wpdopplr_date_format = $_POST['wpdopplr_date_format'];
			update_option('wpdopplr_date_format', $wpdopplr_date_format);
		}
		// Time format
		if (empty($_POST['wpdopplr_time_format'])) {
			$wpdopplr_time_format = '';
			delete_option('wpdopplr_time_format');
		} else {
			$wpdopplr_time_format = $_POST['wpdopplr_time_format'];
			update_option('wpdopplr_time_format', $wpdopplr_time_format);
		}
		// Cities Links
		if (empty($_POST['wpdopplr_cities_links'])) {
			$wpdopplr_cities_links = '';
			delete_option('wpdopplr_cities_links');
		} else {
			$wpdopplr_cities_links = $_POST['wpdopplr_cities_links'];
			update_option('wpdopplr_cities_links', $wpdopplr_cities_links);
		}
		// Display city colour icon
		if (empty($_POST['wpdopplr_display_city_colour_icon'])) {
			$wpdopplr_display_city_colour_icon = '';
			delete_option('wpdopplr_display_city_colour_icon');
		} else {
			$wpdopplr_display_city_colour_icon = 'on';
			update_option('wpdopplr_display_city_colour_icon', $wpdopplr_display_city_colour_icon);
		}
		// Display city colour text
		if (empty($_POST['wpdopplr_display_city_colour_text'])) {
			$wpdopplr_display_city_colour_text = '';
			delete_option('wpdopplr_display_city_colour_text');
		} else {
			$wpdopplr_display_city_colour_text = 'on';
			update_option('wpdopplr_display_city_colour_text', $wpdopplr_display_city_colour_text);
		}
		// Display countries
		if (empty($_POST['wpdopplr_display_countries'])) {
			$wpdopplr_display_countries = '';
			delete_option('wpdopplr_display_countries');
		} else {
			$wpdopplr_display_countries = 'on';
			update_option('wpdopplr_display_countries', $wpdopplr_display_countries);
		}
		// Display cities local time
		if (empty($_POST['wpdopplr_display_localtime'])) {
			$wpdopplr_display_localtime = '';
			delete_option('wpdopplr_display_localtime');
		} else {
			$wpdopplr_display_localtime = 'on';
			update_option('wpdopplr_display_localtime', $wpdopplr_display_localtime);
		}
		// Cache expire time (hours)
		if (empty($_POST['wpdopplr_cache_expire_time'])) {
			$wpdopplr_cache_expire_time = '';
			delete_option('wpdopplr_cache_expire_time');
		} else {
			$wpdopplr_cache_expire_time = $_POST['wpdopplr_cache_expire_time'];
			update_option('wpdopplr_cache_expire_time', $wpdopplr_cache_expire_time);
		}
	} else { 
		if (isset($_POST['dismiss_API'])) {
			$wpdopplr_api_token = "";
			delete_option('wpdopplr_api_token');
		}

		if (isset($_POST['delete_cache'])) {
			$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
			$result = $wpdb->query("
				TRUNCATE TABLE `$wpdb->wpdopplr`
			");
		}

		// Get options 
		$wpdopplr_display_past_trips        = get_option('wpdopplr_display_past_trips');
		$wpdopplr_display_future_trips      = get_option('wpdopplr_display_future_trips');
		$wpdopplr_display_start_date        = get_option('wpdopplr_display_start_date');
		$wpdopplr_display_finish_date       = get_option('wpdopplr_display_finish_date');
		$wpdopplr_date_format               = get_option('wpdopplr_date_format');
		$wpdopplr_time_format               = get_option('wpdopplr_time_format');
		$wpdopplr_cities_links              = get_option('wpdopplr_cities_links');
		$wpdopplr_display_city_colour_icon  = get_option('wpdopplr_display_city_colour_icon');
		$wpdopplr_display_city_colour_text  = get_option('wpdopplr_display_city_colour_text');
		$wpdopplr_display_countries         = get_option('wpdopplr_display_countries');
		$wpdopplr_display_localtime         = get_option('wpdopplr_display_localtime');
		$wpdopplr_cache_expire_time         = get_option('wpdopplr_cache_expire_time');
	}
	
	// Display DOPPLR Authorization
	echo '<div class="wrap">';
	echo '<div class="icon32"><img src="' . plugins_url(dirname(plugin_basename(__FILE__))) . '/wp-dopplr.png" height="28px" width="28px"/></div>';
	echo '<h2>' . __('DOPPLR Authorization', 'wp-dopplr') . '</h2>';
	echo '<div class="narrow">';
	if (function_exists('curl_version')) {
		if (trim($wpdopplr_api_token) == '') {	
			echo '<p style="padding: .5em; background-color: #aa0; color: #fff; font-weight: bold;">' . __('Before you get started, please', 'wp-dopplr') . ' <a href="http://www.dopplr.com/api/AuthSubRequest?scope=http://www.dopplr.com&next=' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-dopplr.php&session=1">' . __('sign in', 'wp-dopplr') . '</a> ' . __('to your DOPPLR account.', 'wp-dopplr') . '</p>';
		} else {
			$traveller_info = wpdopplr_traveller_methods($method = "traveller_info", $use_cache = false);
			if ($traveller_info != null) {
				echo '<form name="formwpdopplr-authorization" method="post" action="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-dopplr.php">';
				echo '<p style="padding: .5em; background-color: #2d2; color: #fff; font-weight: bold;"><a href="http://www.dopplr.com/traveller/' . $traveller_info->traveller->nick . '">' . $traveller_info->traveller->name . '</a> ' . $traveller_info->traveller->status . '</p>';
				echo '<p class="submit"><input type="submit" name="dismiss_API" class="button-primary" value="' . __('Dismiss', 'wp-dopplr') . '" /></p>';
				echo '</form>';
			} else {
				echo '<p style="padding: .5em; background-color: #d22; color: #fff; font-weight: bold;">' . __('Authorization key to access to your DOPPLR account is invalid. Please,', 'wp-dopplr') . ' <a href="http://www.dopplr.com/api/AuthSubRequest?scope=http://www.dopplr.com&next=' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-dopplr.php&session=1">' . __('sign in', 'wp-dopplr') . '</a> ' . __('to your DOPPLR account.', 'wp-dopplr') . '</p>';
			}
		}
	} else {
		echo '<p style="padding: .5em; background-color: #d22; color: #fff; font-weight: bold;">' . __('cURL functions are not available. In order to use WP-DOPPLR, you must install the cURL functions', 'wp-dopplr') . '</p>';
	}
	echo '</div>';
	echo '</div>';

	// Display Plugin Options
	echo '<div class="wrap">';
	echo '<div id="icon-options-general" class="icon32"><br /></div>';
	echo '<h2>' . __('DOPPLR Settings', 'wp-dopplr') . '</h2>';
	echo '<form name="formwpdopplr-options" method="post" action="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-dopplr.php">';
        echo '<table class="form-table">';
	echo '<tr valign="top">';
	echo '<th scope="row">' . __('Trips', 'wp-dopplr') . '</th>';
	if ($wpdopplr_display_past_trips == 'on') { 
		$wpdopplr_display_past_trips_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_past_trips_checked = '';
	}
	echo '<td><fieldset><legend class="hidden">' . __('Trips', 'wp-dopplr') . '</legend><label for="wpdopplr_display_past_trips"><input name="wpdopplr_display_past_trips" id="wpdopplr_display_past_trips" type="checkbox" ' . $wpdopplr_display_past_trips_checked . ' /> ' . __('Display past trips', 'wp-dopplr') . '</label><br />';
	if ($wpdopplr_display_future_trips == 'on') { 
		$wpdopplr_display_future_trips_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_future_trips_checked = '';
	}
	echo '<label for="wpdopplr_display_future_trips"><input name="wpdopplr_display_future_trips" id="wpdopplr_display_future_trips" type="checkbox" ' . $wpdopplr_display_future_trips_checked . ' /> ' . __('Display future trips', 'wp-dopplr') . '</label></fieldset></td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row">' . __('Travel dates', 'wp-dopplr') . '</th>';
	if ($wpdopplr_display_start_date == 'on') { 
		$wpdopplr_display_start_date_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_start_date_checked = '';
	}
	echo '<td><fieldset><legend class="hidden">' . __('Travel dates', 'wp-dopplr') . '</legend><label for="wpdopplr_display_start_date"><input name="wpdopplr_display_start_date" id="wpdopplr_display_start_date" type="checkbox" ' . $wpdopplr_display_start_date_checked . ' /> ' . __('Display trip\'s start date', 'wp-dopplr') . '</label><br />';
	if ($wpdopplr_display_finish_date == 'on') { 
		$wpdopplr_display_finish_date_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_finish_date_checked = '';
	}
	echo '<label for="wpdopplr_display_finish_date"><input name="wpdopplr_display_finish_date" id="wpdopplr_display_finish_date" type="checkbox" ' . $wpdopplr_display_finish_date_checked . ' /> ' . __('Display trip\'s finish date', 'wp-dopplr') . '</label></fieldset></td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="wpdopplr_date_format">' . __('Date format', 'wp-dopplr') . '</label></th>';
	if ($wpdopplr_date_format == '') {
		$wpdopplr_date_format = 'F Y';
	}
	echo '<td><input name="wpdopplr_date_format" id="wpdopplr_date_format" type="text" size="30" value="' . $wpdopplr_date_format . '" /><br />';
	echo __('Output', 'wp-dopplr') . ': <strong>' . mysql2date($wpdopplr_date_format, current_time('mysql')) . '</strong></td>';
	echo '</tr>';	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="wpdopplr_time_format">' . __('Time format', 'wp-dopplr') . '</label></th>';
	if ($wpdopplr_time_format == '') {
		$wpdopplr_time_format = 'h:ia';
	}
	echo '<td><input name="wpdopplr_time_format" id="wpdopplr_time_format" type="text" size="30" value="' . $wpdopplr_time_format . '" /><br />';
	echo __('Output', 'wp-dopplr') . ': <strong>' . gmdate($wpdopplr_time_format, current_time('timestamp')) . '</strong><br />';
	echo '<a href="http://codex.wordpress.org/Formatting_Date_and_Time">' . __('Documentation on date and time formatting', 'wp-dopplr') . '</a>. ' . __('Click "Update Options" to update sample output', 'wp-dopplr') . '.</td>';
	echo '</tr>';	
	echo '<tr valign="top">';
	echo '<th scope="row">' . __('Cities links', 'wp-dopplr') . '</th>';
	$wpdopplr_cities_links_none_checked = '';
	$wpdopplr_cities_links_trip_checked = '';
	$wpdopplr_cities_links_place_checked = '';	
	$wpdopplr_cities_links_gmaps_checked = '';
	if ($wpdopplr_cities_links == 'none') { 
		$wpdopplr_cities_links_none_checked = 'checked="checked"';
	} elseif ($wpdopplr_cities_links == 'trip') { 
		$wpdopplr_cities_links_trip_checked = 'checked="checked"';
	} elseif ($wpdopplr_cities_links == 'place') {
		$wpdopplr_cities_links_place_checked = 'checked="checked"';
	} elseif ($wpdopplr_cities_links == 'gmaps') {
		$wpdopplr_cities_links_gmaps_checked = 'checked="checked"';
	};
	echo '<td><fieldset><legend class="hidden">' . __('Cities links', 'wp-dopplr') . '</legend><label for="wpdopplr_cities_links_none"><input name="wpdopplr_cities_links" id="wpdopplr_cities_links_none" type="radio" value="none" ' . $wpdopplr_cities_links_none_checked . ' /> ' . __('No links', 'wp-dopplr') . '</label><br />';
	echo '<label for="wpdopplr_cities_links_trip"><input name="wpdopplr_cities_links" id="wpdopplr_cities_links_trip" type="radio" value="trip" ' . $wpdopplr_cities_links_trip_checked . ' /> ' . __('Link to trip info', 'wp-dopplr') . '</label><br />';
	echo '<label for="wpdopplr_cities_links_place"><input name="wpdopplr_cities_links" id="wpdopplr_cities_links_place" type="radio" value="place" ' . $wpdopplr_cities_links_place_checked . ' /> ' . __('Link to place info', 'wp-dopplr') . '</label><br />';
	echo '<label for="wpdopplr_cities_links_gmaps"><input name="wpdopplr_cities_links" id="wpdopplr_cities_links_gmaps" type="radio" value="gmaps" ' . $wpdopplr_cities_links_gmaps_checked . ' /> ' . __('Link to Google Maps', 'wp-dopplr') . '</label></fieldset></td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row"><a href="http://blog.dopplr.com/2007/10/23/in-rainbows/">' . __('City Colour', 'wp-dopplr') . '</a></th>';
	if ($wpdopplr_display_city_colour_icon == 'on') { 
		$wpdopplr_display_city_colour_icon_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_city_colour_icon_checked = '';
	}
	echo '<td><fieldset><legend class="hidden">' . __('City Colour', 'wp-dopplr') . '</legend><label for="wpdopplr_display_city_colour_icon"><input name="wpdopplr_display_city_colour_icon" id="wpdopplr_display_city_colour_icon" type="checkbox" ' . $wpdopplr_display_city_colour_icon_checked . ' /> ' . __('Display icon city colour', 'wp-dopplr') . '</label><br />';
	if ($wpdopplr_display_city_colour_text == 'on') { 
		$wpdopplr_display_city_colour_text_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_city_colour_text_checked = '';
	}
	echo '<label for="wpdopplr_display_city_colour_text"><input name="wpdopplr_display_city_colour_text" id="wpdopplr_display_city_colour_text" type="checkbox" ' . $wpdopplr_display_city_colour_text_checked . ' /> ' . __('Display coloured city name', 'wp-dopplr') . '</label></fieldset></td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="wpdopplr_display_countries">' . __('Countries', 'wp-dopplr') . '</label></th>';
	if ($wpdopplr_display_countries == 'on') { 
		$wpdopplr_display_countries_checked = 'checked="checked"';
	} else {
		$wpdopplr_display_countries_checked = '';
	}
	echo '<td><input name="wpdopplr_display_countries" id="wpdopplr_display_countries" type="checkbox" ' . $wpdopplr_display_countries_checked . ' /> ' . __('Display countries', 'wp-dopplr') . '</td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="wpdopplr_display_localtime">' . __('Local Time', 'wp-dopplr') . '</label></th>';
	if (function_exists('date_default_timezone_set')) {
		$wpdopplr_display_localtime_msg = '';
		if ($wpdopplr_display_localtime == 'on') { 
			$wpdopplr_display_localtime_checked = 'checked="checked"';
		} else {
			$wpdopplr_display_localtime_checked = '';
		}
	} else {
		$wpdopplr_display_localtime_checked = 'disabled';
		$wpdopplr_display_localtime_msg = '<br />' . __('You need PHP version >= 5.1.0 to enable this option', 'wp-dopplr');
	}
	echo '<td><input name="wpdopplr_display_localtime" id="wpdopplr_display_localtime" type="checkbox" ' . $wpdopplr_display_localtime_checked . ' /> ' . __('Display cities local time', 'wp-dopplr') . $wpdopplr_display_localtime_msg. '</td>';
	echo '</tr>';
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="wpdopplr_cache_expire_time">' . __('Cache expire time', 'wp-dopplr') . '</label></th>';
	if ($wpdopplr_cache_expire_time == '') {
		$wpdopplr_cache_expire_time = 24;
	}
	echo '<td><input name="wpdopplr_cache_expire_time" id="wpdopplr_cache_expire_time" type="text" size="3" value="' . $wpdopplr_cache_expire_time . '" /> ' . __('hours', 'wp-dopplr') . '<br />';
	echo __('WP-DOPPLR caches your Dopplr information so it can be used often without having to retrieve it on every page load.', 'wp-dopplr') . ' ' . __('Since the information Dopplr returns to the request is unchanged in a high percentage of requests, specify a higher cache expire time in order to gain the maximum performance.', 'wp-dopplr') . '</td>';
	echo '</tr>';	
	echo '</table>';
	echo '<p class="submit"><input type="submit" name="info_update" class="button-primary" value="' . __('Update Options', 'wp-dopplr') . '" /></p>';
	echo '</form>';
	echo '</div>';


	// Display Cache Contents
	$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
	$result = $wpdb->get_results("
		SELECT last_update FROM `$wpdb->wpdopplr`
	");
	$cached_queries = 0;
	$expired_queries = 0;
	foreach ($result as $cached_query) {
		$cached_queries++;
		if (strtotime($cached_query->last_update) < (strtotime('now') - ($wpdopplr_cache_expire_time * 3600))) {
			$expired_queries++;
		}
	}
	echo '<div class="wrap">';
	echo '<h3>' . __('Cache Contents', 'wp-dopplr') . '</h3>';
	echo '<form name="formwpdopplr-cache" method="post" action="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-dopplr.php">';
        echo '<table class="form-table">';
	echo '<tr valign="top">';
	echo '<td>' . $cached_queries . __(' Cached Queries', 'wp-dopplr') . '</td>';
	echo '</tr>';	
	echo '<tr valign="top">';
	echo '<td>' . $expired_queries . __(' Expired Queries', 'wp-dopplr') . '</td>';
	echo '</tr>';	
	echo '</table>';
	echo '<p class="submit"><input type="submit" name="delete_cache" class="button-primary" value="' . __('Delete Cache', 'wp-dopplr') . '" /></p>';
	echo '</form>';
	echo '</div>';
}

// Plugin activate/deactivate functions
function wpdopplr_createdb() {
	global $wpdb;

	$charset_collate = '';
	if (version_compare(mysql_get_server_info(), '4.1.0', '>=')) {
		if (!empty($wpdb->charset)) {
			$charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
		}
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}

	$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
	$result = $wpdb->query("
		CREATE TABLE `$wpdb->wpdopplr` (
			`method` VARCHAR (128) NOT NULL PRIMARY KEY,
			`data` LONGTEXT NOT NULL,
			`last_update` DATETIME NOT NULL
			) $charset_collate
	");
}

function wpdopplr_dropdb() {
	global $wpdb;

	delete_option('wpdopplr_api_token');
	delete_option('wpdopplr_display_past_trips');
	delete_option('wpdopplr_display_future_trips');
	delete_option('wpdopplr_display_start_date');
	delete_option('wpdopplr_display_finish_date');
	delete_option('wpdopplr_date_format');
	delete_option('wpdopplr_time_format');
	delete_option('wpdopplr_cities_links');
	delete_option('wpdopplr_display_city_colour_icon');
	delete_option('wpdopplr_display_city_colour_text');
	delete_option('wpdopplr_display_countries');
	delete_option('wpdopplr_display_localtime');
	delete_option('wpdopplr_cache_expire_time');
	delete_option('wpdopplr_widget_title');

	$wpdb->wpdopplr = $wpdb->prefix . 'wpdopplr';
	$result = $wpdb->query("
		DROP TABLE `$wpdb->wpdopplr`
	");
}

// JSON functions
if (!class_exists('Services_JSON')) {

// PEAR JSON class

/**
* Converts to and from JSON format.
*
* JSON (JavaScript Object Notation) is a lightweight data-interchange
* format. It is easy for humans to read and write. It is easy for machines
* to parse and generate. It is based on a subset of the JavaScript
* Programming Language, Standard ECMA-262 3rd Edition - December 1999.
* This feature can also be found in  Python. JSON is a text format that is
* completely language independent but uses conventions that are familiar
* to programmers of the C-family of languages, including C, C++, C#, Java,
* JavaScript, Perl, TCL, and many others. These properties make JSON an
* ideal data-interchange language.
*
* This package provides a simple encoder and decoder for JSON notation. It
* is intended for use with client-side Javascript applications that make
* use of HTTPRequest to perform server communication functions - data can
* be encoded into JSON notation for use in a client-side javascript, or
* decoded from incoming Javascript requests. JSON format is native to
* Javascript, and can be directly eval()'ed with no further parsing
* overhead
*
* All strings should be in ASCII or UTF-8 format!
*
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
* @category
* @package     Services_JSON
* @author      Michal Migurski <mike-json@teczno.com>
* @author      Matt Knapp <mdknapp[at]gmail[dot]com>
* @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
* @copyright   2005 Michal Migurski
* @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
* @license     http://www.opensource.org/licenses/bsd-license.php
* @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
*/

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_SLICE',   1);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_STR',  2);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_ARR',  3);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_OBJ',  4);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_CMT', 5);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
* Converts to and from JSON format.
*
* Brief example of use:
*
* <code>
* // create a new instance of Services_JSON
* $json = new Services_JSON();
*
* // convert a complexe value to JSON notation, and send it to the browser
* $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
* $output = $json->encode($value);
*
* print($output);
* // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
*
* // accept incoming POST data, assumed to be in JSON notation
* $input = file_get_contents('php://input', 1000000);
* $value = $json->decode($input);
* </code>
*/
class Services_JSON
{
   /**
    * constructs a new JSON instance
    *
    * @param    int     $use    object behavior flags; combine with boolean-OR
    *
    *                           possible values:
    *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
    *                                   "{...}" syntax creates associative arrays
    *                                   instead of objects in decode().
    *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
    *                                   Values which can't be encoded (e.g. resources)
    *                                   appear as NULL instead of throwing errors.
    *                                   By default, a deeply-nested resource will
    *                                   bubble up with an error, so all return values
    *                                   from encode() should be checked with isError()
    */
    function Services_JSON($use = 0)
    {
        $this->use = $use;
    }

   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    function encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }

                return '"'.$ascii.'"';

            case 'array':
               /*
                * As per JSON spec if any array key is not an integer
                * we must treat the the whole array as an object. We
                * also try to catch a sparsely populated associative
                * array with numeric keys here because some JS engines
                * will create an array with empty indexes up to
                * max_index which can cause memory issues and because
                * the keys, which may be relevant, will be remapped
                * otherwise.
                *
                * As per the ECMA and JSON specification an object may
                * have any string as a property. Unfortunately due to
                * a hole in the ECMA specification if the key is a
                * ECMA reserved word or starts with a digit the
                * parameter is only accessible using ECMAScript's
                * bracket notation.
                */

                // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map(array($this, 'name_value'),
                                            array_keys($var),
                                            array_values($var));

                    foreach($properties as $property) {
                        if(Services_JSON::isError($property)) {
                            return $property;
                        }
                    }

                    return '{' . join(',', $properties) . '}';
                }

                // treat it like a regular array
                $elements = array_map(array($this, 'encode'), $var);

                foreach($elements as $element) {
                    if(Services_JSON::isError($element)) {
                        return $element;
                    }
                }

                return '[' . join(',', $elements) . ']';

            case 'object':
                $vars = get_object_vars($var);

                $properties = array_map(array($this, 'name_value'),
                                        array_keys($vars),
                                        array_values($vars));

                foreach($properties as $property) {
                    if(Services_JSON::isError($property)) {
                        return $property;
                    }
                }

                return '{' . join(',', $properties) . '}';

            default:
                return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
        }
    }

   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    function name_value($name, $value)
    {
        $encoded_value = $this->encode($value);

        if(Services_JSON::isError($encoded_value)) {
            return $encoded_value;
        }

        return $this->encode(strval($name)) . ':' . $encoded_value;
    }

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(

                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',

                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',

                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'

            ), '', $str);

        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function decode($str)
    {
        $str = $this->reduce_string($str);

        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;

            default:
                $m = array();

                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;

                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c < $strlen_chrs; ++$c) {

                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});

                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;

                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= $this->utf162utf8($utf16);
                                $c += 5;
                                break;

                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;

                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;

                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;

                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;

                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;

                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }

                    return $utf8;

                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(SERVICES_JSON_IN_ARR);
                        $arr = array();
                    } else {
                        if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = array();
                        } else {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = new stdClass();
                        }
                    }

                    array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = $this->reduce_string($chrs);

                    if ($chrs == '') {
                        if (reset($stk) == SERVICES_JSON_IN_ARR) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    //print("\nparsing {$chrs}\n");

                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c <= $strlen_chrs; ++$c) {

                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);

                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == SERVICES_JSON_IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, $this->decode($slice));

                            } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                $parts = array();
                                
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = $this->decode($parts[1]);
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;

                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);

                            //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        }

                    }

                    if (reset($stk) == SERVICES_JSON_IN_ARR) {
                        return $arr;

                    } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                        return $obj;

                    }

                }
        }
    }

    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    function isError($data, $code = null)
    {
        if (class_exists('pear')) {
            return PEAR::isError($data, $code);
        } elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
                                 is_subclass_of($data, 'services_json_error'))) {
            return true;
        }

        return false;
    }
}

if (class_exists('PEAR_Error')) {

    class Services_JSON_Error extends PEAR_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }
    }

} else {

    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class Services_JSON_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {

        }
    }

}

}

?>