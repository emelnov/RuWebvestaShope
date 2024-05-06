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
class AjaxModalDialogTest extends AjaxAddToCartTestBase {

  /**
   * Tests ajax with a modal dialog message.
   */
  public function testAjaxModalDialogMessage() {
    // Check the modal dialog message as the default message.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'modal_dialog');
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
    $this->assertSession()->elementExists('css', '.ui-dialog');
    $this->assertSession()->elementExists('css', '.ui-widget-overlay');
    $this->assertSession()->elementAttributeContains('css', '.ui-dialog', 'style', 'width: 400px;');
    // @todo Figure out a way to verify our modal height settings. The modal dialog
    // height gets set, but the height subtracts the title bar and button bar.
    $this->assertSession()->elementContains('css', '.ui-dialog', 'AJAX Product added to');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->pageTextContains('1 item');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());

    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);

    // Set and check the modal dialog message as a custom message.
    // Also test changing the pop-up dimensions and adding buttons.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->getSession()->getPage()->selectFieldOption('pop_up_type', 'modal_dialog');
    $this->getSession()->getPage()->fillField('success_message', '[variation_title] has been added to');
    $this->getSession()->getPage()->fillField('cart_link_text', 'your cart');
    $this->getSession()->getPage()->fillField('ajax_modal_title', 'Test Title');
    $this->getSession()->getPage()->fillField('ajax_modal_width', '500');
    $this->getSession()->getPage()->fillField('ajax_modal_height', '600');
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

    // Test the modal visibility, custom size, custom message, and buttons.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('1 item');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('css', '.ui-dialog');
    $this->assertSession()->elementExists('css', '.ui-widget-overlay');
    $this->assertSession()->elementContains('css', '.ui-dialog-title', 'Test Title');
    $this->assertSession()->elementAttributeContains('css', '.ui-dialog', 'style', 'width: 500px;');
    // @todo Figure out a way to verify our modal height settings. The modal dialog
    // height gets set, but the height subtracts the title bar and button bar.
    $this->assertSession()->elementContains('css', '.ui-dialog', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Go to cart');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Proceed to checkout');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Continue shopping');
    // Close the dialog modal.
    $this->assertSession()->waitforButton('Continue shopping')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementNotExists('css', '.ui-dialog');
    $this->assertSession()->elementNotExists('css', '.ui-widget-overlay');
    $this->assertSession()->pageTextContains('2 items');
    $this->assertSession()->pageTextContains($this->variation1->getOrderItemTitle());
    $this->assertSession()->pageTextContains($this->variation2->getOrderItemTitle());
    // Test the cart quantity in storage and on the page.
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());

    // Use the go to cart button in the modal to get to the cart page.
    $this->drupalGet($this->product2->toUrl());
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitforButton('Go to cart')->press();
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);

    // Use the Checkout button to get to the order page.
    $this->drupalGet($this->product2->toUrl());
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitforButton('Proceed to checkout')->press();
    $this->assertSession()->pageTextContains('Order information');
    $this->getSession()->getPage()->hasButton('Continue to review');

    // Test modal with the twig template and view mode.
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->checkboxNotChecked('use_twig_template');
    $this->getSession()->getPage()->checkField('use_twig_template');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet('/admin/commerce/config/ajax-settings');
    $this->assertSession()->checkboxChecked('use_twig_template');

    // Test the modal visibility, custom size, custom message, and buttons.
    $this->drupalGet($this->product2->toUrl());
    $this->assertSession()->pageTextContains('4 items');
    $this->getSession()->getPage()->pressButton('Add to cart');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('css', '.ui-dialog');
    $this->assertSession()->elementExists('css', '.ui-widget-overlay');
    $this->assertSession()->elementContains('css', '.ui-dialog-title', 'Test Title');
    $this->assertSession()->elementAttributeContains('css', '.ui-dialog', 'style', 'width: 500px;');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'AJAX Product 2 has been added to');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'your cart');
    $this->assertSession()->linkExists('your cart');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Go to cart');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Proceed to checkout');
    $this->assertSession()->elementContains('css', '.ui-dialog-buttonpane', 'Continue shopping');
    // Test price, title, and sku fileds being displayed with the view mode.
    $this->assertSession()->elementContains('css', '.ui-dialog', 'Price');
    $this->assertSession()->elementContains('css', '.ui-dialog', '$100.00');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'Title');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'AJAX Product 2');
    $this->assertSession()->elementContains('css', '.ui-dialog', 'SKU');
    $this->assertSession()->elementContains('css', '.ui-dialog', '1235');
  }

}
