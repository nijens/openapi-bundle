# Create your custom request validator

With the improved validation component, you can create your custom request validator by implementing
the [`ValidatorInterface`](../../src/Validation/RequestValidator/ValidatorInterface.php).

It allows you to create, for example, an XML request body validator. First, create your validator class:
```php
<?php

declare(strict_types=1);

namespace App\Example;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class XmlRequestBodyValidator implements ValidatorInterface
{
    public function validate(Request $request): ?RequestProblemExceptionInterface {
        $requestContentType = $this->getRequestContentType($request);
        if ($requestContentType !== 'application/xml') {
            return null;
        }

        $requestBody = $request->getContent();
        $violations = $this->validateAgainstSchema($requestBody);
        if (count($violations) > 0) {
            $exception = new InvalidRequestBodyProblemException(
                ProblemException::DEFAULT_TYPE_URI,
                'The request body contains errors.',
                Response::HTTP_BAD_REQUEST,
                'Validation of XML request body failed.'
            );

            return $exception->withViolations($violations);
        }

        return null;
    }

    private function getRequestContentType(Request $request): string
    {
        return current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';')) ?: '';
    }

    // ...
}
```

Second, add the `nijens_openapi.validation.validator` tag to the class in the service container:

```yaml
# config/services.yaml
services:
    App\Example\XmlRequestBodyValidator:
        tags:
            - { name: 'nijens_openapi.validation.validator', priority: 0 }
```

And done! 🎉
