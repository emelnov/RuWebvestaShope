<?php

namespace Drupal\Tests\commerce_ajax_atc\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce_ajax_atc\Functional\AjaxAddToCartTestBase;

/**
 * Base class for commerce ajax add to cart tests.
 */
abstract class AjaxAttributeTestBase extends AjaxAddToCartTestBase {

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $product3;

  /**
   * The color attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $colorAttributes;

  /**
   * The size attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $sizeAttributes;

  /**
   * The variations to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation[]
   */
  protected $variations;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->product3 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Product with attributes',
      'stores' => [$this->store],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load('default');
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);

    // The matrix is intentionally uneven, blue / large is missing.
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];

    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
      ]);
      $variation->get('attribute_color')->setValue($color_attributes[$value[0]]);
      $variation->get('attribute_size')->setValue($size_attributes[$value[1]]);
      $variation->save();
      $this->product3->addVariation($variation);
    }
    $this->product3->save();
    $this->product3 = Product::load($this->product3->id());

    $this->variations = $this->product3->getVariations();
    $this->colorAttributes = $color_attributes;
    $this->sizeAttributes = $size_attributes;

  }

}
