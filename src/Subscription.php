<?php

namespace Drupal\adobe_campaign_proxy;

/**
 * Class to perform operations on subscriptions.
 */
class Subscription {

  /**
   * The proxy API service.
   *
   * @var Proxy
   */
  protected $proxy;

  /**
   * Constructs the subscription service.
   *
   * @param Proxy $proxy
   *   The proxy API service.
   */
  public function __construct(Proxy $proxy) {
    $this->proxy = $proxy;
  }

  /**
   * Get a profile.
   */
  public function getProfile() {
    $response = $this->proxy->get('profileAndServices/profile');
    return $response;
  }

}
