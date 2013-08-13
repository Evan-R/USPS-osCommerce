<?php  
/*
 $Id: usps.php 5.3.0 - 2.2. RC2a  and 2.3 compatible; php 5.3 compatible
+++++ Original contribution by Brad Waite and Fritz Clapp ++++
++++ Revisions and Modifications made by Greg Deeth, 2008 ++++
Copyright 2008 osCommerce
Released under the GNU General Public License
//VERSION: 5.2.1 Updated to July 28 2013 Changes

	Version 5.3.0
		@sponsor: Til Luchau (advanced-trainings.com)
		@author: Evan Roberts (evan.aedea@gmail.com / github.com/Evan-R/)
		@notes:
			- fixed handling rates not being added
			- fixed processing time not being added to estimates
			- added field to specify delivery times in lieu of estimate provided by USPS
		@license
			- USE AT YOUR OWN RISK. NO WARRANTY OF ANY KIND. AUTHOR AND/OR SPONSOR ARE NOT
			RESPONSIBLE FOR ANY CONSEQUENCES OF USING THIS CODE. BY USING THIS CODE, YOU 
			ASSUME FULL RESPONSIBILITY FOR ANY CONSEQUENCES, INCLUDING BUT NOT LIMITED TO
			DATA LOSS OR SECURITY LIABILITIES THAT MAY ARISE
			
			FULL LICENSE: https://github.com/Evan-R/USPS-osCommerce/blob/master/LICENSE

*/

/////////////////////////////////////////
////////// Sets up USPS Class ///////////
/////////////////////////////////////////

class usps {

	/////////////////////////////////////////
	///////////// Sets Variables ////////////
	/////////////////////////////////////////

	public 
		$code, 
		$title, 
		$description, 
		$icon, 
		$enabled, 
		$countries,
		$sort_order,
		$tax_class,
		$processing,
		$dmstc_handling,
		$dmstc_delivery_time_lieu_estimate,
		$intl_handling,
		$sig_conf_thresh,
		$types,
		$intl_types
	;

	function __construct(){
		global $order;
		
		$this->code = 'usps';
		$this->title = MODULE_SHIPPING_USPS_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_USPS_TEXT_DESCRIPTION;
		$this->icon = DIR_WS_ICONS . 'shipping_usps.gif';
		$this->enabled = ((MODULE_SHIPPING_USPS_STATUS == 'True') ? true : false);
		
		if( ($this->enabled == true) && ((int)MODULE_SHIPPING_USPS_ZONE > 0) ){
			$check_flag = false;
			$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_USPS_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
			while( $check = tep_db_fetch_array($check_query) ){
				if( $check['zone_id'] < 1 ){
					$check_flag = true;
					break;
				} elseif( $check['zone_id'] == $order->delivery['zone_id'] ){
					$check_flag = true;
					break;
				}
			}
			if( $check_flag == false ){
				$this->enabled = false;
			}
		}
		
		$this->countries = $this->country_list();
		$this->sort_order = MODULE_SHIPPING_USPS_SORT_ORDER;
		$this->tax_class = MODULE_SHIPPING_USPS_TAX_CLASS;
		$this->processing = MODULE_SHIPPING_USPS_PROCESSING;
		$this->dmstc_handling = explode( ", ", MODULE_SHIPPING_USPS_DMSTC_HANDLING);
		$this->dmstc_delivery_time_lieu_estimate = explode( ", ", MODULE_SHIPPING_USPS_DMSTC_DELIVERY_TIME_LIEU_ESTIMATE);
		$this->intl_handling = explode( ", ", MODULE_SHIPPING_USPS_INTL_HANDLING);
		$this->sig_conf_thresh = MODULE_SHIPPING_USPS_SIG_THRESH;
		$this->types = array(
			'First-Class Mail regimark' => 'First-Class Mail regimark',
			'First-Class Mail regimark Letter' => 'First-Class Mail regimark Letter',
			'Media Mail regimark' => 'Media Mail regimark',
			'Standard Post regimark' => 'Standard Post regimark',
			'Priority Mail tradmrk' => 'Priority Mail tradmrk',
			'Priority Mail tradmrk Flat Rate Envelope' => 'Priority Mail tradmrk Flat Rate Envelope',
			'Priority Mail tradmrk Small Flat Rate Box' => 'Priority Mail tradmrk Small Flat Rate Box',
			'Priority Mail tradmrk Medium Flat Rate Box' => 'Priority Mail tradmrk Medium Flat Rate Box',
			'Priority Mail tradmrk Large Flat Rate Box' => 'Priority Mail tradmrk Large Flat Rate Box',
			'Priority Mail Express tradmrk' => 'Priority Mail Express tradmrk',
			'Priority Mail Express tradmrk Flat Rate Envelope' => 'Priority Mail Express tradmrk Flat Rate Envelope'
		);
		$this->intl_types = array(
			'Global Express' => 'Global Express Guaranteed regimark (GXG)**',
			'Global Express Non-Doc Rect' => 'Global Express Guaranteed regimark Non-Document Rectangular',
			'Global Express Non-Doc Non-Rect' => 'Global Express Guaranteed regimark Non-Document Non-Rectangular',
			'USPS GXG Envelopes' => 'USPS GXG tradmrk Envelopes**',
			'Priority Mail Express Int' => 'Priority Mail Express International tradmrk',
			'Priority Mail Express Int Flat Rate Env' => 'Priority Mail Express International tradmrk Flat Rate Envelope',
			'Priority Mail International' => 'Priority Mail International regimark',
			'Priority Mail Int Flat Rate Lrg Box' => 'Priority Mail International regimark Large Flat Rate Box',
			'Priority Mail Int Flat Rate Med Box' => 'Priority Mail International regimark Medium Flat Rate Box',
			'Priority Mail Int Flat Rate Small Box' => 'Priority Mail International regimark Small Flat Rate Box**',
			'Priority Mail Int Flat Rate Env' => 'Priority Mail International regimark Flat Rate Envelope**',
			'First-Class Mail Int Lrg Env' => 'First-Class Mail regimark International Large Envelope**',
			'First-Class Mail Int Package' => 'First-Class Package International Service tradmrk**',
			'First-Class Mail Int Letter' => 'First-Class Mail International regimark Letter**'
		);
	}

	/////////////////////////////////////////
	///////////// Ends Variables ////////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	/////////// Sets Country List ///////////
	/////////////////////////////////////////

	function country_list()
	{
		$list = array(
			'AF' => 'Afghanistan',
			'AX' => 'Aland Island (Finland)',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire (Netherlands Antilles)',
			'BA' => 'Bosnia-Herzegovina',
			'BW' => 'Botswana',
			'BR' => 'Brazil',
			'VG' => 'British Virgin Islands',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'MM' => 'Burma',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island (Australia)',
			'CC' => 'Cocos Island (Australia)',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo, Republic of the',
			'CD' => 'Congo, Democratic Republic of the',
			'CK' => 'Cook Islands (New Zealand)',
			'CR' => 'Costa Rica',
			'CI' => 'Cote d\'Ivoire',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CW' => 'Curacao (Netherlands Antilles)',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TL' => 'East Timor (Indonesia)',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia, Republic of',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GB' => 'Great Britain and Northern Ireland',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man (Great Britain and Northern Ireland)',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey (Channel Islands) (Great Britain and Northern Ireland)',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia, Republic of',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte (France)',
			'MX' => 'Mexico',
			'MD' => 'Moldova',
			'MC' => 'Monaco (France)',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Island',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy (Guadeloupe)',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts (Saint Christopher and Nevis)',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French) (Guadeloupe)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia, Republic of',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SX' => 'Saint Maarten (Dutch) (Netherlands Antilles)',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia (Falkland Islands)',
			'KR' => 'South Korea (Korea, Republic of)',
			'SS' => 'Sudan', // South Sudan, not listed separately from Sudan by USPS
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TG' => 'Togo',
			'TK' => 'Tokelau (Union Group) (Western Samoa)',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican City',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'WF' => 'Wallis and Futuna Islands',
			'WS' => 'Western Samoa',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe');
		return $list;
	}

	/////////////////////////////////////////
	/////////// Ends Country List ///////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	////////// Sets Quote ///////////////////
	/////////////////////////////////////////

	function quote($method = '')
	{
		global $order, $shipping_weight, $shipping_num_boxes, $transittime, $dispinsure;
		//&& (in_array($method, $this->types) || in_array($method, $this->intl_types))
		if ( tep_not_null($method) && $method!='First-Class Mail regimark Parcel') {
			$this->_setService($method);
		}
		if ($shipping_weight <= 0) {
			$shipping_weight = 0;
		}
		$shipping_weight = ($shipping_weight < 0.0625 ? 0.0625 : $shipping_weight);
		$shipping_pounds = floor ($shipping_weight);
		$shipping_ounces = tep_round_up((16 * ($shipping_weight - floor($shipping_weight))), 2);
		$this->_setWeight($shipping_pounds, $shipping_ounces, $shipping_weight);
		if (in_array('Display weight', explode(', ', MODULE_SHIPPING_USPS_OPTIONS))) {
			$shiptitle = $shipping_pounds . ' lbs, ' . $shipping_ounces . ' oz';
		} else {
			$shiptitle = '';
		}
		$uspsQuote = $this->_getQuote();
		if (is_array($uspsQuote)) {
			if (isset($uspsQuote['error'])) {
				$this->quotes = array('module' => $this->title,
					'error' => $uspsQuote['error']);
			} else {
				$this->quotes = array('id' => $this->code,
					'module' => $this->title . $shiptitle);
				$methods = array();
				$size = sizeof($uspsQuote);


				//echo 'qqq<pre>';
				//print_r($uspsQuote);

				for ($i=0; $i<$size; $i++) {
					list($type, $cost) = each($uspsQuote[$i]);
					$title = ((isset($this->types[$type])) ? $this->types[$type] : $type);
					$title .= $transittime[$type] . $dispinsure[$type];

					$title = str_replace(' regimark','',$title);
					$title = str_replace(' tradmrk','',$title);

					$title = str_replace('&lt;sup&gt;&#174;&lt;/sup&gt;','',$title);
					$title = str_replace('&lt;sup&gt;&#8482;&lt;/sup&gt;','',$title);

					$title = str_replace(array(' 1-Day',' 2-Day',' 3-Day',' Military',' DPM'),'',$title);
					$title = str_replace('sup/sup','',preg_replace('/&([^`]*?);/','',$title));



					$type = str_replace(array(' 1-Day',' 2-Day',' 3-Day',' Military',' DPM'),'',$type);
					$type = str_replace('&lt;sup&gt;&#174;&lt;/sup&gt;',' regimark',$type);
					$type = str_replace('&lt;sup&gt;&#8482;&lt;/sup&gt;',' tradmrk',$type);
					$type = str_replace('sup/sup','',preg_replace('/&([^`]*?);/','',$type));

					if($type=='First-Class Mail')
					{
						$type .=' regimark';
					}


					if (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') {
						$methods[] = array('id' => $type,
							'title' => $title,
							'cost' => ($cost + $insurance + $handling_cost[0]) * $shipping_num_boxes);
					} else {
						$methods[] = array('id' =>$type,
							'title' => $title,
							'cost' => ($cost + $handling_cost[0]) * $shipping_num_boxes);
					}
				}
				$this->quotes['methods'] = $methods;
				if ($this->tax_class > 0) {
					$this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
				}
			}
		} else {
			$this->quotes = array('module' => $this->title,
				'error' => MODULE_SHIPPING_USPS_TEXT_ERROR);
		}
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);
		return $this->quotes;
	}
	function check() {
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_USPS_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}
		return $this->_check;
	}

	/////////////////////////////////////////
	////////// Ends Quote ///////////////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	//////////// Install Module /////////////
	/////////////////////////////////////////

	function install()
	{
		tep_db_query("ALTER TABLE `configuration` CHANGE `configuration_value` `configuration_value` TEXT NOT NULL, CHANGE `set_function` `set_function` TEXT NULL DEFAULT NULL");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable USPS Shipping', 'MODULE_SHIPPING_USPS_STATUS', 'True', 'Do you want to offer USPS shipping?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enter the USPS User ID', 'MODULE_SHIPPING_USPS_USERID', 'NONE', 'Enter the USPS USERID assigned to you. <u>You must contact USPS to have them switch you to the Production server.</u>  Otherwise this module will not work!', '6', '3', 'tep_cfg_multiinput_list(array(\'\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_USPS_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_USPS_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_USPS_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Options', 'MODULE_SHIPPING_USPS_OPTIONS', 'Display weight, Display transit time, Display insurance', 'Select display options', '6', '0', 'tep_cfg_select_multioption(array(\'Display weight\', \'Display transit time\', \'Display insurance\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Processing Time', 'MODULE_SHIPPING_USPS_PROCESSING', '1', 'Days to Process Order', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Shipping Methods', 'MODULE_SHIPPING_USPS_DMSTC_TYPES', 'First-Class Mail regimark, First-Class Mail regimark Letter, Media Mail regimark, Standard Post regimark, Priority Mail tradmrk, Priority Mail tradmrk Flat Rate Envelope, Priority Mail tradmrk Small Flat Rate Box, Priority Mail tradmrk Medium Flat Rate Box, Priority Mail tradmrk Large Flat Rate Box, Priority Mail Express tradmrk, Priority Mail Express tradmrk Flat Rate Envelope', 'Select the domestic services to be offered:', '6', '4', 'tep_cfg_select_multioption(array(\'First-Class Mail regimark\', \'First-Class Mail regimark Letter\', \'Media Mail regimark\', \'Standard Post regimark\', \'Priority Mail tradmrk\', \'Priority Mail tradmrk Flat Rate Envelope\', \'Priority Mail tradmrk Small Flat Rate Box\', \'Priority Mail tradmrk Medium Flat Rate Box\', \'Priority Mail tradmrk Large Flat Rate Box\', \'Priority Mail Express tradmrk\', \'Priority Mail Express tradmrk Flat Rate Envelope\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Rates', 'MODULE_SHIPPING_USPS_DMSTC_RATE', 'Retail', 'Charge retail pricing or internet pricing?', '6', '0', 'tep_cfg_select_option(array(\'Retail\', \'Internet\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Delivery Confirmation', 'MODULE_SHIPPING_USPS_DMST_DEL_CONF', 'True', 'Add Delivery Confirmation Charge, if any ($0.90)?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Signature Confirmation', 'MODULE_SHIPPING_USPS_DMST_SIG_CONF', 'True', 'Automatically charge Signature Confirmation when available ($2.70)?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Signature Confirmation Threshold', 'MODULE_SHIPPING_USPS_SIG_THRESH', '100', 'Order total required before Signature Confirmation is triggered?', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Insurance Options', 'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION', 'False', 'Force USPS Calculated Domestic Insurance?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		
		// domestic handling rates..
		tep_db_query("
			insert into 
				" . TABLE_CONFIGURATION . " 
			(
				configuration_title, 
				configuration_key, 
				configuration_value, 
				configuration_description, 
				configuration_group_id, 
				sort_order, 
				set_function, 
				date_added
			) 
				values 
			(
				'Domestic Flat Handling Fees', 
				'MODULE_SHIPPING_USPS_DMSTC_HANDLING', 
				'0, 0, 0, 0, 0, 0, 0, 0, 0, 0', 
				'Add a different handling fee for each shipping type.', 
				'6', 
				'0', 
				'tep_cfg_multiinput_list(array(
					\'First-Class\', 
					\'Media\', 
					\'Standard (aka Parcel)\', 
					\'Priority\', 
					\'Priority Flat Env\', 
					\'Priority Sm Flat Box\', 
					\'Priority Med Flat Box\', 
					\'Priority Lg Flat Box\', 
					\'Priority Mail Express\', 
					\'Priority Express Flat Env\'
				),', 
				now()
			)
		");
		
		// domestic delivery times in lieu of estimate
		tep_db_query("
			insert into
				" . TABLE_CONFIGURATION . "
			(
				configuration_title,
				configuration_key,
				configuration_value,
				configuration_description,
				configuration_group_id,
				sort_order,
				set_function,
				date_added
			)
				values
			(
				'Domestic Delivery Time In Lieu of Estimate',
				'MODULE_SHIPPING_USPS_DMSTC_DELIVERY_TIME_LIEU_ESTIMATE',
				'0, 10-14 business days, 6-12 business days, 0, 0, 0, 0, 0, 0, 0',
				'Specifiy a delivery time (eg: 5-10 business days) | these do not take into account processing time',
				'6',
				'0',
				'tep_cfg_multiinput_list(array(
					\'First-Class\',
					\'Media\',
					\'Standard (aka Parcel)\',
					\'Priority\',
					\'Priority Flat Env\',
					\'Priority Sm Flat Box\',
					\'Priority Med Flat Box\',
					\'Priority Lg Flat Box\',
					\'Priority Mail Express\',
					\'Priority Express Flat Env\'
				),',
				now()
			)
		");
		
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic First-Class Threshold', 'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_THRESHOLD', '0, 3.5, 3.5, 10, 10, 13', '<u>Maximums:</u><br>Letters 3.5oz<br>Large envelopes and parcels 13oz', '6', '0', 'tep_cfg_multiinput_duallist_oz(array(\'Letter\', \'Lg Env\', \'Package\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Other Mail Threshold', 'MODULE_SHIPPING_USPS_DMSTC_OTHER_THRESHOLD', '0, 3, 0, 3, 3, 11, 11, 15, 0, 70, 0, 3, 0, 70, 0, 70, 0, 70', '<u>Maximums:</u><br>70 lb', '6', '0', 'tep_cfg_multiinput_duallist_lb(array(\'Flat Rate Envelope\', \'Sm Flat Rate Box\', \'Md Flat Rate Box\', \'Lg Flat Rate Box\', \'Standard Priority\', \'Express FltRt Env\', \'Express Standard\', \'Parcel Pst\', \'Media Mail\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Int\'l Shipping Methods', 'MODULE_SHIPPING_USPS_INTL_TYPES', 'Global Express, Global Express Non-Doc Rect, Global Express Non-Doc Non-Rect, USPS GXG Envelopes, Priority Mail Express Int, Priority Mail Express Int Flat Rate Env, Priority Mail International, Priority Mail Int Flat Rate Env, Priority Mail Int Flat Rate Small Box, Priority Mail Int Flat Rate Med Box, Priority Mail Int Flat Rate Lrg Box, First-Class Mail Int Lrg Env, First-Class Mail Int Package, First-Class Mail Int Letter', 'Select the international services to be offered:', '6', '0', 'tep_cfg_select_multioption(array(\'Global Express\', \'Global Express Non-Doc Rect\', \'Global Express Non-Doc Non-Rect\', \'USPS GXG Envelopes\', \'Priority Mail Express Int\', \'Priority Mail Express Int Flat Rate Env\', \'Priority Mail International\', \'Priority Mail Int Flat Rate Env\', \'Priority Mail Int Flat Rate Small Box\', \'Priority Mail Int Flat Rate Med Box\', \'Priority Mail Int Flat Rate Lrg Box\', \'First-Class Mail Int Lrg Env\', \'First-Class Mail Int Package\', \'First-Class Mail Int Letter\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Int\'l Rates', 'MODULE_SHIPPING_USPS_INTL_RATE', 'Retail', 'Charge retail pricing or internet pricing?', '6', '0', 'tep_cfg_select_option(array(\'Retail\', \'Internet\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Int\'l Insurance Options', 'MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION', 'False', 'Force USPS Calculated International Insurance?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Int\'l Flat Handling Fees', 'MODULE_SHIPPING_USPS_INTL_HANDLING', '0', 'Add a flat fee international shipping.', '6', '0', 'tep_cfg_multiinput_list(array(\'\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Int\'l Package Sizes', 'MODULE_SHIPPING_USPS_INTL_SIZE', '1, 1, 1, 0', 'Standard package dimensions required by USPS for international rates', '6', '0', 'tep_cfg_multiinput_list(array(\'Width\', \'Length\', \'Height\', \'Girth\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Non USPS Insurance - Domestic and international', 'MODULE_SHIPPING_USPS_INSURE', 'False', 'Would you like to charge insurance for packages independent of USPS, i.e, merchant provided, Stamps.com, Endicia?  If used in conjunction with USPS calculated insurance, the higher of the two will apply.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS1', '1.75', 'Totals $.01-$50.00', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS2', '2.25', 'Totals $50.01-$100', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS3', '2.75', 'Totals $100.01-$200', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS4', '4.70', 'Totals $200.01-$300', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS5', '1.00', 'For every $100 over $300 (add)', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Insure Tax?', 'MODULE_SHIPPING_USPS_INSURE_TAX', 'False', 'Would you like to insure sales tax paid by the customer?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	}

	function keys() {
		return array(
			'MODULE_SHIPPING_USPS_STATUS', 
			'MODULE_SHIPPING_USPS_USERID', 
			'MODULE_SHIPPING_USPS_SORT_ORDER', 
			'MODULE_SHIPPING_USPS_TAX_CLASS', 
			'MODULE_SHIPPING_USPS_ZONE', 
			'MODULE_SHIPPING_USPS_OPTIONS', 
			'MODULE_SHIPPING_USPS_PROCESSING', 
			'MODULE_SHIPPING_USPS_DMSTC_TYPES', 
			'MODULE_SHIPPING_USPS_DMSTC_RATE', 
			'MODULE_SHIPPING_USPS_DMST_DEL_CONF', 
			'MODULE_SHIPPING_USPS_DMST_SIG_CONF', 
			'MODULE_SHIPPING_USPS_SIG_THRESH', 
			'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION', 
			'MODULE_SHIPPING_USPS_DMSTC_HANDLING',
			'MODULE_SHIPPING_USPS_DMSTC_DELIVERY_TIME_LIEU_ESTIMATE', 
			'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_THRESHOLD', 
			'MODULE_SHIPPING_USPS_DMSTC_OTHER_THRESHOLD', 
			'MODULE_SHIPPING_USPS_INTL_TYPES', 
			'MODULE_SHIPPING_USPS_INTL_RATE', 
			'MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION', 
			'MODULE_SHIPPING_USPS_INTL_HANDLING', 
			'MODULE_SHIPPING_USPS_INTL_SIZE', 
			'MODULE_SHIPPING_USPS_INSURE', 
			'MODULE_SHIPPING_USPS_INS1', 
			'MODULE_SHIPPING_USPS_INS2', 
			'MODULE_SHIPPING_USPS_INS3',
			'MODULE_SHIPPING_USPS_INS4', 
			'MODULE_SHIPPING_USPS_INS5', 
			'MODULE_SHIPPING_USPS_INSURE_TAX'
		);
	}

	/////////////////////////////////////////
	///////// End Install Module ////////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	///////////// Remove Module /////////////
	//////////////////////////////////////////

	function remove()
	{
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	/////////////////////////////////////////
	////////// End Remove Module ////////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	//////////// SET DEFAULTS ///////////////
	/////////////////////////////////////////

	function _setService($service) {
		$this->service = $service;
		$this->service = str_replace('First-Class Mail regimark Letter','First-Class Mail regimark',$this->service);
	}

	function _setWeight($pounds, $ounces=0, $weight) {
		$this->pounds = $pounds;
		$this->ounces = $ounces;
		$this->weight = $weight;
	}

	function _setMachinable($machinable) {
		$this->machinable = $machinable;
	}

	/////////////////////////////////////////
	//////////// END DEFAULTS ///////////////
	/////////////////////////////////////////

	/////////////////////////////////////////
	/////////// START RATE REQUEST //////////
	/////////////////////////////////////////

	function _getQuote()
	{
		global $order, $transittime, $dispinsure;
		if (MODULE_SHIPPING_USPS_INSURE_TAX) {
			$insurable = $order->info['subtotal'] + $order->info['tax'];
		}
		else {$insurable = $order->info['subtotal'];
		}
		$transit = (in_array('Display transit time', explode(', ', MODULE_SHIPPING_USPS_OPTIONS)));
		$dispinsurance = (in_array('Display insurance', explode(', ', MODULE_SHIPPING_USPS_OPTIONS)));
		$Authentication = explode( ", ", MODULE_SHIPPING_USPS_USERID);
		if ($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY)


			 

			/////////////////////////////////////////
			////// START USPS DOMESTIC REQUEST //////
			/////////////////////////////////////////

		{

			$request  = '<RateV4Request USERID="' . $Authentication[0] . '">' .
					'<Revision>2</Revision>';
			$services_count = 0;

			//echo 'service = ' . $this->service;

			if (isset($this->service)) {
				$this->types = array($this->service => $this->types[$this->service]);
			}

			$domesticHandlingRates = array();
			$domesticDeliveryTimesLieuEstimate = array();

			$dest_zip = str_replace(' ', '', $order->delivery['postcode']);
			if ($order->delivery['country']['iso_code_2'] == 'US') $dest_zip = substr($dest_zip, 0, 5);
			reset($this->types);

			/////////////////////////////////////////
			//// REQUEST IF WITHIN ALLOWED LIST /////
			/////////////////////////////////////////

			$allowed_types = explode(", ", MODULE_SHIPPING_USPS_DMSTC_TYPES);

			//echo '<pre>';
			//print_r($this->types);
			//print_r($allowed_types);

			//exit();



			while (list($key, $value) = each($this->types)) {
				if ( !in_array($key, $allowed_types) ) continue;


				//echo $key . '<br>';;

				/////////////////////////////////////////
				// REQUEST IF WITHIN WEIGHT THRESHOLDS //
				/////////////////////////////////////////

				$FMT = explode(", ", MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_THRESHOLD);
				if ($key == 'First-Class Mail regimark'){
					$transid = $key;
					$key = 'First-Class Mail';
					if($this->pounds == 0) {
						if($FMT[0] < $this->ounces && $this->ounces <= $FMT[1]) {
							$transid = 'First-Class Mail regimark Letter';
							$this->FirstClassMailType = 'LETTER';
							$this->machinable = 'TRUE';
							$this->size = 'REGULAR';
							$this->container = 'VARIABLE';
						} else if($FMT[2] < $this->ounces && $this->ounces <= $FMT[3]) {
							$transid = 'First-Class Mail regimark Large Envelope';
							$this->FirstClassMailType = 'FLAT';
							$this->machinable = 'TRUE';
							$this->size = 'REGULAR';
							$this->container = 'VARIABLE';
						} else if($FMT[4] < $this->ounces && $this->ounces <= $FMT[5]) {
							$transid = 'First-Class Mail regimark Parcel';
							$this->FirstClassMailType = 'PARCEL';
							$this->machinable = 'TRUE';
							$this->size = 'REGULAR';
							$this->container = 'VARIABLE';
						} else {
							$key = 'none';
						}
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[0];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[0];
				}

				$OMT = explode(", ", MODULE_SHIPPING_USPS_DMSTC_OTHER_THRESHOLD);
				if ($key == 'Priority Mail tradmrk'){
					$transid = $key;
					if ($OMT[8] < $this->weight && $this->weight <= $OMT[9]) {
						$key = 'Priority Commercial';
						$this->container = '';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
						
					$domesticHandlingRates[$transid] = $this->dmstc_handling[3];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[3];
				}

				if ($key == 'Priority Mail tradmrk Flat Rate Envelope'){
					$transid = $key;
					if ($OMT[0] < $this->weight && $this->weight <= $OMT[1]) {
						$key = 'Priority Commercial';
						$this->container = 'FLAT RATE ENVELOPE';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[4];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[4];
				}
				
				if ($key == 'Priority Mail tradmrk Small Flat Rate Box'){
					$transid = $key;
					if ($OMT[2] < $this->weight && $this->weight <= $OMT[3]) {
						$key = 'Priority Commercial';
						$this->container = 'SM FLAT RATE BOX';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[5];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[5];
				}
				
				if ($key == 'Priority Mail tradmrk Medium Flat Rate Box'){
					$transid = $key;
					if ($OMT[4] < $this->weight && $this->weight <= $OMT[5]) {
						$key = 'Priority Commercial';
						$this->container = 'MD FLAT RATE BOX';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[6];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[6];
				}
				
				if ($key == 'Priority Mail tradmrk Large Flat Rate Box'){
					$transid = $key;
					if ($OMT[6] < $this->weight && $this->weight <= $OMT[7]) {
						$key = 'Priority Commercial';
						$this->container = 'LG FLAT RATE BOX';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[7];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[7];
				}
				
				if ($key == 'Priority Mail Express tradmrk'){
					$transid = $key;
					if ($OMT[12] < $this->weight && $this->weight <= $OMT[13]) {
						$this->container = '';
						$key = 'Express Commercial';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[8];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[8];
				}
				
				if ($key == 'Priority Mail Express tradmrk Flat Rate Envelope'){
					$transid = $key;
					if ($OMT[10] < $this->weight && $this->weight <= $OMT[11]) {
						$key = 'Express Commercial';
						$this->container = 'FLAT RATE ENVELOPE';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[9];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[9];
				}
				
				if ($key == 'Standard Post regimark'){
					$transid = $key;
					$key = 'Standard Post';
					if ($OMT[14] < $this->weight && $this->weight <= $OMT[15]){
						$this->machinable = 'TRUE';
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[2];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[2];
				}

				if ($key == 'Media Mail regimark'){
					$transid = $key;
					$key = 'Media Mail';
					if ($OMT[16] < $this->weight && $this->weight <= $OMT[17]){
						$this->size = 'REGULAR';
					} else {
						$key = 'none';
					}
					
					$domesticHandlingRates[$transid] = $this->dmstc_handling[1];
					$domesticDeliveryTimesLieuEstimate[$transid] = $this->dmstc_delivery_time_lieu_estimate[1];
				}

				/////////////////////////////////////////////
				// END REQUEST IF WITHIN WEIGHT THRESHOLDS //
				/////////////////////////////////////////////

				$request .=
				'<Package ID="' . $services_count . '">' .
				'<Service>' . $key . '</Service>' .
				'<FirstClassMailType>' . $this->FirstClassMailType . '</FirstClassMailType>' .
				'<ZipOrigination>' . SHIPPING_ORIGIN_ZIP . '</ZipOrigination>' .
				'<ZipDestination>' . $dest_zip . '</ZipDestination>' .
				'<Pounds>' . $this->pounds . '</Pounds>' .
				'<Ounces>' . $this->ounces . '</Ounces>' .
				'<Container>' . $this->container . '</Container>' .
				'<Size>' . $this->size . '</Size>' .
				'<Value>' . $insurable . '</Value>' .
				'<Machinable>' . $this->machinable . '</Machinable>' .
				'<ShipDate>' . date('d-M-Y') . '</ShipDate>' .
				'</Package>';

				/////////////////////////////////////////
				////// START USPS TRANSIT REQUEST ///////
				/////////////////////////////////////////

				if($transit)
				{
					$transitreq  = 'USERID="' . $Authentication[0] . '">' .
							'<OriginZip>' . SHIPPING_ORIGIN_ZIP . '</OriginZip>' .
							'<DestinationZip>' . $dest_zip . '</DestinationZip>';
					switch ($key)
					{
						case 'First-Class Mail':   $transreq[$transid] = 'API=PriorityMail&XML=' .
								urlencode( '<PriorityMailRequest ' . $transitreq . '</PriorityMailRequest>');
								break;
						case 'Media Mail':   $transreq[$transid] = 'API=StandardB&XML=' .
								urlencode( '<StandardBRequest ' . $transitreq . '</StandardBRequest>');
								break;
						case 'Standard Post':   $transreq[$transid] = 'API=StandardB&XML=' .
								urlencode( '<StandardBRequest ' . $transitreq . '</StandardBRequest>');
								break;
						case 'Priority Commercial': $transreq[$transid] = 'API=PriorityMail&XML=' .
								urlencode( '<PriorityMailRequest ' . $transitreq . '</PriorityMailRequest>');
								break;
						default:        $transreq[$transid] = '';
						break;
					}
				}

				/////////////////////////////////////////
				//////// END USPS TRANSIT REQUEST ///////
				/////////////////////////////////////////

				$services_count++;
			}
			
// 			print_r( $domesticHandlingRates );

			/////////////////////////////////////////
			////// END IF WITHIN ALLOWED LIST ///////
			/////////////////////////////////////////

			$request .= '</RateV4Request>';
			$request =      'API=RateV4&XML=' . urlencode($request);
		}

		/////////////////////////////////////////
		/////// END USPS DOMESTIC REQUEST ///////
		/////////////////////////////////////////

		else

			/////////////////////////////////////////
			//// START USPS INTERNATIONAL REQUEST ///
			/////////////////////////////////////////

		{
			$request =      '<IntlRateV2Request USERID="' . $Authentication[0] . '">' .
					'<Revision>2</Revision>' .
					'<Package ID="0">' .
					'<Pounds>' . $this->pounds . '</Pounds>' .
					'<Ounces>' . $this->ounces . '</Ounces>' .
					'<Machinable>True</Machinable>' .
					'<MailType>All</MailType>' .
					'<GXG>' .
					'<POBoxFlag>N</POBoxFlag>' .
					'<GiftFlag>N</GiftFlag>' .
					'</GXG>' .
					'<ValueOfContents>' . $insurable . '</ValueOfContents>' .
					'<Country>' . $this->countries[$order->delivery['country']['iso_code_2']] . '</Country>' .
					'<Container>RECTANGULAR</Container>' .
					'<Size>REGULAR</Size>' ;
			$IPS = explode(", ", MODULE_SHIPPING_USPS_INTL_SIZE);
			$request .= '<Width>' . $IPS[0] . '</Width>' .
					'<Length>' . $IPS[1] . '</Length>' .
					'<Height>' . $IPS[2] . '</Height>' .
					'<Girth>' . $IPS[3] . '</Girth>' .
					'<OriginZip>' . SHIPPING_ORIGIN_ZIP . '</OriginZip>' .
					'<CommercialFlag>Y</CommercialFlag>' .
					'<ExtraServices>' .
					'<ExtraService>1</ExtraService>' .
					'<ExtraService>2</ExtraService>' .
					'</ExtraServices>' .
					'</Package>' .
					'</IntlRateV2Request>';
			$request =      'API=IntlRateV2&XML=' . urlencode($request);
		}

		/////////////////////////////////////////
		//// END USPS INTERNATIONAL REQUEST /////
		/////////////////////////////////////////

		/////////////////////////////////////////
		/////// USPS HTTP COMMUNICATION /////////
		/////////////////////////////////////////

		$usps_server = 'production.shippingapis.com';
		//            $usps_server = 'stg-production.shippingapis.com';
		//      $usps_server = 'testing.shippingapis.com';

		$api_dll = 'ShippingAPI.dll';
		$body = '';
		if (!class_exists('httpClient')) {
			include('includes/classes/http_client.php');
		}
		$http = new httpClient();
		if ($http->Connect($usps_server, 80)) {
			$http->addHeader('Host', $usps_server);
			$http->addHeader('User-Agent', 'osCommerce');
			$http->addHeader('Connection', 'Close');
			if ($http->Get('/' . $api_dll . '?' . $request)) $body = $http->getBody();
			//              mail('user@localhost.com','USPS Rate Quote response',$body,'From: <user@localhost.com>');
			if ($transit && is_array($transreq) && ($order->delivery['country']['id'] == STORE_COUNTRY)) {
				while (list($key, $value) = each($transreq)) {
					if ($http->Get('/' . $api_dll . '?' . $value)) $transresp[$key] = $http->getBody();
					//              mail('user@localhost.com','USPS Transit Response',$transresp[$key],'From: <user@localhost.com>');
				}
			}
			$http->Disconnect();
		} else {
			return false;
		}
		$body = htmlspecialchars_decode($body);
		$body = preg_replace('/\&lt;sup\&gt;\&amp;reg;\&lt;\/sup\&gt;/', ' regimark', $body);
		$body = preg_replace('/\&lt;sup\&gt;\&amp;trade;\&lt;\/sup\&gt;/', ' tradmrk', $body);

		$body = str_replace('&lt;sup&gt;&#174;&lt;/sup&gt;',' regimark',$body);
		$body = str_replace('&lt;sup&gt;&#8482;&lt;/sup&gt;',' tradmrk',$body);



		/////////////////////////////////////////
		/////END USPS HTTP COMMUNICATION ////////
		/////////////////////////////////////////

		/////////////////////////////////////////
		/////////// START RATE RESPONSE /////////
		/////////////////////////////////////////
		$response = array();
		while (true) {
			if ($start = strpos($body, '<Package ID=')) {

				$body = substr($body, $start);
				$end = strpos($body, '</Package>');
				$response[] = substr($body, 0, $end+10);
				$body = substr($body, $end+9);
			} else {
				break;
			}
		}

		//echo '<pre>';
		//print_r($response);


		$rates = array();
		$rates_sorter = array();
		if ($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) {

			/////////////////////////////////////////
			///////// START DOMESTIC RESPONSE ///////
			/////////////////////////////////////////

			if (sizeof($response) == '1') {
				if (preg_match('/<Error>/', $response[0])) {
					$number = preg_match('/<Number>(.*)<\/Number>/', $response[0], $regs);
					$number = $regs[1];
					$description = preg_match('/<Description>(.*)<\/Description>/', $response[0], $regs);
					$description = $regs[1];
					return array('error' => $number . ' - ' . $description);
				}
			}
			$n = sizeof($response);
			for ($i=0; $i<$n; $i++) {
				if (strpos($response[$i], '<Rate>'))
				{
					$service = preg_match('/<MailService>(.*)<\/MailService>/', $response[$i], $regs);
					$service = $regs[1];
					if ((MODULE_SHIPPING_USPS_DMSTC_RATE == 'Internet') && preg_match('/CommercialRate/', $response[$i]))
					{
						$postage = preg_match('/<CommercialRate>(.*)<\/CommercialRate>/', $response[$i], $regs);
						$postage = $regs[1];
					}
					else
					{       $postage = preg_match('/<Rate>(.*)<\/Rate>/', $response[$i], $regs);
					$postage = $regs[1];
					}
					if (preg_match('/Insurance<\/ServiceName><Available>true<\/Available><AvailableOnline>true/', $response[$i]))
					{
						$insurance = preg_match('/Insurance<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i], $regs);
						$insurance = $regs[1];
					}
					elseif (preg_match('/Insurance<\/ServiceName><Available>true<\/Available><AvailableOnline>false/', $response[$i]))
					{
						$insurance = preg_match('/Insurance<\/ServiceName><Available>true<\/Available><AvailableOnline>false<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i], $regs);
						$insurance = $regs[1];
					}
					else { $insurance = 0;
					}
					if ($insurable<=50)  {
						$uinsurance=MODULE_SHIPPING_USPS_INS1;
					}
					else if ($insurable<=100) {
						$uinsurance=MODULE_SHIPPING_USPS_INS2;
					}
					else if ($insurable<=200) {
						$uinsurance=MODULE_SHIPPING_USPS_INS3;
					}
					else if ($insurable<=300) {
						$uinsurance=MODULE_SHIPPING_USPS_INS4;
					}
					else {$uinsurance = MODULE_SHIPPING_USPS_INS4 + ((ceil($insurable/100) -3) * MODULE_SHIPPING_USPS_INS5);
					}
					if (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True' && MODULE_SHIPPING_USPS_INSURE == 'True')
					{
						$postage = $postage + max($insurance, $uinsurance);
					}
					elseif (MODULE_SHIPPING_USPS_INSURE == 'True')
					{
						$postage = $postage + $uinsurance;
					}
					elseif (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True' && $insurance > 0)
					{
						$postage = $postage + $insurance;
					}
					if ((MODULE_SHIPPING_USPS_DMST_DEL_CONF == 'True') && (preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available>/', $response[$i])))
					{
						if (MODULE_SHIPPING_USPS_DMSTC_RATE == 'Retail')
						{
							$del_conf = preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i], $regs);
						}
						elseif (MODULE_SHIPPING_USPS_DMSTC_RATE == 'Internet')
						{
							if (preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i]))
							{
								$del_conf = preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price><PriceOnline>(.*)<\/PriceOnline>/', $response[$i], $regs);
							}
							elseif (preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i]))
							{
								$del_conf = preg_match('/Delivery Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price><PriceOnline>(.*)<\/PriceOnline>/', $response[$i], $regs);
							}
						}
						$del_conf = $regs[1];
						$postage = $postage + $del_conf;
					}
					if ((MODULE_SHIPPING_USPS_DMST_SIG_CONF == 'True') && ($this->sig_conf_thresh <= $order->info['subtotal']) && (preg_match('/Signature Confirmation tradmrk<\/ServiceName><Available>true<\/Available>/', $response[$i])))
					{
						if (MODULE_SHIPPING_USPS_DMSTC_RATE == 'Retail')
						{
							$sig_conf = preg_match('/Signature Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price>/', $response[$i], $regs);
						}
						elseif (MODULE_SHIPPING_USPS_DMSTC_RATE == 'Internet')  /* was hardcoded to $2.30 */
						{$sig_conf = preg_match('/Signature Confirmation tradmrk<\/ServiceName><Available>true<\/Available><AvailableOnline>true<\/AvailableOnline><Price>(.*)<\/Price><PriceOnline>(.*)<\/PriceOnline>/', $response[$i], $regs);
						}
						$sig_conf = $regs[1];
						$postage = $postage + $sig_conf;
					}

					 
					$time = '';
					//  if($time = ereg('<CommitmentName>(.*)</CommitmentName>', $response[$i], $regs))
						
					if( $time = preg_match('/<CommitmentDate>(.*)<\/CommitmentDate>/', $response[$i], $regs) ){
						// add processing days to date
						if( $timestamp = strtotime($regs[1]) ){
							$regs[1] = date('Y-m-d', $timestamp + ($this->processing * 86400));
						}
						
						$time = MODULE_SHIPPING_USPS_TEXT_ESTIMATED . ' sup/sup' . $regs[1];
					}

					
					if(
						$dispinsurance 
							&& 
						(
							(
								MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True' 
									&& 
								$insurance > 0
							) 
								|| 
							(
								MODULE_SHIPPING_USPS_INSURE == 'True' 
									&& 
								$uinsurance > 0
							)
						)
					){
						$dispinsure[$service] = '<br />' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . tep_round_up($insurable, 2);
					} else {
						$dispinsure[$service] = '';
					}

					
					$noTimeService = str_replace(array(' 1-Day',' 2-Day',' 3-Day',' Military',' DPM'), '', $service);
					
					
					if( $transit && ($time != '') ){
						$transittime[$service] = '<br />' . $time;
					} else {
						$transittime[$service] = '<br />' . MODULE_SHIPPING_USPS_TEXT_ESTIMATED 
							. ' sup/sup' . $domesticDeliveryTimesLieuEstimate[$noTimeService]
						;
					}
										
					// add handling cost
					$postage += $domesticHandlingRates[$noTimeService];
					
					$rates[] = array($service => $postage);
					$rates_sorter[] = $postage;
				}
			}


			//echo '$transittime<pre>';
			//print_r($transittime);


		}

		/////////////////////////////////////////
		////////// END DOMESTIC RESPONSE ////////
		/////////////////////////////////////////

		else

			/////////////////////////////////////////
			////// START INTERNATIONAL RESPONSE /////
			/////////////////////////////////////////

		{
			if (strstr($response[0],'<Error>')) {
				$number = preg_match('/<Number>(.*)<\/Number>/', $response[0], $regs);
				$number = $regs[1];
				$description = preg_match('/<Description>(.*)<\/Description>/', $response[0], $regs);
				$description = $regs[1];
				return array('error' => $number . ' - ' . $description);
			} else {
				$body = $response[0];
				$services = array();
				while (true) {
					if ($start = strpos($body, '<Service ID=')) {
						$body = substr($body, $start);
						$end = strpos($body, '</Service>');
						$services[] = substr($body, 0, $end+10);
						$body = substr($body, $end+9);
					} else {
						break;
					}
				}



				$allowed_types = array();
				foreach( explode(", ", MODULE_SHIPPING_USPS_INTL_TYPES) as $value ) $allowed_types[$value] = $this->intl_types[$value];
				$size = sizeof($services);


				//echo '<pre>';
				//print_r($services);

				//print_r($allowed_types);

				for ($i=0, $n=$size; $i<$n; $i++) {
					if (strpos($services[$i], '<Postage>')) {
						$service = preg_match('/<SvcDescription>(.*)<\/SvcDescription>/', $services[$i], $regs);
						$service = $regs[1];
						$CMP = preg_match('/<CommercialPostage>(.*)<\/CommercialPostage>/', $services[$i], $regs);
						$CMP = $regs[1];
						if ($CMP == 0)
						{
							$postage = preg_match('/<Postage>(.*)<\/Postage>/', $services[$i], $regs);
							$postage = $regs[1];
						}
						else{
							switch (MODULE_SHIPPING_USPS_INTL_RATE) {
								case 'Internet':
									if (preg_match('/<CommercialPostage>/', $services[$i]))
									{
										$postage = preg_match('/<CommercialPostage>(.*)<\/CommercialPostage>/', $services[$i], $regs);
										$postage = $regs[1];
									}
									else
									{
										$postage = preg_match('/<Postage>(.*)<\/Postage>/', $services[$i], $regs);
										$postage = $regs[1];
									}
									break;
								case 'Retail':
									$postage = preg_match('/<Postage>(.*)<\/Postage>/', $services[$i], $regs);
									$postage = $regs[1];
									break;
							}
						}
						$postage = $postage + $this->intl_handling[0];
						if (preg_match('/Insurance<\/ServiceName><Available>True/', $services[$i]))
						{
							$iinsurance = preg_match('/Insurance<\/ServiceName><Available>True<\/Available><Price>(.*)<\/Price>/', $services[$i], $regs);
							$iinsurance = $regs[1];
						}
						else {$iinsurance = 0;
						}
						if ($insurable<=50)  {
							$iuinsurance=MODULE_SHIPPING_USPS_INS1;
						}
						else if ($insurable<=100) {
							$iuinsurance=MODULE_SHIPPING_USPS_INS2;
						}
						else if ($insurable<=200) {
							$iuinsurance=MODULE_SHIPPING_USPS_INS3;
						}
						else if ($insurable<=300) {
							$iuinsurance=MODULE_SHIPPING_USPS_INS4;
						}
						else {$iuinsurance = MODULE_SHIPPING_USPS_INS4 + ((ceil($insurable/100) -3) * MODULE_SHIPPING_USPS_INS5);
						}
						if (MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True' && MODULE_SHIPPING_USPS_INSURE == 'True')
						{
							$postage = $postage + max($iinsurance, $iuinsurance);
						}
						elseif (MODULE_SHIPPING_USPS_INSURE == 'True')
						{
							$postage = $postage + $iuinsurance;
						}
						elseif (MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True')
						{
							$postage = $postage + $iinsurance;
						}
						$time = preg_match('/<SvcCommitments>(.*)<\/SvcCommitments>/', $services[$i], $tregs);
						$time = MODULE_SHIPPING_USPS_TEXT_ESTIMATED . $tregs[1];
						$time = preg_replace('/Weeks$/', MODULE_SHIPPING_USPS_TEXT_WEEKS, $time);
						$time = preg_replace('/Days$/', MODULE_SHIPPING_USPS_TEXT_DAYS, $time);
						$time = preg_replace('/Day$/', MODULE_SHIPPING_USPS_TEXT_DAY, $time);
						if( !in_array($service, $allowed_types) ) continue;
						if (isset($this->service) && ($service != $this->service) ) {
							continue;
						}
						if (($dispinsurance) && ((MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True' && $iinsurance > 0) || (MODULE_SHIPPING_USPS_INSURE == 'True' && $iuinsurance > 0)))
						{
							$dispinsure[$service] = '<br>' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . tep_round_up($insurable, 2);
						}
						else {$dispinsure[$service] = '';
						}
						if (($transit) && ($time != ''))
						{
							$transittime[$service] = '<br>' . $time;
						}
						else {$transittime[$service] = '';
						}
						$rates[] = array($service => $postage);
						$rates_sorter[] = $postage;
					}
				}
			}
		}

		//echo 'rates;';
		//print_r($rates_sorter);

		asort($rates_sorter);
		$sorted_rates = array();
		foreach (array_keys($rates_sorter) as $key){
			$sorted_rates[] = $rates[$key];
		}

		/////////////////////////////////////////
		/////// END INTERNATIONAL RESPONSE //////
		/////////////////////////////////////////

		return ((sizeof($sorted_rates) > 0) ? $sorted_rates : false);
	}

	/////////////////////////////////////////
	/////////// END RATE RESPONSE////////////
	/////////////////////////////////////////

}

/////////////////////////////////////////
////////// Ends USPS Class ///////////
/////////////////////////////////////////
