<?php


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
    protected $redirectPath;

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
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
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
     * @return string
     */
    public function getRedirectPath(): string
    {
        return $this->redirectPath;
    }

    /**
     * @param string $url
     * @return Pwa
     */
    public function setRedirectPath(string $url): self
    {
        $this->redirectPath = $url;
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

        if ($this->getRedirectPath()) {
            $resultLayout->setHeader('Location', $this->getRedirectPath());
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
