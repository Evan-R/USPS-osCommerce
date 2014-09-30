<?php
/*
	Version 7.3.0
		@Author: Evan Roberts (evan.aedea@gmail.com) 
		@Notes: https://github.com/Evan-R/USPS-osCommerce/releases
		@License: https://github.com/Evan-R/USPS-osCommerce/blob/master/LICENSE
		@Support: https://github.com/Evan-R/USPS-osCommerce/issues


	Legacy / Historical Information:
		$Id: usps.php 6.3 by Kevin L Shelton on August 15, 2013
		+++++ Original contribution by Brad Waite and Fritz Clapp ++++
		++++ Revisions and Modifications made by Greg Deeth, 2008 ++++
		Copyright 2008 osCommerce
		Released under the GNU General Public License
		//VERSION: 5.2.1 ALPHA LAST UPDATED: January 23rd, 2011 by Fulluv Scents
*/

// Incorporate the XML conversion library
require_once(DIR_WS_CLASSES . 'xml_5.php');

// Sets up USPS Class
class usps {

	// Sets Variables
	public 
		$code, 
		$version,
		$title, 
		$description, 
		$icon, 
		$enabled, 
		$countries,
		$sort_order,
		$tax_class,
		$processing,
		$dmstc_handling,
		$intl_handling,
		$sig_conf_thresh,
		$types,
		$intl_types,
		
		$display_weight,
		$display_transit,
		$display_insurance,
		$display_confirmation,
		
		$type_to_request,
		$type_to_container,
		$first_class_to_mail_type,
		
		// detect type changing..
		$dmstc_type_change,
		$intl_type_change,
		
		$dmstc_available,
		$intl_available,
		$first_class_letter,
		$first_class_large_letter,
		$first_class_parcel_max_ounces,
		
		$shipping_method_sort_direction
	;

	function __construct() {
		global $order, $packing;
		$this->code = 'usps';
		$this->version = '7.3.0';
		$this->title = MODULE_SHIPPING_USPS_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_USPS_TEXT_DESCRIPTION;
		$this->icon = DIR_WS_ICONS . 'shipping_usps.gif';
		$this->enabled = ((MODULE_SHIPPING_USPS_STATUS == 'True') ? true : false);
		if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_USPS_ZONE > 0) ) {
			$check_flag = false;
			$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_USPS_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
			while ($check = tep_db_fetch_array($check_query)) {
				if ($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check['zone_id'] == $order->delivery['zone_id']) {
					$check_flag = true;
					break;
				}
			}
			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
		if (defined('SHIPPING_DIMENSIONS_SUPPORT') && SHIPPING_DIMENSIONS_SUPPORT == 'Ready-to-ship only') {
			$this->dimensions_support = 1;
		} elseif (defined('SHIPPING_DIMENSIONS_SUPPORT') && SHIPPING_DIMENSIONS_SUPPORT == 'With product dimensions') {
			$this->dimensions_support = 2;
		} else {
			$this->dimensions_support = 0;
		}
		$this->countries = $this->country_list();
		$this->sort_order = MODULE_SHIPPING_USPS_SORT_ORDER;
		$this->tax_class = MODULE_SHIPPING_USPS_TAX_CLASS;
		$this->processing = (int)MODULE_SHIPPING_USPS_PROCESSING;
		$this->sig_conf_thresh = MODULE_SHIPPING_USPS_SIG_THRESH;
		$options = explode(', ', MODULE_SHIPPING_USPS_OPTIONS);
		$this->display_weight = in_array('Display Weight', $options);
		$this->display_transit = in_array('Display Transit Time', $options);
		$this->display_insurance = in_array('Display Insurance', $options);
		$this->display_confirmation = in_array('Display Sig./Del. Confirmation', $options);
		$this->types = array(
			'First-Class Mail',
			'First-Class Mail Large Envelope',
			'First-Class Mail Large Postcards',
			'First-Class Mail Letter',
			'First-Class Mail Parcel',
			'First-Class Mail Postcards',
			'First-Class Package Service',
			'First-Class Package Service Hold For Pickup',
			'Library Mail Parcel',
			'Media Mail Parcel',
			'Priority Mail',
			'Priority Mail Flat Rate Envelope',
			'Priority Mail Flat Rate Envelope Hold For Pickup',
			'Priority Mail Gift Card Flat Rate Envelope',
			'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup',
			'Priority Mail Hold For Pickup',
			'Priority Mail Large Flat Rate Box',
			'Priority Mail Large Flat Rate Box Hold For Pickup',
			'Priority Mail Legal Flat Rate Envelope',
			'Priority Mail Legal Flat Rate Envelope Hold For Pickup',
			'Priority Mail Medium Flat Rate Box',
			'Priority Mail Medium Flat Rate Box Hold For Pickup',
			'Priority Mail Padded Flat Rate Envelope',
			'Priority Mail Padded Flat Rate Envelope Hold For Pickup',
			'Priority Mail Regional Rate Box A',
			'Priority Mail Regional Rate Box A Hold For Pickup',
			'Priority Mail Regional Rate Box B',
			'Priority Mail Regional Rate Box B Hold For Pickup',
			'Priority Mail Regional Rate Box C',
			'Priority Mail Regional Rate Box C Hold For Pickup',
			'Priority Mail Small Flat Rate Box',
			'Priority Mail Small Flat Rate Box Hold For Pickup',
			'Priority Mail Small Flat Rate Envelope',
			'Priority Mail Small Flat Rate Envelope Hold For Pickup',
			'Priority Mail Window Flat Rate Envelope',
			'Priority Mail Window Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express',
			'Priority Mail Express Flat Rate Boxes',
			'Priority Mail Express Flat Rate Boxes Hold For Pickup',
			'Priority Mail Express Flat Rate Envelope',
			'Priority Mail Express Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express Hold For Pickup',
			'Priority Mail Express Legal Flat Rate Envelope',
			'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express Padded Flat Rate Envelope',
			'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express Sunday/Holiday Delivery',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope',
			'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope',
			'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope',
			'Standard Post'
		);
		$this->type_to_request = array(
			'First-Class Mail' => 'First Class',
			'First-Class Mail Large Envelope' => 'First Class',
			'First-Class Mail Letter' => 'First Class',
			'First-Class Mail Parcel' => 'First Class',
			'First-Class Mail Postcards' => 'First Class',
			'Priority Mail' => 'Priority Commercial',
			'Priority Mail Express Hold For Pickup' => 'Priority Express HFP Commercial',
			'Priority Mail Express' => 'Priority Express Commercial',
			'Standard Post' => 'Standard Post',
			'Media Mail Parcel' => 'Media',
			'Library Mail Parcel' => 'Library',
			'Priority Mail Express Flat Rate Envelope' => 'Priority Express Commercial',
			'First-Class Mail Large Postcards' => 'First Class',
			'Priority Mail Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Medium Flat Rate Box' => 'Priority Commercial',
			'Priority Mail Large Flat Rate Box' => 'Priority Commercial',
			'Priority Mail Express Sunday/Holiday Delivery' => 'Priority Express SH Commercial',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope' => 'Priority Express SH Commercial',
			'Priority Mail Express Flat Rate Envelope Hold For Pickup' => 'Priority Express HFP Commercial',
			'Priority Mail Small Flat Rate Box' => 'Priority Commercial',
			'Priority Mail Padded Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Express Legal Flat Rate Envelope' => 'Priority Express Commercial',
			'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup' => 'Priority Express HFP Commercial',
			'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope' => 'Priority Express SH Commercial',
			'Priority Mail Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Large Flat Rate Box Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Medium Flat Rate Box Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Small Flat Rate Box Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Gift Card Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Window Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Window Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Small Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Small Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Legal Flat Rate Envelope' => 'Priority Commercial',
			'Priority Mail Legal Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Padded Flat Rate Envelope Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Regional Rate Box A' => 'Priority Commercial',
			'Priority Mail Regional Rate Box A Hold For Pickup' => 'Priority HFP Commercial',
			'Priority Mail Regional Rate Box B' => 'Priority Commercial',
			'Priority Mail Regional Rate Box B Hold For Pickup' => 'Priority HFP Commercial',
			'First-Class Package Service Hold For Pickup' => 'First Class HFP Commercial',
			'Priority Mail Express Flat Rate Boxes' => 'Priority Express Commercial',
			'Priority Mail Express Flat Rate Boxes Hold For Pickup' => 'Priority Express HFP Commercial',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes' => 'Priority Express SH Commercial',
			'Priority Mail Regional Rate Box C' => 'Priority Commercial',
			'Priority Mail Regional Rate Box C Hold For Pickup' => 'Priority HFP Commercial',
			'First-Class Package Service' => 'First Class Commercial',
			'Priority Mail Express Padded Flat Rate Envelope' => 'Priority Express Commercial',
			'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup' => 'Priority Express HFP Commercial',
			'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope' => 'Priority Express SH Commercial'
		);
		$this->type_to_container = array(
			'First-Class Mail' => '',
			'First-Class Mail Large Envelope' => '',
			'First-Class Mail Letter' => '',
			'First-Class Mail Parcel' => '',
			'First-Class Mail Postcards' => '',
			'Priority Mail' => '',
			'Priority Mail Express Hold For Pickup' => '',
			'Priority Mail Express' => '',
			'Standard Post' => '',
			'Media Mail Parcel' => '',
			'Library Mail Parcel' => '',
			'Priority Mail Express Flat Rate Envelope' => 'Flat Rate Envelope',
			'First-Class Mail Large Postcards' => '',
			'Priority Mail Flat Rate Envelope' => 'Flat Rate Envelope',
			'Priority Mail Medium Flat Rate Box' => 'MD Flat Rate Box',
			'Priority Mail Large Flat Rate Box' => 'LG Flat Rate Box',
			'Priority Mail Express Sunday/Holiday Delivery' => '',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope' => 'Flat Rate Envelope',
			'Priority Mail Express Flat Rate Envelope Hold For Pickup' => 'Flat Rate Envelope',
			'Priority Mail Small Flat Rate Box' => 'SM Flat Rate Box',
			'Priority Mail Padded Flat Rate Envelope' => 'Padded Flat Rate Envelope',
			'Priority Mail Express Legal Flat Rate Envelope' => 'Legal Flat Rate Envelope',
			'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup' => 'Legal Flat Rate Envelope',
			'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope' => 'Legal Flat Rate Envelope',
			'Priority Mail Hold For Pickup' => '',
			'Priority Mail Large Flat Rate Box Hold For Pickup' => 'LG Flat Rate Box',
			'Priority Mail Medium Flat Rate Box Hold For Pickup' => 'MD Flat Rate Box',
			'Priority Mail Small Flat Rate Box Hold For Pickup' => 'SM Flat Rate Box',
			'Priority Mail Flat Rate Envelope Hold For Pickup' => 'Flat Rate Envelope',
			'Priority Mail Gift Card Flat Rate Envelope' => 'Gift Card Flat Rate Envelope',
			'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup' => 'Gift Card Flat Rate Envelope',
			'Priority Mail Window Flat Rate Envelope' => 'Window Flat Rate Envelope',
			'Priority Mail Window Flat Rate Envelope Hold For Pickup' => 'Window Flat Rate Envelope',
			'Priority Mail Small Flat Rate Envelope' => 'SM Flat Rate Envelope',
			'Priority Mail Small Flat Rate Envelope Hold For Pickup' => 'SM Flat Rate Envelope',
			'Priority Mail Legal Flat Rate Envelope' => 'Legal Flat Rate Envelope',
			'Priority Mail Legal Flat Rate Envelope Hold For Pickup' => 'Legal Flat Rate Envelope',
			'Priority Mail Padded Flat Rate Envelope Hold For Pickup' => 'Padded Flat Rate Envelope',
			'Priority Mail Regional Rate Box A' => 'Regional Rate Box A',
			'Priority Mail Regional Rate Box A Hold For Pickup' => 'Regional Rate Box A',
			'Priority Mail Regional Rate Box B' => 'Regional Rate Box B',
			'Priority Mail Regional Rate Box B Hold For Pickup' => 'Regional Rate Box B',
			'First-Class Package Service Hold For Pickup' => '',
			'Priority Mail Express Flat Rate Boxes' => 'Flat Rate Box',
			'Priority Mail Express Flat Rate Boxes Hold For Pickup' => 'Flat Rate Box',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes' => 'Flat Rate Box',
			'Priority Mail Regional Rate Box C' => 'Regional Rate Box C',
			'Priority Mail Regional Rate Box C Hold For Pickup' => 'Regional Rate Box C',
			'First-Class Package Service' => '',
			'Priority Mail Express Padded Flat Rate Envelope' => 'Padded Flat Rate Envelope',
			'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup' => 'Padded Flat Rate Envelope',
			'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope' => 'Padded Flat Rate Envelope'
		);
		$this->first_class_to_mail_type = array(
			'First-Class Mail' => 'PARCEL',
			'First-Class Mail Large Envelope' => 'FLAT',
			'First-Class Mail Letter' => 'LETTER',
			'First-Class Mail Parcel' => 'PARCEL',
			'First-Class Mail Postcards' => 'POSTCARD',
			'First-Class Mail Large Postcards' => 'POSTCARD',
			'First-Class Package Service Hold For Pickup' => 'PACKAGE SERVICE',
			'First-Class Package Service' => 'PACKAGE SERVICE'
		);
		$this->intl_types = array(
			'Priority Mail Express International',
			'Priority Mail International',
			'Global Express Guaranteed (GXG)',
			'Global Express Guaranteed Document',
			'Global Express Guaranteed Non-Document Rectangular',
			'Global Express Guaranteed Non-Document Non-Rectangular',
			'Priority Mail International Flat Rate Envelope**',
			'Priority Mail International Medium Flat Rate Box',
			'Priority Mail Express International Flat Rate Envelope',
			'Priority Mail International Large Flat Rate Box',
			'USPS GXG Envelopes',
			'First-Class Mail International Letter**',
			'First-Class Mail International Large Envelope**',
			'First-Class Package International Service',
			'Priority Mail International Small Flat Rate Box**',
			'Priority Mail Express International Legal Flat Rate Envelope',
			'Priority Mail International Gift Card Flat Rate Envelope**',
			'Priority Mail International Window Flat Rate Envelope**',
			'Priority Mail International Small Flat Rate Envelope**',
			'First-Class Mail International Postcard',
			'Priority Mail International Legal Flat Rate Envelope**',
			'Priority Mail International Padded Flat Rate Envelope**',
			'Priority Mail International DVD Flat Rate priced box**',
			'Priority Mail International Large Video Flat Rate priced box**',
			'Priority Mail Express International Flat Rate Boxes',
			'Priority Mail Express International Padded Flat Rate Envelope'
		);
		$this->dmstc_type_change = array();
		$this->intl_type_change = array();
		$this->dmstc_available = explode(', ', MODULE_SHIPPING_USPS_DMSTC_TYPES);
		$this->intl_available = explode(', ', MODULE_SHIPPING_USPS_INTL_TYPES);
		list($length, $width, $height, $ounces) = explode(', ', MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LETTER);
		$this->first_class_letter = array('length' => $length, 'width' => $width, 'height' => $height, 'ounces' => $ounces);
		list($length, $width, $height, $ounces) = explode(', ', MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LRGLTR);
		$this->first_class_large_letter = array('length' => $length, 'width' => $width, 'height' => $height, 'ounces' => $ounces);
		$this->first_class_parcel_max_ounces = MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_PARCEL;
		$handling = explode( ", ", MODULE_SHIPPING_USPS_DMSTC_HANDLING);
		$x = 0;
		$tmp = array(); // handling values are stored in same order as types
		foreach ($this->types as $title) { // create handling array keyed by method
			$tmp[$title] = $handling[$x];
			$x++;
		}
		$this->dmstc_handling = $tmp;
		$handling = explode( ", ", MODULE_SHIPPING_USPS_INTL_HANDLING);
		$x = 0;
		$tmp = array();
		foreach ($this->intl_types as $title) { // create handling array keyed by method
			$tmp[$title] = $handling[$x];
			$x++;
		}
		$this->intl_handling = $tmp;
		
		$this->shipping_method_sort_direction = strtolower(MODULE_SHIPPING_USPS_SHIPPING_METHOD_SORT_DIRECTION);
	} // end constructor
	// Ends Variable Set Up

	// Sets Country List
	function country_list()	{
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
			'RS' =>	'Serbia, Republic of',
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
	// Ends Country List

	// Sets Quote
	function quote($method = '') {
		global $order, $shipping_weight, $shipping_num_boxes, $transittime, $dispinsure, $packing;
		if ($this->dimensions_support > 0 && is_object($packing)) {
			$totalWeight = $packing->getTotalWeight();
			$boxesToShip = $packing->getPackedBoxes();
			if ($this->dimensions_support == 1) { // only ready to ship items are set with dimensions
				for ($i = 0; $i < sizeof($boxesToShip); $i++) {
					if ($boxesToShip[$i]['item_length'] == 0) { // size wasn't set
						if ($boxesToShip[$i]['item_weight'] > 60) { // use module estimated dimesions when size not set
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER60);
						} elseif ($boxesToShip[$i]['item_weight'] > 50) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER50);
						} elseif ($boxesToShip[$i]['item_weight'] > 40) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER40);
						} elseif ($boxesToShip[$i]['item_weight'] > 30) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER30);
						} elseif ($boxesToShip[$i]['item_weight'] > 20) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER20);
						} elseif ($boxesToShip[$i]['item_weight'] > 10) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER10);
						} elseif ($boxesToShip[$i]['item_weight'] > 5) {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER5);
						} else {
							list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_LESS5);
						}
						$boxesToShip[$i]['item_length'] = $length;
						$boxesToShip[$i]['item_width'] = $width;
						$boxesToShip[$i]['item_height'] = $height;
					}
				}
			}
			$numBoxes = $packing->getNumberOfBoxes();
			if (SHIPPING_UNIT_LENGTH == 'CM') { // must convert centimeters to inches before getting quote
				for ($i = 0; $i < sizeof($boxesToShip); $i++) {
					$boxesToShip[$i]['item_length'] *= 0.39370079;
					$boxesToShip[$i]['item_width'] *= 0.39370079;
					$boxesToShip[$i]['item_height'] *= 0.39370079;
				}
			}
		} else { // The old method. Let osCommerce tell us how many boxes plus the box weight
			if ($shipping_weight > 60) { // these are defined in inches and don't need converting
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER60);
			} elseif ($shipping_weight > 50) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER50);
			} elseif ($shipping_weight > 40) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER40);
			} elseif ($shipping_weight > 30) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER30);
			} elseif ($shipping_weight > 20) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER20);
			} elseif ($shipping_weight > 10) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER10);
			} elseif ($shipping_weight > 5) {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_OVER5);
			} else {
				list($length, $width, $height) = explode(', ', MODULE_SHIPPING_USPS_PKG_SIZE_LESS5);
			}
			$package = array('item_weight' => $shipping_weight,
				'item_price' => round($order->info['subtotal'] / $shipping_num_boxes, 2),
				'item_length' => $length,
				'item_width' => $width,
				'item_height' => $height);
			$boxesToShip = array();
			for ($i = 0; $i < $shipping_num_boxes; $i++) {
				$boxesToShip[] = $package;
			}
			$totalWeight = round($shipping_weight * $shipping_num_boxes, 2);
			$numBoxes = $shipping_num_boxes;
		}
		if (SHIPPING_UNIT_WEIGHT == 'KGS') { // must convert kilograms to pounds before getting quote
			for ($i = 0; $i < sizeof($boxesToShip); $i++) {
				$boxesToShip[$i]['item_weight'] *= 2.2046226;
			}
		}
		if ($this->display_weight) {
			$shiptitle = sprintf(MODULE_SHIPPING_USPS_TEXT_WEIGHT_DISPLAY, $numBoxes, $totalWeight);
		} else {
			$shiptitle = '';
		}
		$this->dest_zip = str_replace(' ', '', $order->delivery['postcode']);
		if ($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) { // domestic quote
			$this->dest_zip = substr($this->dest_zip, 0, 5);
			$dmstcquotes = array();
			if( $this->display_transit ){
				$trnstime = $this->_getDmstcTransitTimes();
			}
			
			$error = false;
			foreach ($boxesToShip as $package) {
				$pkgQuote = $this->_getDmstcQuote($package, $method);
				if (isset($pkgQuote['error'])) {
					$uspsQuote = $pkgQuote;
					$error = true;
					break; // stop if an error is returned
				}
				foreach ($pkgQuote as $quote) { // combine quotes for package with the quotes for other packages
					if (isset($dmstcquotes[$quote['id']])) { // already set for this mail type?
						$dmstcquotes[$quote['id']]['retailavail'] = ($dmstcquotes[$quote['id']]['retailavail'] && $quote['retailavail']);
						$dmstcquotes[$quote['id']]['onlineavail'] = ($dmstcquotes[$quote['id']]['onlineavail'] && $quote['onlineavail']);
						$dmstcquotes[$quote['id']]['retailrate'] += $quote['retailrate'];
						$dmstcquotes[$quote['id']]['onlinerate'] += $quote['onlinerate'];
						$dmstcquotes[$quote['id']]['retailinsval'] += $quote['retailinsval'];
						$dmstcquotes[$quote['id']]['onlineinsval'] += $quote['onlineinsval'];
						$dmstcquotes[$quote['id']]['count']++;
					} else { // create combined quote since it didn't exist
						$dmstcquotes[$quote['id']] = $quote;
						$dmstcquotes[$quote['id']]['count'] = 1;
					}
				}
			}
			if (!$error) {
				$methods = array();
				$transtypes = array();
				foreach ($dmstcquotes as $quote) {
					if ($quote['count'] != $numBoxes) continue; // skip methods that don't work for all packages
					if( (MODULE_SHIPPING_USPS_DMSTC_RATE == 'Internet') && $quote['onlineavail'] ){
						$title = $quote['name']; //$this->types[$quote['id']];
						if ($this->display_insurance && ($quote['onlineinsval'] > 0)) $title .= '<br />---' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . (ceil($quote['onlineinsval'] * 100) / 100);
						if ($this->display_confirmation && tep_not_null($quote['onlineconf'])) $title .= '<br />---' . $quote['onlineconf'];
						if ($this->display_transit && ($trnstime !== false)) {
							$time = '';
							if ((strpos($quote['id'], 'First') !== false) || (strpos($quote['id'], 'Priority') !== false)) {
								$time = $trnstime['priority'];
							} elseif (strpos($quote['id'], 'Express') !== false) {
								$time = $trnstime['express'];
							} else {
								$time = $trnstime['parcel'];
							}
							
							if( $time != '' ){
								$title .= '<br />' . $time;
							}
						}
						if (MODULE_SHIPPING_USPS_HANDLING_TYPE == 'Per Shipment') {
							$cost = $quote['onlinerate'] + $this->dmstc_handling[$quote['id']];
						} else {
							$cost = $quote['onlinerate'] + ($this->dmstc_handling[$quote['id']] * $numBoxes);
						}
						$methods[] = array(
							'id' => $quote['id'],
							'title' => $title,
							'cost' => $cost
						);
					} elseif( $quote['retailavail'] ){
						$title = $quote['name']; //$this->types[$quote['id']];
						if ($this->display_insurance && ($quote['retailinsval'] > 0)) $title .= '<br />---' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . (ceil($quote['retailinsval'] * 100) / 100);
						if ($this->display_confirmation && tep_not_null($quote['retailconf'])) $title .= '<br />---' . $quote['retailconf'];
						if ($this->display_transit && ($trnstime !== false)) {
							$time = '';
							if ((strpos($quote['id'], 'First') !== false) || (strpos($quote['id'], 'Priority') !== false)) {
								$time = $trnstime['priority'];
							} elseif (strpos($quote['id'], 'Express') !== false) {
								$time = $trnstime['express'];
							} else {
								$time = $trnstime['parcel'];
							}
							
							if( $time != '' ){
								$title .= '<br />' . $time;
							}
						}
						if (MODULE_SHIPPING_USPS_HANDLING_TYPE == 'Per Shipment') {
							$cost = $quote['retailrate'] + $this->dmstc_handling[$quote['id']];
						} else {
							$cost = $quote['retailrate'] + ($this->dmstc_handling[$quote['id']] * $numBoxes);
						}
						$methods[] = array(
							'id' => $quote['id'],
							'title' => $title,
							'cost' => $cost
						);
					}
				} // end foreach $dmstcquotes
				if (empty($methods)) { // no quotes valid for all packages
					$uspsQuote = false;
				} else {
					$uspsQuote = $methods;
				}
			}
			if( sizeof($this->dmstc_type_change) > 0 ){
				$this->logTypeChange(array(
					'type' => 'Domestic',
					'changes' => $this->dmstc_type_change,
				));
			}
		} else { // international quote
			$maxinsurance_query = tep_db_query("select distinct(max_insurance) from USPS_intl_maxins where insurable and country_code = '" . tep_db_input($order->delivery['country']['iso_code_2']) . "' order by max_insurance");
			$this->intl_maxinsure = array();
			while ($x = tep_db_fetch_array($maxinsurance_query)) {
				$this->intl_maxinsure[] = $x['max_insurance'];
			}
			$intlquotes = array();
			foreach ($boxesToShip as $package) {
				$pkgQuote = $this->_getIntlQuote($package, $method);
				if (isset($pkgQuote['error'])) {
					$uspsQuote = $pkgQuote;
					$error = true;
					break; // stop if an error is returned
				}
				foreach ($pkgQuote as $quote) { // combine quotes for package with the quotes for other packages
					if (isset($intlquotes[$quote['id']])) { // already set for this mail type?
						$intlquotes[$quote['id']]['retailavail'] = ($intlquotes[$quote['id']]['retailavail'] && $quote['retailavail']);
						$intlquotes[$quote['id']]['onlineavail'] = ($intlquotes[$quote['id']]['onlineavail'] && $quote['onlineavail']);
						$intlquotes[$quote['id']]['retailrate'] += $quote['retailrate'];
						$intlquotes[$quote['id']]['onlinerate'] += $quote['onlinerate'];
						$intlquotes[$quote['id']]['retailinsval'] += $quote['retailinsval'];
						$intlquotes[$quote['id']]['onlineinsval'] += $quote['onlineinsval'];
						$intlquotes[$quote['id']]['count']++;
					} else { // create combined quote since it didn't exist
						$intlquotes[$quote['id']] = $quote;
						$intlquotes[$quote['id']]['count'] = 1;
					}
				}
			}
			if (!$error) {
				$methods = array();
				$transtypes = array();
				foreach ($intlquotes as $quote) {
					if ($quote['count'] != $numBoxes) continue; // skip methods that don't work for all packages
					if ((MODULE_SHIPPING_USPS_INTL_RATE == 'Internet') && $quote['onlineavail']) {
						$title = $quote['name'];
						if ($this->display_insurance && ($quote['onlineinsval'] > 0)) $title .= '<br />---' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . (ceil($quote['onlineinsval'] * 100) / 100);
						if ($this->display_transit && tep_not_null($quote['transtime'])) {
							$title .= '<br />---' . MODULE_SHIPPING_USPS_TEXT_ESTIMATED . $quote['transtime'];
						}
						if (MODULE_SHIPPING_USPS_HANDLING_TYPE == 'Per Shipment') {
							$cost = $quote['onlinerate'] + $this->intl_handling[$quote['id']];
						} else {
							$cost = $quote['onlinerate'] + ($this->intl_handling[$quote['id']] * $numBoxes);
						}
						$methods[] = array('id' => $quote['id'],
							'title' => $title,
							'cost' => $cost);
					} elseif ($quote['retailavail']) {
						$title = $quote['name'];
						if ($this->display_insurance && ($quote['retailinsval'] > 0)) $title .= '<br />---' . MODULE_SHIPPING_USPS_TEXT_INSURED . '$' . (ceil($quote['retailinsval'] * 100) / 100);
						if ($this->display_transit && tep_not_null($quote['transtime'])) {
							$title .= '<br />' . MODULE_SHIPPING_USPS_TEXT_ESTIMATED . $quote['transtime'];
						}
						if (MODULE_SHIPPING_USPS_HANDLING_TYPE == 'Per Shipment') {
							$cost = $quote['retailrate'] + $this->intl_handling[$quote['id']];
						} else {
							$cost = $quote['retailrate'] + ($this->intl_handling[$quote['id']] * $numBoxes);
						}
						$methods[] = array('id' => $quote['id'],
							'title' => $title,
							'cost' => $cost);
					}
				} // end foreach $intlquotes
				if (empty($methods)) { // no quotes valid for all packages
					$uspsQuote = false;
				} else {
					$uspsQuote = $methods;
				}
			}
			
			// if there's been a change detected, log it..
			if( sizeof($this->intl_type_change) > 0 ){
				$this->logTypeChange(array(
					'type' => 'International',
					'changes' => $this->intl_type_change,
				));
			}
		}
		if (is_array($uspsQuote)) {
			if (isset($uspsQuote['error'])) {
				$this->quotes = array('module' => $this->title . $shiptitle,
					'error' => $uspsQuote['error']);
			} else {
				$quotesort = array();
				foreach ($uspsQuote as $method) {
					$quotesort[$method['id']] = $method['cost'];
				}
				
				if( $this->shipping_method_sort_direction == 'desc' ){
					arsort($quotesort); // sort methods by cost high to low
				} else {
					asort($quotesort);
				}
				
				$methods = array();
				foreach ($quotesort as $key => $cost) {
					foreach ($uspsQuote as $method) {
						if ($method['id'] == $key) $methods[] = $method;
					}
				}
				$this->quotes = array('id' => $this->code,
					'module' => $this->title . $shiptitle,
					'methods' => $methods);
				if ($this->tax_class > 0) {
					$this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
				}
			}
		} else { // quotes was empty
			$this->quotes = array('module' => $this->title . $shiptitle,
				'error' => MODULE_SHIPPING_USPS_TEXT_ERROR);
		}
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);
		
// 		print_r( $this->quotes );
		
		return $this->quotes;
	}
	// Ends Quote

	function check() {
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_USPS_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}
		return $this->_check;
	}

	// Install Module
	function install() {
		tep_db_query("ALTER TABLE `configuration` CHANGE `configuration_value` `configuration_value` TEXT NOT NULL, CHANGE `set_function` `set_function` TEXT NULL DEFAULT NULL");
		tep_db_query("create table if not exists USPS_intl_maxins (country_code char(2) not null, method varchar(128) not null, insurable tinyint(1) not null default 0, max_insurance smallint unsigned not null default 0, last_modified datetime not null, primary key (country_code, method)) collate utf8_general_ci");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable USPS Shipping', 'MODULE_SHIPPING_USPS_STATUS', 'True', 'Do you want to offer USPS shipping?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Enter the USPS User ID', 'MODULE_SHIPPING_USPS_USERID', 'NONE', 'Enter the USPS USERID assigned to you. <u>You must contact USPS to have them switch you to the Production server.</u>  Otherwise this module will not work!', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_USPS_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_USPS_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_USPS_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Options', 'MODULE_SHIPPING_USPS_OPTIONS', 'Display Weight, Display Transit Time, Display Insurance, Display Sig./Del. Confirmation', 'Select display options', '6', '0', 'usps_cfg_select_multioption(array(\'Display Weight\', \'Display Transit Time\', \'Display Insurance\', \'Display Sig./Del. Confirmation\'), ', now())");
		
		// shipping method sort direction
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
				'Shipping Method Sort Direction', 
				'MODULE_SHIPPING_USPS_SHIPPING_METHOD_SORT_DIRECTION', 
				'Asc', 
				'Sort by Ascending or Descending', 
				'6', 
				'0', 
				'tep_cfg_select_option(array(\'Asc\', \'Desc\'), ', 
				now()
			)
		");
		
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Processing Time', 'MODULE_SHIPPING_USPS_PROCESSING', '1', 'Days to Process Order', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size Over 60 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER60', '24, 24, 12', 'Typical package dimensions in inches required by USPS for rates when package weight exceeds 60 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 50 - 60 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER50', '22, 22, 16', 'Typical package dimensions in inches required by USPS for rates when package weight is between 50 and 60 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 40 - 50 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER40', '18, 18, 16', 'Typical package dimensions in inches required by USPS for rates when package weight is between 40 and 50 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 30 - 40 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER30', '16, 16, 16', 'Typical package dimensions in inches required by USPS for rates when package weight is between 30 and 40 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 20 - 30 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER20', '24, 12, 6', 'Typical package dimensions in inches required by USPS for rates when package weight is between 20 and 30 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 10 - 20 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER10', '16, 12, 10', 'Typical package dimensions in inches required by USPS for rates when package weight is between 10 and 20 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size 5 - 10 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_OVER5', '12, 12, 5', 'Typical package dimensions in inches required by USPS for rates when package weight is between 5 and 10 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Package Size Up To 5 Pounds', 'MODULE_SHIPPING_USPS_PKG_SIZE_LESS5', '12, 9, 3', 'Typical package dimensions in inches required by USPS for rates when package weight is up to 5 pounds', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Height\'), ', now())");
		$cfglist = array();
		$handling = array();
		foreach ($this->types as $title) {
			$cfglist[] = "\'" . $title . "\'";
			$handling[] = '0';
		}
		// Values to select domestic shipping methods and handling are set from list created in function usps
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Shipping Methods', 'MODULE_SHIPPING_USPS_DMSTC_TYPES', '" . implode(', ', $this->types) . "', 'Select the domestic services to be offered:', '6', '0', 'usps_cfg_select_multioption(array(" . implode(', ', $cfglist) . "), ', now())");
		
		// domestic types
		tep_db_query("
			INSERT INTO
				" . TABLE_CONFIGURATION . " 
			(
				configuration_title, 
				configuration_key, 
				configuration_value, 
				configuration_description, 
				configuration_group_id, 
				sort_order, 
				use_function, 
				set_function,
				date_added
			) 
				VALUES 
			(
				'Domestic Type Changes', 
				'MODULE_SHIPPING_USPS_DMSTC_TYPE_CHANGE_DETECTED', 
				'[]', 
				'Changed Types (for use by developer):', 
				'6', 
				'0', 
				'usps_cfg_display_json_as_list', 
				'usps_cfg_display_json_as_list(', 
				now()
			)
		");
		
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Flat Handling Fees', 'MODULE_SHIPPING_USPS_DMSTC_HANDLING', '" . implode(', ', $handling) . "', 'Add a different handling fee for each shipping type.', '6', '0', 'usps_cfg_multiinput_list(array(" . implode(', ', $cfglist) . "), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Rates', 'MODULE_SHIPPING_USPS_DMSTC_RATE', 'Retail', 'Charge retail pricing or internet pricing?', '6', '0', 'tep_cfg_select_option(array(\'Retail\', \'Internet\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Delivery Confirmation', 'MODULE_SHIPPING_USPS_DMST_DEL_CONF', 'True', 'Automatically charge Delivery Confirmation when available? Note: Signature Confirmation will override this if it is selected.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Signature Confirmation', 'MODULE_SHIPPING_USPS_DMST_SIG_CONF', 'True', 'Automatically charge Signature Confirmation when available and order total exceeds threshold?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Signature Confirmation Threshold', 'MODULE_SHIPPING_USPS_SIG_THRESH', '100', 'Order total required before Signature Confirmation is triggered?', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Domestic Insurance', 'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION', 'True', 'Use USPS Calculated Domestic Insurance?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Maximum Domestic Insurance Amount', 'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX', '5000', 'Enter the maximum package value that the USPS allows for domestic insurance.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Maximum Online Domestic Insurance', 'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX_ONLINE', '5000', 'Enter the maximum package value that the USPS allows for domestic insurance when using internet shipping.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('First Class Letter Maximums', 'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LETTER', '11.5, 6.125, 0.25, 3.5', 'Enter the maximum dimensions in inches and weight in ounces for a standard First Class letter.', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Thickness\', \'Ounces\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('First Class Large Letter Maximums', 'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LRGLTR', '15, 12, 0.75, 13', 'Enter the maximum dimensions in inches and weight in ounces for a standard First Class large letter.', '6', '0', 'usps_cfg_multiinput_list(array(\'Length\', \'Width\', \'Thickness\', \'Ounces\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('First Class Parcel Maximum', 'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_PARCEL', '13', 'Enter the maximum weight in ounces for a standard First Class parcel.', '6', '0', now())");
		$cfglist = array();
		$handling = array();
		foreach ($this->intl_types as $title) {
			$cfglist[] = "\'" . $title . "\'";
			$handling[] = '0';
		}
		// Values to select international shipping methods and handling are set from list created in function usps
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('International Shipping Methods', 'MODULE_SHIPPING_USPS_INTL_TYPES', '" . implode(', ', $this->intl_types) . "', 'Select the international services to be offered:', '6', '0', 'usps_cfg_select_multioption(array(" . implode(', ', $cfglist) . "), ', now())");
		
		// international type changes
		tep_db_query("
			INSERT INTO 
				" . TABLE_CONFIGURATION . " 
			(
				configuration_title, 
				configuration_key, 
				configuration_value, 
				configuration_description, 
				configuration_group_id, 
				sort_order, 
				use_function,
				set_function, 
				date_added
			) 
				VALUES 
			(
				'International Type Changes', 
				'MODULE_SHIPPING_USPS_INTL_TYPE_CHANGE_DETECTED', 
				'[]', 
				'Changed Types (for use by developer):', 
				'6', 
				'0', 
				'usps_cfg_display_json_as_list', 
				'usps_cfg_display_json_as_list(', 		
				NOW()
			)
		");
		
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('International Flat Handling Fees', 'MODULE_SHIPPING_USPS_INTL_HANDLING', '" . implode(', ', $handling) . "', 'Add a different handling fee for each shipping type.', '6', '0', 'usps_cfg_multiinput_list(array(" . implode(', ', $cfglist) . "), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('International Rates', 'MODULE_SHIPPING_USPS_INTL_RATE', 'Retail', 'Charge retail pricing or internet pricing? (Note: If set to internet and internet pricing is not available retail rate will be returned.)', '6', '0', 'tep_cfg_select_option(array(\'Retail\', \'Internet\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('International Insurance', 'MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION', 'True', 'Use USPS calculated international insurance?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Maximum Online International Insurance', 'MODULE_SHIPPING_USPS_INTL_INSURANCE_MAX_ONLINE', '500', 'Enter the maximum package value that the USPS allows for international insurance when using internet shipping.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Non USPS Insurance - Domestic and international', 'MODULE_SHIPPING_USPS_NON_USPS_INSURE', 'False', 'Would you like to charge insurance for packages independent of USPS, i.e, merchant provided, Stamps.com, Endicia?  If used in conjunction with USPS calculated insurance, the higher of the two will apply.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS1', '1.75', 'Totals $.01-$50.00', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS2', '2.25', 'Totals $50.01-$100', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS3', '2.75', 'Totals $100.01-$200', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS4', '4.70', 'Totals $200.01-$300', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Non USPS Insurance', 'MODULE_SHIPPING_USPS_INS5', '1.00', 'For every $100 over $300 add:', '6', '0', 'currencies->format', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Handling Fees Charged', 'MODULE_SHIPPING_USPS_HANDLING_TYPE', 'Per Package', 'Select whether domestic and international handling fees are charged:', '6', '0', 'tep_cfg_select_option(array(\'Per Package\', \'Per Shipment\'), ', now())");
	}

	function keys() {
		return array(
			'MODULE_SHIPPING_USPS_STATUS', 
			'MODULE_SHIPPING_USPS_USERID', 
			'MODULE_SHIPPING_USPS_SORT_ORDER', 
			'MODULE_SHIPPING_USPS_TAX_CLASS', 
			'MODULE_SHIPPING_USPS_ZONE', 
			'MODULE_SHIPPING_USPS_OPTIONS', 
			'MODULE_SHIPPING_USPS_SHIPPING_METHOD_SORT_DIRECTION', 
			'MODULE_SHIPPING_USPS_PROCESSING', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER60', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER50', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER40', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER30', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER20', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER10', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_OVER5', 
			'MODULE_SHIPPING_USPS_PKG_SIZE_LESS5', 
			'MODULE_SHIPPING_USPS_DMSTC_TYPES', 
			'MODULE_SHIPPING_USPS_DMSTC_TYPE_CHANGE_DETECTED', 
			'MODULE_SHIPPING_USPS_DMSTC_HANDLING', 
			'MODULE_SHIPPING_USPS_DMSTC_RATE', 
			'MODULE_SHIPPING_USPS_DMST_DEL_CONF', 
			'MODULE_SHIPPING_USPS_DMST_SIG_CONF', 
			'MODULE_SHIPPING_USPS_SIG_THRESH', 
			'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION', 
			'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX', 
			'MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX_ONLINE', 
			'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LETTER', 
			'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_LRGLTR', 
			'MODULE_SHIPPING_USPS_DMSTC_FIRSTCLASS_PARCEL', 
			'MODULE_SHIPPING_USPS_INTL_TYPES', 
			'MODULE_SHIPPING_USPS_INTL_TYPE_CHANGE_DETECTED', 
			'MODULE_SHIPPING_USPS_INTL_HANDLING', 
			'MODULE_SHIPPING_USPS_INTL_RATE', 
			'MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION', 
			'MODULE_SHIPPING_USPS_INTL_INSURANCE_MAX_ONLINE', 
			'MODULE_SHIPPING_USPS_NON_USPS_INSURE', 
			'MODULE_SHIPPING_USPS_INS1', 
			'MODULE_SHIPPING_USPS_INS2', 
			'MODULE_SHIPPING_USPS_INS3',
			'MODULE_SHIPPING_USPS_INS4', 
			'MODULE_SHIPPING_USPS_INS5', 
			'MODULE_SHIPPING_USPS_HANDLING_TYPE'
		);
	}
	// End Install Module

	// Remove Module
	function remove()	{
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}
	// End Remove Module

	// START RATE REQUEST FUNCTIONS

	// START USPS DOMESTIC REQUEST
	function _getDmstcQuote($pkg, $method = '') {
		global $order;
		if (tep_not_null($method) && in_array($method, $this->dmstc_available)) {
			$request_types = array($method);
		} else {
			$request_types = $this->dmstc_available;
		}
		$shipping_weight = ($pkg['item_weight'] < 0.0625 ? 0.0625 : $pkg['item_weight']);
		$shipping_pounds = floor($shipping_weight);
		$shipping_ounces = ceil((16 * ($shipping_weight - $shipping_pounds)) * 100) / 100; // rounded to two decimal digits
		$ounces = $shipping_weight * 16.0;
		$pkgvalue = $pkg['item_price'];
		if ($pkgvalue > MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX) $pkgvalue = MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX;
		if ($pkgvalue <= 0) $pkgvalue = 0.01;
		$nonuspsinsurancecost = 0;
		if (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') {
			if ($pkg['item_price'] <= 50) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS1;
			} else if ($pkg['item_price'] <= 100) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS2;
			}	else if ($pkg['item_price'] <= 200) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS3;
			} else if ($pkg['item_price'] <= 300) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS4;
			} else {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS4 + ((ceil($pkg['item_price']/100) - 3) * MODULE_SHIPPING_USPS_INS5);
			}
		}
		$sservs = array(); // any special services to request
		if (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') $sservs = array(1, 11); //request insurance, both regular & express mail
		if ((MODULE_SHIPPING_USPS_DMST_SIG_CONF == 'True') && ($order->info['subtotal'] >= MODULE_SHIPPING_USPS_SIG_THRESH)) {
			$sservs[] = 15;
		} elseif (MODULE_SHIPPING_USPS_DMST_DEL_CONF == 'True') {
			$sservs[] = 13;
		}
		$sservice = '';
		if (!empty($sservs)) {
			foreach ($sservs as $id) $sservice .= '<SpecialService>' . $id . '</SpecialService>';
			$sservice = '<SpecialServices>' . $sservice . '</SpecialServices>';
		}
		$box = array($pkg['item_length'], $pkg['item_width'], $pkg['item_height']);
		rsort($box); // put package size in large to small order for purposes of checking dimensions
		list($length, $width, $height) = $box;
		$id = 0;
		$quotes = $request_group = array();
		$group_count = 1;
		$type_count = 0;
		$urllength = strlen('production.shippingapis.com/shippingAPI.dll?<RateV4Request USERID="' . MODULE_SHIPPING_USPS_USERID . '"><Revision>2</Revision></RateV4Request>');
		foreach ($request_types as $service) { // break the requested shipping methods into groups
			if (strpos($service, 'First') !== false) { // if first class mail service check if will fit type
				$fcmt = $this->first_class_to_mail_type[$service];
				if (($fcmt == 'LETTER') && (($length > $this->first_class_letter['length']) || ($width > $this->first_class_letter['width']) || ($height > $this->first_class_letter['height']) || ($ounces > $this->first_class_letter['ounces']))) continue; // don't check letter type if package doesn't fit
				if (($fcmt == 'FLAT') && (($length > $this->first_class_large_letter['length']) || ($width > $this->first_class_large_letter['width']) || ($height > $this->first_class_large_letter['height']) || ($ounces > $this->first_class_large_letter['ounces']))) continue;  // don't check large letter type if package doesn't fit
				if (($fcmt == 'POSTCARD') && (($length > 6) || ($width > 4.25) || ($height > 0.016) || ($ounces > 3.5))) continue;  // don't check postcard type if package doesn't fit
				if ($ounces > $this->first_class_parcel_max_ounces) continue; // don't check First Class if too heavy
			}
			$cont = $this->type_to_container[$service]; // begin checking for packages larger than USPS containers
			// if this service type specifies a container and this package won't fit then skip this service
			if (strpos($cont, 'Envelope') !== false) { // if service container is envelope
				if ($height > 1) continue; // anything thicker than one inch won't fit an envelope
				if ((strpos($cont, 'SM') !== false) && (($length > 10) || ($width > 6))) continue;
				if ((strpos($cont, 'Window') !== false) && (($length > 10) || ($width > 5))) continue;
				if ((strpos($cont, 'Gift') !== false) && (($length > 10) || ($width > 7))) continue;
				if ((strpos($cont, 'Legal') !== false) && (($length > 15) || ($width > 9.5))) continue;
				if (($length > 12.5) || ($width > 9.5)) continue; // other envelopes
			}
			if (($cont == 'SM Flat Rate Box') && (($length > 8.625) || ($width > 5.375) || ($height > 1.625))) continue;
			if (($cont == 'MD Flat Rate Box') || ($cont == 'Flat Rate Box')) {
				if ($length > 13.625) continue; // too big for longest medium box
				if ($length > 11) { // check medium type 2 box
					if (($length > 13.625) || ($width > 11.875) || ($height > 3.375)) continue; // won't fit medium type 2 box
				} elseif (($length > 11) || ($width > 8.5) || ($height > 5.5)) continue; // won't fit either type medium box
			}
			if (($cont == 'LG Flat Rate Box') && (($length > 12) || ($width > 12) || ($height > 5.5))) continue;
			if ($cont == 'Regional Rate Box A') {
				if ($length > 12.8125) continue; // too big for longest A box
				if ($length > 10) { // check A2 box
					if (($length > 12.8125) || ($width > 10.9375) || ($height > 2.375)) continue; // won't fit A2 box
				} elseif (($length > 10) || ($width > 7) || ($height > 4.75)) continue; // won't fit either type A box
			}
			if ($cont == 'Regional Rate Box B') {
				if ($length > 15.875) continue; // too big for longest B box
				if ($length > 12) { // check B2 box
					if (($length > 15.875) || ($width > 14.375) || ($height > 2.875)) continue; // won't fit B2 box
				} elseif (($length > 12) || ($width > 10.25) || ($height > 5)) continue; // won't fit either type B box
			}
			if (($cont == 'Regional Rate Box C') && (($length > 14.75) || ($width > 11.75) || ($height > 11.5))) continue;
			// passed all size checks so build request
			$request = '<Package ID="'. $id . '"><Service>' . $this->type_to_request[$service] . '</Service>';
			if (strpos($service, 'First') !== false) $request .= '<FirstClassMailType>' . $fcmt . '</FirstClassMailType>';
			$request .= '<ZipOrigination>' . SHIPPING_ORIGIN_ZIP . '</ZipOrigination>' .
					'<ZipDestination>' . $this->dest_zip . '</ZipDestination>' .
					'<Pounds>' . $shipping_pounds . '</Pounds>' .
	  		'<Ounces>' . $shipping_ounces . '</Ounces>';
			if ((strpos($cont, 'Rate') === false) && (max($length, $width, $height) > 12)) {
				$request .= '<Container>RECTANGULAR</Container><Size>LARGE</Size>' .
						'<Width>' . $width . '</Width>' .
						'<Length>' . $length . '</Length>'.
						'<Height>' . $height . '</Height>';
			} else {
				$request .= '<Container>' .  $cont . '</Container><Size>REGULAR</Size>';
			}
			if (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') $request .= '<Value>' . number_format($pkgvalue, 2, '.', '') . '</Value>';
			$request .= $sservice;
			if ((strpos($service, 'First') !== false) || (strpos($service, 'Post') !== false)) $request .= '<Machinable>true</Machinable>';
			$request .= '</Package>';
			$id++;
			$type_count++;
			if (($type_count > 25) || (($urllength + strlen(urlencode($request))) > 2000)) { // only 25 services allowed per request or 2000 characters per url
				$group_count++;
				$type_count = 1;
				$urllength = strlen('production.shippingapis.com/shippingAPI.dll?<RateV4Request USERID="' . MODULE_SHIPPING_USPS_USERID . '"><Revision>2</Revision></RateV4Request>');
			}
			$urllength += strlen(urlencode($request));
			$request_group[$group_count][$type_count] = $request;
		} // end foreach request type as service
		if (empty($request_group)) return array('error' => MODULE_SHIPPING_USPS_ERROR_NO_SERVICES);
		if (!class_exists('httpClient')) {
			include(DIR_FS_CATALOG . DIR_WS_CLASSES .'http_client.php');
		} //print_r($request_group);
		foreach ($request_group as $service_group) {
			$request = '<RateV4Request USERID="' . MODULE_SHIPPING_USPS_USERID . '"><Revision>2</Revision>';
			foreach ($service_group as $service_request) {
				$request .= $service_request;
			}
			$request .= '</RateV4Request>';
			$request = 	'API=RateV4&XML=' . urlencode($request);
			$body = '';
			$http = new httpClient();
			if ($http->Connect('production.shippingapis.com', 80)) {
				$http->addHeader('Host', 'production.shippingapis.com');
				$http->addHeader('User-Agent', 'osCommerce');
				$http->addHeader('Connection', 'Close');
				if ($http->Get('/shippingAPI.dll?' . $request)) $body = $http->getBody();
				$http->Disconnect();
			} else {
				$body = '<Error><Number></Number><Description>' . MODULE_SHIPPING_USPS_TEXT_CONNECTION_ERROR . '</Description></Error>';
			}
			$doc = XML_unserialize($body); //print_r($doc);
			if (isset($doc['Error'])) return array('error' => $doc['Error']['Number'] . ' ' . $doc['Error']['Description']);
			if (isset($doc['RateV4Response']['Package']['Postage'])) { // single mail service response
				$tmp = $this->_decode_domestic_response($doc['RateV4Response']['Package']['Postage'], $pkgvalue, $nonuspsinsurancecost, $pkg['item_price']);
				if (!empty($tmp)) $quotes[$tmp['id']] = $tmp;
			} elseif (isset($doc['RateV4Response']['Package'][0])) { // multiple mailing services returned
				foreach ($doc['RateV4Response']['Package'] as $mailsvc) {
					if (isset($mailsvc['Postage'])) {
						$tmp = $this->_decode_domestic_response($mailsvc['Postage'], $pkgvalue, $nonuspsinsurancecost, $pkg['item_price']);
						if (!empty($tmp)) $quotes[$tmp['id']] = $tmp;
					}
				}
			}
		} // end foreach $request_group
		return $quotes;
	}
	// END DOMESTIC REQUEST

	// START USPS DECODE DOMESTIC RESPONSE
	function _decode_domestic_response($service, $pkgvalue, $nonuspsinsurancecost, $opval) {
		if (!isset($service['MailService'])) return array();
		$qname = htmlspecialchars_decode($service['MailService']);
		$qid = str_replace(array('<sup>', '</sup>', '&reg;', '&trade;', '&#174;', '&#8482;', ' 1-Day', ' 2-Day', ' 3-Day', ' Military', ' DPO'), '', $qname);
		
		// if not in the complete list of types, log the change..
		if( ! in_array($qid, $this->types) ){
			$this->dmstc_type_change[] = $qid; 
		}
		
		$retailrate = $service['Rate'];
		$onlinerate = $service['CommercialRate'];
		$retailavailable = ($retailrate > 0);
		$onlineavailable = ($onlinerate > 0);
		$insset = $confset = false;
		$retailinsfor = $onlineinsfor = 0;
		$retailcname = $onlinecname = '';
		$confname = '';
		if (is_array($service['SpecialServices']['SpecialService'])) {
			foreach ($service['SpecialServices']['SpecialService'] as $special) {
				if (strpos($special['ServiceName'], 'Insurance') !== false) {
					$insavailable = ($special['Available'] == 'true');
					$insavailableonline = ($special['AvailableOnline'] == 'true');
					$insrateretail = $special['Price'];
					$insrateonline = $special['PriceOnline'];
					$insset = true;
				} elseif ((strpos($special['ServiceName'], 'Confirmation') !== false) || (strpos($special['ServiceName'], 'Tracking') !== false)) {
					$confname = htmlspecialchars_decode($special['ServiceName']);
					$confavailable = ($special['Available'] == 'true');
					$confavailableonline = ($special['AvailableOnline'] == 'true');
					$confrateretail = $special['Price'];
					$confrateonline = $special['PriceOnline'];
					$confset = true;
				}
			}
		}
		if (MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') $onlineavailable = ($onlineavailable && ($pkgvalue <= MODULE_SHIPPING_USPS_DMSTC_INSURANCE_MAX_ONLINE)); // can't use online if package value exceeds maximum available online
		if ((MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') && (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') && $insset) {
			if ($insavailable && $retailavailable) { // retail rate & retail insurance available
				if ($insrateretail > $nonuspsinsurancecost) {
					$retailrate += $insrateretail;
					$retailinsfor = $pkgvalue;
				} else {
					$retailrate += $nonuspsinsurancecost;
					$retailinsfor = $opval;
				}
			} elseif ($retailavailable) { // only retail rate available
				$retailrate += $nonuspsinsurancecost;
				$retailinsfor = $opval;
			}
			if ($insavailableonline && $onlineavailable) { // online rate & online insurance available
				if ($insrateonline > $nonuspsinsurancecost) {
					$onlinerate += $insrateonline;
					$onlineinsfor = $pkgvalue;
				} else {
					$onlinerate += $nonuspsinsurancecost;
					$onlineinsfor = $opval;
				}
			} elseif ($onlineavailable) { // only online rate available
				$onlinerate += $nonuspsinsurancecost;
				$onlineinsfor = $opval;
			}
		} elseif (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') {
			if ($retailavailable) { // retail rate available
				$retailrate += $nonuspsinsurancecost;
				$retailinsfor = $opval;
			}
			if ($onlineavailable) { // online rate available
				$onlinerate += $nonuspsinsurancecost;
				$onlineinsfor = $opval;
			}
		} elseif ((MODULE_SHIPPING_USPS_DMSTC_INSURANCE_OPTION == 'True') && $insset) {
			if ($insavailable && $retailavailable) { // retail rate & retail insurance available
				$retailrate += $insrateretail;
				$retailinsfor = $pkgvalue;
			}
			if ($insavailableonline && $onlineavailable) { // online rate & online insurance available
				$onlinerate += $insrateonline;
				$onlineinsfor = $pkgvalue;
			}
		}
		if ((((MODULE_SHIPPING_USPS_DMST_SIG_CONF == 'True') && ($order->info['subtotal'] >= MODULE_SHIPPING_USPS_SIG_THRESH)) || (MODULE_SHIPPING_USPS_DMST_DEL_CONF == 'True')) && $confset) { // using confirmation and it was set
			if ($confavailable && $retailavailable) {
				$retailcname .= $confname;
				$retailrate += $confrateretail;
			}
			if ($confavailableonline && $onlineavailable) {
				$onlinecname .= $confname;
				$onlinerate += $confrateonline;
			}
		}
		return array('id' => $qid,
			'name' => $qname,
			'retailavail' => $retailavailable,
			'onlineavail' => $onlineavailable,
			'retailrate' => $retailrate,
			'onlinerate' => $onlinerate,
			'retailconf' => $retailcname,
			'onlineconf' => $onlinecname,
			'retailinsval' => $retailinsfor,
			'onlineinsval' => $onlineinsfor);
	}
	// END DOMESTIC RESPONSE

	// START GET DOMESTIC TRANSIT TIME
	function _getDmstcTransitTimes() {
		$transitreq  = 'USERID="' . MODULE_SHIPPING_USPS_USERID . '">' .
				'<OriginZip>' . SHIPPING_ORIGIN_ZIP . '</OriginZip>' .
				'<DestinationZip>' . $this->dest_zip . '</DestinationZip>';
		$transitpriorityreq = 'API=PriorityMail&XML=' . urlencode( '<PriorityMailRequest ' . $transitreq . '</PriorityMailRequest>');
		$transitexpressreq = 'API=ExpressMailCommitment&XML=' . urlencode( '<ExpressMailCommitmentRequest USERID="' . MODULE_SHIPPING_USPS_USERID . '"><OriginZIP>' . SHIPPING_ORIGIN_ZIP . '</OriginZIP><DestinationZIP>' . $this->dest_zip . '</DestinationZIP><Date></Date></ExpressMailCommitmentRequest>');
		$transitparcelreq = 'API=StandardB&XML=' . urlencode( '<StandardBRequest ' . $transitreq . '</StandardBRequest>');
		$usps_server = 'production.shippingapis.com';
		$api_dll = 'shippingAPI.dll';
		$body = '';
		if (!class_exists('httpClient')) {
			include(DIR_FS_CATALOG . DIR_WS_CLASSES . 'http_client.php');
		}
		$http = new httpClient();
		if ($http->Connect($usps_server, 80)) {
			$http->addHeader('Host', $usps_server);
			$http->addHeader('User-Agent', 'osCommerce');
			$http->addHeader('Connection', 'Close');
			if ($http->Get('/' . $api_dll . '?' . $transitpriorityreq)) $transpriorityresp = $http->getBody();
			if ($http->Get('/' . $api_dll . '?' . $transitexpressreq)) $transexpressresp = $http->getBody();
			if ($http->Get('/' . $api_dll . '?' . $transitparcelreq)) $transparcelresp = $http->getBody();
			$http->Disconnect();
		} else {
			return false;
		}
		$prioritytime = '';
		$expresstime = '';
		$parceltime = '';
		
		$doc = XML_unserialize($transpriorityresp);
		if( isset($doc['PriorityMailResponse']['Days']) ){
			// add processing time..
			$doc['PriorityMailResponse']['Days'] += $this->processing;
			
			$prioritytime = 
				MODULE_SHIPPING_USPS_TEXT_ESTIMATED 
				. $doc['PriorityMailResponse']['Days'] 
				. ' ' 
				. (
					$doc['PriorityMailResponse']['Days'] == 1 
						? 
					MODULE_SHIPPING_USPS_TEXT_DAY 
						: 
					MODULE_SHIPPING_USPS_TEXT_DAYS
				)
			;
		}
		
		$doc = XML_unserialize($transparcelresp);
		if( isset($doc['StandardBResponse']['Days']) ){
			// add processing time..
			$doc['StandardBResponse']['Days'] += $this->processing;
			
			$parceltime = 
				MODULE_SHIPPING_USPS_TEXT_ESTIMATED 
				. $doc['StandardBResponse']['Days'] 
				. ' ' 
				. (
					$doc['StandardBResponse']['Days'] == 1 
						? 
					MODULE_SHIPPING_USPS_TEXT_DAY 
						: 
					MODULE_SHIPPING_USPS_TEXT_DAYS
				)
			;
		}
		
		$doc = XML_unserialize($transexpressresp);
// 		print_r( $doc );
		if( isset($doc['ExpressMailCommitmentResponse']['Commitment']) ){
			if( isset($doc['ExpressMailCommitmentResponse']['Commitment']['CommitmentName']) ){ // single date
				$sequence = str_replace(
					array(
						'Next', 
						'Days', 
						'Day'
					), 
					array(
						'1', 
						MODULE_SHIPPING_USPS_TEXT_DAYS, 
						MODULE_SHIPPING_USPS_TEXT_DAY
					), 
					$doc['ExpressMailCommitmentResponse']['Commitment']['CommitmentName']
				);
			} else { // multiple dates returned, choose longest time
				$seqlist = array();
				foreach( $doc['ExpressMailCommitmentResponse']['Commitment'] as $commit ){
					if( isset($commit['CommitmentName']) ){
						$seqlist[] = str_replace(
							array(
								'Next', 
								'Days', 
								'Day'
							), 
							array(
								'1', 
								MODULE_SHIPPING_USPS_TEXT_DAYS, 
								MODULE_SHIPPING_USPS_TEXT_DAY
							), 
							$commit['CommitmentName']
						);
					}
				}
				rsort($seqlist);
				$sequence = $seqlist[0];
			}
			$expresstime = MODULE_SHIPPING_USPS_TEXT_ESTIMATED . $sequence;
		}
		
		return array(
			'express' => $expresstime, 
			'priority' => $prioritytime, 
			'parcel' => $parceltime
		);
	}
	// END GET TRANSIT TIME

	// START USPS INTERNATIONAL REQUEST
	function _getIntlQuote($pkg, $method) {
		global $order;
		if (tep_not_null($method) && in_array($method, $this->intl_available)) {
			$request_types = array($method);
		} else {
			$request_types = $this->intl_available;
		}
		$this->intl_request_types = $request_types;
		$shipping_weight = ($pkg['item_weight'] < 0.0625 ? 0.0625 : $pkg['item_weight']);
		$shipping_pounds = floor($shipping_weight);
		$shipping_ounces = ceil((16 * ($shipping_weight - $shipping_pounds)) * 100) / 100; // rounded to two decimal digits
		$nonuspsinsurancecost = 0;
		if (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') {
			if ($pkg['item_price'] <= 50) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS1;
			} else if ($pkg['item_price'] <= 100) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS2;
			}	else if ($pkg['item_price'] <= 200) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS3;
			} else if ($pkg['item_price'] <= 300) {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS4;
			} else {
				$nonuspsinsurancecost = MODULE_SHIPPING_USPS_INS4 + ((ceil($pkg['item_price']/100) - 3) * MODULE_SHIPPING_USPS_INS5);
			}
		}
		if (empty($this->intl_maxinsure) || (MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'False')) {
			$checkvalues = array($pkg['item_price']);
		} else {
			$checkvalues = array();
			foreach ($this->intl_maxinsure as $maxins) {
				if ($pkg['item_price'] <= $maxins) {
					$checkvalues[] = $pkg['item_price'];
					break; // stop once  we find package value is less than allowed maximum
				} else {
					$checkvalues[] = $maxins;
				}
			}
		}
		rsort($checkvalues); // values must be checked in reverse order
		$request = 	'<IntlRateV2Request USERID="' . MODULE_SHIPPING_USPS_USERID . '">' .
				'<Revision>2</Revision>';
		foreach ($checkvalues as $pkgvalue) {
			$request .= '<Package ID="' . intval($pkgvalue) . '">' .
					'<Pounds>' . $shipping_pounds . '</Pounds>' .
					'<Ounces>' . $shipping_ounces . '</Ounces>' .
					'<Machinable>True</Machinable>' .
					'<MailType>All</MailType>' .
					'<GXG>' .
					'<POBoxFlag>N</POBoxFlag>' .
					'<GiftFlag>N</GiftFlag>' .
					'</GXG>' .
					'<ValueOfContents>' . number_format($pkgvalue, 2, '.', '') . '</ValueOfContents>' .
					'<Country>' . $this->countries[$order->delivery['country']['iso_code_2']] . '</Country>' .
					'<Container>RECTANGULAR</Container>' .
					'<Size>' . ((max($pkg['item_length'], $pkg['item_width'], $pkg['item_height']) > 12) ? ' LARGE' : 'REGULAR') . '</Size>' .
					'<Width>' . $pkg['item_width'] . '</Width>' .
					'<Length>' . $pkg['item_length'] . '</Length>' .
					'<Height>' . $pkg['item_height'] . '</Height>' .
					'<Girth>' . ($pkg['item_height'] + $pkg['item_height'] + $pkg['item_width'] + $pkg['item_width']) . '</Girth>' .
					'<OriginZip>' . SHIPPING_ORIGIN_ZIP . '</OriginZip>' .
					
					'<CommercialFlag>Y</CommercialFlag>' .
					'<ExtraServices>' .
					'<ExtraService>1</ExtraService>' .
					'</ExtraServices>' .
					'</Package>';
		}
		$request .= '</IntlRateV2Request>';
		
		$request = 	'API=IntlRateV2&XML=' . urlencode($request);
		$usps_server = 'production.shippingapis.com';
		$api_dll = 'shippingAPI.dll';
		$body = '';
		if (!class_exists('httpClient')) {
			include(DIR_FS_CATALOG . DIR_WS_CLASSES . 'http_client.php');
		}
		$http = new httpClient();
		if ($http->Connect($usps_server, 80)) {
			$http->addHeader('Host', $usps_server);
			$http->addHeader('User-Agent', 'osCommerce');
			$http->addHeader('Connection', 'Close');
			if ($http->Get('/' . $api_dll . '?' . $request)) $body = $http->getBody();
			$http->Disconnect();
		} else {
			$body = '<Error><Number></Number><Description>' . MODULE_SHIPPING_USPS_TEXT_CONNECTION_ERROR . '</Description></Error>';
		}
		$doc = XML_unserialize($body);
		
		
// 		print_R( $doc );
// 		exit;
		
		
		$quotes = array();
		if (isset($doc['Error'])) return array('error' => $doc['Error']['Number'] . ' ' . $doc['Error']['Description']);
		if (isset($doc['IntlRateV2Response']['Package']['Error'])) return array('error' => $doc['IntlRateV2Response']['Package']['Error']['Number'] . ' ' . $doc['IntlRateV2Response']['Package']['Error']['Description']);
		if (isset($doc['IntlRateV2Response']['Package'][0]['Error'])) return array('error' => $doc['IntlRateV2Response']['Package']['Error'][0]['Number'] . ' ' . $doc['IntlRateV2Response']['Package'][0]['Error']['Description']);
		if (isset($doc['IntlRateV2Response']['Package']['Service']['SvcDescription'])) { // single mail service response
			$tmp = $this->_decode_intl_response($doc['RateV4Response']['Package']['Service'], $nonuspsinsurancecost, $pkg['item_price']);
			if (!empty($tmp)) $quotes[$tmp['id']] = $tmp;
		} elseif (isset($doc['IntlRateV2Response']['Package']['Service'][0])) { // multiple mailing services returned
			foreach ($doc['IntlRateV2Response']['Package']['Service'] as $mailsvc) {
				if (isset($mailsvc['SvcDescription'])) {
					$tmp = $this->_decode_intl_response($mailsvc, $nonuspsinsurancecost, $pkg['item_price']);
					if (!empty($tmp)) $quotes[$tmp['id']] = $tmp;
				}
			}
		} elseif (isset($doc['IntlRateV2Response']['Package'][0]['Service'])) { // multiple packages requested for insurance purposes
			foreach ($doc['IntlRateV2Response']['Package'] as $package) {
				if (isset($package['Service']['SvcDescription'])) { // single mail service response for package
					$tmp = $this->_decode_intl_response($package['Service'], $nonuspsinsurancecost, $pkg['item_price']);
					if (!empty($tmp)) if (!isset($quotes[$tmp['id']])) $quotes[$tmp['id']] = $tmp; // save only first valid response
				} elseif (isset($package['Service'][0])) { // multiple mailing services returned for package
					foreach ($package['Service'] as $mailsvc) {
						if (isset($mailsvc['SvcDescription'])) {
							$tmp = $this->_decode_intl_response($mailsvc, $nonuspsinsurancecost, $pkg['item_price']);
							if (!empty($tmp)) if (!isset($quotes[$tmp['id']])) $quotes[$tmp['id']] = $tmp; // save only first valid response
						}
					}
				}
			}
		}
		return $quotes;
	}
	// END INTERNATIONAL REQUEST

	// START DECODE INTERNATIONAL RESPONSE
	function _decode_intl_response($service, $nonuspsinsurancecost, $opval) {
		if (!isset($service['SvcDescription'])) return array();
		$insured_for = 0;
		$insurance_cost = 0;
		if (MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True') {
			if (isset($service['InsComment'])) {
				if ($service['InsComment'] == 'INSURED VALUE') return array(); // skip if invalid insured value when doing USPS insurance
			} else {
				if (isset($service['ExtraServices']['ExtraService'])) {
					if (isset($service['ExtraServices']['ExtraService'][0])) { // multiple extras returned
						foreach ($service['ExtraServices']['ExtraService'] as $extra) {
							if (($extra['ServiceName'] == 'Insurance') && ($extra['Available'] == 'True')) {
								$insurance_cost = $extra['Price'];
								$insured_for = $service['ValueOfContents'];
							}
						}
					} elseif (isset($service['ExtraServices']['ExtraService']['ServiceName'])) { //single extra returned
						if (($service['ExtraServices']['ExtraService']['ServiceName'] == 'Insurance') && ($service['ExtraServices']['ExtraService']['Available'] == 'True')) {
							$insurance_cost = $service['ExtraServices']['ExtraService']['Price'];
							$insured_for = $service['ValueOfContents'];
						}
					}
				}
			}
		}
		$qname = htmlspecialchars_decode($service['SvcDescription']);
		$qid = str_replace(array('<sup>', '</sup>', '&reg;', '&trade;', '&#174;', '&#8482;'), '', $qname);
		
		
		// if not in the complete list of types, log the change..
		if( ! in_array($qid, $this->intl_types) ){
			$this->intl_type_change[] = $qid;
		} 
				
		
		if (!in_array($qid, $this->intl_request_types)) return array(); // not an allowed international method
		$retailrate = $service['Postage'];
		$onlinerate = $service['CommercialPostage'];
		$retailavailable = ($retailrate > 0);
		$onlineavailable = ($onlinerate > 0);
		if (MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True') $onlineavailable = $onlineavailable && ($insured_for <= MODULE_SHIPPING_USPS_INTL_INSURANCE_MAX_ONLINE); // can't use online for USPS international insurance over maximum
		if ((MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True') && (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') && ($insurance_cost > 0)) {
			if (($insurance_cost > 0) && $retailavailable) { // retail rate & insurance available
				if ($insurance_cost > $nonuspsinsurancecost) {
					$retailrate += $insurance_cost;
					$retailinsfor = $insured_for;
				} else {
					$retailrate += $nonuspsinsurancecost;
					$retailinsfor = $opval;
				}
			} elseif ($retailavailable) { // only retail rate available
				$retailrate += $nonuspsinsurancecost;
				$retailinsfor = $opval;
			}
			if (($insurance_cost > 0) && $onlineavailable) { // online rate & online insurance available
				if ($insurance_cost > $nonuspsinsurancecost) {
					$onlinerate += $insurance_cost;
					$onlineinsfor = $insured_for;
				} else {
					$onlinerate += $nonuspsinsurancecost;
					$onlineinsfor = $opval;
				}
			} elseif ($onlineavailable) { // only online rate available
				$onlinerate += $nonuspsinsurancecost;
				$onlineinsfor = $opval;
			}
		} elseif (MODULE_SHIPPING_USPS_NON_USPS_INSURE == 'True') {
			if ($retailavailable) { // retail rate available
				$retailrate += $nonuspsinsurancecost;
				$retailinsfor = $opval;
			}
			if ($onlineavailable) { // online rate available
				$onlinerate += $nonuspsinsurancecost;
				$onlineinsfor = $opval;
			}
		} elseif ((MODULE_SHIPPING_USPS_INTL_INSURANCE_OPTION == 'True') && ($insurance_cost > 0)) {
			if (($insurance_cost > 0) && $retailavailable) { // retail rate & retail insurance available
				$retailrate += $insurance_cost;
				$retailinsfor = $insured_for;
			}
			if (($insurance_cost > 0) && $onlineavailable) { // online rate & online insurance available
				$onlinerate += $insurance_cost;
				$onlineinsfor = $insured_for;
			}
		}
		
		// add processing days to transit time..
// 		echo "[{$service['SvcCommitments']}]<br />";
		
		// days
		if( strpos($service['SvcCommitments'], 'day') ){
			$days = explode('-', $service['SvcCommitments']);
			// remove non-alphanumeric
			foreach( $days as $key => $val ){
				$days[$key] = preg_replace('/[^0-9]/', '', $val) + $this->processing;
			}
			
			if( sizeof($days) == 1 ){
				$transtime = $days[0] . ' ' . MODULE_SHIPPING_USPS_TEXT_BUSINESS_DAYS;
			} else {
				$transtime = $days[0] . ' - ' . $days[1] . ' ' . MODULE_SHIPPING_USPS_TEXT_BUSINESS_DAYS;
			}
		} 
		
		elseif( strpos($service['SvcCommitments'], 'week') ){
			// don't add processing time
			// TODO: support if necessary, display "varies" for now
			$transtime = MODULE_SHIPPING_USPS_TEXT_VARIES;
		} 
		
		// n/a or varies
		else {
			$transtime = MODULE_SHIPPING_USPS_TEXT_VARIES;
		}
		
		
		return array(
			'id' => $qid,
			'name' => $qname,
			'retailavail' => $retailavailable,
			'onlineavail' => $onlineavailable,
			'retailrate' => $retailrate,
			'onlinerate' => $onlinerate,
			'retailinsval' => $retailinsfor,
			'onlineinsval' => $onlineinsfor,
			'transtime' => $transtime
		);
	}
	// END INTERNATIONAL RESPONSE
	
	
	/**
	 * logs a type change
	 * 
	 * Expected Params:
	 * 	- (string) type
	 * 	- (array) changes
	 */
	private function logTypeChange( $params = array() ){		
		// if new change have been detected, send a notification email
		if( $new_changes = $this->updateTypeChangeStorage($params) ){
			$this->sendNotificationEmailTypeChange(array(
				'type' => $params['type'],
				'changes' => $new_changes,
			));
		}
	}
	
	
	/**
	 * updates type change storage value
	 * 
	 * Expected Params:
	 * 	- (string) type
	 * 	- (array) changes
	 */
	private function updateTypeChangeStorage( $params = array() ){
		$new_changes = array();
		
		// determine what configuration value key we're working with..
		$cfg_key = ($params['type'] === 'International'
				?
			'MODULE_SHIPPING_USPS_INTL_TYPE_CHANGE_DETECTED'
				:
			'MODULE_SHIPPING_USPS_DMSTC_TYPE_CHANGE_DETECTED'
		);
		
		// query existing changes..
		$q = tep_db_query("
			SELECT	
				configuration_value
			FROM
				" . TABLE_CONFIGURATION . "
			WHERE
				configuration_key = '" . $cfg_key . "'
		");
		
		// fetch result; older PHP version compatible, so ugly..
		$r = tep_db_fetch_array($q);
		$existing_changes = json_decode($r['configuration_value'], true);
				
		// remove any change that have been added..
		$changes_added = false;
		foreach( $existing_changes as $k => $change ){
			if( in_array($change, ($params['type'] === 'International'
					?
				$this->intl_types
					:
				$this->types
			)) ){
				unset($existing_changes[$k]);
				$changes_added = true;
			}
		}
		
		// check for new changes..
		foreach( $params['changes'] as $change ){
			if( ! in_array($change, $existing_changes) ){
				$new_changes[] = $change;
			} 
		}
		
		// if changes have been added or we've got new changes, update the db..
		if( $changes_added || $new_changes ){
			tep_db_query("
				UPDATE	
					" . TABLE_CONFIGURATION . "
				SET
					configuration_value = '" . tep_db_input(
						json_encode(array_merge(
							$existing_changes,
							$new_changes
						))
					) . "'
				WHERE
					configuration_key = '" . $cfg_key . "'
			");
		}
		
		return $new_changes;
	}
	
	
	/**
	 * sends a notification email regarding type changes
	 * 
	 * Expected Params:
	 * 	- (string) type
	 * 	- (array) changes	 
	 */
	private function sendNotificationEmailTypeChange( $params = array() ){
		tep_mail(
			STORE_OWNER,
			STORE_OWNER_EMAIL_ADDRESS,
			'USPS ' . $params['type'] . ' Shipping Type Change',
			'The following unsupported shipping types have been detected:'
				. "\n"
				. implode("\n", $params['changes'])
				. "\n\n"
				. "Community Support: "
				. "https://github.com/Evan-R/USPS-osCommerce/issues"
			,
			'osCommerce USPS Shipping Module [' . $this->version . ']',
			STORE_OWNER_EMAIL_ADDRESS
		);
	}
}
// ENDS USPS CLASS

// Required configuration functions
// USPS Methods.  Added by Greg Deeth

// Alias function for Store configuration values in the Administration Tool
function usps_cfg_select_multioption($select_array, $key_value, $key = '') {
	for ($i=0; $i<sizeof($select_array); $i++) {
		$name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
		$string .= '<br /><input type="checkbox" name="' . $name . '" value="' . $select_array[$i] . '"';
		$key_values = explode( ", ", $key_value);
		if ( in_array($select_array[$i], $key_values) ) $string .= ' CHECKED';
		$string .= '> ' . $select_array[$i];
	}
	$string .= '<input type="hidden" name="' . $name . '" value="--none--">';
	return $string;
}

// Alias function for Store configuration values in the Administration Tool.
function usps_cfg_multiinput_list($select_array, $key_value, $key = '') {
	$key_values = explode( ", ", $key_value);

	for ($i=0; $i<sizeof($select_array); $i++) {
		$name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
		$string .= '<br /><input type="text" name="' . $name . '" value="' . $key_values[$i] . '" size="7"> ' . $select_array[$i];
	}
	$string .= '<input type="hidden" name="' . $name . '" value="--none--">';
	return $string;
}

/**
 * displays a json string as a list
 *
 * @param (string) $cfg_value
 */
function usps_cfg_display_json_as_list( $cfg_value ){
	$array = json_decode($cfg_value, true);

	$string = '<ul>';
	foreach( $array as $li ){
		$string .= '<li><pre>' . $li . '</pre></li>';
	}
	$string .= '</ul>';

	return $string;
}


