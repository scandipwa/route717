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
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\Router\Base;
use Magento\Framework\App\Router\PathConfigInterface;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use ScandiPWA\Router\ValidationManagerInterface;

class Router extends Base
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
     * Router constructor.
     * @param ActionList             $actionList
     * @param ActionFactory          $actionFactory
     * @param DefaultPathInterface   $defaultPath
     * @param ResponseFactory        $responseFactory
     * @param ConfigInterface        $routeConfig
     * @param UrlInterface           $url
     * @param NameBuilder            $nameBuilder
     * @param PathConfigInterface    $pathConfig
     * @param ObjectManagerInterface $om
     * @param array                  $paths
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
        ValidationManagerInterface $validationManager
    )
    {
        $this->_storeManager = $storeManager;
        $this->validationManager = $validationManager;
        parent::__construct($actionList, $actionFactory, $defaultPath, $responseFactory, $routeConfig, $url, $nameBuilder, $pathConfig);
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface | null
     */
    public function match(RequestInterface $request)
    {
        $this->forceHttpRedirect($request);
        $valid = $this->validationManager->validate($request);
        if (!$valid) {
            return null;
        }

        return $this->actionFactory->create(\ScandiPWA\Router\Controller\Ok\Index::class);
    }

    /**
     * @param RequestInterface $request
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _checkShouldBeSecure(\Magento\Framework\App\RequestInterface $request, $path = '')
    {
        if ($request->getPostValue()) {
            return;
        }

        if ($this->pathConfig->shouldBeSecure($path) && !$request->isSecure()) {
            $alias = $request->getAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS) ?: $request->getPathInfo();
            $url = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . "$alias";


            if ($this->_shouldRedirectToSecure()) {
                $url = $this->_url->getRedirectUrl($url);
            }

            $this->_responseFactory->create()->setRedirect($url)->sendResponse();
            exit;
        }
    }
}
