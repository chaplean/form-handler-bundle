# Upgrading Guide

## From 4.x to 5.x

Version 5 brings the status code `HTTP_CREATED` (201) to the response when the handler returns an entity inserted to the database. This change may break the attended response code status.

The `ParamFetcherInterface` is now accepted in replacement of the `Request` to limit the responsibility of the bundle.

Now, instanciated services can be put in argument of `successHandler` and `failureHandler` to avoid using the container to get them.

Be aware that the Bundle no longer use the `RegistryInterface` to get the `EntityManager` but will use directly the `EntityManagerInterface` service.

Finally, all services available from the bundle are now available for autowiring. 
