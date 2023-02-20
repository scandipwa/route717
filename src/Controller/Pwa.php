<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;

class Pwa extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var PageFactory $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $phrase;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $sku;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $display_mode;

    /**
     * @var array|null
     */
    protected $cmsPage;

    /**
     * @var array|null
     */
    protected $slider;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $categoryDefaultSortBy;

    /**
     * @var array|null
     */
    protected $storeConfig;

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type = 'UNKNOWN'): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param int $code
     * @return Pwa
     */
    public function setCode(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @param string $phrase
     * @return Pwa
     */
    public function setPhrase(string $phrase): self
    {
        $this->phrase = $phrase;
        return $this;
    }

    /**
     * @param string $id
     * @return Pwa
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $sku
     * @return Pwa
     */
    public function setSku(string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }


    /**
     * @param string $name
     * @return Pwa
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $display_mode
     * @return Pwa
     */
    public function setDisplayMode(string $display_mode): self
    {
        $this->display_mode = $display_mode;
        return $this;
    }

    /**
     * @param $cmsPage
     * @return Pwa
     */
    public function setCmsPage($cmsPage): self
    {
        $this->cmsPage = $cmsPage;
        return $this;
    }

    public function setSlider($slider): self
    {
        $this->slider = $slider;
        return $this;
    }

    /**
     * @param string $description
     * @return Pwa
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $catalogDefaultSortBy
     * @return Pwa
     */
    public function setCategoryDefaultSortBy(string $categoryDefaultSortBy): self
    {
        $this->categoryDefaultSortBy = $categoryDefaultSortBy;
        return $this;
    }

    /**
     * @param $storeConfig
     * @return Pwa
     */
    public function setStoreConfig($storeConfig): self
    {
        $this->storeConfig = $storeConfig;
        return $this;
    }

    /**
     * Rewrite constructor.
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->type = 'PWA_ROUTER';
        $this->id = '';
        $this->sku = '';
        $this->name = '';
        $this->display_mode = '';
        $this->cmsPage = null;
        $this->slider = null;
        $this->description = '';
        $this->categoryDefaultSortBy = '';
        $this->storeConfig = null;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     * @throws \Exception
     */
    public function execute()
    {
        $this->validate();
        $resultLayout = $this->resultPageFactory->create();
        $resultLayout->setStatusHeader($this->code, '1.1', $this->phrase);
        $resultLayout->setHeader('X-Status', $this->phrase);
        $resultLayout->setAction($this->type);
        $resultLayout->setId($this->id);
        $resultLayout->setSku($this->sku);
        $resultLayout->setName($this->name);
        $resultLayout->setCmsPage($this->cmsPage);
        $resultLayout->setSlider($this->slider);
        $resultLayout->setDisplayMode($this->display_mode);
        $resultLayout->setDescription($this->description);
        $resultLayout->setCategoryDefaultSortBy($this->categoryDefaultSortBy);
        $resultLayout->setStoreConfig($this->storeConfig);
        try{
            $templateName = 'pwa-root';
            $resultLayout->setRootTemplate($templateName);
        } catch (\Exception $exception) {
            throw new \Exception(__($templateName . ' template not found'));
        }

        return $resultLayout;
    }

    protected function validate()
    {
        if (!$this->code || !$this->phrase || !$this->type) {
            throw new \Exception('Action was not configured properly');
        }
    }
}
