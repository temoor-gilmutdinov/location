<?php
namespace Drupal\location\Tests;

/**
 * Class LocationTestCase.
 */
class LocationTestCase extends \Drupal\simpletest\WebTestBase {

  protected $profile = 'standard';

  /**
   * Custom assertion -- will check each element of an array against a reference value.
   */
  protected function assertArrayEpsilon($result, $expected, $epsilon, $message = '', $group = 'Other') {
    foreach ($expected as $k => $test) {
      $lower = $test - $epsilon;
      $upper = $test + $epsilon;
      if ($result[$k] < $lower || $result[$k] > $upper) {
        $this->assert('fail', $message ? $message : t('Value deviates by @amt, which is more than @maxdev.', [
          '@amt' => abs($test - $result[$k]),
          '@maxdev' => $epsilon,
        ]), $group);
      }
      else {
        $this->assert('pass', $message ? $message : t('Value within expected margin.'), $group);
      }
    }
  }

  /**
   * Get a set of location field defaults.
   * This will also enable collection on all parts of the location field.
   */
  protected function getLocationFieldDefaults() {
    // Get the (settable) defaults.
    $defaults = [];
    $d = location_invoke_locationapi($location, 'defaults');
    $fields = location_field_names();
    foreach ($fields as $k => $v) {
      if (!isset($d[$k]['nodiff'])) {
        $defaults[$k] = $d[$k];
      }
    }

    foreach ($defaults as $k => $v) {
      // Change collection to allow.
      $defaults[$k]['collect'] = 1;
    }

    return $defaults;
  }

  /**
   * Flatten a post settings array because drupalPost isn't smart enough to.
   */
  protected function flattenPostData(&$edit) {
    do {
      $edit_flattened = TRUE;
      foreach ($edit as $k => $v) {
        if (is_array($v)) {
          $edit_flattened = FALSE;
          foreach ($v as $kk => $vv) {
            $edit["{$k}[{$kk}]"] = $vv;
          }
          unset($edit[$k]);
        }
      }
    } while (!$edit_flattened);
  }

  protected function addLocationContentType(&$settings, $add = []) {
    // find a non-existent random type name.

    $name = strtolower($this->randomName(8));

    // Get the (settable) defaults.
    $defaults = $this->getLocationFieldDefaults();

    $settings = [
      'name' => $name,
      'type' => $name,
      'location_settings' => [
        'multiple' => [
          'max' => 1,
          'add' => 1,
        ],
        'form' => ['fields' => $defaults],
      ],
    ];

    //$settings['location_settings'] = array_merge_recursive($settings['location_settings'], $add);
    $this->flattenPostData($settings);
    $add = ['location_settings' => $add];
    $this->flattenPostData($add);
    $settings = array_merge($settings, $add);
    $this->drupalPost('admin/structure/types/add', $settings, 'Save content type');
    $this->refreshVariables();
    // @FIXME
    // // @FIXME
    // // The correct configuration object could not be determined. You'll need to
    // // rewrite this call manually.
    // $settings = variable_get('location_settings_node_' . $name, array());


    return $name;
  }

  /**
   * Delete a node.
   */
  protected function deleteNode($nid) {
    $nid->delete();
  }

  /**
   * Order locations in a node by LID for testing repeatability purposes.
   */
  protected function reorderLocations(&$node, $field = 'locations') {
    $locations = [];
    foreach ($node->{$field} as $location) {
      if ($location['lid']) {
        $locations[$location['lid']] = $location;
      }
    }
    ksort($locations);
    $node->{$field} = [];
    foreach ($locations as $location) {
      $node->{$field}[] = $location;
    }
  }

  /**
   * Creates a node based on default settings. This uses the internal simpletest
   * browser, meaning the node will be owned by the current simpletest _browser user.
   *
   * Code modified from #212304.
   * This is mainly for testing for differences between node_save() and
   * submitting a node/add/* form.
   *
   * @param values
   *   An associative array of values to change from the defaults, keys are
   *   node properties, for example 'body' => 'Hello, world!'.
   * @return object Created node object.
   */
  protected function drupalCreateNodeViaForm($values = []) {
    $defaults = [
      'type' => 'page',
      'title' => $this->randomName(8),
    ];

    $edit = ($values + $defaults);

    if (empty($edit['body'])) {
      $content_type = db_fetch_array(db_query("select name, has_body from {node_type} where type='%s'", $edit['type']));

      if ($content_type['has_body']) {
        $edit['body'] = $this->randomName(32);
      }
    }
    $type = $edit['type'];
    // Only used in URL.
    unset($edit['type']);
    $this->flattenPostData($edit);
    $this->drupalPost('node/add/' . str_replace('_', '-', $type), $edit, t('Save'));

    $node = \Drupal::entityManager()->getStorage('node')->load([
      'title' => $edit['title']
      ]);
    $this->assertRaw(t('@type %title has been created.', [
      '@type' => node_get_types('name', $node),
      '%title' => $edit['title'],
    ]), t('Node created successfully.'));

    return $node;
  }

}
