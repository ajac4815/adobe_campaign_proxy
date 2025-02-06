<?php

namespace Drupal\adobe_campaign_proxy;

/**
 * Get and manage subscriptions.
 */
class SubscriptionManager {

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
   * Get all active services.
   *
   * @return array|bool
   *   An array of services or FALSE.
   */
  public function getSubscriptions() {
    // Filter by visibility.
    // Ensures that backend only and/or test services are not included.
    $endpoint = "profileAndServicesExt/service/byVisibility?_parameter=true";
    $data = $this->proxy->get($endpoint);
    if ($data && !empty($data->content)) {
      $serviceData = $data->content;
      $services = [];
      foreach ($serviceData as $service) {
        $name = $service->name;
        $label = $service->label;
        $id = $service->PKey;
        $services[$name] = [
          'label' => $label,
          'id' => $id,
        ];
      }
      return $services;
    }
    return FALSE;
  }

}
