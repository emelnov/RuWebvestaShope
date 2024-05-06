<?php

namespace Drupal\Tests\commerce_ajax_atc\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Ajax add to cart tests for the vado add to cart form implementation.
 *
 * @ingroup commerce_ajax_atc
 *
 * @group commerce_ajax_atc
 */
class AjaxVadoAddToCartFormTest extends AjaxAddToCartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_vado',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_order_type',
      'access commerce_order overview',
      'access vado administration pages',
      'administer commerce_vado_group',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Switch to the Group add to cart form.
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    if (!$product_view_display) {
      $product_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    // Ensure ajax is disabled on the variations vado add to cart form.
    $product_view_display->setComponent('variations', [
      'type' => 'commerce_vado_group_add_to_cart',
      'region' => 'content',
      'label' => 'hidden',
      'settings' => [
        'combine' => TRUE,
      ],
      'third_party_settings' => [
        'commerce_ajax_atc' => [
          'enable_ajax' => '0',
        ],
      ],
    ]);
    $product_view_display->save();
    // Create the vado add to cart form display.
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.vado_group_add_to_cart');
    if (!$order_item_form_display) {
      $order_item_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'commerce_order_item',
        'bundle' => 'default',
        'mode' => 'vado_group_add_to_cart',
        'status' => TRUE,
      ]);
    }
    // Use the variation title for the vado add to cart form.
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title',
    ]);
    $order_item_form_display->save();

  }

  /**
   * Tests ajax with vado add to cart form.
   *
   * @todo get cart quantity to show on page so we can check that.
   */
  public function testAjaxVadoAddToCartForm() {
    // Check the vado add to cart form without ajax.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'non_modal');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->fieldValueEquals('success_message', '[variation_title] has been added to');
    $this->assertSession()->fieldValueEquals('cart_link_text', 'your cart');
    // Add the product to the cart.
    $this->drupalGet($this->product1->toUrl());
    $this->assertSession()->pageTextContains('0 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->pageTextNotContains('AJAX Product has been added to your cart');
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);

    // Enable Ajax on the variations vado add to cart form.
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    $product_view_display->setComponent('variations', [
      'type' => 'commerce_vado_group_add_to_cart',
      'region' => 'content',
      'label' => 'hidden',
      'settings' => [
        'combine' => TRUE,
      ],
      'third_party_settings' => [
        'commerce_ajax_atc' => [
          'enable_ajax' => '1',
        ],
      ],
    ]);
    $product_view_display->save();
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
