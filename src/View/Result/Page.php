<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\UrlrewriteGraphql
 * @author    Vladimirs Mihnovics <info@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\View\Result;

use Magento\Framework;
use Magento\Framework\View;
use Magento\Framework\View\Result\Page as ExtendedPage;

class Page extends ExtendedPage
{
    /**
     * @var string
     */
    protected $pageLayout;

    /**
     * @var \Magento\Framework\View\Page\Config
     */
    protected $pageConfig;

    /**
     * @var \Magento\Framework\View\Page\Config\RendererInterface
     */
    protected $pageConfigRenderer;

    /**
     * @var \Magento\Framework\View\Page\Config\RendererFactory
     */
    protected $pageConfigRendererFactory;

    /**
     * @var \Magento\Framework\View\Page\Layout\Reader
     */
    protected $pageLayoutReader;

    /**
     * @var \Magento\Framework\View\FileSystem
     */
    protected $viewFileSystem;

    /**
     * @var array
     */
    protected $viewVars;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Asset service
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var View\EntitySpecificHandlesList
     */
    private $entitySpecificHandlesList;

    /**
     * @var string;
     */
    private $action;
    /**
     * Constructor
     *
     * @param View\Element\Template\Context $context
     * @param View\LayoutFactory $layoutFactory
     * @param View\Layout\ReaderPool $layoutReaderPool
     * @param Framework\Translate\InlineInterface $translateInline
     * @param View\Layout\BuilderFactory $layoutBuilderFactory
     * @param View\Layout\GeneratorPool $generatorPool
     * @param View\Page\Config\RendererFactory $pageConfigRendererFactory
     * @param View\Page\Layout\Reader $pageLayoutReader
     * @param string $template
     * @param bool $isIsolated
     * @param View\EntitySpecificHandlesList $entitySpecificHandlesList
     * @param string $action
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        View\Element\Template\Context $context,
        View\LayoutFactory $layoutFactory,
        View\Layout\ReaderPool $layoutReaderPool,
        Framework\Translate\InlineInterface $translateInline,
        View\Layout\BuilderFactory $layoutBuilderFactory,
        View\Layout\GeneratorPool $generatorPool,
        View\Page\Config\RendererFactory $pageConfigRendererFactory,
        View\Page\Layout\Reader $pageLayoutReader,
        $template,
        $isIsolated = false,
        View\EntitySpecificHandlesList $entitySpecificHandlesList = null,
        $action = null
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $layoutReaderPool,
            $translateInline,
            $layoutBuilderFactory,
            $generatorPool,
            $pageConfigRendererFactory,
            $pageLayoutReader,
            $template,
            $isIsolated,
            $entitySpecificHandlesList
        );
        $this->action = $action;
    }

    /**
     * Set action type
     *
     * @param string
     * @return \Magento\Framework\View\Result\Page
     */
    public function setAction(string $actionType)
    {
        if($this->action === null) {
            $this->action = $actionType;
            return $this;
        }

        return null;
    }

    /**
     * Retrieve action type
     *
     * @return  string
     */
    public function getAction()
    {
        return $this->action;
    }

}
