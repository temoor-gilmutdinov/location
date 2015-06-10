<?php

/**
 * @file
 * Contains \Drupal\location\Form\LocationUtilForm.
 */

namespace Drupal\location\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LocationUtilForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'location_util_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface &$form_state) {
    $form['province_clear'] = [
      '#type' => 'fieldset',
      '#title' => t('Clear province cache'),
      '#description' => t('If you have modified location.xx.inc files, you will need to clear the province cache to get Location to recognize the modifications.'),
    ];

    $form['supported_countries_clear'] = [
      '#type' => 'fieldset',
      '#title' => t('Clear supported country list'),
      '#description' => t('If you have added support for a new country, you will need to clear the supported country list to get Location to recognize the modifications.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['province_clear_submit'] = [
      '#type' => 'submit',
      '#value' => t('Clear province cache'),
      '#submit' => [
        'location_util_form_clear_province_cache_submit'
        ],
    ];
    $form['actions']['supported_countries_clear_submit'] = [
      '#type' => 'submit',
      '#value' => t('Clear supported country list'),
      '#submit' => [
        'location_util_form_clear_supported_countries_submit'
        ],
    ];

    return $form;
  }

}
