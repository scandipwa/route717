# ScandiPWA Route717

Module is providing custom router to properly resolve HTTP codes on specific page request.

## v1.1.0 update
Custom router is now checking theme type in order to support URL rewrites. 
You must ensure PWA theme type is set to `4` to utilize the options.

[Installer](https://github.com/scandipwa/installer) is doing this automatically for any newly generated theme.

For already generated theme please update manually: set PWA theme type to `4` in `theme` table.


## Installation
```composer require scandipwa/route717```

## Description
Module is providing validators for such entities:
1) Product (/product)
2) Category (/category)
3) Cms pages (/page)
4) Cart (/cart)
5) Root (/)

## How it works
Each frontend path (Magento 2 front name) should be added to validator list in `src/etc/di.xml`. Depending on entity 
type it might have more or less complex validation logic, to i.e. determine if specific product exists or not.
Router is only responding 200 OK to entities, which it was able to validate. Otherwise it is falling back to default 
Magento 2 routers. 


## Customization
To add new path you must provide your own validator, that must implement `ScandiPWA\Router\ValidatorInterface` and 
register it as an array element passed to `ScandiPWA\Router\ValidationManager` using `di.xml`.


## Accessing Magento 2 default routes
In order to allow Magento 2 to handle some routes in default manner (only serverside functionality) you must allow
 specific routes to be accessed by adding RegExp to `src/app/etc/di.xml::ignoredURLs` arguments list.
 
So far there are 3 paths whitelisted out of the box:
- `/newsletter/subscriber/confirm` - subscribe to newsletter
- `/newsletter/subscriber/unsubscribe` - unsubscribe to newsletter
- `/customer/account/confirm/` - confirm e-mail (redirect to homepage in any case, modify according to your needs)