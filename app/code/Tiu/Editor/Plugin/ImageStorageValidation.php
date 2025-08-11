<?php

namespace Tiu\Editor\Plugin;

use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\App\Filesystem\DirectoryList;

class ImageStorageValidation
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    private $_directory;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->ioFile = $file;
    }

    public function beforeUploadFile(
        Storage $subject,
        $targetPath,
        $type
    ) {
        if (is_null($type)) {
            $type = 'image';
        }

        return [
            $targetPath,
            $type
        ];
    }

    public function aroundResizeFile(
        Storage $subject,
        callable $proceed,
        $source,
        $keepRatio = true
    ) {
        if (strpos($source, '.svg')===false
            && strpos($source, '.pdf')===false
        ) {
            $result = $proceed($source, $keepRatio);
            return $result;
        }

        $realPath = $this->_directory->getRelativePath($source);
        if (!$this->_directory->isFile($realPath) || !$this->_directory->isExist($realPath)) {
            return false;
        }

        $targetDir = $subject->getThumbsPath($source);
        $pathTargetDir = $this->_directory->getRelativePath($targetDir);
        if (!$this->_directory->isExist($pathTargetDir)) {
            $this->_directory->create($pathTargetDir);
        }
        if (!$this->_directory->isExist($pathTargetDir)) {
            return false;
        }
        $dest = $targetDir . '/' . $this->ioFile->getPathInfo($source)['basename'];
        $this->ioFile->mv($source, $dest);
        if ($this->_directory->isFile($this->_directory->getRelativePath($dest))) {
            return $dest;
        }

        return false;
    }
}