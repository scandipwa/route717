<?php


namespace ScandiPWA\Router\Controller;


use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;


class Pwa extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    const ACTION_TYPE_COOKIE = 'action_type';

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
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

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
     * @param Context                $context
     * @param PageFactory            $resultPageFactory
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory  $cookieMetadataFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
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
        $this->setActionCookie($this->type);
        $resultLayout = $this->resultPageFactory->create();
        $resultLayout->setStatusHeader($this->code, '1.1', $this->phrase);
        $resultLayout->setHeader('X-Status', $this->phrase);
        
        return $resultLayout;
    }

    /**
     * Sets cookie with action type.
     * @param string $type
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function setActionCookie(string $type): void
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                              ->setPath($this->sessionManager->getCookiePath())
                              ->setDomain($this->sessionManager->getCookieDomain())
                              ->setHttpOnly(true);
        $this->cookieManager->setPublicCookie(self::ACTION_TYPE_COOKIE, $type, $publicCookieMetadata);
    }

    protected function validate()
    {
        if (!$this->code || !$this->phrase || !$this->type) {
            throw new \Exception('Action was not configured properly');
        }
    }
}
