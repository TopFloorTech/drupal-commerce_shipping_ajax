(function ($) {
  'use strict';

  var shipmentsSelector = 'input[data-drupal-selector^="edit-shipping-information-shipments-"]';

  /**
   * Trigger the change event on the checked shipping method if it's different than it was before.
   *
   * @param context
   *   The context
   */
  function triggerShippingAjax(context) {
    var $checked = $(shipmentsSelector + ':checked', context);
    var $body = $('body');
    var selected = $body.data('selected-shipping-method') || '';
    if ($checked.length) {
      var label = $('label[for="' + $checked.attr('id') + '"]').text();
      if (selected !== label) {
        $checked.trigger('change');
        $body.data('selected-shipping-method', label);
      }
    }
  }

  /**
   * A behavior for auto-triggering the change handler on a shipping method one time.
   *
   * @type {{attach: Drupal.behaviors.commerceShippingAjax.attach}}
   */
  Drupal.behaviors.commerceShippingAjax = {
    attach: function (context, settings) {
      if (context !== document) {
        triggerShippingAjax(context);
      }
    }
  };

}(jQuery));
