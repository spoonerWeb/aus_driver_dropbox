<?php
namespace AUS\AusDriverDropbox\Utility;

use \AUS\AusDriverDropbox\Driver\DropboxDriver;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Hölzle <m.hoelzle@andersundsehr.com>, anders und sehr GmbH
 *  All rights reserved
 ***************************************************************/

/**
 * Class FlexFormUtility
 *
 * @package AUS\AusDriverDropbox\Utility
 */
class FlexFormUtility implements \TYPO3\CMS\Core\SingletonInterface {


	/**
	 * @return FlexFormUtility
	 */
	public function __construct() {
		\AUS\AusDriverDropbox\Utility\AutoLoader::registerAutoLoader();
	}


	/**
	 * @param $PA
	 * @param $fObj
	 */
	public function authorize($PA, $fObj) {
		$config = GeneralUtility::xml2array($PA['row']['configuration']);
		$authCode = $config['data']['sDEF']['lDEF']['authCode']['vDEF'];
		$localLangPath = 'LLL:EXT:' . DropboxDriver::EXTENSION_KEY . '/Resources/Private/Language/locallang_be.xlf:';

		$appInfo = new \Dropbox\AppInfo(DropboxAdapter::DROPBOX_APP_KEY, DropboxAdapter::DROPBOX_APP_SECRET);
		$webAuth = new \Dropbox\WebAuthNoRedirect($appInfo, DropboxAdapter::getClientIdentifier());

		try{
			$res = $webAuth->finish($authCode);
			$config['data']['sDEF']['lDEF']['authCode']['vDEF'] = base64_encode(serialize($res));
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_storage', 'uid = ' . $PA['row']['uid'], array('configuration' => GeneralUtility::array2xml_cs($config, 'T3FlexForms')));
			$PA['item'] = '<div style="font-weight: bold; color: green; padding: 10px;">' . LocalizationUtility::translate($localLangPath . 'driverConfiguration.authCode.success', '') . '</div>';
		}catch (\Exception $e){}

		$PA['item'] = '<ol>
			<li>' . LocalizationUtility::translate($localLangPath . 'driverConfiguration.authCode.tutorial1', '') . ' <a target="_blank" href="' . $webAuth->start() . '">' . LocalizationUtility::translate($localLangPath . 'driverConfiguration.authCode.tutorial1_linkText', '') . '</a></li>
			<li>' . LocalizationUtility::translate($localLangPath . 'driverConfiguration.authCode.tutorial2', '') . '</li>
			<li>' . LocalizationUtility::translate($localLangPath . 'driverConfiguration.authCode.tutorial3', '') . '</li>
		</ol>' . $PA['item'];
	}

}
