<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Ilja Lapkovskis <info@scandiweb.com / ilja@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Controller\Customer;


use Magento\Customer\Controller\Account\Confirm;
use \Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\UrlInterface;


class RedirectPlugin
{
    /**
     * @var UrlInterface
     */
    protected $urlModel;
    
    /**
     * ValidateRedirect constructor.
     * @param UrlInterface $urlModel
     */
    public function __construct(UrlInterface $urlModel)
    {
        $this->urlModel = $urlModel;
    }
    
    /**
     * @param Confirm  $a
     * @param Redirect $result
     * @return Redirect
     */
    public function afterExecute(Confirm $subject, $result)
    {
        return $result->setPath($this->urlModel->getBaseUrl());
    }
}