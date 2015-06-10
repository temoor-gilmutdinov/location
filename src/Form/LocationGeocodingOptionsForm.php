<?php

/**
 * @file
 * Contains \Drupal\location\Form\LocationGeocodingOptionsForm.
 */

namespace Drupal\location\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LocationGeocodingOptionsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'location_geocoding_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('location.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['location.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface &$form_state) {
    $form = [];

    $form['location_geocode_google_minimum_accuracy'] = [
      '#type' => 'select',
      '#title' => t('Google Maps geocoding minimum accuracy'),
      '#options' => location_google_geocode_accuracy_codes(),
      '#default_value' => \Drupal::config('location.settings')->get('location_geocode_google_minimum_accuracy'),
      '#description' => t('The Google Maps geocoding API returns results with a given accuracy. Any responses below this minimum accuracy will be ignored. See a !accuracy_values_link.', [
        '!accuracy_values_link' => '<a href="http://code.google.com/apis/maps/documentation/reference.html#GGeoAddressAccuracy">description of these values</a>'
        ]),
    ];
    $form['countries'] = [];

    // First, we build two arrays to help us figure out on the fly whether a specific country is covered by a multi-country geocoder,
    // and what the details of the multi-country geocoder are
    // (1) Get list of geocoders.
    $general_geocoders_list = location_get_general_geocoder_list();

    // (2) get data about each geocoder and the list of coutnries covered by each geocoder.
    $general_geocoders_data = [];
    $general_geocoders_countries = [];
    foreach ($general_geocoders_list as $geocoder_name) {
      location_load_geocoder($geocoder_name);
      $info_function = $geocoder_name . '_geocode_info';
      if (function_exists($info_function)) {
        $general_geocoders_data[$geocoder_name] = $info_function();
      }

      $countries_function = $geocoder_name . '_geocode_country_list';
      if (function_exists($countries_function)) {
        $general_geocoders_countries[$geocoder_name] = $countries_function();
      }
    }

    foreach (_location_supported_countries() as $country_iso => $country_name) {
      location_load_country($country_iso);

      $geocoding_options = [];

      $form['countries'][$country_iso] = [
        '#type' => 'markup',
        '#markup' => '',
      ];

      $form['countries'][$country_iso]['label_' . $country_iso] = [
        '#type' => 'markup',
        '#markup' => '<div id="' . $country_iso . '">' . $country_name . '</div>',
      ];

      // Next, we look for options presented by country specific providers.
      $country_specific_provider_function = 'location_geocode_' . $country_iso . '_providers';
      if (function_exists($country_specific_provider_function)) {
        foreach ($country_specific_provider_function() as $name => $details) {
          $geocoding_options[$name . '|' . $country_iso] = '<a href="' . $details['url'] . '">' . $details['name'] . '</a> (<a href="' . $details['tos'] . '">Terms of Use</a>)';
        }
      }

      foreach ($general_geocoders_list as $geocoder_name) {
        if (in_array($country_iso, $general_geocoders_countries[$geocoder_name])) {
          $geocoding_options[$geocoder_name] = '<a href="' . $general_geocoders_data[$geocoder_name]['url'] . '">' . $general_geocoders_data[$geocoder_name]['name'] . '</a> (<a href="' . $general_geocoders_data[$geocoder_name]['tos'] . '">Terms of Use</a>)';
        }
      }

      if (count($geocoding_options)) {
        $geocoding_options = array_merge(['none' => t('None')], $geocoding_options);

        // @FIXME
        // // @FIXME
        // // The correct configuration object could not be determined. You'll need to
        // // rewrite this call manually.
        // $form['countries'][$country_iso]['location_geocode_' . $country_iso] = array(
        //         '#type' => 'radios',
        //         '#default_value' => variable_get('location_geocode_' . $country_iso, 'none'),
        //         '#options' => $geocoding_options,
        //       );

      }
      else {
        $form['countries'][$country_iso]['location_geocode_' . $country_iso] = [
          '#type' => 'markup',
          '#markup' => t('None supported.'),
        ];
      }

      // @FIXME
      // // @FIXME
      // // The correct configuration object could not be determined. You'll need to
      // // rewrite this call manually.
      // $current_value = variable_get('location_geocode_' . $country_iso, 'none');

      if ($current_value == 'none') {
        $form['countries'][$country_iso]['location_geocode_config_link_' . $country_iso] = [
          '#type' => 'markup',
          '#markup' => t('No service selected for country.'),
        ];
      }
      else {
        $current_val_chopped = substr($current_value, 0, strpos($current_value, '|'));
        $geocode_settings_form_function_specific = 'location_geocode_' . $country_iso . '_' . $current_val_chopped . '_settings';
        $geocode_settings_form_function_general = $current_value . '_geocode_settings';

        if (function_exists($geocode_settings_form_function_specific)) {
          $form['countries'][$country_iso]['location_geocode_config_link_' . $country_iso] = [
            '#type' => 'link',
            '#title' => t('Configure parameters'),
            '#href' => 'admin/config/content/location/geocoding/' . $country_iso . '/' . $current_val_chopped,
          ];
        }
        elseif (function_exists($geocode_settings_form_function_general)) {
          $form['countries'][$country_iso]['location_geocode_config_link_' . $country_iso] = [
            '#type' => 'link',
            '#title' => t('Configure parameters'),
            '#href' => 'admin/config/content/location/geocoding/' . $country_iso . '/' . $current_value,
          ];
        }
        else {
          $form['countries'][$country_iso]['location_geocode_config_link_' . $country_iso] = [
            '#type' => 'markup',
            '#markup' => t('No configuration necessary for selected service.'),
          ];
        }
      }
    }
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = 'location_geocoding_options';
    array_unshift($form['#submit'], 'location_geocoding_options_form_submit');

    return $form;
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface &$form_state) {
    $general_geocoders = location_get_general_geocoder_list();
    $general_geocoders_in_use = [];

    foreach ($form_state->getValues() as $key => $value) {
      if (substr($key, 0, 17) == 'location_geocode_' && $key != 'location_geocode_google_minimum_accuracy') {
        if (in_array($value, $general_geocoders)) {
          $general_geocoders_in_use[$value] = $value;
          // @FIXME
          // // @FIXME
          // // The correct configuration object could not be determined. You'll need to
          // // rewrite this call manually.
          // variable_set($key, $value);

        }
      }
    }

    \Drupal::config('location.settings')->set('location_geocode_google_minimum_accuracy', $form_state->getValue(['location_geocode_google_minimum_accuracy']))->save();
    \Drupal::config('location.settings')->set('location_general_geocoders_in_use', $general_geocoders_in_use)->save();
  }

}
