(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.commerce_ajax_atc = {
    attach: function (context, settings) {
      parent.jQuery.colorbox.resize({width:drupalSettings.commerce_ajax_atc.colorbox.width , height:drupalSettings.commerce_ajax_atc.colorbox.height});
    }
  };
  Drupal.AjaxCommands.prototype.colorboxLoadClose = function (ajax, response) {
    $.colorbox.close()
  };
  Drupal.attachBehaviors();
})(jQuery, Drupal, drupalSettings);
