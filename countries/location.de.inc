<?php
// $Id$

/**
 * Parameters:
 *   -> $location_a is an associative array that represents a full location where
 *        'street'       => the street portions of the location
 *        'supplemental' => additional street portion of the location
 *        'province'     => the province, state, or territory
 *        'country'      => lower-cased two-letter ISO code (REQUIRED)
 *   -> $location_b is associative array that represents a full location in the same way that
 *       parameter $location_b does.
 *
 * Returns: a link to driving directions
 */
function location_map_link_de($location = array(), $hide = array()) {
  $map_links = array();
  // For now, just call the tinfo function.  May want to make this configurable on some level
  // in order to maintain freedom of choice so users and site-admins don't have to be slaves
  // to tinfo!.... not that I have anything personal against tinfo!.
  if ($link = _location_map_link_de_tinfo($location)) {
    $map_links['T-info'] = $link;
}

  return $map_links;
}

function _location_map_link_de_tinfo($location = array()) {
  $get_query = '?';
  $get_query .= 'map_input.country=2';
  if (isset($location['street'])) {
    $get_query .= '&amp;'.'map_input.street='. urlencode($location['street']);
  }
  if ($location['postal_code'] != '') {
    $get_query .= '&amp;'.'map_input.zip='. urlencode($location['postal_code']);
  }
  if ($location['city'] != '') {
    $get_query .= '&amp;'.'map_input.city_manual='. urlencode($location['city']);
  }
//  if ($location['number'] != '') {
//    $get_query .= '&amp;' . 'map_input.number='. urlencode($location['number']);
//  }
  return ('http://www.t-info.de/map/jumppage/map.jsp'. $get_query);
}


function location_map_link_de_providers() {
  return array('tinfo' => array('name' => 'T-Info', 'url' => 'http://www.t-info.de/map/index.jsp', 'tos' => 'http://www.t-info.de/map/application?origin=note.jsp&event=bea.portal.framework.internal.refresh&pageid=TermsOfUse&portal.content_id=30600'));
}

function location_map_link_de_default_providers() {
  return array();
}

/**
 * Parameters:
 *   -> $location_a is an associative array that represents a full location where
 *        'street'       => the street portions of the location
 *        'supplemental' => additional street portion of the location
 *        'province'     => the province, state, or territory
 *        'country'      => lower-cased two-letter ISO code (REQUIRED)
 *   -> $location_b is associative array that represents a full location in the same way that
 *       parameter $location_b does.
 *
 * Returns: a link to driving directions
 *
 * For now, assume site-admin wants driving directions linked to tinfo!
 * Maybe later, we can add some kind of country-specific settings page that allows the site-admin to
 * decide which site to link to for driving directions.
 */
function location_driving_directions_link_de($location_a, $location_b) {
  return _location_driving_directions_link_de_tinfo($location_a, $location_b);
}


/**
 * Parameters:
 *    Function that is called by location_driving_directions_link_ca() under assumption that it
 *    is the chosen function
 *
 * Returns:
 *    a URL with HTTP GET variables
 *    Depending on how full the locationes are, the URL will either point to the driving directions
 *    on tinfo! or, if only partial locationes are provided, a URL that points to the *form* for
 *    tinfo! driving directions where the form is filled with whatever fields have been provided
 *    for the partial location(es).
 */
function _location_driving_directions_link_de_tinfo($location_a, $location_b) {

  foreach ($location_a as $field => $value) {
    $location_a[$field] = trim($value);
  }

  foreach ($location_b as $field => $value) {
    $location_b[$field] = trim($value);
  }

  if ($location_a['country'] == 'de' and $location_b['country'] == 'de') {

    $get_query = '?';
    $get_query .= 'route_input.start_country=2';
    if (isset($location['street'])) {
      $get_query .= '&amp;'.'route_input.start_street='. urlencode($location['street']);
    }
    if ($location['postal_code'] != '') {
      $get_query .= '&amp;'.'route_input.start_zip='. urlencode($location['postal_code']);
    }
    if ($location['city'] != '') {
      $get_query .= '&amp;'.'route_input.start_city_manual='. urlencode($location['city']);
    }
    $get_query .= 'route_input.target_country=2';
    if (isset($location['street'])) {
      $get_query .= '&amp;'.'route_input.target_street='. urlencode($location['street']);
    }
    if ($location['postal_code'] != '') {
      $get_query .= '&amp;'.'route_input.target_zip='. urlencode($location['postal_code']);
    }
    if ($location['city'] != '') {
      $get_query .= '&amp;'.'route_input.target_city_manual='. urlencode($location['city']);
    }
    return ('http://www.t-info.de/map/jumppage/route_stations.jsp'. $get_query);
  }
}

function theme_location_de($location = array(), $hide = array()) {
  $output = '';
  if (count($location)) {
    $output .= "\n";
    $output .= '<div class="location vcard"><div class="adr">'."\n";
    if (!empty($location['name']) and !in_array('name', $hide)) {
      $output .= '<div class="fn">'. $location['name'] .'</div>';
    }

    if (!empty($location['street']) and !in_array('street', $hide)) {
      $output .= '<div class="street-address">'. $location['street'];
      if (!empty($location['additional']) and !in_array('street', $hide)) {
        $output .= ' '. $location['additional'];
      }
      $output .='</div>';
    }

    if ((!empty($location['city']) and !in_array('city', $hide)) or
        (!empty($location['postal_codet']) and !in_array('postal_code', $hide))) {

      $city_postal = array();

      if (!empty($location['postal_code']) and !in_array('postal_code', $hide)) {
        $city_postal[] = '<span class="postal-code">'. $location['postal_code'] .'</span>';
      }

      if (!empty($location['city']) and !in_array('city', $hide)) {
        $city_postal[] = '<span class="locality">'. $location['city'] .'</span>';
      }

      $output .= '<div>'. implode(' ', $city_postal) .'</div>';
    }

    if (!in_array('country', $hide)) {
      $output .= '<div class="country-name">'. t('Germany') .'</div>';
    }

    if (isset($location['latitude']) and isset($location['longitude'])) {
      $output .=  '<div class="geo"><abbr class="latitude" title="'. $location['latitude'] .
        '" /><abbr class="longitude" title="'. $location['latitude'] .'" /></div>';
    }

    $output .= '</div></div>';

  }
  return $output;
}

/**
 * Returns a lat/lon pair of the approximate center of the given postal code in the given country
 *
 * @param $location
 *   An associative array $location where only postal code and country are necessary, but can have the keys:
 *     'street'       => the street portion of the location
 *     'supplemental' => additional street portion of the location
 *     'province'     => the province, state, or territory
 *     'country'      => lower-cased two-letter ISO code (REQUIRED)
 *     'postal_code'  => the international postal code for this location (REQUIRED)
 *
 * @return
 *   An associative array where
 *      'lat' => approximate latitude of the center of the postal code's area
 *      'lon' => approximate longitude of the center of the postal code's area
 *
 */
function _location_latlon_postalcode_de($location = array()) {
  $dash_index == strpos($location['postal_code'], '-');
  // First we strip slash off if we're dealing with a 9-digit US zipcode
  if ($dash_index === FALSE) {
    $location['postal_code'] = substr($location['postal_code'], 0, $dash_index);
  }

  // Now we pad the thing and query.
  $res = db_query("SELECT * FROM {zipcodes} where country = '%s' AND zip = '%s'", $location['country'], str_pad($location['postal_code'], 5, "0", STR_PAD_LEFT));
  if ($row = db_fetch_object($res)) {
    return array('lat' => $row->latitude, 'lon' => $row->longitude, 'city' => $row->city, 'province' => $row->state, 'country' => $row->country);
  }
  else {
    return NULL;
  }
}


function location_province_list_de() {
  return array(
    'BB' => 'Brandenburg',
    'BE' => 'Berlin',
    'BW' => 'Baden-Württemberg',
    'BY' => 'Bayern',
    'HB' => 'Bremen',
    'HE' => 'Hessen',
    'HH' => 'Hamburg',
    'MV' => 'Mecklenburg-Vorpommern',
    'NI' => 'Niedersachsen',
    'NW' => 'Nordrhein-Westfalen',
    'RP' => 'Rheinland-Pfalz',
    'SH' => 'Schleswig-Holstein',
    'SL' => 'Saarland',
    'SN' => 'Sachsen',
    'ST' => 'Sachsen-Anhalt',
    'TH' => 'Thüringen'
  );
}