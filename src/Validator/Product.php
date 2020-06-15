<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use ScandiPWA\Router\PathTrait;
use ScandiPWA\Router\ValidatorInterface;
use \Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Class Product
 * @package ScandiPWA\Router\Validator
 */
class Product implements ValidatorInterface
{
    use PathTrait;

    /** @var Collection  */
    protected $productCollection;
    /**
     * @var Configurable
     */
    private $configurable;
    /**
     * @var ProductModel
     */
    private $product;

    /**
     * Product constructor.
     * @param Collection $productCollection
     * @param Configurable $configurable
     * @param ProductModel $product
     */
    public function __construct(
        Collection $productCollection,
        Configurable $configurable,
        ProductModel $product
    ) {
        $this->productCollection = $productCollection;
        $this->configurable = $configurable;
        $this->product = $product;
    }

    /**
     * @inheritdoc
     */
    public function validateRequest(RequestInterface $request): bool
    {
        $urlKey = $this->getPathFrontName($request);
        $parameters = $request->getParams();

        $productCollection = $this->productCollection->clear();
        $productCollection->addAttributeToFilter('url_key', $urlKey);
        $ids = $productCollection->getAllIds();
        $productId = reset($ids);

        if (!$productId) {
            return false;
        }

        $typeIds = $productCollection->getProductTypeIds();
        $type = reset($typeIds);

        switch ($type) {
            case Configurable::TYPE_CODE:
                return $this->checkConfigurableProduct($productId, $parameters);
            default:
                return true;
        }
    }

    /**
     * Checks whether configurable product with specified parameters does exist
     * @param int $productId
     * @param array $parameters
     * @return bool
     */
    protected function checkConfigurableProduct(int $productId, array $parameters): bool
    {
        if (!count($parameters)) {
            return true;
        }

        $product = $this->product->load($productId);
        $attributes = $this->configurable->getConfigurableAttributes($product);
        $attributeCount = 0;

        // loop through all configurable product attributes
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getProductAttribute()->getAttributeCode();

            // if configurable attribute code is mentioned URL param
            if (array_key_exists($attributeCode, $parameters)) {
                $parameterValue = $parameters[$attributeCode];
                unset($parameters[$attributeCode]);
                $options = $attribute->getOptions();

                if (!in_array($parameterValue, array_column($options, 'value_index'), true)) {
                    return false;
                }

                $attributeCount++;
            }
        }

        if ($attributeCount !== count($attributes)) { // if all configurable attributes are not matched
            return false;
        }

        if (!empty($parameters)) { // if all parameters were processed
            return false;
        }

        return true;
    }
}
