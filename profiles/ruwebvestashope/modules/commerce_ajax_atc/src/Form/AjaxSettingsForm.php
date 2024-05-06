<?php

namespace Drupal\commerce_ajax_atc\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The settings form for commerce_ajax_atc.
 */
class AjaxSettingsForm extends ConfigFormBase {

  const AJAX_MODAL_INPUT_SIZE = 5;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new AjaxSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidator $path_validator
   *   The path validator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, PathValidator $path_validator, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->pathValidator = $path_validator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('commerce_ajax_atc.settings');
    $form['global_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global settings'),
    ];
    $pop_up_options = [
      'non_modal' => $this->t('Non-modal message'),
      'modal_dialog' => $this->t('Modal dialog pop-up'),
    ];
    // If colorbox load is installed, create a colorbox option.
    if ($this->moduleHandler->moduleExists('colorbox_load')) {
      $pop_up_options['colorbox'] = $this->t('Colorbox pop-up');
    }
    $form['global_settings']['pop_up_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Confirmation type'),
      '#default_value' => array_key_exists($config->get('pop_up_type'), $pop_up_options) ? $config->get('pop_up_type') : NULL,
      '#options' => $pop_up_options,
      '#description' => $this->t('Choose the type of ajax add to cart confirmation.'),
      '#required' => TRUE,
    ];
    // Enable ajax for the commerce_variation_cart_form module.
    if ($this->moduleHandler->moduleExists('commerce_variation_cart_form')) {
      $form['global_settings']['variation_cart_form_settings']['enable_variation_cart_form_ajax'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Commerce Variation Cart Form Ajax'),
        '#default_value' => $config->get('enable_variation_cart_form_ajax'),
        '#description' => $this->t('Enable Ajax specifically for the Commerce Variation Cart Form module.'),
      ];
    }
    $form['message_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Message settings'),
      '#description' => $this->t('This message will be displayed unless you enable the twig template on one of the pop-ups.'),
    ];
    $form['message_settings']['success_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#default_value' => $config->get('success_message'),
      '#description' => $this->t('Enter the success message. If left blank the title will be "[variation_title] added to". You can also use the [variation_title] token in your text.'),
    ];
    $form['message_settings']['cart_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cart link text'),
      '#default_value' => $config->get('cart_link_text'),
      '#description' => $this->t('Enter the cart link text. If left blank the link text will be "your cart." Use [none] to display no link.'),
    ];
    // General Pop-up settings.
    $form['pop_up_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pop-Up Settings'),
      '#states' => [
        'visible' => [
          ':input[name="pop_up_type"]' => [
            ['value' => 'modal_dialog'],
            'or',
            ['value' => 'colorbox'],
          ],
        ],
      ],
    ];
    // Modal dialog settings.
    $form['pop_up_settings']['ajax_modal_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Modal settings'),
      '#states' => [
        'visible' => [
          ':input[name="pop_up_type"]' => ['value' => 'modal_dialog'],
        ],
      ],
    ];
    $form['pop_up_settings']['ajax_modal_settings']['ajax_modal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal title'),
      '#default_value' => $config->get('ajax_modal_title'),
      '#description' => $this->t('Enter the title for the modal window, or leave it blank.'),
    ];
    $form['pop_up_settings']['ajax_modal_settings']['ajax_modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal width'),
      '#default_value' => $config->get('ajax_modal_width'),
      '#size' => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix' => ' px',
    ];
    $form['pop_up_settings']['ajax_modal_settings']['ajax_modal_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal height'),
      '#default_value' => $config->get('ajax_modal_height'),
      '#size' => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix' => ' px',
    ];
    // Colorbox settings.
    $form['pop_up_settings']['colorbox_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Colorbox settings'),
      '#states' => [
        'visible' => [
          ':input[name="pop_up_type"]' => ['value' => 'colorbox'],
        ],
      ],
    ];
    $form['pop_up_settings']['colorbox_settings']['colorbox_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Colorbox width'),
      '#default_value' => $config->get('colorbox_width'),
      '#size' => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix' => ' px',
    ];
    $form['pop_up_settings']['colorbox_settings']['colorbox_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Colorbox height'),
      '#default_value' => $config->get('colorbox_height'),
      '#size' => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix' => ' px',
    ];
    $form['pop_up_settings']['button_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Button settings'),
      '#states' => [
        'visible' => [
          ':input[name="pop_up_type"]' => [
            ['value' => 'modal_dialog'],
            'or',
            ['value' => 'colorbox'],
          ],
        ],
      ],
    ];
    $form['pop_up_settings']['button_settings']['include_cart_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include a view cart button'),
      '#default_value' => $config->get('include_cart_button'),
      '#description' => $this->t('Display a view cart button in the pop-up.'),
    ];
    $form['pop_up_settings']['button_settings']['cart_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View cart button text'),
      '#default_value' => $config->get('cart_button_text'),
      '#description' => $this->t('Enter the text for the view cart button. If left blank the link text will be "View cart"'),
      '#states' => [
        'visible' => [
          ':input[name="include_cart_button"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['pop_up_settings']['button_settings']['include_checkout_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include checkout button'),
      '#default_value' => $config->get('include_checkout_button'),
      '#description' => $this->t('Display a checkout button in the pop-up.'),
    ];
    $form['pop_up_settings']['button_settings']['checkout_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkout button text'),
      '#default_value' => $config->get('checkout_button_text'),
      '#description' => $this->t('Enter the text for the checkout button. If left blank the link text will be "Checkout"'),
      '#states' => [
        'visible' => [
          ':input[name="include_checkout_button"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['pop_up_settings']['button_settings']['include_close_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include a close button'),
      '#default_value' => $config->get('include_close_button'),
      '#description' => $this->t('Display a close button in the pop-up.'),
    ];
    $form['pop_up_settings']['button_settings']['close_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button text'),
      '#default_value' => $config->get('close_button_text'),
      '#description' => $this->t('Enter the text for the pop-up close button. If left blank the link text will be "Continue shopping"'),
      '#states' => [
        'visible' => [
          ':input[name="include_close_button"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['pop_up_settings']['use_twig_template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the commerce-ajax-add-to-cart-popup.html.twig template for the pop-up.'),
      '#default_value' => $config->get('use_twig_template'),
      '#description' => $this->t('The variation can be rendered within the template using the Ajax Add to Cart Pop-up view mode.'),
      '#states' => [
        'visible' => [
          ':input[name="pop_up_type"]' => [
            ['value' => 'modal_dialog'],
            'or',
            ['value' => 'colorbox'],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('commerce_ajax_atc.settings');
    $config
      ->set('pop_up_type', $form_state->getValue('pop_up_type'))
      ->set('include_cart_button', $form_state->getValue('include_cart_button'))
      ->set('cart_button_text', $form_state->getValue('cart_button_text'))
      ->set('include_checkout_button', $form_state->getValue('include_checkout_button'))
      ->set('checkout_button_text', $form_state->getValue('checkout_button_text'))
      ->set('include_close_button', $form_state->getValue('include_close_button'))
      ->set('close_button_text', $form_state->getValue('close_button_text'))
      ->set('success_message', $form_state->getValue('success_message'))
      ->set('cart_link_text', $form_state->getValue('cart_link_text'))
      ->set('use_twig_template', $form_state->getValue('use_twig_template'))
      ->set('ajax_modal_title', $form_state->getValue('ajax_modal_title'))
      ->set('ajax_modal_width', $form_state->getValue('ajax_modal_width') ?: '400')
      ->set('ajax_modal_height', $form_state->getValue('ajax_modal_height') ?: '300')
      ->set('colorbox_width', $form_state->getValue('colorbox_width') ?: '400')
      ->set('colorbox_height', $form_state->getValue('colorbox_height') ?: '300')
      ->set('enable_variation_cart_form_ajax', $form_state->getValue('enable_variation_cart_form_ajax'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_ajax_atc.settings',
    ];
  }

}
