<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Ajax add to cart tests for colorbox messages.
 *
 * @ingroup commerce_ajax_atc
 *
 * @group commerce_ajax_atc
 */
class AjaxColorboxTest extends AjaxAddToCartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'colorbox_load',
    'colorbox_library_test',
  ];

  /**
   * Tests ajax with a colorbox message.
   */
  public function testAjaxColorboxMessage() {
    // Enable colorbox, and check the default settings.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'colorbox');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldValueEquals('success_message', '');
    $this->assertSession()->fieldValueEquals('cart_link_text', '');
    $this->assertSession()->fieldValueEquals('colorbox_width', '400');
    $this->assertSession()->fieldValueEquals('colorbox_height', '300');

    // Test the colorbox visibility, default size, and default message.
    $this->drupalGet($this->product1->toUrl());
    $this->assertSession()->pageTextContains('0 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementAttributeContains('css', '#cboxOverlay', 'style', 'visibility: visible;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'width: 400px;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'height: 300px;');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'AJAX Product added to ');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'your cart');
    $this->assertSession()->linkExists('your cart');
    // Test the cart block contents.
    // @todo Make sure the page wasn't actually reloaded.
    $this->assertSession()->pageTextContains('1 item');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);

    // Set and check custom values for all colorbox dependant fields.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->fillField('colorbox_width', '300');
    $this->getSession()->getPage()->fillField('colorbox_height', '500');
    $this->getSession()->getPage()->checkField('include_cart_button');
    $this->getSession()->getPage()->fillField('cart_button_text', 'Go to cart');
    $this->getSession()->getPage()->checkField('include_checkout_button');
    $this->getSession()->getPage()->fillField('checkout_button_text', 'Proceed to checkout');
    $this->getSession()->getPage()->checkField('include_close_button');
    $this->getSession()->getPage()->fillField('close_button_text', 'Continue shopping');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('success_message', '[variation_title] has been added to');
    $this->assertSession()->fieldValueEquals('cart_link_text', 'your cart');
    $this->assertSession()->fieldValueEquals('colorbox_width', '300');
    $this->assertSession()->fieldValueEquals('colorbox_height', '500');
    $this->assertSession()->fieldValueEquals('cart_button_text', 'Go to cart');
    $this->assertSession()->fieldValueEquals('checkout_button_text', 'Proceed to checkout');
    $this->assertSession()->fieldValueEquals('close_button_text', 'Continue shopping');

    // Test the colorbox visibility, custom size, custom message, and buttons.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('1 item');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementAttributeContains('css', '#cboxOverlay', 'style', 'visibility: visible;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'display: block;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'width: 300px;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'height: 500px;');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Go to cart');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Proceed to checkout');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Continue shopping');
    // Close the colorbox.
    $this->getSession()->getPage()->clickLink('Continue shopping');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementAttributeContains('css', '#cboxOverlay', 'style', 'display: none;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'display: none;');
    // Test the cart block contents.
    // @todo Make sure the page wasn't actually reloaded.
    $this->assertSession()->pageTextContains('2 items');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    $this->assertSession()->pageTextContains($this->variation2->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());

    // Use the go to cart link in the colorbox to get to the cart page.
    $this->drupalGet($this->product2->toUrl());
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->clickLink('Go to cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);

    // Use the Checkout link in the colorbox to get to the order page.
    $this->drupalGet($this->product2->toUrl());
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->clickLink('Proceed to checkout');
    $this->assertSession()->pageTextContains('Order information');
    $this->getSession()->getPage()->hasButton('Continue to review');

    // Test colorbox with the twig template and view mode.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->checkboxNotChecked('use_twig_template');
    $this->getSession()->getPage()->checkField('use_twig_template');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->checkboxChecked('use_twig_template');

    // Test the colorbox visibility, custom size, custom message, and buttons.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('4 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementAttributeContains('css', '#cboxOverlay', 'style', 'visibility: visible;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'display: block;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'width: 300px;');
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'height: 500px;');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Go to cart');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Proceed to checkout');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Continue shopping');
    // Test price, title, and sku fileds being displayed with the view mode.
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Price');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', '$100.00');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'Title');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'AJAX Product 2');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', 'SKU');
    $this->assertSession()->elementContains('css', '#cboxLoadedContent', '1235');
  }

}
