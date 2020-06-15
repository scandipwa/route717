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

trait PathTrait
{
    /**
     * @param RequestInterface $request
     * @return string
     */
    protected function getPathFrontName(RequestInterface $request)
    {
        $path = trim($request->getPathInfo(), '/');
        $params = explode('/', $path);

        if (count($params) >= 2) {
            return $params[1];
        }

        return null;
    }
}
