<?php
/**
 * Created by PhpStorm.
 * User: alt
 * Date: 17-Oct-18
 * Time: 9:41 PM
 */

namespace As247\Flysystem\DriveSupport\Support;

use As247\Flysystem\DriveSupport\Cache\TempCache;
use As247\Flysystem\DriveSupport\Contracts\Cache\CacheInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;


class OneDriveOauth
{
	protected $clientId;
	protected $clientSecret;
	protected $accessToken;
	protected $refreshToken;
	protected $tenantId='common';
	protected $httpClient;
	/**
	 * @var CacheInterface
	 */
	protected $cache;
	protected $tokenEndpoint = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';
	protected $oauthUrl = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize';
	protected $oauthParams = [
		'access_type' => 'offline',
		'redirect_uri' => 'http://localhost:53682',
		'response_type' => 'code',
		'scope' => 'files.readwrite.all offline_access',
	];

	public function __construct(CacheInterface $cache = null)
	{
		$this->cache = $cache;
	}

	public function createAuthUrl()
	{
		$this->oauthParams['client_id'] = $this->clientId;
		$this->oauthParams['redirect_uri'] = $this->getCurrentUrl();
		$this->oauthParams['state'] = time();
		return str_replace('{tenant}', $this->tenantId, $this->oauthUrl) . '?' . http_build_query($this->oauthParams);
	}

	protected function getCurrentUrl()
	{
		return 'http://' . $_SERVER['HTTP_HOST'];
	}

	public function fetchAccessTokenWithAuthCode($code)
	{
		$postKey = 'form_params';
		$response = $this->getHttpClient()->post(
			str_replace('{tenant}', $this->tenantId, $this->tokenEndpoint),
			[
				'headers' => ['Accept' => 'application/json'],
				$postKey => [
					'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
					'grant_type' => 'authorization_code',
					'redirect_uri' => $this->getCurrentUrl(),
					'code' => $code,
				],
			]);

		return json_decode($response->getBody(), true);
	}

	function getAccessToken()
	{
		$key = $this->clientId . $this->clientSecret . $this->refreshToken;
		$token = $this->getCache()->get($key);

		if (!$token) {
			$token = [];
			$token['refresh_token'] = $this->refreshToken;
		}

		$renewAt = 600;
		$token_created_at = isset($token['created_at']) ? $token['created_at'] : 0;
		$token_expired_in = isset($token['expires_in']) ? $token['expires_in'] : 0;
		$willBeExpireIn = $token_expired_in + $token_created_at - time();


		if (empty($token['access_token']) || $willBeExpireIn <= $renewAt) {
			$token = $this->fetchAccessTokenWithRefreshToken($this->refreshToken);

			$token['created_at'] = time();
			if (!empty($token['access_token'])) {
				$this->getCache()->put($key, $token, 0);
			}

		}
		return $token['access_token'];


	}

	function fetchAccessTokenWithRefreshToken($refresh_token = '')
	{
		$refresh_token = $refresh_token ?: $this->refreshToken;
		$postKey = 'form_params';
		$response = $this->getHttpClient()->post(
			str_replace('{tenant}', $this->tenantId, $this->tokenEndpoint),
			[
				'headers' => ['Accept' => 'application/json'],
				$postKey => [
					'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
					'grant_type' => 'refresh_token',
					'refresh_token' => $refresh_token,
				],
			]);

		return json_decode($response->getBody(), true);
	}

	/**
	 * Get a instance of the Guzzle HTTP client.
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected function getHttpClient()
	{
		if (is_null($this->httpClient)) {
			$this->httpClient = new Client();
		}

		return $this->httpClient;
	}

	/**
	 * @return TempCache|CacheInterface
	 */
	public function getCache()
	{
		if (!$this->cache) {
			$this->cache = new TempCache(static::class);
		}
		return $this->cache;
	}

	/**
	 * @param string $clientId
	 * @return OneDriveOauth
	 */
	public function setClientId(string $clientId): OneDriveOauth
	{
		$this->clientId = $clientId;
		return $this;
	}

	/**
	 * @param string $clientSecret
	 * @return OneDriveOauth
	 */
	public function setClientSecret(string $clientSecret): OneDriveOauth
	{
		$this->clientSecret = $clientSecret;
		return $this;
	}

	/**
	 * @param mixed $accessToken
	 * @return OneDriveOauth
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
		return $this;
	}

	/**
	 * @param string $refreshToken
	 * @return OneDriveOauth
	 */
	public function setRefreshToken(string $refreshToken): OneDriveOauth
	{
		$this->refreshToken = $refreshToken;
		return $this;
	}

	/**
	 * @param mixed $httpClient
	 * @return OneDriveOauth
	 */
	public function setHttpClient($httpClient)
	{
		$this->httpClient = $httpClient;
		return $this;
	}

	/**
	 * @param $cache
	 * @return OneDriveOauth
	 */
	public function setCache(CacheInterface $cache): OneDriveOauth
	{
		$this->cache = $cache;
		return $this;
	}

	/**
	 * @param string $tokenEndpoint
	 * @return OneDriveOauth
	 */
	public function setTokenEndpoint(string $tokenEndpoint): OneDriveOauth
	{
		$this->tokenEndpoint = $tokenEndpoint;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTenantId()
	{
		return $this->tenantId;
	}

	/**
	 * @param mixed $tenantId
	 * @return OneDriveOauth
	 */
	public function setTenantId($tenantId)
	{
		$this->tenantId = $tenantId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOauthUrl(): string
	{
		return $this->oauthUrl;
	}

	/**
	 * @param string $oauthUrl
	 * @return OneDriveOauth
	 */
	public function setOauthUrl(string $oauthUrl): OneDriveOauth
	{
		$this->oauthUrl = $oauthUrl;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getClientId(): string
	{
		return $this->clientId;
	}

	/**
	 * @return string
	 */
	public function getClientSecret(): string
	{
		return $this->clientSecret;
	}

	/**
	 * @return string
	 */
	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	/**
	 * @return string
	 */
	public function getTokenEndpoint(): string
	{
		return $this->tokenEndpoint;
	}

	/**
	 * @return string[]
	 */
	public function getOauthParams(): array
	{
		return $this->oauthParams;
	}

	/**
	 * @param string[] $oauthParams
	 * @return OneDriveOauth
	 */
	public function setOauthParams(array $oauthParams): OneDriveOauth
	{
		$this->oauthParams = $oauthParams;
		return $this;
	}

}
