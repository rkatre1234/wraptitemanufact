<?php

/**
 * @Author: DOng NGuyen<nguyen@dvn.com>
 * @@Create Date: 2023-09-05 10:40:51
 * @@Modify Date: 2023-09-05 10:40:59
 * @@Function:
 */

namespace Magiccart\Alothemes\Controller\Adminhtml\Export;

use Magento\Framework\App\Filesystem\DirectoryList;

class Pagebuilder extends \Magiccart\Alothemes\Controller\Adminhtml\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    public function execute()
    {
        if ($this->getRequest()->getParam('theme_path')) $this->ExportXml();
        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }

    public function ExportXml()
    {
        $fileName = 'pagebuilder.xml';
        $theme_path = $this->getRequest()->getParam('theme_path');
        $exportIds = $this->getRequest()->getParam('exportIds');
        $dir = $this->_filesystem->getDirectoryWrite(DirectoryList::APP);
        $filePath = sprintf(self::CMS, $theme_path) . $fileName;
        try {
            $collection = $this->_objectManager->create('\Magento\PageBuilder\Model\ResourceModel\Template\Collection');
            $collection->addFieldToFilter('template_id', array('in' => $exportIds));
            $options = array('name', 'preview_image', 'template', 'created_for');
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<root>';
            $xml .= '<pagebuilder>';
            $num = 0;
            foreach ($collection as $menu) {
                $xml .= '<item>';
                foreach ($options as $opt) {
                    $xml .= '<' . $opt . '><![CDATA[' . $menu->getData($opt) . ']]></' . $opt . '>' ; 
                }
                $xml .='</item>' ;
                $num++; 
            } 
            $xml .='</pagebuilder>' ;
            $xml .='</root>' ; 
            // $this->_sendUploadResponse($fileName, $xml);
            $dir->writeFile($filePath, '$xml');
            $backupFilePath = $dir->getAbsolutePath($filePath);
            // @mkdir($filePath, 0644, true);
            $doc = new \DOMDocument('1.0', 'UTF-8');
            $doc->loadXML($xml);
            $doc->formatOutput = true;
            $doc->save($backupFilePath);

            $this->messageManager->addSuccess(__('Export (%1) Item(s):', $num));
            $this->messageManager->addSuccess(__('Successfully exported to file "%1"', $backupFilePath));
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Can not save export file "%1".<br />"%2"', $filePath, $e->getMessage() ));
        }
    }
}