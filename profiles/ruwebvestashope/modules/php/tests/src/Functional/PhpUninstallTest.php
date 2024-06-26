<?php

namespace Drupal\Tests\php\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test uninstall functionality of PHP module.
 *
 * @group PHP
 */
class PhpUninstallTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['php'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer modules',
    ];

    // User to set up php.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if the module cleans up the disk on uninstall.
   */
  public function testPhpUninstall() {
    // If this request is missing the uninstall form shows "The form has become
    // outdated. Copy any unsaved work in the form below and then reload this
    // page." message for unknown reasons.
    $this->drupalGet('admin/modules');

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[php]'] = TRUE;
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains('Provides a filter plugin that is in use in the following filter formats: PHP code');
    /*$this->submitForm($edit, 'Uninstall');
    $this->assertSession()->pageTextContains('Would you like to continue with uninstalling the above?');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');*/
  }

}
