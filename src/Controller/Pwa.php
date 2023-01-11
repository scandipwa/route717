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
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $catalogDefaultSortBy;

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
     * @param string $identifier
     * @return Pwa
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
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
    public function setCatalogDefaultSortBy(string $catalogDefaultSortBy): self
    {
        $this->catalogDefaultSortBy = $catalogDefaultSortBy;
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
        $this->identifier = '';
        $this->description = '';
        $this->catalogDefaultSortBy = '';
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
        $resultLayout->setIdentifier($this->identifier);
        $resultLayout->setDisplayMode($this->display_mode);
        $resultLayout->setDescription($this->description);
        $resultLayout->setCatalogDefaultSortBy($this->catalogDefaultSortBy);
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
