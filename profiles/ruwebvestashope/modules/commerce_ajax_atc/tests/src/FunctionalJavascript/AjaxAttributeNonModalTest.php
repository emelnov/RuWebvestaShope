<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAttributeTestBase;

/**
 * Tests the add to cart form.
 *
 * @group commerce
 */
class AjaxAttributeNonModalTest extends AjaxAttributeTestBase {

  /**
   * Tests adding a product to the cart when there are multiple variations.
   */
  public function testNonModalAttributes() {
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

    // Make no selections and make sure the first call is Ajax.
    $this->drupalGet($this->product3->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Small');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'Product with attributes - Red, Small has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('1 item');

    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Assert that the address was updated to contain the query parameter.
    $this->assertEquals('v=5', parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY));
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Medium');

    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'Blue');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertEquals('v=8', parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY));
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'Product with attributes - Blue, Medium has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('2 items');

    // Use AJAX to change the color back to Red, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'Product with attributes - Red, Medium has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('3 item');

    // Add the same variation to cart to make sure our quntity increments.
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'Product with attributes - Red, Medium has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('4 item');

    // Test the cart quantity in storage.
    $this->cart = $this->orderStorage->load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variations[0], $order_items[0]);
    $this->assertOrderItemInOrder($this->variations[4], $order_items[1]);
    $this->assertOrderItemInOrder($this->variations[1], $order_items[2], 2);
  }

}
