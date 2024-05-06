<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Ajax add to cart tests for non-modal and modal messages.
 *
 * @ingroup commerce_ajax_atc
 *
 * @group commerce_ajax_atc
 */
class AjaxNonModalMessageTest extends AjaxAddToCartTestBase {

  /**
   * Tests ajax with a non-modal message.
   *
   * @todo get cart quantity to show on page so we can check that.
   */
  public function testAjaxNonModalMessage() {
    // Check the non-modal message as the default message.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    $this->assertSession()->fieldValueEquals('success_message', '');
    $this->assertSession()->fieldValueEquals('cart_link_text', '');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // Add the product to the cart and check the ajax requests.
    $this->drupalGet($this->product1->toUrl());
    $this->assertSession()->pageTextContains('0 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    // Test the message, and that the cart block refreshed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'AJAX Product added to ');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->pageTextContains('1 item');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);

    // Set and check the non-modal message as a custom message.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('success_message', '[variation_title] has been added to');
    $this->assertSession()->fieldValueEquals('cart_link_text', 'your cart');
    // Add the product to the cart and check the ajax requests.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('1 item');
    $this->getSession()->getPage()->pressButton('Add to cart');
    // Test the message, and that the cart block refreshed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('2 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    // Test the message, and that the cart block refreshed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('3 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    // Test the message, and that the cart block refreshed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('4 items');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    $this->assertSession()->pageTextContains($this->variation2->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
  }

}
