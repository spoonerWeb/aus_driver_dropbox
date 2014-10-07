<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// register additional driver
$TYPO3_CONF_VARS['SYS']['fal']['registeredDrivers'][\AUS\AusDriverDropbox\Driver\DropboxDriver::DRIVER_TYPE] = array(
	'class' => 'AUS\AusDriverDropbox\Driver\DropboxDriver',
	'label' => 'Dropbox',
	'flexFormDS' => 'FILE:EXT:aus_driver_dropbox/Configuration/FlexForm/DropboxDriverFlexForm.xml'
);

/**
 * register cache for extension
 */
if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['ausdriverdropbox_cache'])) {
	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['ausdriverdropbox_cache'] = array();
	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['ausdriverdropbox_cache']['frontend'] = 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend';
	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['ausdriverdropbox_cache']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend';
	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['ausdriverdropbox_cache']['options']['compression'] = 1;
}
