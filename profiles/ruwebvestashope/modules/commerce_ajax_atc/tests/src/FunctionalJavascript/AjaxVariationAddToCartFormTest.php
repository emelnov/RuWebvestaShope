<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Ajax add to cart tests for the variation add to cart form implementation.
 *
 * @ingroup commerce_ajax_atc
 *
 * @group commerce_ajax_atc
 */
class AjaxVariationAddToCartFormTest extends AjaxAddToCartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_variation_cart_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Enable the variation add to cart form.
    $product_variation_view_display = EntityViewDisplay::load('commerce_product_variation.default.default');
    if (!$product_variation_view_display) {
      $product_variation_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_variation',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $product_variation_view_display->setComponent('commerce_variation_cart_form', [
      'region' => 'content',
    ]);
    $product_variation_view_display->save();

    // Set the default add to cart form to an entity reference label.
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    $product_view_display->setComponent('variations', [
      'type' => 'entity_reference_label',
    ]);
    $product_view_display->save();
  }

  /**
   * Tests ajax with variation add to cart form.
   *
   * @todo get cart quantity to show on page so we can check that.
   */
  public function testAjaxVariationAddToCartForm() {
    // Check the variation add to cart form without ajax.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldExists('enable_variation_cart_form_ajax');
    $this->assertSession()->checkboxNotChecked('enable_variation_cart_form_ajax');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to ');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->checkboxNotChecked('enable_variation_cart_form_ajax');
    $this->assertSession()->fieldValueEquals('success_message', '[variation_title] has been added to ');
    $this->assertSession()->fieldValueEquals('cart_link_text', 'your cart');
    // Add the product to the cart without ajax.
    $this->drupalGet($this->product1->toUrl());
    $this->assertSession()->pageTextContains('0 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    // Test the ajax message and class do NOT appear.
    $this->assertSession()->pageTextNotContains('AJAX Product has been added to your cart');
    $this->assertSession()->elementNotExists('css', '.add-to-cart-message');
    // @todo This shows the cart block refresh test is insufficient.
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
    $this->assertSession()->fieldExists('enable_variation_cart_form_ajax');
    $this->assertSession()->checkboxNotChecked('enable_variation_cart_form_ajax');
    $this->getSession()->getPage()->checkField('enable_variation_cart_form_ajax');
    $this->assertSession()->checkboxChecked('enable_variation_cart_form_ajax');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to ');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('success_message', '[variation_title] has been added to ');
    $this->assertSession()->fieldValueEquals('cart_link_text', 'your cart');
    // Add the product to the cart and wait for the ajax request.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('1 item');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Test the message, and that the cart block refreshed.
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.add-to-cart-message', 'your cart');
    $this->assertSession()->pageTextContains('2 items');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    $this->assertSession()->pageTextContains($this->variation2->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
  }

}
