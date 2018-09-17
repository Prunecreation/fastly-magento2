<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2018 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth\Item;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class Delete
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth\Item
 */
class Delete extends Action
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        parent::__construct($context);
    }

    /**
     * Delete auth item
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $dictionary = $this->api->getSingleDictionary($activeVersion, 'magentomodule_basic_auth');

            if ((is_array($dictionary) && empty($dictionary)) || !isset($dictionary->id)) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'Authentication dictionary does not exist.'
                ]);
            }

            // Check if there are any entries left
            $authItems = $this->api->dictionaryItemsList($dictionary->id);

            if ((is_array($authItems) && count($authItems) < 2) || $authItems == false) {
                // No users left, send message
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'While Basic Authenticaton is enabled, et least one user must exist.',
                ]);
            }

            $itemKey = $this->getRequest()->getParam('item_key_id');
            $deleteItem = $this->api->deleteDictionaryItem($dictionary->id, $itemKey);

            if (!$deleteItem) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to create Dictionary item.'
                ]);
            }

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
