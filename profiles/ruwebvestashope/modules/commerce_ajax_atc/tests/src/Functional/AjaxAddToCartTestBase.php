<?php

namespace Drupal\Tests\commerce_ajax_atc\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\commerce_cart\FunctionalJavascript\CartWebDriverTestBase;

/**
 * Base class for commerce ajax add to cart tests.
 */
abstract class AjaxAddToCartTestBase extends CartWebDriverTestBase {

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation1;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $product1;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $product2;

  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorage
   */
  protected $orderItemStorage;

  /**
   * Modules to enable from ProductWebDriverTestBase.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'options',
    'commerce_ajax_atc',
    'commerce_checkout',
  ];

  /**
   * Permissions plus those from ProductWebDriverTestBase.
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
      'administer commerce_product_type',
      'administer commerce_product fields',
      'administer commerce_product display',
      'administer commerce_product_variation fields',
      'administer commerce_product_variation display',
      'administer commerce_order_item form display',
      'access commerce_product overview',
      'access ajax atc administration pages',
      'access commerce administration pages',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Make sure ajax is enabled on the view display for the product.
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    if (!$product_view_display) {
      $product_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $product_view_display->setComponent('variations', [
      'type' => 'commerce_add_to_cart',
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
    $this->product1 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'AJAX Product',
      'stores' => [$this->store],
      'body' => ['value' => 'This is a product'],
      'variations' => [
        $this->variation1 = $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => '1234',
          'price' => [
            'number' => '50',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);
    $this->product2 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'AJAX Product 2',
      'stores' => [$this->store],
      'body' => ['value' => 'This is another product'],
      'variations' => [
        $this->variation2 = $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => '1235',
          'price' => [
            'number' => '100',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    // Test that ajax is enabled on the add to cart form.
    $this->drupalGet('/admin/commerce/config/product-types/default/edit/display');
    $this->assertSession()->pageTextContains('Ajax is enabled for this add to cart form.');

    // Enable the display mode for the twig template.
    $this->drupalGet('/admin/commerce/config/product-variation-types/default/edit/display');
    $this->getSession()->getPage()->pressButton('Custom display settings');
    $this->getSession()->getPage()->checkField('edit-display-modes-custom-commerce-ajax-atc-popup');
    $this->getSession()->getPage()->pressButton('Save');
    $twig_variation_view_display = EntityViewDisplay::load('commerce_product_variation.default.commerce_ajax_atc_popup');
    $twig_variation_view_display->setComponent('title', [
      'region' => 'content',
    ]);
    $twig_variation_view_display->setComponent('sku', [
      'region' => 'content',
    ]);
    $twig_variation_view_display->removeComponent('list_price');
    $twig_variation_view_display->save();

    $this->orderStorage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $this->orderItemStorage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $this->placeBlock('commerce_cart');
  }

}
