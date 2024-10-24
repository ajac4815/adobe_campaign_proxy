<?php

namespace Drupal\adobe_campaign_proxy\Plugin\WebformHandler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subscription Webform Handler.
 *
 * @WebformHandler(
 *   id = "adobe_campaign_subscribe",
 *   label = @Translation("Adobe Campaign Subscription"),
 *   category = @Translation("Adobe Campaign"),
 *   description = @Translation("Subscribe submissions to Adobe Campaign service."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL
 * )
 */
class Subscribe extends WebformHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The subscription service.
   *
   * @var \Drupal\adobe_campaign_proxy\Subscription
   */
  protected $subscription;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->subscription = $container->get('adobe_campaign_proxy.subscription');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $service = $webform_submission->getElementData('service');
    $mail = $webform_submission->getElementData('email');
    if ($service && $mail) {
      $result = $this->subscription->subscribe($service, $mail);
      if ($result === 'DUPLICATE') {
        $this->messenger()->addWarning('An existing subscription has been found for this email address.');
        return;
      }
      elseif ($result) {
        return;
      }
    }
    $this->messenger()->addError('There was an issue with your subscription. Please try again.');
  }

}
