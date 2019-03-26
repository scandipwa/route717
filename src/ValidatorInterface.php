<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router;


use Magento\Framework\App\RequestInterface;

/**
 * Interface ValidatorInterface
 * @package ScandiPWA\Router
 */
interface ValidatorInterface
{
    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function validateRequest(RequestInterface $request): bool;
}
