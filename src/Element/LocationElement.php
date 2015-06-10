<?php

/**
 * @file
 * Contains \Drupal\location\Element\LocationElement.
 */

namespace Drupal\location\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("location_element")
 */
class LocationElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [$class, 'processLocationElement'],
      ],
      '#attributes' => array('class' => array('location')),
    ];
  }

  /**
   * Render API callback: Expands the location_element element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
  public static function processLocationElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // This is TRUE if we are processing a form that already contains values, such as during an AJAX call.
    $element['#attached']['css'][] = drupal_get_path('module', 'location') . '/location.css';

    $element['#tree'] = TRUE;

    if (!isset($element['#title'])) {
      $element['#title'] = t('Location', [], ['context' => 'geolocation']);
    }
    if (empty($element['#location_settings'])) {
      $element['#location_settings'] = [];
    }
    if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
      $element['#default_value'] = [];
    }

    $element['location_settings'] = array(
      '#type' => 'value',
      '#value' => $element['#location_settings'],
    );

    // Ensure this isn't accidentally used later.
    unset($element['#location_settings']);

    // Make a reference to the settings.
    $settings =& $element['location_settings']['#value'];

    if (isset($element['#default_value']['lid']) && $element['#default_value']['lid']) {
      // Keep track of the old LID.
      $element['lid'] = array(
        '#type' => 'value',
        '#value' => $element['#default_value']['lid'],
      );
    }

    // Fill in missing defaults, etc.
    location_normalize_settings($settings, $element['#required']);

    $defaults = location_empty_location($settings);

    if (isset($element['lid']['#value']) && $element['lid']['#value']) {
      $defaults = location_load_location($element['lid']['#value']);
    }

    $fsettings =& $settings['form']['fields'];

    // $settings -> $settings['form']['fields']

    // $defaults is not necessarily what we want.
    // If #default_value was already specified, we want to use that, because
    // otherwise we will lose our values on preview!
    $fdefaults = $defaults;
    foreach ($element['#default_value'] as $k => $v) {
      $fdefaults[$k] = $v;
    }

    $fields = location_field_names();
    foreach ($fields as $field => $title) {
      if (!isset($element[$field])) {
        // @@@ Permission check hook?
        if ($fsettings[$field]['collect'] != 0) {
          $fsettings[$field]['#parents'] = $element['#parents'];
          $element[$field] = location_invoke_locationapi(
            $fdefaults[$field],
            'field_expand',
            $field,
            $fsettings[$field],
            $fdefaults
          );
          $element[$field]['#weight'] = (int) $fsettings[$field]['weight'];
        }
      }

      // If State/Province is using the select widget, update the element's options.
      if ($field == 'province' && $fsettings[$field]['widget'] == 'select') {
        // We are building the element for the first time.
        if (!isset($element['value']['country'])) {
          $country = $fdefaults['country'];
        }
        else {
          $country = $element['#value']['country'];
        }
        $provinces = location_get_provinces($country);
        // The submit handler expects to find the full province name, not the
        // abbreviation. The select options should reflect this expectation.
        $element[$field]['#options'] = array('' => t('Select'), 'xx' => t('NOT LISTED')) + $provinces;
        $element[$field]['#validated'] = TRUE;
      }
    }

    // Only include 'Street Additional' if 'Street' is 'allowed' or 'required'
    if (!isset($element['street'])) {
      unset($element['additional']);
    }

    // @@@ Split into submit and view permissions?
    if (\Drupal::currentUser()->hasPermission('submit latitude/longitude') && $fsettings['locpick']['collect']) {
      $element['locpick'] = array('#weight' => $fsettings['locpick']['weight']);

      if (location_has_coordinates($defaults, FALSE)) {
        $element['locpick']['current'] = array(
          '#type' => 'fieldset',
          '#title' => t('Current coordinates'),
          '#attributes' => array('class' => array('location-current-coordinates-fieldset')),
        );
        $element['locpick']['current']['current_latitude'] = array(
          '#type' => 'item',
          '#title' => t('Latitude'),
          '#markup' => $defaults['latitude'],
        );
        $element['locpick']['current']['current_longitude'] = array(
          '#type' => 'item',
          '#title' => t('Longitude'),
          '#markup' => $defaults['longitude'],
        );
        $source = t('Unknown');
        switch ($defaults['source']) {
          case LOCATION_LATLON_USER_SUBMITTED:
            $source = t('User-submitted');
            break;

          case LOCATION_LATLON_GEOCODED_APPROX:
            $source = t('Geocoded (Postal code level)');
            break;

          case LOCATION_LATLON_GEOCODED_EXACT:
            $source = t('Geocoded (Exact)');
        }
        $element['locpick']['current']['current_source'] = array(
          '#type' => 'item',
          '#title' => t('Source'),
          '#markup' => $source,
        );
      }

      $element['locpick']['user_latitude'] = array(
        '#type' => 'textfield',
        '#title' => t('Latitude'),
        '#default_value' => isset($element['#default_value']['locpick']['user_latitude']) ? $element['#default_value']['locpick']['user_latitude'] : '',
        '#size' => 16,
        '#attributes' => array('class' => array('container-inline')),
        '#maxlength' => 20,
        '#required' => $fsettings['locpick']['collect'] == 2,
      );
      $element['locpick']['user_longitude'] = array(
        '#type' => 'textfield',
        '#title' => t('Longitude'),
        '#default_value' => isset($element['#default_value']['locpick']['user_longitude']) ? $element['#default_value']['locpick']['user_longitude'] : '',
        '#size' => 16,
        '#maxlength' => 20,
        '#required' => $fsettings['locpick']['collect'] == 2,
      );

      $element['locpick']['instructions'] = array(
        '#type' => 'markup',
        '#weight' => 1,
        '#prefix' => '<div class=\'description\'>',
        '#markup' => '<br /><br />' . t(
            'If you wish to supply your own latitude and longitude, you may enter them above.  If you leave these fields blank, the system will attempt to determine a latitude and longitude for you from the entered address.  To have the system recalculate your location from the address, for example if you change the address, delete the values for these fields.'
          ),
        '#suffix' => '</div>',
      );
      if (function_exists('gmap_get_auto_mapid') && \Drupal::config('location.settings')->get('location_usegmap')) {
        $mapid = gmap_get_auto_mapid();
        $map = array_merge(gmap_defaults(), gmap_parse_macro(\Drupal::config('location.settings')->get('location_locpick_macro')));
        $map['id'] = $mapid;
        $map['points'] = array();
        $map['pointsOverlays'] = array();
        $map['lines'] = array();

        $map['behavior']['locpick'] = TRUE;
        $map['behavior']['collapsehack'] = TRUE;
        // Use previous coordinates to center the map.
        if (location_has_coordinates($defaults, FALSE)) {
          $map['latitude'] = (float) $defaults['latitude'];
          $map['longitude'] = (float) $defaults['longitude'];

          $map['markers'][] = array(
            'latitude' => $defaults['latitude'],
            'longitude' => $defaults['longitude'],
            'markername' => 'small gray',
            'offset' => 0,
            'opts' => array(
              'clickable' => FALSE,
            ),
          );
        }
        $element['locpick']['user_latitude']['#map'] = $mapid;
        gmap_widget_setup($element['locpick']['user_latitude'], 'locpick_latitude');
        $element['locpick']['user_longitude']['#map'] = $mapid;
        gmap_widget_setup($element['locpick']['user_longitude'], 'locpick_longitude');

        $element['locpick']['map'] = array(
          '#type' => 'gmap',
          '#weight' => -1,
          '#gmap_settings' => $map,
        );
        $element['locpick']['map_instructions'] = array(
          '#type' => 'markup',
          '#weight' => 2,
          '#prefix' => '<div class=\'description\'>',
          '#markup' => t(
            'You may set the location by clicking on the map, or dragging the location marker.  To clear the location and cause it to be recalculated, click on the marker.'
          ),
          '#suffix' => '</div>',
        );
      }
    }

    if (isset($defaults['lid']) && !empty($defaults['lid'])) {
      $element['re_geocode_location'] = array(
        '#type' => 'checkbox',
        '#title' => t('Re geocode'),
        '#default_value' => isset($fdefaults['re_geocode_location']) ? $fdefaults['re_geocode_location'] : FALSE,
        '#description' => t('Check this box to re-geocode location.'),
      );
      $element['delete_location'] = array(
        '#type' => 'checkbox',
        '#title' => t('Delete'),
        '#default_value' => isset($fdefaults['delete_location']) ? $fdefaults['delete_location'] : FALSE,
        '#description' => t('Check this box to delete this location.'),
      );
    }

    $fieldset_info = element_info('fieldset');
    $element += $fieldset_info;
    $element['#process'] = array_merge($element['#process'], $fieldset_info['#process']);
    if (isset($fieldset_info['#process'])) {
      foreach ($fieldset_info['#process'] as $process) {
        $element = $process($element, $form_state, $form_state['complete form']);
      }
    }

    \Drupal::moduleHandler()->alter('location_element', $element);

    return $element;
  }
}

