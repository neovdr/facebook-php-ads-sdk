<?php
/**
 * Copyright 2014 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

namespace FacebookAds;

use FacebookAds\Http\Client;
use FacebookAds\Http\RequestInterface;
use FacebookAds\Http\ResponseInterface;
use FacebookAds\Logger\LoggerInterface;
use FacebookAds\Logger\NullLogger;

class Api {

  /**
   * @var string
   */
  const VERSION = '2.2.1';

  /**
   * @var Api
   */
  private static $instance;

  /**
   * @var Session
   */
  private $session;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var Client
   */
  protected $httpClient;

  /**
   * @var string
   */
  protected $defaultGraphVersion;

  /**
   * @param Client $http_client
   * @param Session $session A Facebook API session
   */
  public function __construct(
    Client $http_client,
    Session $session) {

    $this->httpClient = $http_client;
    $this->session = $session;
  }

  /**
   * @param string $app_id
   * @param string $app_secret
   * @param string $access_token
   * @return static
   */
  public static function init($app_id, $app_secret, $access_token) {
    $session = new Session($app_id, $app_secret, $access_token);
    $api = new static(new Client(), $session);
    static::setInstance($api);

    return $api;
  }

  /**
   * @return Api|null
   */
  public static function instance() {
    return static::$instance;
  }

  /**
   * @param Api $instance
   */
  public static function setInstance(Api $instance) {
    static::$instance = $instance;
  }

  /**
   * @param string $path
   * @param string $method
   * @param array $params
   * @return RequestInterface
   */
  public function prepareRequest(
    $path,
    $method = RequestInterface::METHOD_GET,
    array $params = array()) {

    $request = $this->getHttpClient()->createRequest();
    $request->setMethod($method);
    $request->setGraphVersion($this->getDefaultGraphVersion());
    $request->setPath($path);

    if ($method === RequestInterface::METHOD_GET) {
      $params_ref = $request->getQueryParams();
    } else {
      $params_ref = $request->getBodyParams();
    }

    $params_ref->enhance($params);
    $params_ref['access_token'] = $this->getSession()->getAccessToken();
    $params_ref['appsecret_proof'] = $this->getSession()->getAppSecretProof();

    return $request;
  }

  /**
   * @param RequestInterface $request
   * @return ResponseInterface
   */
  public function executeRequest(RequestInterface $request) {
    $this->getLogger()->logRequest('debug', $request);
    $response = $request->execute();
    $this->getLogger()->logResponse('debug', $response);

    return $response;
  }

  /**
   * @return string
   */
  public function getDefaultGraphVersion() {
    if ($this->defaultGraphVersion === null) {
      $match = array();
      if (preg_match("/^\d+\.\d+/", static::VERSION, $match)) {
        $this->defaultGraphVersion = $match[0];
      }
    }

    return $this->defaultGraphVersion;
  }

  /**
   * @param string $version
   */
  public function setDefaultGraphVersion($version) {
    $this->defaultGraphVersion = $version;
  }

  /**
   * Make graph api calls
   *
   * @param string $path Ads API endpoint
   * @param string $method Ads API request type
   * @param array $params Assoc of request parameters
   * @return ResponseInterface Graph API responses
   */
  public function call(
    $path,
    $method = RequestInterface::METHOD_GET,
    array $params = array()) {

    $request = $this->prepareRequest($path, $method, $params);

    return $this->executeRequest($request);
  }

  /**
   * @return Session
   */
  public function getSession() {
    return $this->session;
  }

  /**
   * @param LoggerInterface $logger
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * @return LoggerInterface
   */
  public function getLogger() {
    if ($this->logger === null) {
      $this->logger = new NullLogger();
    }

    return $this->logger;
  }

  /**
   * @return Client
   */
  public function getHttpClient() {
    return $this->httpClient;
  }
}
