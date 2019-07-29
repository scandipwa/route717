<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use ScandiPWA\Router\PathTrait;
use ScandiPWA\Router\ValidatorInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

class Category implements ValidatorInterface
{
    use PathTrait;

    /**
     * @var Collection
     */
    protected $categoryCollection;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Category constructor.
     * @param Collection           $categoryCollection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Collection $categoryCollection,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->categoryCollection = $categoryCollection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateRequest(RequestInterface $request): bool
    {
        $urlPath = $this->getPathFrontName($request);
        /**
         * @var $category \Magento\Catalog\Model\Category
         */
        $category = $this->categoryCollection
            ->addAttributeToFilter('url_path', $urlPath)
            ->addAttributeToSelect(['entity_id'])
            ->getFirstItem();
        $categoryId = $category->getEntityId();

        if (!$categoryId) return false;

        $pageNumber = $request->getParam('page') ?? 1;
        $productsCount = $category->getProductCollection()->count();

        return $this->doesPageExist($pageNumber, $productsCount);
    }

    /**
     * Checks whether page exists on category
     * @param int $pageNumber
     * @param int $productsCount
     * @return bool
     */
    private function doesPageExist(int $pageNumber, int $productsCount): bool
    {
        if ($pageNumber <= 0 ) return false;

        $pageSize = $this->scopeConfig->getValue('catalog/frontend/grid_per_page');
        // '< 0' - previous Page does not exist, '== 0' - previous page is the last page, '> 0' next page does exist
        $remainingProducts = $productsCount - ($pageSize * ($pageNumber - 1));

        if ($remainingProducts > 0) {
            return true;
        }

        return false;
    }
}
