<?php

namespace Drupal\commerce_shipping_ajax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * A helper class for form callbacks for commerce_shipping_ajax.
 */
class CommerceShippingAjax {

  /**
   * The #element_validate callback.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function elementValidate(array $element, FormStateInterface $form_state) {
    /** @var OrderInterface $order */
    $order = \Drupal::routeMatch()->getParameter('commerce_order');
    if (!$order) {
      return;
    }

    $form = $form_state->getCompleteForm();
    if (self::shippingMethodHasChanged($order, $form, $form_state)) {
      /** @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface $order_manager */
      $order_manager = \Drupal::service('commerce_checkout.checkout_order_manager');
      /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase $checkout_flow */
      $checkout_flow = $order_manager->getCheckoutFlow($order)->getPlugin();
      $pane = $checkout_flow->getPane('shipping_information');
      $pane->validatePaneForm($form['shipping_information'], $form_state, $form);
      $pane->submitPaneForm($form['shipping_information'], $form_state, $form);
      /** @var \Drupal\commerce_order\OrderRefreshInterface $order_refresh */
      $order_refresh = \Drupal::service('commerce_order.order_refresh');
      $order_refresh->refresh($order);
      $order->save();
      $form['shipping_information'] = $checkout_flow->buildForm($form['shipping_information'], $form_state, $pane->getStepId());
    }
  }

  /**
   * The #ajax callback for shipping information.
   *
   * @param $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The part of the form to replace.
   */
  public static function elementCallback(&$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Determine if the shipping method has changed since the last submission.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order object.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if the shipping method has changed, FALSE otherwise.
   */
  public static function shippingMethodHasChanged(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    // Save the modified shipments.
    $shipping_methods = [];
    foreach (Element::children($form['shipping_information']['shipments']) as $index) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = $form['shipping_information']['shipments'][$index]['#shipment'];
      $shipping_methods[] = $shipment->getShippingMethodId();
    }
    $storage = $form_state->getStorage();

    $changed = TRUE;
    if (isset($storage['selected_shipping_methods'])) {
      $changed = FALSE;
      foreach ($storage['selected_shipping_methods'] as $index => $shipping_method) {
        if ($shipping_methods[$index] != $shipping_method) {
          $changed = TRUE;
          break;
        }
      }
    }
    if ($changed) {
      $storage['selected_shipping_methods'] = $shipping_methods;
      $form_state->setStorage($storage);
    }
    return $changed;
  }

}
