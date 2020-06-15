<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\Router\PathConfigInterface;
use Magento\Framework\App\Router\Base as BaseRouter;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use ScandiPWA\Router\ValidationManagerInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

class Router extends BaseRouter
{
    /**
     * @var ValidationManagerInterface
     */
    protected $validationManager;
    
    /**
     * @var array
     */
    protected $paths;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;
    
    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;
    
    /**
     * @var array
     */
    private $ignoredURLs;
    
    /**
     * Router constructor.
     * @param ActionList                 $actionList
     * @param ActionFactory              $actionFactory
     * @param DefaultPathInterface       $defaultPath
     * @param ResponseFactory            $responseFactory
     * @param ConfigInterface            $routeConfig
     * @param UrlInterface               $url
     * @param NameBuilder                $nameBuilder
     * @param PathConfigInterface        $pathConfig
     * @param ValidationManagerInterface $validationManager
     * @param UrlFinderInterface         $urlFinder
     * @param StoreManagerInterface      $storeManager
     * @param ScopeConfigInterface       $scopeConfig
     * @param ThemeProviderInterface     $themeProvider
     */
    public function __construct(
        ActionList $actionList,
        ActionFactory $actionFactory,
        DefaultPathInterface $defaultPath,
        ResponseFactory $responseFactory,
        ConfigInterface $routeConfig,
        UrlInterface $url,
        NameBuilder $nameBuilder,
        PathConfigInterface $pathConfig,
        ValidationManagerInterface $validationManager,
        UrlFinderInterface $urlFinder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ThemeProviderInterface $themeProvider,
        array $ignoredURLs = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
        $this->validationManager = $validationManager;
        $this->urlFinder = $urlFinder;
        $this->storeManager = $storeManager;
        $this->ignoredURLs = $ignoredURLs;

        parent::__construct(
            $actionList,
            $actionFactory,
            $defaultPath,
            $responseFactory,
            $routeConfig,
            $url,
            $nameBuilder,
            $pathConfig
        );
    }
    
    /**
     * @param RequestInterface $request
     * @return ActionInterface|null
     * @throws NoSuchEntityException
     */
    public function match(RequestInterface $request)
    {
        $themeId = $this->_scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        $theme = $this->themeProvider->getThemeById($themeId);
        $themeType = $theme->getType();

        if ((int) $themeType !== 4) { // Use custom theme type to support PWA and non-PWA within one installation
            return null;
        }

        if ($this->isRequestIgnored($request)) { // Bypass to standard router, i.e. for payment GW callbacks
            return null;
        }

        $this->forceHttpRedirect($request);
        $this->redirectOn301($request);

        $action = $this->actionFactory->create(Pwa::class);
        $rewrite = $this->getRewrite($request);

        if ($rewrite) {
            // Do not execute any action for external rewrites,
            // allow passing to default UrlRewrite router to make the work done
            if ($rewrite->getEntityType() === 'custom') {
                return null;
            }

            // Otherwise properly hint response for correct FE app placeholders
            $action->setType($this->getDefaultActionType($rewrite));
            $action->setCode(200)->setPhrase('OK');
        } elseif ($this->validationManager->validate($request)) { // Validate custom PWA routing
            $action->setType('PWA_ROUTER');
            $action->setCode(200)->setPhrase('OK');
        } else { //Fallback to 404 but return PWA app
            $action->setType('NOT_FOUND');
            $action->setCode(404)->setPhrase('Not Found');
        }

        return $action;
    }

    /**
     * @param RequestInterface $request
     * @return UrlRewrite|null
     * @throws NoSuchEntityException
     */
    protected function getRewrite(RequestInterface $request)
    {
        $requestPath = $request->getPathInfo();
        return $this->resolveRewrite($requestPath);
    }

    /**
     * @param string $requestPath
     * @return UrlRewrite|null
     * @throws NoSuchEntityException
     */
    protected function resolveRewrite(string $requestPath)
    {
        $storeId = $this->storeManager->getStore()->getId();

        return $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => ltrim($requestPath, '/'),
            UrlRewrite::STORE_ID => $storeId
        ]);
    }

    /**
     * @param UrlRewrite $urlRewrite
     * @return string
     */
    protected function getDefaultActionType(UrlRewrite $urlRewrite)
    {
        $type = $urlRewrite->getEntityType();

        if ($type === 'cms-page') {
            return 'CMS_PAGE';
        } elseif ($type === 'category') {
            return 'CATEGORY';
        } elseif ($type === 'product') {
            return 'PRODUCT';
        }

        return 'CUSTOM';
    }

    /**
     * @param RequestInterface $request
     * @throws NoSuchEntityException
     */
    protected function forceHttpRedirect(RequestInterface $request): void
    {
        $params = $this->parseRequest($request);
        $actionPath = $this->matchActionPath($request, $params['actionPath']);
        $action = $request->getActionName() ?: ($params['actionName'] ?: $this->_defaultPath->getPart('action'));
        $moduleFrontName = $this->matchModuleFrontName($request, $params['moduleFrontName']);

        $this->_checkShouldBeSecure($request, '/' . $moduleFrontName . '/' . $actionPath . '/' . $action);
    }

    /**
     * Redirect user if it is required per URL Rewrite
     * *NOTE* It process requests counting in categories URL precondition
     *
     * @param RequestInterface $request
     * @return void
     * @throws NoSuchEntityException
     */
    protected function redirectOn301(RequestInterface $request): void
    {
        $rewrite = $this->resolveRewrite($request->getPathInfo());

        if ($rewrite && in_array($rewrite->getRedirectType(), [301, 302])) {
            $target = $rewrite->getTargetPath();

            if ($target[0] !== '/') {
                $target = '/' . $target;
            }

            $this->_performRedirect($target, $rewrite->getRedirectType());
        }
    }

    /**
     * Performs redirect
     *
     * @param string $url
     * @param int $type
     * @return void
     */
    protected function _performRedirect(string $url, int $type = 302): void
    {
        $this->_responseFactory->create()->setRedirect($url, $type)->sendResponse();
        // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
        exit;
    }

    /**
     * Checks whether request is ignored using provided regular expression
     * @param RequestInterface $request
     * @return boolean
     */
    protected function isRequestIgnored(RequestInterface $request): bool
    {
        $requestPath = $request->getPathInfo();
        
        foreach ($this->ignoredURLs as $pattern) {
            // Use | as delimiter to allow / without escaping
            if (preg_match('|' . $pattern . '|', $requestPath)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @param RequestInterface $request
     * @param string           $path
     * @throws NoSuchEntityException
     */
    protected function _checkShouldBeSecure(RequestInterface $request, $path = '')
    {
        if ($request->getPostValue()) {
            return;
        }
        
        if ($this->pathConfig->shouldBeSecure($path) && !$request->isSecure()) {
            $alias = $request->getAlias(Url::REWRITE_REQUEST_PATH_ALIAS) ?: $request->getPathInfo();
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . "$alias";
            
            if ($this->_shouldRedirectToSecure()) {
                $url = $this->_url->getRedirectUrl($url);
            }

            $this->_performRedirect($url);
        }
    }
}
