<?php /**
 * @file
 * Contains \Drupal\location\Controller\DefaultController.
 */

namespace Drupal\location\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the location module.
 */
class DefaultController extends ControllerBase {

  public function _location_autocomplete($country, $string = '') {
    $counter = 0;
    $string = strtolower($string);
    $string = '/^' . preg_quote($string) . '/';
    $matches = [];

    if (strpos($country, ',') !== FALSE) {
      // Multiple countries specified.
      $provinces = [];
      $country = explode(',', $country);
      foreach ($country as $c) {
        $provinces = $provinces + location_get_provinces($c);
      }
    }
    else {
      $provinces = location_get_provinces($country);
    }

    if (!empty($provinces)) {
      while (list($code, $name) = each($provinces)) {
        if ($counter < 5) {
          if (preg_match($string, strtolower($name))) {
            $matches[$name] = $name;
            ++$counter;
          }
        }
      }
    }
    drupal_json_output($matches);
  }

  public function location_geocoding_parameters_page($country_iso, $service) {
    // @FIXME
// drupal_set_title() has been removed. There are now a few ways to set the title
// dynamically, depending on the situation.
// 
// 
// @see https://www.drupal.org/node/2067859
// drupal_set_title(t('Configure parameters for %service geocoding', array('%service' => $service)), PASS_THROUGH);


    $breadcrumbs = drupal_get_breadcrumb();
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $breadcrumbs[] = l(t('Location'), 'admin/config/content/location');

    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $breadcrumbs[] = l(t('Geocoding'), 'admin/config/content/location/geocoding');

    $countries = location_get_iso3166_list();
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $breadcrumbs[] = l(
    //     $countries[$country_iso],
    //     'admin/config/content/location/geocoding',
    //     array('fragment' => $country_iso)
    //   );

    drupal_set_breadcrumb($breadcrumbs);

    return \Drupal::formBuilder()->getForm('location_geocoding_parameters_form', $country_iso, $service);
  }

}
