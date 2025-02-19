<?php
/**
 * Country Mapping Functions for Claims Management Plugin.
 */

/**
 * Install or update the master country list on plugin activation.
 */
function cm_install_master_country_list() {
    // Master list: country name => ISO code.
    $master_countries = array(
        'Afghanistan'           => 'af',
        'Albania'               => 'al',
        'Algeria'               => 'dz',
        'Andorra'               => 'ad',
        'Angola'                => 'ao',
        'Antigua and Barbuda'   => 'ag',
        'Argentina'             => 'ar',
        'Armenia'               => 'am',
        'Australia'             => 'au',
        'Austria'               => 'at',
        'Azerbaijan'            => 'az',
        'Bahamas'               => 'bs',
        'Bahrain'               => 'bh',
        'Bangladesh'            => 'bd',
        'Barbados'              => 'bb',
        'Belarus'               => 'by',
        'Belgium'               => 'be',
        'Belize'                => 'bz',
        'Benin'                 => 'bj',
        'Bhutan'                => 'bt',
        'Bolivia'               => 'bo',
        'Bosnia and Herzegovina'=> 'ba',
        'Botswana'              => 'bw',
        'Brazil'                => 'br',
        'Brunei'                => 'bn',
        'Bulgaria'              => 'bg',
        'Burkina Faso'          => 'bf',
        'Burundi'               => 'bi',
        'Cabo Verde'            => 'cv',
        'Cambodia'              => 'kh',
        'Cameroon'              => 'cm',
        'Canada'                => 'ca',
        'Central African Republic' => 'cf',
        'Chad'                  => 'td',
        'Chile'                 => 'cl',
        'China'                 => 'cn',
        'Colombia'              => 'co',
        'Comoros'               => 'km',
        'Congo (Congo-Brazzaville)' => 'cg',
        'Costa Rica'            => 'cr',
        'Croatia'               => 'hr',
        'Cuba'                  => 'cu',
        'Cyprus'                => 'cy',
        'Czech Republic'        => 'cz',
        'Denmark'               => 'dk',
        'Djibouti'              => 'dj',
        'Dominica'              => 'dm',
        'Dominican Republic'    => 'do',
        'Ecuador'               => 'ec',
        'Egypt'                 => 'eg',
        'El Salvador'           => 'sv',
        'Equatorial Guinea'     => 'gq',
        'Eritrea'               => 'er',
        'Estonia'               => 'ee',
        'Eswatini'              => 'sz',
        'Ethiopia'              => 'et',
        'Fiji'                  => 'fj',
        'Finland'               => 'fi',
        'France'                => 'fr',
        'Gabon'                 => 'ga',
        'Gambia'                => 'gm',
        'Georgia'               => 'ge',
        'Germany'               => 'de',
        'Ghana'                 => 'gh',
        'Greece'                => 'gr',
        'Grenada'               => 'gd',
        'Guatemala'             => 'gt',
        'Guinea'                => 'gn',
        'Guinea-Bissau'         => 'gw',
        'Guyana'                => 'gy',
        'Haiti'                 => 'ht',
        'Honduras'              => 'hn',
        'Hungary'               => 'hu',
        'Iceland'              => 'is',
        'India'                => 'in',
        'Indonesia'            => 'id',
        'Iran'                 => 'ir',
        'Iraq'                 => 'iq',
        'Ireland'              => 'ie',
        'Israel'               => 'il',
        'Italy'                => 'it',
        'Jamaica'              => 'jm',
        'Japan'                => 'jp',
        'Jordan'               => 'jo',
        'Kazakhstan'           => 'kz',
        'Kenya'                => 'ke',
        'Kiribati'             => 'ki',
        'Kuwait'               => 'kw',
        'Kyrgyzstan'           => 'kg',
        'Laos'                 => 'la',
        'Latvia'               => 'lv',
        'Lebanon'              => 'lb',
        'Lesotho'              => 'ls',
        'Liberia'              => 'lr',
        'Libya'                => 'ly',
        'Liechtenstein'        => 'li',
        'Lithuania'            => 'lt',
        'Luxembourg'           => 'lu',
        'Madagascar'           => 'mg',
        'Malawi'               => 'mw',
        'Malaysia'             => 'my',
        'Maldives'             => 'mv',
        'Mali'                 => 'ml',
        'Malta'                => 'mt',
        'Marshall Islands'     => 'mh',
        'Mauritania'           => 'mr',
        'Mauritius'            => 'mu',
        'Mexico'               => 'mx',
        'Micronesia'           => 'fm',
        'Moldova'              => 'md',
        'Monaco'               => 'mc',
        'Mongolia'             => 'mn',
        'Montenegro'           => 'me',
        'Morocco'              => 'ma',
        'Mozambique'           => 'mz',
        'Myanmar (Burma)'      => 'mm',
        'Namibia'              => 'na',
        'Nauru'                => 'nr',
        'Nepal'                => 'np',
        'Netherlands'          => 'nl',
        'New Zealand'          => 'nz',
        'Nicaragua'            => 'ni',
        'Niger'                => 'ne',
        'Nigeria'              => 'ng',
        'North Korea'          => 'kp',
        'North Macedonia'      => 'mk',
        'Norway'               => 'no',
        'Oman'                 => 'om',
        'Pakistan'             => 'pk',
        'Palau'                => 'pw',
        'Palestine State'      => 'ps',
        'Panama'               => 'pa',
        'Papua New Guinea'     => 'pg',
        'Paraguay'             => 'py',
        'Peru'                 => 'pe',
        'Philippines'          => 'ph',
        'Poland'               => 'pl',
        'Portugal'             => 'pt',
        'Qatar'                => 'qa',
        'Romania'              => 'ro',
        'Russia'               => 'ru',
        'Rwanda'               => 'rw',
        'Saint Kitts and Nevis'=> 'kn',
        'Saint Lucia'          => 'lc',
        'Saint Vincent and the Grenadines' => 'vc',
        'Samoa'                => 'ws',
        'San Marino'           => 'sm',
        'Sao Tome and Principe'=> 'st',
        'Saudi Arabia'         => 'sa',
        'Senegal'              => 'sn',
        'Serbia'               => 'rs',
        'Seychelles'           => 'sc',
        'Sierra Leone'         => 'sl',
        'Singapore'            => 'sg',
        'Slovakia'             => 'sk',
        'Slovenia'             => 'si',
        'Solomon Islands'      => 'sb',
        'Somalia'              => 'so',
        'South Africa'         => 'za',
        'South Korea'          => 'kr',
        'South Sudan'          => 'ss',
        'Spain'                => 'es',
        'Sri Lanka'            => 'lk',
        'Sudan'                => 'sd',
        'Suriname'             => 'sr',
        'Sweden'               => 'se',
        'Switzerland'          => 'ch',
        'Syria'                => 'sy',
        'Tajikistan'           => 'tj',
        'Tanzania'             => 'tz',
        'Thailand'             => 'th',
        'Timor-Leste'          => 'tl',
        'Togo'                 => 'tg',
        'Tonga'                => 'to',
        'Trinidad and Tobago'  => 'tt',
        'Tunisia'              => 'tn',
        'Turkey'               => 'tr',
        'Turkmenistan'         => 'tm',
        'Tuvalu'               => 'tv',
        'Uganda'               => 'ug',
        'Ukraine'              => 'ua',
        'United Arab Emirates' => 'ae',
        'United Kingdom'       => 'gb',
        'United States'        => 'us',
        'Uruguay'              => 'uy',
        'Uzbekistan'           => 'uz',
        'Vanuatu'              => 'vu',
        'Vatican City'         => 'va',
        'Venezuela'            => 've',
        'Vietnam'              => 'vn',
        'Yemen'                => 'ye',
        'Zambia'               => 'zm',
        'Zimbabwe'             => 'zw'
    );
    // Save the master country list.
    update_option( 'cm_master_country_list', $master_countries );
    
    // Build the dialing codes array so that it has exactly the same keys as the master country list.
   $dialing_codes = array(
    'Afghanistan'           => '+93',
    'Albania'               => '+355',
    'Algeria'               => '+213',
    'Andorra'               => '+376',
    'Angola'                => '+244',
    'Antigua and Barbuda'   => '+1-268',
    'Argentina'             => '+54',
    'Armenia'               => '+374',
    'Australia'             => '+61',
    'Austria'               => '+43',
    'Azerbaijan'            => '+994',
    'Bahamas'               => '+1-242',
    'Bahrain'               => '+973',
    'Bangladesh'            => '+880',
    'Barbados'              => '+1-246',
    'Belarus'               => '+375',
    'Belgium'               => '+32',
    'Belize'                => '+501',
    'Benin'                 => '+229',
    'Bhutan'                => '+975',
    'Bolivia'               => '+591',
    'Bosnia and Herzegovina'=> '+387',
    'Botswana'              => '+267',
    'Brazil'                => '+55',
    'Brunei'                => '+673',
    'Bulgaria'              => '+359',
    'Burkina Faso'          => '+226',
    'Burundi'               => '+257',
    'Cabo Verde'            => '+238',
    'Cambodia'              => '+855',
    'Cameroon'              => '+237',
    'Canada'                => '+1',
    'Central African Republic' => '+236',
    'Chad'                  => '+235',
    'Chile'                 => '+56',
    'China'                 => '+86',
    'Colombia'              => '+57',
    'Comoros'               => '+269',
    'Congo (Congo-Brazzaville)' => '+242',
    'Costa Rica'            => '+506',
    'Croatia'               => '+385',
    'Cuba'                  => '+53',
    'Cyprus'                => '+357',
    'Czech Republic'        => '+420',
    'Denmark'               => '+45',
    'Djibouti'              => '+253',
    'Dominica'              => '+1-767',
    'Dominican Republic'    => '+1-809', // Note: Also +1-829, +1-849 exist.
    'Ecuador'               => '+593',
    'Egypt'                 => '+20',
    'El Salvador'           => '+503',
    'Equatorial Guinea'     => '+240',
    'Eritrea'               => '+291',
    'Estonia'               => '+372',
    'Eswatini'              => '+268',
    'Ethiopia'              => '+251',
    'Fiji'                  => '+679',
    'Finland'               => '+358',
    'France'                => '+33',
    'Gabon'                 => '+241',
    'Gambia'                => '+220',
    'Georgia'               => '+995',
    'Germany'               => '+49',
    'Ghana'                 => '+233',
    'Greece'                => '+30',
    'Grenada'               => '+1-473',
    'Guatemala'             => '+502',
    'Guinea'                => '+224',
    'Guinea-Bissau'         => '+245',
    'Guyana'                => '+592',
    'Haiti'                 => '+509',
    'Honduras'              => '+504',
    'Hungary'               => '+36',
    'Iceland'               => '+354',
    'India'                 => '+91',
    'Indonesia'             => '+62',
    'Iran'                  => '+98',
    'Iraq'                  => '+964',
    'Ireland'               => '+353',
    'Israel'                => '+972',
    'Italy'                 => '+39',
    'Jamaica'               => '+1-876',
    'Japan'                 => '+81',
    'Jordan'                => '+962',
    'Kazakhstan'            => '+7',
    'Kenya'                 => '+254',
    'Kiribati'              => '+686',
    'Kuwait'                => '+965',
    'Kyrgyzstan'            => '+996',
    'Laos'                  => '+856',
    'Latvia'                => '+371',
    'Lebanon'               => '+961',
    'Lesotho'               => '+266',
    'Liberia'               => '+231',
    'Libya'                 => '+218',
    'Liechtenstein'         => '+423',
    'Lithuania'             => '+370',
    'Luxembourg'            => '+352',
    'Madagascar'            => '+261',
    'Malawi'                => '+265',
    'Malaysia'              => '+60',
    'Maldives'              => '+960',
    'Mali'                  => '+223',
    'Malta'                 => '+356',
    'Marshall Islands'      => '+692',
    'Mauritania'            => '+222',
    'Mauritius'             => '+230',
    'Mexico'                => '+52',
    'Micronesia'            => '+691',
    'Moldova'               => '+373',
    'Monaco'                => '+377',
    'Mongolia'              => '+976',
    'Montenegro'            => '+382',
    'Morocco'               => '+212',
    'Mozambique'            => '+258',
    'Myanmar (Burma)'       => '+95',
    'Namibia'               => '+264',
    'Nauru'                 => '+674',
    'Nepal'                 => '+977',
    'Netherlands'           => '+31',
    'New Zealand'           => '+64',
    'Nicaragua'             => '+505',
    'Niger'                 => '+227',
    'Nigeria'               => '+234',
    'North Korea'           => '+850',
    'North Macedonia'       => '+389',
    'Norway'                => '+47',
    'Oman'                  => '+968',
    'Pakistan'              => '+92',
    'Palau'                 => '+680',
    'Palestine State'       => '+970',
    'Panama'                => '+507',
    'Papua New Guinea'      => '+675',
    'Paraguay'              => '+595',
    'Peru'                  => '+51',
    'Philippines'           => '+63',
    'Poland'                => '+48',
    'Portugal'              => '+351',
    'Qatar'                 => '+974',
    'Romania'               => '+40',
    'Russia'                => '+7',
    'Rwanda'                => '+250',
    'Saint Kitts and Nevis' => '+1-869',
    'Saint Lucia'           => '+1-758',
    'Saint Vincent and the Grenadines' => '+1-784',
    'Samoa'                 => '+685',
    'San Marino'            => '+378',
    'Sao Tome and Principe' => '+239',
    'Saudi Arabia'          => '+966',
    'Senegal'               => '+221',
    'Serbia'                => '+381',
    'Seychelles'            => '+248',
    'Sierra Leone'          => '+232',
    'Singapore'             => '+65',
    'Slovakia'              => '+421',
    'Slovenia'              => '+386',
    'Solomon Islands'       => '+677',
    'Somalia'               => '+252',
    'South Africa'          => '+27',
    'South Korea'           => '+82',
    'South Sudan'           => '+211',
    'Spain'                 => '+34',
    'Sri Lanka'             => '+94',
    'Sudan'                 => '+249',
    'Suriname'              => '+597',
    'Sweden'                => '+46',
    'Switzerland'           => '+41',
    'Syria'                 => '+963',
    'Tajikistan'            => '+992',
    'Tanzania'              => '+255',
    'Thailand'              => '+66',
    'Timor-Leste'           => '+670',
    'Togo'                  => '+228',
    'Tonga'                 => '+676',
    'Trinidad and Tobago'   => '+1-868',
    'Tunisia'               => '+216',
    'Turkey'                => '+90',
    'Turkmenistan'          => '+993',
    'Tuvalu'                => '+688',
    'Uganda'                => '+256',
    'Ukraine'               => '+380',
    'United Arab Emirates'  => '+971',
    'United Kingdom'        => '+44',
    'United States'         => '+1',
    'Uruguay'               => '+598',
    'Uzbekistan'            => '+998',
    'Vanuatu'               => '+678',
    'Vatican City'          => '+379',
    'Venezuela'             => '+58',
    'Vietnam'               => '+84',
    'Yemen'                 => '+967',
    'Zambia'                => '+260',
    'Zimbabwe'              => '+263'
);

// Ensure every country in the master list is in the dialing codes array.
foreach ( $master_countries as $country => $iso ) {
    if ( ! isset( $dialing_codes[ $country ] ) ) {
        $dialing_codes[ $country ] = ''; // Set to empty string if not defined.
    }
}

update_option( 'cm_country_dialing_codes', $dialing_codes );
}
register_activation_hook( __FILE__, 'cm_install_master_country_list' );

/**
 * Retrieve the master country list from the database.
 *
 * @return array Associative array of country names => ISO codes.
 */
if ( ! function_exists( 'cm_get_master_country_list' ) ) {
    function cm_get_master_country_list() {
        $mapping = get_option( 'cm_master_country_list' );
        if ( ! is_array( $mapping ) || empty( $mapping ) ) {
            // If not found, install the default master list.
            cm_install_master_country_list();
            $mapping = get_option( 'cm_master_country_list' );
        }
        return $mapping;
    }
}

/**
 * Given a country name, automatically return its ISO code by matching against the master list.
 *
 * @param string $country_name The country name to map.
 * @return string The ISO code if found, or an empty string if not.
 */
if ( ! function_exists( 'cm_map_country_to_iso' ) ) {
    function cm_map_country_to_iso( $country_name ) {
        $country_name = trim( $country_name );
        $master_list  = cm_get_master_country_list();
        foreach ( $master_list as $name => $iso ) {
            if ( strcasecmp( $name, $country_name ) === 0 ) {
                return strtolower( $iso );
            }
        }
        return ''; // No match found.
    }
}

/**
 * Retrieve the URL of the flag image for a given ISO code.
 *
 * @param string $iso_code Two-letter ISO country code.
 * @param string $size The flag image size (default is "16x12").
 * @return string The URL to the flag image.
 */
if ( ! function_exists( 'cm_get_flag_url' ) ) {
    function cm_get_flag_url( $iso_code, $size = '16x12' ) {
        $iso_code = strtolower( $iso_code );
        return "https://flagcdn.com/{$size}/{$iso_code}.png";
    }
}

/**
 * Return an HTML image tag for the flag of the given ISO country code.
 *
 * @param string $iso_code Two-letter ISO country code.
 * @param string $alt Optional alt text.
 * @param string $size The flag image size.
 * @param string $style Optional inline CSS styling.
 * @return string An HTML image tag with the flag.
 */
if ( ! function_exists( 'cm_get_flag_img' ) ) {
    function cm_get_flag_img( $iso_code, $alt = '', $size = '16x12', $style = 'margin-right:5px;' ) {
        $url = cm_get_flag_url( $iso_code, $size );
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . ' flag" style="' . esc_attr( $style ) . '">';
    }
}

/**
 * Retrieve the country dialing codes from the database.
 *
 * @return array Associative array of country names => dialing codes.
 */
if ( ! function_exists( 'cm_get_country_dialing_codes' ) ) {
    function cm_get_country_dialing_codes() {
        $codes = get_option( 'cm_country_dialing_codes' );
        if ( ! is_array( $codes ) || empty( $codes ) ) {
            cm_install_master_country_list();
            $codes = get_option( 'cm_country_dialing_codes' );
        }
        return $codes;
    }
}

/**
 * Given a country name, return its international dialing code.
 *
 * @param string $country_name The country name.
 * @return string The dialing code if found, or an empty string.
 */
if ( ! function_exists( 'cm_get_country_dialing_code' ) ) {
    function cm_get_country_dialing_code( $country_name ) {
        $codes = cm_get_country_dialing_codes();
        foreach ( $codes as $name => $code ) {
            if ( strcasecmp( $name, $country_name ) === 0 ) {
                return $code;
            }
        }
        return '';
    }
}
?>