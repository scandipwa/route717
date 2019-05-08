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
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\Router\PathConfigInterface;
use Magento\Framework\App\Router\Base as BaseRouter;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Controller\Product\View as ProductView;
use Magento\Catalog\Controller\Category\View as CategoryView;
use Magento\Cms\Controller\Page\View as PageView;
use Magento\UrlRewrite\Controller\Router as UrlRewriteRouter;
use ScandiPWA\Router\ValidationManagerInterface;


use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\View\Design\Theme\ThemeProviderInterface;

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
     * @var UrlRewriteRouter
     */
    private $urlRewriteRouter;
    
    /**
     * @var BaseRouter
     */
    private $baseRouter;
    
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
     * @param StoreManagerInterface      $storeManager
     * @param ValidationManagerInterface $validationManager
     * @param UrlRewriteRouter           $urlRewriteRouter
     * @param BaseRouter                 $baseRouter
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
        StoreManagerInterface $storeManager,
        ValidationManagerInterface $validationManager,
        UrlRewriteRouter $urlRewriteRouter,
        BaseRouter $baseRouter,
        ScopeConfigInterface $scopeConfig,
        ThemeProviderInterface $themeProvider
    )
    {
        $this->storeManager = $storeManager;
        $this->urlRewriteRouter = $urlRewriteRouter;
        $this->baseRouter = $baseRouter;
        $this->validationManager = $validationManager;
        $this->_scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
        parent::__construct($actionList, $actionFactory, $defaultPath, $responseFactory, $routeConfig, $url, $nameBuilder, $pathConfig);
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
        if ((int)$themeType !== 4) {
            return null;
        }
        
        
        $this->forceHttpRedirect($request);
        $rewriteAction = $this->urlRewriteRouter->match($request);
        /**
         * @var Rewrite $action
         */
        $req = clone $request;
        $defaultAction = $this->baseRouter->match($req);
        $action = null;
        
        $pwaAction = $this->validationManager->validate($request);
        if ($pwaAction) {
            $action = $this->actionFactory->create(Pwa::class);
            $action->setType('PWA_ROUTER');
            $action->setCode(200)->setPhrase('OK');
        }
        

        
        if ($rewriteAction) {
            $action = $this->actionFactory->create(Pwa::class);
            $action->setType($this->getDefaultActionType($defaultAction));
            $action->setCode(200)->setPhrase('OK');
        }
        
        if ($defaultAction instanceof \Magento\Cms\Controller\Index\DefaultNoRoute) {
            $action = $this->actionFactory->create(Pwa::class);
            $action->setType('NOT_FOUND');
            $action->setCode(404)->setPhrase('Not Found');
        }
        
        return $action;
    }
    
    /**
     * @param ActionInterface $actionInstance
     * @return string|null
     */
    protected function getDefaultActionType(ActionInterface $actionInstance)
    {
        if ($actionInstance instanceof ProductView) {
            return 'PRODUCT';
        } elseif ($actionInstance instanceof CategoryView) {
            return 'CATEGORY';
        } elseif ($actionInstance instanceof PageView) {
            return 'CMS_PAGE';
        }
        
        return null;
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
     * @param RequestInterface $request
     * @param string           $path
     * @throws NoSuchEntityException
     */
    protected function _checkShouldBeSecure(\Magento\Framework\App\RequestInterface $request, $path = '')
    {
        if ($request->getPostValue()) {
            return;
        }
        
        if ($this->pathConfig->shouldBeSecure($path) && !$request->isSecure()) {
            $alias = $request->getAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS) ?: $request->getPathInfo();
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . "$alias";
            
            
            if ($this->_shouldRedirectToSecure()) {
                $url = $this->_url->getRedirectUrl($url);
            }
            
            $this->_responseFactory->create()->setRedirect($url)->sendResponse();
            exit;
        }
    }
}
