<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ImportExport\Controller\Adminhtml\Import;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * @magentoAppArea adminhtml
 */
class ValidateTest extends \Magento\Backend\Utility\Controller
{
    /**
     * @backupGlobals enabled
     * @magentoDbIsolation enabled
     */
    public function testFieldStateAfterValidation()
    {
        $this->getRequest()->setParam('isAjax', true);
        $this->getRequest()->setMethod('POST');
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        /** @var $formKey \Magento\Framework\Data\Form\FormKey */
        $formKey = $this->_objectManager->get('Magento\Framework\Data\Form\FormKey');
        $this->getRequest()->setPost('form_key', $formKey->getFormKey());
        $this->getRequest()->setPost('entity', 'catalog_product');
        $this->getRequest()->setPost('behavior', 'replace');


        $name = 'catalog_product.csv';

        /** @var \Magento\TestFramework\App\Filesystem $filesystem */
        $filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
        $tmpDir = $filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $subDir = str_replace('\\', '_', __CLASS__);
        $tmpDir->create($subDir);
        $target = $tmpDir->getAbsolutePath("{$subDir}/{$name}");
        copy(__DIR__ . "/_files/{$name}", $target);

        $_FILES = [
            'import_file' => [
                'name' => $name,
                'type' => 'text/csv',
                'tmp_name' => $target,
                'error' => 0,
                'size' => filesize($target)
            ]
        ];

        $this->_objectManager->configure(
            [
                'preferences' => [
                    'Magento\Framework\HTTP\Adapter\FileTransferFactory' =>
                        'Magento\ImportExport\Controller\Adminhtml\Import\HttpFactoryMock'
                    ]
            ]
        );

        $this->dispatch('backend/admin/import/validate');

        $this->assertContains('File is valid', $this->getResponse()->getBody());
        $this->assertNotContains('File was not uploaded', $this->getResponse()->getBody());
        $this->assertNotRegExp(
            '/clear[^\[]*\[[^\]]*(import_file|import_image_archive)[^\]]*\]/m',
            $this->getResponse()->getBody()
        );
    }
}
