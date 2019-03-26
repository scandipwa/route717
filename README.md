#ScandiPWA Route717

Module is providing custom router to properly resolve HTTP codes on specific page request.

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
