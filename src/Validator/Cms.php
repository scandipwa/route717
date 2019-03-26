<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;

use Magento\Cms\Model\GetPageByIdentifier;
use Magento\Framework\App\RequestInterface;
use ScandiPWA\Router\PathTrait;
use ScandiPWA\Router\ValidatorInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Cms
 * @package ScandiPWA\Router\Validator
 */
class Cms implements ValidatorInterface
{
    use PathTrait;
    /**
     * @var GetPageByIdentifier
     */
    protected $getPageById;

    /**
     * Cms constructor.
     * @param GetPageByIdentifier $getPageByIdentifier
     */
    public function __construct(GetPageByIdentifier $getPageByIdentifier)
    {
        $this->getPageById = $getPageByIdentifier;
    }

    /**
     * @inheritdoc
     */
    public function validateRequest(RequestInterface $request): bool
    {
        $cmsPageId = $this->getPathFrontName($request);

        try {
            $this->getPageById->execute($cmsPageId, 0);
        } catch (NoSuchEntityException $e) {
            return false;
        }
        return true;
    }
}
