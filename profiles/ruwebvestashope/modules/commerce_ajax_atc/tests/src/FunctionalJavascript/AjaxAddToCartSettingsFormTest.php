<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Ajax add to cart tests.
 *
 * @ingroup commerce_ajax_atc
 *
 * @group commerce_ajax_atc
 */
class AjaxAddToCartSettingsFormTest extends AjaxAddToCartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_variation_cart_form',
    'colorbox_load',
  ];

  /**
   * Tests add to cart settings form.
   */
  public function testAddToCartSettingsForm() {
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertTrue($this->getSession()->getPage()->findField('enable_variation_cart_form_ajax')->isVisible());

    // Check the non-modal states visibility.
    $this->assertSession()->fieldExists('pop_up_type');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    // All this stuff should not be visible.
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-pop-up-settings')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('ajax_modal_title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('ajax_modal_width')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('ajax_modal_height')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('colorbox_width')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('colorbox_height')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('include_cart_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('cart_button_text')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('include_checkout_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('checkout_button_text')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('include_close_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('close_button_text')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('use_twig_template')->isVisible());
    // Success message and cart link should be visible.
    $this->assertTrue($this->getSession()->getPage()->findField('success_message')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('cart_link_text')->isVisible());

    // Check the modal dialog states visibility and default settings.
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'modal_dialog');
    $this->assertTrue($this->getSession()->getPage()->find('css', '#edit-pop-up-settings')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('ajax_modal_width')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('ajax_modal_height')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_cart_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('cart_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_cart_button');
    $this->assertTrue($this->getSession()->getPage()->findField('cart_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_checkout_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('checkout_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_checkout_button');
    $this->assertTrue($this->getSession()->getPage()->findField('checkout_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_close_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('close_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_close_button');
    $this->assertTrue($this->getSession()->getPage()->findField('close_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('use_twig_template')->isVisible());
    $this->getSession()->getPage()->uncheckField('include_cart_button');
    $this->getSession()->getPage()->uncheckField('include_close_button');
    $this->getSession()->getPage()->uncheckField('include_checkout_button');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // If empty our default width and height will set to default when saved.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('ajax_modal_width', '400');
    $this->assertSession()->fieldValueEquals('ajax_modal_height', '300');

    // Check the colorbox states visibility and default settings.
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'colorbox');
    $this->assertTrue($this->getSession()->getPage()->find('css', '#edit-pop-up-settings')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('colorbox_width')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('colorbox_height')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_cart_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('cart_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_cart_button');
    $this->assertTrue($this->getSession()->getPage()->findField('cart_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_checkout_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('checkout_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_checkout_button');
    $this->assertTrue($this->getSession()->getPage()->findField('checkout_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('include_close_button')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->findField('close_button_text')->isVisible());
    $this->getSession()->getPage()->checkField('include_close_button');
    $this->assertTrue($this->getSession()->getPage()->findField('close_button_text')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->findField('use_twig_template')->isVisible());
    $this->getSession()->getPage()->uncheckField('include_cart_button');
    $this->getSession()->getPage()->uncheckField('include_close_button');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // If empty our default width and height will set to default when saved.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('colorbox_width', '400');
    $this->assertSession()->fieldValueEquals('colorbox_height', '300');
  }

}
