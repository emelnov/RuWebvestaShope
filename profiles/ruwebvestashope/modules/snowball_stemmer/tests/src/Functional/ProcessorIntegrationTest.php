<?php

namespace Drupal\Tests\snowball_stemmer\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api_test\PluginTestTrait;
use Drupal\Tests\search_api\Functional\SearchApiBrowserTestBase;

/**
 * Tests Search API processor plugin.
 *
 * @group snowball_stemmer
 */
class ProcessorIntegrationTest extends SearchApiBrowserTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'taxonomy',
    'language',
    'search_api_test_no_ui',
    'snowball_stemmer',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);

    $this->indexId = 'test_index';
    $index = Index::create([
      'name' => 'Test index',
      'id' => $this->indexId,
      'status' => 1,
      'datasource_settings' => [
        'entity:node' => [],
        'entity:user' => [],
      ],
    ]);
    $index->save();

  }

  /**
   * Tests the admin UI for processors.
   */
  public function testProcessorIntegration() {
    $this->loadProcessorsTab();
    $this->assertSession()->fieldExists('status[snowball_stemmer]');
    
    $this->enableProcessor('snowball_stemmer');
    $configuration = [
      'all_fields' => TRUE,
      'exceptions' => ['indian' => 'india'],
    ];
    $form_values = [
      'all_fields' => TRUE,
      'exceptions' => 'indian=india',
    ];
    $this->editSettingsForm($configuration, 'snowball_stemmer', $form_values);

    $this->assertArrayHasKey('snowball_stemmer', $this->loadIndex()->getProcessors());
  }

  /**
   * Test language availability.
   */
  public function testLanguageDisabled() {
    // Set un-stemmable language.
    ConfigurableLanguage::createFromLangcode('xx-lolspeak')->save();

    // Remove stemmable languages.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('default_langcode', 'xx-lolspeak')
      ->save();
    ConfigurableLanguage::load('en')->delete();

    $this->loadProcessorsTab();
    $this->assertSession()->fieldNotExists('status[snowball_stemmer]');
  }

  /**
   * Test language availability.
   */
  public function testLanguageAvailability() {
    // Set un-stemmable language.
    ConfigurableLanguage::createFromLangcode('xx-lolspeak')->save();
    // Add, non-english, stemmable language. 
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Remove English.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('default_langcode', 'xx-lolspeak')
      ->save();
    ConfigurableLanguage::load('en')->delete();

    $this->loadProcessorsTab();
    $this->assertSession()->fieldExists('status[snowball_stemmer]');
  }

  /**
   * Tests that a processor can be enabled.
   *
   * @param string $processor_id
   *   The ID of the processor to enable.
   */
  protected function enableProcessor($processor_id) {
    $this->loadProcessorsTab();

    $edit = [
      "status[$processor_id]" => 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertTrue($this->loadIndex()->isValidProcessor($processor_id), "Successfully enabled the '$processor_id' processor.'");
  }

  /**
   * Enables a processor with a given configuration.
   *
   * @param array $configuration
   *   The configuration to set for the processor.
   * @param string $processor_id
   *   The ID of the processor to edit.
   * @param array|null $form_values
   *   (optional) The processor configuration to set, as it appears in the form.
   *   Only relevant if the processor does some processing on the form values
   *   before storing them, like parsing YAML or cleaning up checkboxes values.
   *   Defaults to using $configuration as-is.
   * @param bool $enable
   *   (optional) If TRUE, explicitly enable the processor. If FALSE, it should
   *   already be enabled.
   * @param bool $unset_fields
   *   (optional) If TRUE, the "fields" property will be removed from the
   *   actual configuration prior to comparing with the given configuration.
   */
  protected function editSettingsForm(array $configuration, $processor_id, array $form_values = NULL, $enable = TRUE, $unset_fields = TRUE) {
    $this->loadProcessorsTab();

    $edit = $this->getFormValues($form_values ?? $configuration, "processors[$processor_id][settings]");
    if ($enable) {
      $edit["status[$processor_id]"] = 1;
    }
    $this->submitForm($edit, 'Save');

    $processor = $this->loadIndex()->getProcessor($processor_id);
    $this->assertInstanceOf(ProcessorInterface::class, $processor, "Successfully enabled the '$processor_id' processor.'");
    if ($processor) {
      $actual_configuration = $processor->getConfiguration();
      unset($actual_configuration['weights']);
      if ($unset_fields) {
        unset($actual_configuration['fields']);
      }
      $configuration += $processor->defaultConfiguration();
      $this->assertEquals($configuration, $actual_configuration, "Processor configuration for processor '$processor_id' was set correctly.");
    }
  }

  /**
   * Loads the test index's "Processors" tab in the test browser, if necessary.
   *
   * @param bool $force
   *   (optional) If TRUE, even load the tab if we are already on it.
   */
  protected function loadProcessorsTab($force = FALSE) {
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/processors';
    if ($force || $this->getAbsoluteUrl($settings_path) != $this->getUrl()) {
      $this->drupalGet($settings_path);
    }
  }

  /**
   * Loads the search index used by this test.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index used by this test.
   */
  protected function loadIndex() {
    $index_storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index_storage->resetCache([$this->indexId]);

    return $index_storage->load($this->indexId);
  }

  /**
   * Converts a configuration array into an array of form values.
   *
   * @param array $configuration
   *   The configuration to convert.
   * @param string $prefix
   *   The common prefix for all form values.
   *
   * @return string[]
   *   An array of form values ready for submission.
   */
  protected function getFormValues(array $configuration, $prefix) {
    $edit = [];

    foreach ($configuration as $key => $value) {
      $key = $prefix . "[$key]";
      if (is_array($value)) {
        // Handling of numerically indexed and associative arrays needs to be
        // different.
        if ($value == array_values($value)) {
          $key .= '[]';
          $edit[$key] = $value;
        }
        else {
          $edit += $this->getFormValues($value, $key);
        }
      }
      else {
        $edit[$key] = $value;
      }
    }

    return $edit;
  }

}
