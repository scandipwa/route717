<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Controller;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;
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
use Magento\Framework\Interception\ConfigLoaderInterface;
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
use Magento\Cms\Model\PageFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use ScandiPWA\CmsGraphQl\Model\Resolver\DataProvider\Page as PageProvider;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Filter\Template\Tokenizer\Parameter as TokenizerParameter;
use ScandiPWA\SliderGraphQl\Model\Resolver\Slider as SliderResolver;

class Router extends BaseRouter
{
    const XML_PATH_CMS_HOME_PAGE = 'web/default/cms_home_page';
    const XML_PATH_THEME_USER_AGENT = 'design/theme/ua_regexp';
    const XML_PATH_CATALOG_DEFAULT_SORT_BY = 'catalog/frontend/default_sort_by';

    const PAGE_TYPE_PRODUCT = 'PRODUCT';
    const PAGE_TYPE_CATEGORY = 'CATEGORY';
    const PAGE_TYPE_CMS_PAGE = 'CMS_PAGE';

    const CRUCIAL_STORE_CONFIG_VALUES = [
        'cms_home_page' => self::XML_PATH_CMS_HOME_PAGE,
        'catalog_default_sort_by' => self::XML_PATH_CATALOG_DEFAULT_SORT_BY
    ];

    /**
     * @var ValidationManagerInterface
     */
    protected $validationManager;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;

    /**
     * @var ThemeProviderInterface
     */
    protected $themeProvider;

    /**
     * @var array
     */
    protected $ignoredURLs;

    /**
     * @var int|string
     */
    protected $storeId;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var PageProvider
     */
    protected PageProvider $pageProvider;

    /**
     * @var TokenizerParameter
     */
    protected $tokenizerParameter;

    /**
     * @var SliderResolver
     */
    protected $sliderResolver;

    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var GetPageByIdentifierInterface
     */
    protected $pageByIdentifier;

    /**
     * Router constructor.
     * @param ActionList $actionList
     * @param ActionFactory $actionFactory
     * @param DefaultPathInterface $defaultPath
     * @param ResponseFactory $responseFactory
     * @param ConfigInterface $routeConfig
     * @param UrlInterface $url
     * @param NameBuilder $nameBuilder
     * @param PathConfigInterface $pathConfig
     * @param ValidationManagerInterface $validationManager
     * @param UrlFinderInterface $urlFinder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ThemeProviderInterface $themeProvider
     * @param PageFactory $pageFactory
     * @param ProductRepository $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param PageProvider $pageProvider
     * @param TokenizerParameter $tokenizerParameter
     * @param SliderResolver $sliderResolver
     */
    public function __construct(
        ActionList                   $actionList,
        ActionFactory                $actionFactory,
        DefaultPathInterface         $defaultPath,
        ResponseFactory              $responseFactory,
        ConfigInterface              $routeConfig,
        UrlInterface                 $url,
        NameBuilder                  $nameBuilder,
        PathConfigInterface          $pathConfig,
        ValidationManagerInterface   $validationManager,
        UrlFinderInterface           $urlFinder,
        StoreManagerInterface        $storeManager,
        ScopeConfigInterface         $scopeConfig,
        ThemeProviderInterface       $themeProvider,
        PageFactory                  $pageFactory,
        ProductRepository            $productRepository,
        CategoryRepositoryInterface  $categoryRepository,
        PageProvider                 $pageProvider,
        TokenizerParameter           $tokenizerParameter,
        SliderResolver               $sliderResolver,
        PageRepositoryInterface      $pageRepository,
        GetPageByIdentifierInterface $getPageByIdentifier,
        array                        $ignoredURLs = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
        $this->validationManager = $validationManager;
        $this->urlFinder = $urlFinder;
        $this->storeManager = $storeManager;
        $this->pageFactory = $pageFactory;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageProvider = $pageProvider;
        $this->ignoredURLs = $ignoredURLs;
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->tokenizerParameter = $tokenizerParameter;
        $this->sliderResolver = $sliderResolver;
        $this->pageRepository = $pageRepository;
        $this->pageByIdentifier = $getPageByIdentifier;

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
        $themeId = $this->scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );

        $expressions = $this->scopeConfig->getValue(
            self::XML_PATH_THEME_USER_AGENT,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );

        if ($expressions) {
            $userAgentRules = json_decode($expressions, true);

            foreach ($userAgentRules as $userAgentRule) {
                $regexp = stripslashes($userAgentRule['regexp']);

                if (preg_match($regexp, $_SERVER['HTTP_USER_AGENT'])) {
                    $themeId = $userAgentRule['value'];
                }
            }
        }

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
            $this->setPageDetails($rewrite, $action);
        } elseif ($this->validationManager->validate($request)) { // Validate custom PWA routing
            $action->setType('PWA_ROUTER');
            $action->setCode(200)->setPhrase('OK');
        } else { //Fallback to 404 but return PWA app
            $action->setType('NOT_FOUND');
            $action->setCode(404)->setPhrase('Not Found');
        }

        if ($this->isHomePage($request)) {
            $this->setResponseHomePage($action);
            $this->setStoreConfigs($action);
        }

        return $action;
    }

    /**
     * Update response
     *
     * @param UrlRewrite $urlRewrite
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function setPageDetails(UrlRewrite $urlRewrite, ActionInterface $action)
    {
        $actionType = $this->getDefaultActionType($urlRewrite);
        $entityId = $urlRewrite->getEntityId();

        switch ($actionType) {
            case self::PAGE_TYPE_CMS_PAGE:
                $this->setResponseCmsPage($entityId, $action);
                break;
            case self::PAGE_TYPE_PRODUCT:
                $this->setResponseProduct($entityId, $action);
                break;
            case self::PAGE_TYPE_CATEGORY:
                $this->setResponseCategory($entityId, $action);
                break;
        }

        $this->setStoreConfigs($action);
    }

    protected function setResponseHomePage(ActionInterface $action)
    {
        $homePageIdentifier = $this->scopeConfig->getValue(
            self::XML_PATH_CMS_HOME_PAGE,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );

        try {
            $page = $this->getCmsPageFromGraphqlArea(null, $homePageIdentifier);

            if (!isset($page['cmsPage'])) {
                $action->setType('PWA_ROUTER');
                return;
            }

            $action->setType(self::PAGE_TYPE_CMS_PAGE);
            $action->setId($page['cmsPage']['page_id'] ?? '');
            $action->setCmsPage($page['cmsPage']);
            $action->setSlider($this->getSliderInformation($page['content'] ?? ''));
        } catch (\Throwable $th) {
            $action->setType('PWA_ROUTER');
        }
    }

    /**
     * @param string $content
     *
     * This function gets first widget from page content
     * In case if it is slider, then it gets slider it.
     */
    protected function getSliderInformation($content)
    {
        preg_match('/{{(.*?)}}/m', $content, $match);

        $this->tokenizerParameter->setString($match[0] ?? '');
        $params = $this->tokenizerParameter->tokenize();

        if (isset($params['slider_id'])) {
            return $this->sliderResolver->getSlider($params['slider_id']);
        }
    }

    /**
     * Validate that CMS page is assigned to current store and is enabled and return 404 if not
     *
     * @param int|string $id
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function setResponseCmsPage($id, ActionInterface $action)
    {
        try {
            $page = $this->getCmsPageFromGraphqlArea((int)$id);

            if (!isset($page['cmsPage'])) {
                $this->setNotFound($action);
                return;
            }

            $action->setId($page['cmsPage']['page_id'] ?? '');
            $action->setCmsPage($page['cmsPage']);
            $action->setSlider($this->getSliderInformation($page['content'] ?? ''));
        } catch (\Throwable $th) {
            $this->setNotFound($action);
        }
    }

    /**
     * Validate that product is enabled on current store and return 404 if not
     *
     * @param int|string $id
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function setResponseProduct($id, ActionInterface $action)
    {
        try {
            $product = $this->productRepository->getById($id, false, $this->storeId);

            if (!$product->getId() || $product->getStatus() != Status::STATUS_ENABLED) {
                $this->setNotFound($action);
                return;
            }

            $action->setId($product->getId() ?? '');
            $action->setSku($product->getSku() ?? '');
            $action->setName($product->getName() ?? '');
        } catch (NoSuchEntityException $e) {
            $this->setNotFound($action);
        }
    }

    /**
     * Validate that category is enabled on current store and return 404 if not
     *
     * @param int|string $id
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function setResponseCategory($id, ActionInterface $action)
    {
        try {
            $category = $this->categoryRepository->get($id, $this->storeId);

            if (!$category->getIsActive()) {
                $this->setNotFound($action);
                return;
            }

            $action->setId($category->getId() ?? '');
            $action->setName($category->getName() ?? '');
            $action->setDisplayMode($category->getDisplayMode() ?? 'PRODUCTS');
            $action->setDescription($category->getDescription() ?? '');
            $action->setCategoryDefaultSortBy($category->getDefaultSortBy() ?? '');
        } catch (NoSuchEntityException $e) {
            $this->setNotFound($action);
        }
    }

    protected function setStoreConfigs(ActionInterface $action)
    {
        $storeConfig = [];
        $crucialStoreConfigs = self::CRUCIAL_STORE_CONFIG_VALUES;

        foreach ($crucialStoreConfigs as $configKey => $path) {
            $configValue = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeId
            );

            $storeConfig[$configKey] = $configValue;
        }

        $action->setStoreConfig($storeConfig);
    }

    protected function getCmsPageFromGraphqlArea(int $id = null, string $identifier = null)
    {
        try {
            // vvv construct PageProvider with grahql area
            $objectManager = ObjectManager::getInstance();
            $configLoader = $objectManager->get(ConfigLoaderInterface::class);
            $currentConfigArea = $objectManager->get(State::class)->getAreaCode();
            $objectManager->configure($configLoader->load(Area::AREA_GRAPHQL));

            $pageProvider = $objectManager->create(PageProvider::class);
            $page = null;
            $result = [];

            if ($id) {
                $page = $this->pageRepository->getById($id);
            }

            if ($identifier) {
                $page = $this->pageByIdentifier->execute($identifier, (int)$this->storeId);
            }

            $result['cmsPage'] = $pageProvider->convertPageData($page);
            $result['content'] = $page->getContent();

            // vvv reset config area back to initial
            $objectManager->configure($configLoader->load($currentConfigArea));

            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set "404 Not Found" response
     *
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function setNotFound(ActionInterface $action)
    {
        $action->setType('NOT_FOUND');
        $action->setCode(404)->setPhrase('Not Found');
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

        return $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => ltrim($requestPath, '/'),
            UrlRewrite::STORE_ID => $this->storeId
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
            $url = $this->_url->getDirectUrl($target);
            $this->_performRedirect($url, $rewrite->getRedirectType());
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

    protected function isHomePage(RequestInterface $request): bool
    {
        $requestPath = $request->getPathInfo();

        if (!$requestPath || $requestPath === '/') {
            return true;
        }

        $storeCode = $this->storeManager->getStore()->getCode();

        if (substr($requestPath, 0, 1) === '/' && $requestPath !== '/') {
            $requestPath = ltrim($requestPath, '/');
        }

        $routes = array_filter(explode('/', $requestPath));
        $code = $routes[0] ?? '';

        if (count($routes) == 1 && $code == $storeCode) {
            return true;
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

