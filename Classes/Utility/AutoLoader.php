<?php
namespace AUS\AusDriverDropbox\Utility;

use AUS\AusDriverDropbox\Driver\DropboxDriver;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Hölzle <m.hoelzle@andersundsehr.com>, anders und sehr GmbH
 *  All rights reserved
 ***************************************************************/

/**
 * Class AutoLoader
 *
 * @package AUS\AusDriverDropbox\Utility
 */
class AutoLoader implements \TYPO3\CMS\Core\SingletonInterface
{


    /**
     * register the dropbox autoLoader
     */
    public static function registerAutoLoader()
    {
        spl_autoload_register(['\AUS\AusDriverDropbox\Utility\AutoLoader', 'autoload']);
    }


    /**
     * autoLoader for spl_autoload_register().
     *
     * @param $className
     */
    public static function autoload($className)
    {
        // If the name doesn't start with "Dropbox\", then its not once of our classes.
        if (\substr_compare($className, 'Dropbox\\', 0, 8) !== 0) {
            return;
        }

        // Take the "Dropbox\" prefix off.
        $stem = \substr($className, 8);

        // Convert "\" and "_" to path separators.
        $pathifiedStem = \str_replace(["\\", "_"], '/', $stem);

        $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(DropboxDriver::EXTENSION_KEY) . 'Resources/PHP/Dropbox/' . $pathifiedStem . ".php";
        if (\is_file($path)) {
            require_once $path;
        }
    }

}
