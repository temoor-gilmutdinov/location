<?php

/**
 * @file
 * Contains \Drupal\location\Form\LocationMapLinkOptionsForm.
 */

namespace Drupal\location\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LocationMapLinkOptionsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'location_map_link_options_form';
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

    $form['countries'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    foreach (_location_supported_countries() as $country_iso => $country_name) {
      location_load_country($country_iso);

      $form['countries'][$country_iso] = [
        '#type' => 'markup',
        '#markup' => '',
      ];

      $form['countries'][$country_iso]['label_' . $country_iso] = [
        '#type' => 'markup',
        '#markup' => $country_name,
      ];

      // Set up '#options' array for mapping providers for the current country.
      $mapping_options = [];
      $provider_function = 'location_map_link_' . $country_iso . '_providers';
      $default_provider_function = 'location_map_link_' . $country_iso . '_default_providers';

      // Default providers will be taken from the country specific default providers
      // function if it exists, otherwise it will use the global function.
      // @FIXME
      // // @FIXME
      // // The correct configuration object could not be determined. You'll need to
      // // rewrite this call manually.
      // $checked = variable_get(
      //       'location_map_link_' . $country_iso,
      //       function_exists($default_provider_function) ? $default_provider_function() : location_map_link_default_providers()
      //     );


      // Merge the global providers with country specific ones so that countries
      // can add to or override the defaults.
      $providers = function_exists($provider_function) ? array_merge(location_map_link_providers(), $provider_function()) : location_map_link_providers();
      foreach ($providers as $name => $details) {
        $mapping_options[$name] = '<a href="' . $details['url'] . '">' . $details['name'] . '</a> (<a href="' . $details['tos'] . '">Terms of Use</a>)';
      }

      if (count($mapping_options)) {
        $form['countries'][$country_iso]['location_map_link_' . $country_iso] = [
          '#title' => '',
          '#type' => 'checkboxes',
          '#default_value' => $checked,
          '#options' => $mapping_options,
        ];
      }
      else {
        $form['countries'][$country_iso]['location_map_link_' . $country_iso] = [
          '#type' => 'markup',
          '#markup' => t('None supported.'),
        ];
      }
    }

    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = 'location_map_link_options';

    return $form;
  }

}
