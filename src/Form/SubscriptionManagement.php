<?php

namespace Drupal\adobe_campaign_proxy\Form;

use Drupal\adobe_campaign_proxy\Subscription;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to manage subscriptions.
 */
class SubscriptionManagement extends FormBase {

  /**
   * The subscription service.
   *
   * @var \Drupal\adobe_campaign_proxy\Subscription
   */
  protected $subscriptionManager;

  /**
   * Constructs a SubscriptionManagement form.
   *
   * @param \Drupal\adobe_campaign_proxy\Subscription $subscriptionManager
   *   The subscription service.
   */
  public function __construct(Subscription $subscriptionManager) {
    $this->subscriptionManager = $subscriptionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('adobe_campaign_proxy.subscription')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'adobe_campaign_proxy_sub_management';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $services = $this->subscriptionManager->getSubscriptions();
    $servicesByTopic = $this->subscriptionManager->getSubscriptionsByTopic();
    if ($servicesByTopic && $servicesByTopic['topics']) {
      foreach ($servicesByTopic['topics'] as $key => $value) {
        $form[$key] = [
          '#type' => 'markup',
          '#markup' => "<h3>{$value['label']}</h3>",
        ];
        foreach ($value['services'] as $service) {
          $key = $service['id'];
          $label = $service['label'];
          $form[$key] = [
            '#type' => 'checkbox',
            '#title' => $label,
          ];
        }
      }
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Update',
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
    $test = 'test';
  }

}
