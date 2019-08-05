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
use Magento\ConfigurableProduct\Api\Data\OptionValueInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
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
    )
    {
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
        $productCollection = $this->productCollection->clear();
        $productCollection->addAttributeToFilter('url_key', $urlKey);

        $ids = $productCollection->getAllIds();
        $productId = reset($ids);

        if (!$productId) return false;

        $typeIds = $productCollection->getProductTypeIds();
        $type = reset($typeIds);
        $parameters = $request->getParams();

        if ($type === Configurable::TYPE_CODE && !empty($parameters) && !$this->checkConfigurableProduct($productId, $parameters))
            return false;

        return true;
    }

    /**
     * Checks whether configurable product with specified parameters does exist
     * @param int $productId
     * @param array $parameters
     * @return bool
     */
    protected function checkConfigurableProduct(int $productId, array $parameters): bool {
        $product = $this->product->load($productId);

        $attributes = $this->configurable->getConfigurableAttributes($product);

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getProductAttribute()->getAttributeCode();

            if (array_key_exists($attributeCode, $parameters)) {
                $parameterValue = $parameters[$attributeCode];
                unset($parameters[$attributeCode]);
                $options = $attribute->getOptions();

                if (array_search($parameterValue, array_column($options,  'value_index')) === false) return false;
            }
        }

        if (!empty($parameters)) return false;

        return true;
    }
}
