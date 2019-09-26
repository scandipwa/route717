<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Raivis Dejus <info@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;

use Magento\Framework\App\RequestInterface;
use ScandiPWA\Router\ValidatorInterface;

/**
 * Class Cms
 * @package ScandiPWA\Router\Validator
 */
class Checkout implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validateRequest(RequestInterface $request): bool
    {
        return true;
    }
}
