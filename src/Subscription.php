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
   * Create new profile.
   *
   * @param string $email
   *   The profile email.
   *
   * @return bool
   *   The result.
   */
  public function createProfile(string $email) {
    $endpoint = "profileAndServices/profile";
    $data = ['email' => $email];
    return $this->proxy->post($endpoint, $data);
  }

  /**
   * Get a profile.
   *
   * @param string $email
   *   The email identifier.
   *
   * @return bool
   *   Bool for if profile was found.
   */
  public function getProfile(string $email) {
    $endpoint = "profileAndServices/profile/byText?text={$email}&filterType=email";
    $response = $this->proxy->get($endpoint);
    if ($response) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Subscribe a profile to a service.
   *
   * @param string $service
   *   The service ID to subscribe to.
   * @param string $email
   *   The profile email to subscribe.
   */
  public function subscribe(string $service, string $email) {
    // Create profile if one does not already exist.
    $hasProfile = $this->getProfile($email);
    if (!$hasProfile) {
      $success = $this->createProfile($email);
    }
  }

}
