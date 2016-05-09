<?php
namespace AUS\AusDriverDropbox\Utility;

use AUS\AusDriverDropbox\Driver\DropboxDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Hölzle <m.hoelzle@andersundsehr.com>, anders und sehr GmbH
 *  All rights reserved
 ***************************************************************/

/**
 * Class DropboxAdapter
 *
 * @package AUS\AusDriverDropbox\Utility
 */
class DropboxAdapter implements \TYPO3\CMS\Core\SingletonInterface
{


    const DROPBOX_APP_KEY = 'vuzko45113jezpg';
    const DROPBOX_APP_SECRET = '90gk6p7j52i8izx';


    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var array
     */
    protected $publicUrlCache;

    /**
     * @var \Dropbox\Client
     */
    protected $dbxClient;


    /**
     * @param string $authCode
     * @param string $basePath
     */
    public function __construct($authCode, $basePath)
    {
        $this->basePath = $basePath;
        $this->initCache();
        $this->initDropboxClient($authCode);
    }


    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->persistCache();
    }


    /**
     * @return string
     */
    public static function getClientIdentifier()
    {
        return 'TYPO3-FAL-aus_driver_dropbox/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion(DropboxDriver::EXTENSION_KEY);
    }


    /**
     * @param string $identifier
     * @param string $targetIdentifier
     * @return array|null The metadata
     */
    public function downloadFile($identifier, $targetIdentifier)
    {
        $fd = fopen($targetIdentifier, 'wb');
        $metadata = $this->dbxClient->getFile($this->getAbsolutePath($identifier), $fd);
        fclose($fd);

        return $metadata;
    }


    /**
     * @param string $localIdentifier
     * @param string $targetIdentifier
     * @return array|null The metadata object for the file/folder.
     */
    public function uploadFile($localIdentifier, $targetIdentifier)
    {
        $fd = fopen($localIdentifier, 'rb');
        $metaData = $this->dbxClient->uploadFile($this->getAbsolutePath($targetIdentifier), \Dropbox\WriteMode::force(), $fd);
        fclose($fd);

        return $metaData;
    }


    /**
     * @param string $identifier
     * @param string $string
     * @return array|null The metadata object for the file/folder.
     */
    public function uploadFileFromString($identifier, $string)
    {
        return $this->dbxClient->uploadFileFromString($this->getAbsolutePath($identifier), \Dropbox\WriteMode::force(), $string);
    }


    /**
     * @param string $identifier
     * @return array|null The metadata object for the file/folder.
     */
    public function createFolder($identifier)
    {
        return $this->dbxClient->createFolder($this->getAbsolutePath($identifier));
    }


    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return array|null The metadata object for the file/folder.
     */
    public function move($oldIdentifier, $newIdentifier)
    {
        return $this->dbxClient->move($this->getAbsolutePath($oldIdentifier), $this->getAbsolutePath($newIdentifier));
    }


    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return array|null The metadata object for the file/folder.
     */
    public function copy($oldIdentifier, $newIdentifier)
    {
        return $this->dbxClient->copy($this->getAbsolutePath($oldIdentifier), $this->getAbsolutePath($newIdentifier));
    }


    /**
     * @param string $identifier
     * @return array|null The metadata object for the file/folder.
     */
    public function delete($identifier)
    {
        return $this->dbxClient->delete($this->getAbsolutePath($identifier));
    }


    /**
     * @param string $identifier
     * @return array|null The metadata object for the file/folder.
     */
    public function getMetadataWithChildren($identifier)
    {
        return $this->dbxClient->getMetadataWithChildren($this->getAbsolutePath($identifier));
    }


    /**
     * @param string $identifier
     * @return string The public URL
     */
    public function getPublicUrl($identifier)
    {
        $identifier = $this->getAbsolutePath($identifier);
        if (!isset($this->publicUrlCache[$identifier])
            || !is_array($this->publicUrlCache[$identifier])
            || $this->publicUrlCache[$identifier][1] <= (new \DateTime())
        ) {
            $this->publicUrlCache[$identifier] = $this->dbxClient->createTemporaryDirectLink($identifier);
        }

        return $this->publicUrlCache[$identifier][0];
    }









    /*************************************************************/
    /****************** Protected Helpers ************************/
    /*************************************************************/


    /**
     * @param $authCode
     */
    protected function initDropboxClient($authCode)
    {
        if ($authCode) {
            list($accessToken, $dropboxUserId) = unserialize(base64_decode($authCode));
            if ($accessToken && $dropboxUserId) {
                $this->dbxClient = new \Dropbox\Client($accessToken, self::getClientIdentifier());
            }
        }
    }


    /**
     * @param $identifier
     * @return string
     */
    protected function getAbsolutePath($identifier)
    {
        if (!GeneralUtility::isFirstPartOfStr($identifier, $this->basePath)) {
            $identifier = $this->basePath . $identifier;
        }

        return $identifier;
    }


    /**
     * @return $this
     */
    protected function initCache()
    {
        $cache = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('ausdriverdropbox_cache');
        $cachedObjects = $cache->get('ausdriverdropbox_driver_caches');
        if ($cachedObjects) {
            $this->publicUrlCache = $cachedObjects['publicUrlCache'];
        }

        return $this;
    }


    /**
     * @return void
     */
    protected function persistCache()
    {
        $cache = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('ausdriverdropbox_cache');
        $cache->set('ausdriverdropbox_driver_caches', [
            'publicUrlCache' => $this->publicUrlCache,
        ], ['ausdriverdropbox_driver_caches']);
    }

}
