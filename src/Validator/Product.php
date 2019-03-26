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
use \Magento\Catalog\Model\ResourceModel\Product\Collection;

class Product implements ValidatorInterface
{
    use PathTrait;

    /**
     * @var Collection
     */
    protected $productCollection;

    /**
     * Product constructor.
     * @param Collection $productCollection
     */
    public function __construct(Collection $productCollection)
    {
        $this->productCollection = $productCollection;
    }

    /**
     * @inheritdoc
     */
    public function validateRequest(RequestInterface $request): bool
    {
        $urlKey = $this->getPathFrontName($request);
        $productCollection = $this->productCollection->clear();
        $productCollection->addAttributeToFilter('url_key', $urlKey);
        $ids = $productCollection->getAllIds();
        $productId = reset($ids);

        return !!$productId;
    }
}
