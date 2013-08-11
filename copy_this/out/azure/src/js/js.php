<?php
	/**
	 * This file should combine the javascript files as as good and fast as possible.
	 *
	 * It is not refactored into a module dir, to enable this loading style, if this file is copied in the theme chain.
	 * This script does only use as much OXID framework as needed, to get the best possible performance with not so many
	 * side effects and requirements,
	 * @author blange <code@wbl-konzept.de>
	 * @category out
	 * @package theme
	 * @subpackage src/js
	 * @version $id$
	 */

	// Typical OXID-Defaults.
	$sShopBasePath = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;

	function getShopBasePath() {
		return $GLOBALS['sShopBasePath'];
	} // function

if (!function_exists('_exitWBLScriptWithNoFiles')) {
		/**
		 * Sends the 404 header, if there is no requested file.
		 * @author blange <code@wbl-konzept.de>
		 * @return void
		 */
		function _exitWBLScriptWithNoFiles() {
			header('HTTP/1.1 404 Not Found');
		} // function
	} // if

	if (!function_exists('_getCacheContentForWBLResources')) {
		/**
		 * Returns the cached content.
		 * @author blange <code@wbl-konzept.de>
		 * @param string $sCacheKey
		 * @return string
		 */
		function _getCacheContentForWBLResources($sCacheKey) {
			$sContent = '';

			if (class_exists('\oxCache', true)) {
				$oCache   = class_exists('\oxRegistry', true) ? \oxRegistry::get('oxcache') : oxNew('oxcache');
				$sContent = (string) $oCache->get($sCacheKey);
				unset($oCache);
			} else {
				$oUtils   = class_exists('\oxRegistry', true) ? \oxRegistry::getUtils() : oxUtils::getInstance();
				$sContent = (string) $oUtils->fromFileCache($sCacheKey);
				unset($oUtils);
			} // else

			return $sContent;
		} // function
	} // if

	if (!function_exists('_getContentFromWBLResources')) {
		/**
		 * Iterates through the requested files and returns their content.
		 * @author blange <code@wbl-konzept.de>
		 * @param array $aFiles
		 * @return string
		 */
		function _getContentFromWBLResources(array $aFiles = array()) {
			$oConfig = class_exists('\oxRegistry', true) ? \oxRegistry::getConfig() : oxConfig::getInstance();

			if (!$aFiles) {
				$aFiles = _getFilesFromWBLRequest();
			} // if

			$sCacheContent = '';

			foreach ($aFiles as $sFile) {
				if ($sSrc = $oConfig->getResourcePath(current(explode('?', $sFile)), false)) {
					$sCacheContent .= file_get_contents($sSrc);
				} // if
			} // foreach

			return $sCacheContent;
		} // function
	} // if

	if (!function_exists('_getFilesFromWBLRequest')) {
		/**
		 * Returns the content of the request vars.
		 * @author blange <code@wbl-konzept.de>
		 * @return array
		 */
		function _getFilesFromWBLRequest() {
			$aFiles = array();

			if (isset($_GET['aWBLFiles']) && is_array($_GET['aWBLFiles'])) {
				$aFiles = $_GET['aWBLFiles'];
			} // if

			return array_unique($aFiles);
		} // function
	} // if

	if (!function_exists('_outputHeaderForWBLResources')) {
		/**
		 * Outputs the needed headers for the resource file.
		 * @author blange <code@wbl-konzept.de>
		 * @param string $sETag The Entity Tag.
		 * @param string $sResourceType The resource type.
		 * @return bool
		 */
		function _outputHeaderForWBLResources($sETag, $sResourceType) {
			header('Content-Type: text/' . $sResourceType);
			header('Cache-Control: max-age=' . (3600 * 24 * 31) . ', public');
			header('Etag: "' . $sETag . '"');
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600 * 24 * 31) . ' GMT');
			// No Content length, because this makes problems with the gzip handler.
			ob_end_flush();

			return true;
		} // function
	} // if

	if (!function_exists('_saveWBLResourceContent2Cache')) {
		/**
		 * Saves the resource content to the cache.
		 * @author blange <code@wbl-konzept.de>
		 * @param string $sCacheContent The content for the caching.
		 * @param string $sCacheKey The key for the cache.
		 * @return bool
		 */
		function _saveWBLResourceContent2Cache($sCacheContent, $sCacheKey) {
			if (class_exists('\oxCache', true)) {
				$oCache = class_exists('\oxRegistry', true) ? \oxRegistry::get('oxcache') : oxNew('oxcache');

				$oCache->put($sCacheKey, $sCacheContent);
				unset($oCache);
			} else {
				$oUtils = class_exists('\oxRegistry', true) ? \oxRegistry::getUtils() : oxUtils::getInstance();

				if ($oUtils->toFileCache($sCacheKey, $sCacheContent)) {
					$oUtils->commitFileCache();
				} // if

				unset($oUtils);
			} // else

			return true;
		} // function
	} // if

	if (!function_exists('_startScriptForWBLLoader')) {
		/**
		 * Starts the oxid framework and the resource request.
		 * @author blange <code@wbl-konzept.de>
		 * @return void
		 */
		function _startScriptForWBLLoader() {
			ob_start("ob_gzhandler");

			$sShopBasePath = getShopBasePath();

			if (file_exists($sBootstrap = $sShopBasePath . 'bootstrap.php')) {
				require_once $sShopBasePath . 'bootstrap.php';
			} else {
				// Same handling as in the index.php
				if (defined('E_DEPRECATED')) {
					error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
				} // if
				else {
					error_reporting(E_ALL ^ E_NOTICE);
				} // else

				include $sShopBasePath . 'modules' . DIRECTORY_SEPARATOR . 'functions.php';
				require_once $sShopBasePath . 'core' . DIRECTORY_SEPARATOR . 'oxfunctions.php';
				require_once $sShopBasePath . 'core' . DIRECTORY_SEPARATOR . 'adodblite' . DIRECTORY_SEPARATOR . 'adodb.inc.php';
			} // else
		} // function
	} // if

	_startScriptForWBLLoader();

	if (!isset($sWBLResourceType)) {
		$sWBLResourceType = 'javascript';
	} // if

	if (!$aFiles = _getFilesFromWBLRequest()) {
		_exitWBLScriptWithNoFiles();
	} // if

	$sETag       = sha1(serialize($aFiles) . (int) @ $_GET['iWBLTimestamp']);
	$bFoundCache = true;

	/*
	 * ETag-Check
	 * If the browser sends a known etag, this means, it knows the resource allready and does not need fresh content and
	 * oxid does not need to render or save it.
	 */
	if (@$_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $sETag . '"') {
		header('HTTP/1.1 304 Not Modified');
	} else {
		header('HTTP/1.1 200 OK');

		$bFoundCache = (bool) $sCacheContent = _getCacheContentForWBLResources(
			$sCacheKey = 'wbl_' . $sWBLResourceType . '_' . $sETag
		);

		if (!$bFoundCache) {
			$sCacheContent = _getContentFromWBLResources($aFiles);
		} // if

		print $sCacheContent;

		if (!$bFoundCache) {
			_saveWBLResourceContent2Cache($sCacheContent, $sCacheKey);
		} // if
	} // else

	_outputHeaderForWBLResources($sETag, $sWBLResourceType);