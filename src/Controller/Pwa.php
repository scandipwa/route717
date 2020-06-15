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
        
        return $resultLayout;
    }
    
    protected function validate()
    {
        if (!$this->code || !$this->phrase || !$this->type) {
            throw new \Exception('Action was not configured properly');
        }
    }
}
