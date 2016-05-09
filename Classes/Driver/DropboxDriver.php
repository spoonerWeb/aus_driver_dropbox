<?php
namespace AUS\AusDriverDropbox\Driver;

use \TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use \TYPO3\CMS\Core\Utility\PathUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Resource\ResourceStorage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Hölzle <m.hoelzle@andersundsehr.com>, anders und sehr GmbH
 *  All rights reserved
 ***************************************************************/

/**
 * Driver for Dropbox
 *
 * @author Markus Hölzle <m.hoelzle@andersundsehr.com>
 */
class DropboxDriver extends \TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver
{


    const DEBUG_MODE = false;

    const DRIVER_TYPE = 'AusDriverDropbox';

    const EXTENSION_KEY = 'aus_driver_dropbox';


    /**
     * @var \AUS\AusDriverDropbox\Utility\DropboxAdapter
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var array
     */
    protected $objectMetaWithChildrenCache = [];

    /**
     * @var array
     */
    protected $publicUrlCache = [];

    /**
     * The identifier map used for renaming
     *
     * @var array
     */
    protected $identifierMap;

    /**
     * Processing folder
     *
     * @var string
     */
    protected $processingFolder;

    /**
     * Default processing folder
     *
     * @var string
     */
    protected $processingFolderDefault = '_processed_';

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $storage;


    /**
     * @return void
     */
    public function processConfiguration()
    {
    }


    /**
     * @return void
     */
    public function initialize()
    {
        \AUS\AusDriverDropbox\Utility\AutoLoader::registerAutoLoader();
        $this->initializeAdapter();
        $this->capabilities = \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_BROWSABLE | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_WRITABLE;
    }


    /**
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        $this->normalizeIdentifier($identifier);

        return $this->adapter->getPublicUrl($identifier);
    }


    /**
     * Creates a (cryptographic) hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        return $this->hashIdentifier($fileIdentifier);
    }


    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        return $this->getRootLevelFolder();
    }


    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return '';
    }


    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                    If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->normalizeIdentifier($fileIdentifier);
        $metadata = $this->getMetaWithChildren($fileIdentifier);
        $time = $this->getDate($metadata['modified']);

        return [
            'name' => basename($fileIdentifier),
            'identifier' => $fileIdentifier,
            'mtime' => $time,
            'ctime' => $time,
            'mimetype' => $metadata['mime_type'],
            'size' => $metadata['bytes'],
            'identifier_hash' => $this->hashIdentifier($fileIdentifier),
            'folder_hash' => $this->hashIdentifier(\TYPO3\CMS\Core\Utility\PathUtility::dirname($fileIdentifier)),
            'storage' => $this->storageUid
        ];
    }


    /**
     * Checks if a file exists
     *
     * @param \string $identifier
     * @return \bool
     */
    public function fileExists($identifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        if (substr($identifier, -1) === '/') {
            return false;
        }

        return $this->objectExists($identifier);
    }


    /**
     * Checks if a folder exists
     *
     * @param \string $identifier
     * @return \boolean
     */
    public function folderExists($identifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        return $this->objectExists($identifier);
    }


    /**
     * @param string $fileName
     * @param string $folderIdentifier
     * @return boolean
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        return $this->objectExists($folderIdentifier . $fileName);
    }


    /**
     * Checks if a folder exists inside a storage folder
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return boolean
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        return $this->objectExists($folderIdentifier . $folderName . '/');
    }


    /**
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param boolean $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     * @throws \RuntimeException
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $targetIdentifier = $targetFolderIdentifier . $newFileName;
        $this->normalizeIdentifier($targetIdentifier);

        $metaData = $this->adapter->uploadFile($localFilePath, $targetIdentifier);

        if ($removeOriginal) {
            unlink($localFilePath);
        }

        if (!$metaData) {
            throw new \RuntimeException('Adding file ' . $localFilePath . ' at ' . $targetIdentifier . ' failed.');
        }

        return $targetIdentifier;
    }


    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     *
     * @return string
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $targetIdentifier = $targetFolderIdentifier . $newFileName;
        $this->normalizeIdentifier($fileIdentifier);
        $this->normalizeIdentifier($targetIdentifier);
        $this->adapter->move($fileIdentifier, $targetIdentifier);

        return $targetIdentifier;
    }


    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $targetIdentifier = $targetFolderIdentifier . $fileName;
        $this->normalizeIdentifier($targetIdentifier);
        $this->normalizeIdentifier($fileIdentifier);

        return $this->adapter->copy($fileIdentifier, $targetIdentifier) ? true : false;
    }


    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return boolean TRUE if the operation succeeded
     * @throws \RuntimeException
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $this->normalizeIdentifier($fileIdentifier);
        $metaData = $this->adapter->uploadFile($localFilePath, $fileIdentifier);
        unlink($localFilePath);

        if (!$metaData) {
            throw new \RuntimeException('Replacing file ' . $fileIdentifier . ' with ' . $localFilePath . ' failed.');
        }

        return true;
    }


    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return boolean TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->normalizeIdentifier($identifier);

        return $this->adapter->delete($identifier) ? true : false;
    }


    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param boolean $deleteRecursively
     * @return boolean
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->normalizeIdentifier($folderIdentifier);

        return $this->adapter->delete($folderIdentifier) ? true : false;
    }


    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                         operations. This might speed up things, e.g. by using
     *                         a cached local version. Never modify the file if you
     *                         have set this flag!
     * @return string The path to the file on the local disk
     * @throws \RuntimeException
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $this->normalizeIdentifier($fileIdentifier);
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);

        $metadata = $this->adapter->downloadFile($fileIdentifier, $temporaryPath);
        if (!$metadata) {
            throw new \RuntimeException('Copying file ' . $fileIdentifier . ' to temporary path failed.', 1320577649);
        }

        return $temporaryPath;
    }


    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $identifier = $parentFolderIdentifier . $fileName;
        $this->createObject($identifier);

        return $identifier;
    }


    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param boolean $recursive
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $newFolderName = trim($newFolderName, '/');
        $identifier = $parentFolderIdentifier . $newFolderName;
        $this->normalizeIdentifier($identifier);
        $this->adapter->createFolder($identifier);

        return $identifier;
    }


    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $localPath = $this->getFileForLocalProcessing($fileIdentifier);

        return file_get_contents($localPath);
    }


    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return integer The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->normalizeIdentifier($fileIdentifier);
        $metaData = $this->adapter->uploadFileFromString($fileIdentifier, $contents);

        return $metaData['bytes'];
    }


    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $newIdentifier = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/' . $newName;
        $this->normalizeIdentifier($newIdentifier);
        $this->normalizeIdentifier($fileIdentifier);

        $this->adapter->move($fileIdentifier, $newIdentifier);

        return $newIdentifier;
    }


    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->resetIdentifierMap();

        $newIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
        $this->normalizeIdentifier($newIdentifier);
        $this->normalizeIdentifier($folderIdentifier);

        $this->adapter->move($folderIdentifier, $newIdentifier);
        $this->createIdentifierMap($folderIdentifier, $newIdentifier);

        return $this->identifierMap;
    }


    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     *
     * @return array All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        return $this->renameFolder($sourceFolderIdentifier, $newFolderName);
    }


    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     *
     * @return boolean
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $newIdentifier = $targetFolderIdentifier . $newFolderName . '/';
        $this->normalizeIdentifier($newIdentifier);
        $this->normalizeIdentifier($sourceFolderIdentifier);

        return $this->adapter->copy($sourceFolderIdentifier, $newIdentifier) ? true : false;
    }


    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return boolean TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $isFolderEmpty = true;
        $response = $this->getMetaWithChildren($folderIdentifier);
        if (is_array($response['contents']) && count($response['contents']) > 0) {
            $isFolderEmpty = false;
        }

        return $isFolderEmpty;
    }


    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return boolean TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $this->normalizeIdentifier($folderIdentifier);
        $this->normalizeIdentifier($identifier);
        if ($folderIdentifier === $identifier) {
            return true;
        }

        return GeneralUtility::isFirstPartOfStr($identifier, $folderIdentifier);
    }


    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }
        $this->normalizeIdentifier($folderIdentifier);

        return [
            'identifier' => $folderIdentifier,
            'name' => basename(rtrim($folderIdentifier, '/')),
            'storage' => $this->storageUid
        ];
    }


    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param integer $start
     * @param integer $numberOfItems
     * @param boolean $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     *
     * @return array of FileIdentifiers
     * @toDo: Implement params
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = []
    ) {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $files = [];

        $response = $this->getMetaWithChildren($folderIdentifier);
        if (is_array($response['contents'])) {
            foreach ($response['contents'] as $file) {
                if (!$file['is_dir']) {
                    $relativePath = $this->getRelativePath($file['path']);
                    $files[$relativePath] = $relativePath;
                }
            }
        }

        return $files;
    }


    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param integer $start
     * @param integer $numberOfItems
     * @param boolean $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     *
     * @return array of Folder Identifier
     * @toDo: Implement params
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = []
    ) {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        $folders = [];
        $response = $this->getMetaWithChildren($folderIdentifier);
        if (is_array($response['contents'])) {
            foreach ($response['contents'] as $file) {
                if ($file['is_dir'] && basename($file['path']) != $this->getProcessingFolder()) {
                    $relativePath = $this->getRelativePath($file['path']);
                    $folders[$relativePath] = $relativePath;
                }
            }
        }

        return $folders;
    }


    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @return void
     */
    public function dumpFileContents($identifier)
    {
        $this->normalizeIdentifier($identifier);
        $localFile = $this->getFileForLocalProcessing($identifier);
        readfile($localFile, 0);
    }


    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        if (self::DEBUG_MODE) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args(), 'Hello from ' . __METHOD__);
        }

        return $this->getObjectPermissions($identifier);
    }


    /**
     * Merges the capabilites merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param integer $capabilities
     *
     * @return integer
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;

        return $this->capabilities;
    }






    /*************************************************************/
    /****************** Protected Helpers ************************/
    /*************************************************************/


    /**
     * @return $this
     */
    protected function initializeAdapter()
    {
        $this->basePath = '/' . trim($this->configuration['basePath'], '/');
        $this->adapter = new \AUS\AusDriverDropbox\Utility\DropboxAdapter($this->configuration['authCode'], $this->basePath);
    }


    /**
     * Checks if an object exists
     *
     * @param \string $identifier
     * @return \boolean
     */
    protected function objectExists($identifier)
    {
        return ($this->getMetaWithChildren($identifier) ? true : false);
    }


    /**
     * getMetaWithChildren
     *
     * @param $identifier
     * @return mixed
     */
    protected function getMetaWithChildren($identifier)
    {
        $this->normalizeIdentifier($identifier);
        if (!is_array($this->objectMetaWithChildrenCache[$identifier])) {
            $this->objectMetaWithChildrenCache[$identifier] = $this->adapter->getMetadataWithChildren($identifier);
        }

        return $this->objectMetaWithChildrenCache[$identifier];
    }


    /**
     * Call after rename
     *
     * @param $oldIdentifierFolder
     * @param $newIdentifierFolder
     * @return void
     */
    protected function createIdentifierMap($oldIdentifierFolder, $newIdentifierFolder)
    {
        $meta = $this->getMetaWithChildren($newIdentifierFolder);

        $relativePathOld = $this->getRelativePath($oldIdentifierFolder);
        $relativePathNew = $this->getRelativePath($newIdentifierFolder);
        $this->normalizeIdentifier($relativePathOld);
        $this->normalizeIdentifier($relativePathNew);
        $this->identifierMap[$relativePathOld] = $relativePathNew;

        if (is_array($meta['contents'])) {
            foreach ($meta['contents'] as $file) {
                $fileName = '/' . basename($file['path']);
                if ($file['is_dir']) {
                    $this->createIdentifierMap($relativePathOld . $fileName, $relativePathNew . $fileName);
                } else {
                    $this->identifierMap[$relativePathOld . $fileName] = $relativePathNew . $fileName;
                }
            }
        }
    }


    /**
     * @param string $identifier
     * @return mixed
     */
    protected function getObjectPermissions($identifier)
    {
        return $permissions = ['r' => true, 'w' => true];
    }


    /**
     * @return void
     */
    protected function resetIdentifierMap()
    {
        $this->identifierMap = [];
    }


    /**
     * @param \string &$identifier
     */
    protected function normalizeIdentifier(&$identifier)
    {
        $identifier = trim($identifier, '/');
        if ($identifier != '') {
            $identifier = '/' . $identifier;
        }
    }


    /**
     * @param $identifier
     * @return string
     */
    protected function getRelativePath($identifier)
    {
        if (GeneralUtility::isFirstPartOfStr($identifier, $this->basePath)) {
            $identifier = substr($identifier, strlen($this->basePath));
        }

        return $identifier;
    }


    /**
     * @return ResourceStorage
     */
    protected function getStorage()
    {
        if (!$this->storage) {
            /** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
            $storageRepository = GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\StorageRepository');
            $this->storage = $storageRepository->findByUid($this->storageUid);
        }

        return $this->storage;
    }


    /**
     * getProcessingFolder
     *
     * @return string
     */
    protected function getProcessingFolder()
    {
        if (!$this->processingFolder) {
            $confProcessingFolder = $this->getStorage()->getProcessingFolder()->getName();
            $this->processingFolder = $confProcessingFolder ? $confProcessingFolder : $this->processingFolderDefault;
        }

        return $this->processingFolder;
    }


    /**
     * Parse date to unix from dropbox string format
     *
     * @param $dateString
     * @return int
     */
    protected function getDate($dateString)
    {
        $dateArr = strptime($dateString, '%a, %d %b %Y %H:%M:%S %z');

        return mktime($dateArr['tm_hour'], $dateArr['tm_min'], $dateArr['tm_sec'], $dateArr['tm_mon'] + 1, $dateArr['tm_mday'],
            $dateArr['tm_year'] + 1900);
    }

}
