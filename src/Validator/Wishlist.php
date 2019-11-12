<?php
/**
 * @category  ScandiPWA
 * @package   ScandiPWA\Router
 * @author    Artjoms Travkovs <info@scandiweb.com>
 * @copyright Copyright (c) 2019 Scandiweb, Ltd (http://scandiweb.com)
 * @license   OSL-3.0
 */

namespace ScandiPWA\Router\Validator;

use Magento\Framework\App\RequestInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist as WishlistModel;
use Magento\Wishlist\Model\WishlistFactory;
use ScandiPWA\Router\PathTrait;
use ScandiPWA\Router\ValidatorInterface;

class Wishlist implements ValidatorInterface
{
    use PathTrait;

    const SHARED_URL_KEY = 'shared';

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(WishlistResourceModel $wishlistResource, WishlistFactory $wishlistFactory)
    {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
    }

    public function validateRequest(RequestInterface $request): bool
    {
        $urlKey = $this->getPathFrontName($request);

        if ($urlKey !== self::SHARED_URL_KEY) {
            return false;
        }

        $sharingKey = $this->getSharingKey($request);

        return $this->checkIsShared($sharingKey);
    }

    protected function checkIsShared(string $sharingKey): bool
    {
        /** @var WishlistModel $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $sharingKey, 'sharing_code');

        if (!($wishlist->getId() && $wishlist->getShared())) {
            return false;
        }

        return true;
    }

    protected function getSharingKey(RequestInterface $request): string
    {
        $path = trim($request->getPathInfo(), '/');
        $params = explode('/', $path);

        return end($params);
    }
}
