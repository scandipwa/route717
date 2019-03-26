<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;


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
     * Category constructor.
     * @param Collection $categoryCollection
     */
    public function __construct(Collection $categoryCollection)
    {
        $this->categoryCollection = $categoryCollection;
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

        return !!$categoryId;
    }
}
