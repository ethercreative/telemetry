<?php
/**
 * Telemetry
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\telemetry;

use Craft;
use craft\base\Plugin;
use GuzzleHttp\Client;

/**
 * Class TelemetryPlugin
 *
 * @author  Ether Creative
 * @package ether\telemetry
 */
class TelemetryPlugin extends Plugin
{

	// Init
	// =========================================================================

	public function init ()
	{
		parent::init();

		$request = Craft::$app->getRequest();

		if ($request->getIsCpRequest() && $request->getSegment(1) === 'dashboard')
		{
			$cache = Craft::$app->getCache();

			if ($cache->get('telemetry') === false)
			{
				$cache->set('telemetry', true, 86400);
				$this->_tell();
			}
		}
	}

	// Events
	// =========================================================================

	public function install ()
	{
		if (parent::install() !== null)
			return false;

		$this->_tell();

		return null;
	}

	public function uninstall ()
	{
		if (parent::uninstall() !== null)
			return false;

		$this->_tell();

		return null;
	}

	public function afterSaveSettings ()
	{
		parent::afterSaveSettings();

		$this->_tell();
	}

	// Helpers
	// =========================================================================

	/**
	 * Get the Telemetry key
	 *
	 * @param Client $client
	 *
	 * @return false|string
	 */
	private function _key ($client)
	{
		static $key;

		if ($key)
			return $key;

		$path = CRAFT_CONFIG_PATH . DIRECTORY_SEPARATOR . 'telemetry.key';

		if (file_exists($path))
			return $key = file_get_contents($path);

		$key = $client->get('')->getBody()->getContents();
		file_put_contents($path, $key);

		return $key;
	}

	/**
	 * Notify Telemetry
	 */
	private function _tell ()
	{
		static $client;

		if (!$client)
		{
			$client = Craft::createGuzzleClient([
				'base_uri' => 'https://telemetry.ethercreative.co.uk',
				'headers' => [
					'X-Telemetry' => '🔑',
				],
			]);
		}

		try
		{
			$info = Craft::$app->getPlugins()->getPluginInfo($this->getVersion());
			$meta = [
				'key'       => $this->_key($client),
				'handle'    => $this->getHandle(),
				'version'   => $this->getVersion(),
				'edition'   => $info['edition'],
				'editions'  => self::editions(),
				'installed' => $info['isInstalled'],
				'enabled'   => $info['isEnabled'],
				'licence'   => $info['licenseKeyStatus'],
				'issues'    => $info['licenseIssues'],
				'trial'     => $info['isTrial'],
				'env'       => getenv('ENVIRONMENT'),
			];
			$client->postAsync('', [
				'headers' => [
					'X-Telemetry' => http_build_query($meta),
				],
			]);
		} catch (\Exception $e) {
			Craft::error($e->getMessage(), 'telemetry');
		}
	}

}