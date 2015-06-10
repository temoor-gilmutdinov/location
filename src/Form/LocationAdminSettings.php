<?php

/**
 * @file
 * Contains \Drupal\location\Form\LocationAdminSettings.
 */

namespace Drupal\location\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LocationAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'location_admin_settings';
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
    // Recalculate the supported countries.
    cache_clear_all('location:supported-countries', 'cache');
    _location_supported_countries();

    $iso_list_sorted = location_get_iso3166_list();
    array_multisort($iso_list_sorted);
    $iso_list_sorted = array_merge(['' => ''], $iso_list_sorted);

    $form = [];
    $form['location_default_country'] = [
      '#type' => 'select',
      '#title' => t('Default country selection'),
      '#default_value' => \Drupal::config('location.settings')->get('location_default_country'),
      '#options' => $iso_list_sorted,
      '#description' => t('This will be the country that is automatically selected when a location form is served for a new location.'),
    ];
    $form['location_display_location'] = [
      '#type' => 'radios',
      '#title' => t('Toggle location display'),
      '#default_value' => \Drupal::config('location.settings')->get('location_display_location'),
      '#options' => [
        0 => t('Disable the display of locations.'),
        1 => t('Enable the display of locations.'),
      ],
      '#description' => t('If you are interested in turning off locations and having a custom theme control their display, you may want to disable the display of locations so your theme can take that function.'),
    ];

    $form['location_use_province_abbreviation'] = [
      '#type' => 'radios',
      '#title' => t('Province display'),
      '#default_value' => \Drupal::config('location.settings')->get('location_use_province_abbreviation'),
      '#options' => [
        0 => t('Display full province name.'),
        1 => t('Display province/state code.'),
      ],
    ];

    // @FIXME
    // url() expects a route name or an external URI.
    // $form['location_usegmap'] = array(
    //     '#type' => 'checkbox',
    //     '#title' => t('Use a Google Map to set latitude and longitude '),
    //     '#default_value' => variable_get('location_usegmap', FALSE),
    //     '#description' => t(
    //       'If the gmap.module is installed and <a href="@enabled">enabled</a>, and this setting is also turned on, users that are allowed to manually enter latitude/longitude coordinates will be able to do so with an interactive Google Map.  You should also make sure you have entered a <a href="@google_maps_api_key">Google Maps API key</a> into your <a href="@gmap_module_settings">gmap module settings</a>.',
    //       array(
    //         '@enabled' => url('admin/build/modules'),
    //         '@google_maps_api_key' => 'http://www.google.com/apis/maps',
    //         '@gmap_module_settings' => url('admin/config/content/gmap')
    //       )
    //     ),
    //   );


    $form['location_locpick_macro'] = [
      '#type' => 'textfield',
      '#title' => t('Location chooser macro'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => \Drupal::config('location.settings')->get('location_locpick_macro'),
      '#description' => t('If you would like to change the macro used to generate the location chooser map, you can do so here. Note: Behaviors <em>locpick</em> and <em>collapsehack</em> are forced to be enabled and cannot be changed.'),
    ];

    $form['location_jit_geocoding'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable JIT geocoding'),
      '#default_value' => \Drupal::config('location.settings')->get('location_jit_geocoding'),
      '#description' => t('If you are going to be importing locations in bulk directly into the database, you may wish to enable JIT geocoding and load the locations with source set to 4 (LOCATION_LATLON_JIT_GEOCODING). The system will automatically geocode locations as they are loaded.'),
    ];

    $form['maplink_external'] = [
      '#type' => 'fieldset',
      '#title' => t('Map link'),
    ];
    $form['maplink_external']['location_maplink_external'] = [
      '#type' => 'checkbox',
      '#title' => t('Open map link in new window'),
      '#default_value' => \Drupal::config('location.settings')->get('location_maplink_external'),
      '#description' => t('Select this if you want the map link to open in a separate window'),
    ];
    $form['maplink_external']['location_maplink_external_method'] = [
      '#type' => 'radios',
      '#title' => t('Open in new window method'),
      '#options' => [
        'target="_blank"' => 'target="_blank"',
        'rel="external"' => 'rel="external"',
      ],
      '#default_value' => \Drupal::config('location.settings')->get('location_maplink_external_method'),
      '#description' => t('If you have selected to open map in a new window this controls the method used to open in a new window.  target="_blank" will just work but is not XTHML Strict compliant.  rel="external" is XHTML Strict compliant but will not open in a new window unless you add some jQuery to your site to add the target attribute. If you are unsure leave set to target="_blank"'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
