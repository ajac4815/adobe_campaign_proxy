<?php

namespace Drupal\adobe_campaign_proxy;

use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;

/**
 * Proxy class to connect to Adobe Campaign API.
 */
class Proxy {

  /**
   * The adobe token generation URL.
   */
  const GENERATE_TOKEN_URL = 'https://ims-na1.adobelogin.com/ims/token/v3';

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs proxy.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client.
   */
  public function __construct(CacheBackendInterface $cache, ClientInterface $client) {
    $this->cache = $cache;
    $this->client = $client;
  }

  /**
   * Connect to the API.
   *
   * @return string
   *   The access token.
   */
  public function connect() {
    $existing_token = $this->cache->get('adobe_campaign_token');
    if ($existing_token && (int) $existing_token->expire < time()) {
      return $existing_token->data;
    }
    $time = time();
    $token_request = $this->client->request(
      'POST',
      $this::GENERATE_TOKEN_URL,
      [
        'form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => $_SERVER['ADOBE_API_KEY'],
          'client_secret' => $_SERVER['ADOBE_CLIENT_SECRET'],
          'scope' => 'openid,AdobeID,campaign_sdk,additional_info.projectedProductContext,campaign_config_server_general,deliverability_service_general',
        ],
      ]
    );
    $token_body = $token_request->getBody();
    if ($token_body) {
      $token_json = json_decode($token_body);
      if ($token_json) {
        $token = $token_json->access_token;
        $token_expire = $time + $token_json->expires_in;
        $this->cache->set('adobe_campaign_token', $token, $token_expire);
        return $token;
      }
    }
  }

}
