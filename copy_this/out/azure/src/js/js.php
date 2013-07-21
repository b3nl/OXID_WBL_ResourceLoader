<?php
	$sShopBasePath = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;

	function getShopBasePath() {
		return $GLOBALS['sShopBasePath'];
	} // function

	require_once $sShopBasePath . 'bootstrap.php';

	// Merge the files.
	$aFiles = array();
	$oConfig = \oxRegistry::getConfig();

	if (isset($_GET['aFiles']) && is_array($_GET['aFiles'])) {
		foreach ($_GET['aFiles'] as $sParam) {
			$sParam = current(explode('?', $sParam));

			if ($sSrc = $oConfig->getResourcePath($sParam, false)) {
				$aFiles[] = $sSrc;
			} // if
		} // foreach
	} // if

	$aFiles = array_unique($aFiles);

	if (!$aFiles) {
		header('HTTP/1.1 404 Not Found');
		exit;
	} // if

	// ETag Check
	$sETag = '';

	foreach ($aFiles as $sSrc) {
		$sETag .= filemtime($sSrc);
	} // foreach

	$sETag = '"' . md5($sETag) . '"';

	// "Create" fresh file.
	ob_start("ob_gzhandler");

	if (@$_SERVER['HTTP_IF_NONE_MATCH'] === $sETag) {
		header('HTTP/1.1 304 Not Modified');
	} else {
		header('HTTP/1.1 200 OK');

		foreach ($aFiles as $sSrc) {
			readfile($sSrc);
		} // foreach
	} // else

	// Output
	header('Content-Type: ' . ($sWBLResourceType ?: 'text/javascript'));
	header('Cache-Control: max-age=' . (3600 * 24 * 31) . ', public');
	header('Etag: ' . $sETag);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600 * 24 * 31) . ' GMT');
	ob_end_flush();

