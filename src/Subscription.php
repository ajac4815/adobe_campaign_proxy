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
    $response = $this->proxy->post($endpoint, $data);
    if ($response) {
      return $response->PKey;
    }
    return FALSE;
  }

  /**
   * Get a profile.
   *
   * @param string $email
   *   The email identifier.
   *
   * @return object|bool
   *   Profile ID or false.
   */
  public function getProfile(string $email) {
    $endpoint = "profileAndServices/profile/byText?text={$email}&filterType=email";
    $data = $this->proxy->get($endpoint);
    if ($data && $data->content) {
      $data = reset($data->content);
      return $data;
    }
    return FALSE;
  }

  /**
   * Get a subscription service.
   *
   * @param string $service
   *   The service ID.
   *
   * @return string|false
   *   The service key or false.
   */
  public function getService(string $service) {
    $endpoint = "profileAndServices/service/{$service}";
    $data = $this->proxy->get($endpoint);
    if ($data) {
      return $data->PKey;
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
   *
   * @return string|bool
   *   Result being one of  true, false, or duplicate.
   */
  public function subscribe(string $service, string $email) {
    // Create profile if one does not already exist.
    $profile = $this->getProfile($email);
    if (!$profile) {
      $profile_key = $this->createProfile($email);
    }
    else {
      $profile_key = $profile->PKey;
      // Check for existing subscription.
      $subscription_url = $profile->subscriptions->href;
      if ($subscription_url) {
        // Sub. url uses different key format than profile key.
        $subscription_url = str_replace(
          $this->proxy::urlBase(),
          '',
          $subscription_url
        );
        $subscriptions = $this->proxy->get($subscription_url);
        if ($subscriptions && $subscriptions->content) {
          foreach ($subscriptions->content as $subscription) {
            if ($subscription->serviceName === $service) {
              return "DUPLICATE";
            }
          }
        }
      }
    }
    if ($profile_key) {
      // Get service and subscribe.
      $service_key = $this->getService($service);
      if ($service_key) {
        $endpoint = "profileAndServices/service/{$service_key}/subscriptions/";
        $data = ['subscriber' => ['PKey' => $profile_key]];
        $response = $this->proxy->post($endpoint, $data);
        if ($response) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
