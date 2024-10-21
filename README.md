# 🧅 Onion: A Layering Mechanism for PHP Applications

Onion is a lightweight PHP package designed to facilitate layered processing within applications, It provides a clean and efficient way to stack layers of functionality, allowing developers to create flexible and reusable components that can be easily composed and managed.
Each layer can perform a specific operation on the data being passed through, making it simple to build complex workflows while maintaining clear separation of concerns.

* [Installation](#installation)
* [How it works](#how-it-works)
    * [Onion](#onion)
    * [Layers](#layers)
    * [Peeling the Onion](#peeling-the-onion)
* [Advanced Usage](#advanced-usage)
    * [Adding Layers](#adding-layers)
    * [Adding Metadata to Layers](#adding-metadata-to-layers)
    * [Composing Onions of Other Onions](#composing-onions-of-other-onions)
    * [Exception Handling](#exception-handling)
* [Usecases and Examples](#usecases-and-examples)
    * [PHP Pipe Operator](#php-pipe-operator)
    * [PHP League's Pipeline](#php-leagues-pipeline)
    * [Laravel's Pipeline](#laravels-pipeline)
    * [PSR-15 Request Handlers](#psr-15-request-handlers)

---

## Installation

```bash
composer require aldemeery/onion
```

## How it works

### Onion

The Onion object is the core component, representing a stack of layers that process data sequentially.
Each layer can transform the input data and/or perform specific operations.

To create an `Aldemeery\Onion\Onion` instance, you can directly instantiate it:

```php
use Aldemeery\Onion\Onion;

$onion = new Onion();
```

Or, you can use the `Aldemeery\Onion\onion` helper function for convenience:

```php
use function Aldemeery\Onion\onion;

$onion = onion();
```

Either way, you can optionally pass an initial layer or list of layers as an argument:

```php
use Aldemeery\Onion\Onion;
use function Aldemeery\Onion\onion;

// This..
$onion = new Onion([
    fn (string $value): string => strtolower($value),
    // ...
]);

// Is just like this...
$onion = onion(
    fn (string $value): string => strtolower($value),
);
```

### Layers

Layers are simple PHP [Closures](https://www.php.net/manual/en/class.closure.php) or classes that implement the `Aldemeery\Onion\Interfaces\Invokable` interface, and they are the essential building blocks of the Onion object, enabling you to define specific operations that are executed during processing.
Layers are executed sequentially in the order they were added, with the output of one layer becoming the input of the next.

> [!NOTE]
> Layers can only accept a single value because PHP functions/methods can only return a single value.

```php
use Aldemeery\Onion\Interfaces\Invokable;
use function Aldemeery\Onion\onion;

class EvenOrOdd implements Invokable
{
    public function __invoke(mixed $passable = null): string
    {
        return (bool) $passable ? 'Even' : 'Odd';
    }
}

$onion = onion([
    fn (int $value): bool => $value % 2 === 0,
    new EvenOrOdd(),
]);
```

### Peeling the Onion

Once your layers are defined and added to the Onion object, you can execute the full stack of layers using the `peel()` method. This method takes an *optional* value and passes it as an argument to the first layer, from there the output of each layer is passed as the input of the next layer, with the output of the last layer being returned as the output of the Onion itself.

```php
use Aldemeery\Onion\Interfaces\Invokable;
use function Aldemeery\Onion\onion;

class EvenOrOdd implements Invokable
{
    public function __invoke(mixed $passable = null): string
    {
        return (bool) $passable ? 'Even' : 'Odd';
    }
}

$onion = onion([
    fn (int $value): bool => $value % 2 === 0,
    new EvenOrOdd(),
]);

$result = $onion->peel(3); // 'Odd'
```

You can also call `peel()` without any arguments. Layers also don't necessarily need to return a value:

```php
use function Aldemeery\Onion\onion;

$onion = onion([
    function (): void {
        // Update a database record...
    },
    function (): void {
        // Send and email...
    }
]);

$onion->peel();
```

> [!IMPORTANT]
> Since layers can only accept a single value, the `peel()` method passes only the first argument to the first layer, ignoring any additional arguments.
> To pass multiple values, consider using an array or a DTO.

Because Onion objects themselves are callables, you could directly invoke them as functions, or pass them around as arguments:

```php
use function Aldemeery\Onion\onion;

$registerUser = onion([
    function (array $data): User {
        return User::create($data);
    },
    function (User $user): User {
        $user->notify(new VerifyEmail());

        return $user;
    },
]);

$user = $registerUser(['name' => 'John Doe', 'email' => 'john@doe.com']);
```

## Advanced Usage

### Adding Layers

You don't need to provide all the layers when instantiating the Onion object.
You can add more layers later by calling the `add()` method, which accepts either a single layer or an array of layers.

```php
use function Aldemeery\Onion\onion;

$onion = onion([
    fn (string $value): string => $value . 'H',
    fn (string $value): string => $value . 'E',
]);

if ($sunIsHot) {
    $onion->add(fn (string $value): string => $value . 'L');
}

$onion->add([
    fn (string $value): string => $value . 'L',
    fn (string $value): string => $value . 'O',
]);

$result = $onion->peel(''); // 'HELLO'
```

Or for a more fluent approach, you could use the convenience `addIf()` and `addUnless()` methods:

```php
use function Aldemeery\Onion\onion;

$result = onion([
    fn (string $value): string => $value . 'H',
    fn (string $value): string => $value . 'E',
])->addIf(
    $sunIsHot,
    fn (string $value): string => $value . 'L',
)->add([
    fn (string $value): string => $value . 'L',
    fn (string $value): string => $value . 'O',
])->peel(''); // 'HELLO'
```

### Adding Metadata to Layers

Onion provides a way for you to attach metadata to layers.
This metadata can be helpful for debugging purposes, allowing you to gain insights into the operations of your layers.

You can attach metadata to layers using the `Aldemeery\Onion\Attributes\Layer` attribute.
The metadata is stored as an associative array, enabling you to pass any relevant data along with your layers.

```php
use Aldemeery\Onion\Interfaces\Invokable;
use Aldemeery\Onion\Attributes\Layer;
use Illuminate\Http\Request;
use function Aldemeery\Onion\onion;

#[Layer(['name' => 'Authentication Middleware'])]
class Authenticate implements Invokable
{
    public function __invoke(mixed $request = null): Request
    {
        if ($request->user() === null) {
            throw new AuthenticationException();
        }

        return $request;
    }
}

$onion = onion([
    new Authenticate(),
    #[Layer(['name' => 'Log Request Middleware'])]
    function (Request $request): Request {
        log($request);

        return $request;
    }
]);
```

### Composing Onions of Other Onions

Since Onion objects are `Invokable`, they can be treated as layers themselves.
This allows you to combine multiple Onions into a single Onion, making it easy to build complex workflows while maintaining simplicity and readability.

For instance:

```php
use function Aldemeery\Onion\onion;

$unlockPremiumFeatures = onion([
    new EnableFeatureOne(),
    new EnableFeatureTwo(),
]);

$unlockSupport = onion([
    new EnableCustomerSupport(),
    new AssignPrivateSupportAgent(),
]);

$registerUser = onion([
    new StoreUserInDatabase(),
    new SendVerificationEmail(),
]);

$registerPremiumUser = onion([
    $registerUser,
    $unlockPremiumFeatures,
    new SendPremiumWelcomeEmail(),
])->addIf($paidForSupport, [
    $unlockSupport,
    new SendSupportCredentialsEmail(),
]);

$data = ['name' => 'John Doe', 'email' => 'john@doe.com'];

if ($paidForPremium) {
    $registerPremiumUser->peel($data);
} else {
    $registerUser->peel($data);
}
```

By composing Onions in this way, you can modularize your layers, promoting reusability and keeping the structure clean even as complexity grows.

### Exception Handling

In Onion, each layer you add is automatically wrapped with internal handling that catches exceptions and converts them into a standardized `Aldemeery\Onion\Exceptions\LayerException`.
This ensures consistent error handling across all layers, giving you full insight into which layer caused the exception and the context surrounding it.

The `LayerException` encapsulates key information such as the original exception, the passable value (data being processed), the problematic layer, and any metadata attached to that layer.
This makes it easier to trace and debug issues within your layers.

Additionally, `LayerException` provides several methods to help retrieve this contextual data:

|Method|Description|
|-|-|
|`getPassable(): mixed`|Retrieves the value that was passed into the layer.|
|`getLayer(): Closure\|Invokable`|Returns the specific layer that caused the exception.|
|`getLayerMetadata(): array`|Gets the metadata array associated with the layer.|
|`getLayerMetadata(string $key): mixed`|Fetches a specific metadata value by key.|

```php
use Aldemeery\Onion\Attributes\Layer;
use Aldemeery\Onion\Exceptions\LayerException;
use Aldemeery\Onion\Onion;

$onion = new Onion([
    #[Layer(['name' => 'division-by-zero', 'description' => 'Attempt to divide by zero'])]
    fn (int $value): float => $value / 0,
]);

try {
    $onion->peel(1);
} catch (LayerException $e) {
    var_dump([
        'passable' => $e->getPassable(),               // Value passed into the layer
        'layer' => $e->getLayer(),                     // Layer that threw the exception
        'layerMetadata' => $e->getLayerMetadata(),     // Full metadata of the layer
        'metadataKey' => $e->getLayerMetadata('name'), // Specific metadata key
        'message' => $e->getMessage(),                 // Exception message
        'previous' => $e->getPrevious(),               // Original exception before conversion
    ]);
}
```

In certain scenarios, you may wish to bypass exception handling entirely.
To do this, simply invoke the `withoutExceptionHandling()` method on the Onion object:

```php
use Aldemeery\Onion\Exceptions\LayerException;
use Aldemeery\Onion\Onion;

$onion = new Onion([
    fn (int $value): float => $value / 0,
])->withoutExceptionHandling();

try {
    $onion->peel(1);
} catch (DivisionByZeroError $e) {
    $e->getMessage(); // 'Division by zero'
}
```

If you prefer to implement a custom exception handler, you can utilize the `setExceptionHandler()` method on the Onion object.
An exception handler is defined as a Closure with the following signature: `function (Throwable $e, Closure|Invokable $layer, mixed $passable): mixed`.

Here's an example:

```php
use function Aldemeery\Onion\onion;

$onion = onion([
    fn (int $value): float => $value / 0,
]);

$onion->setExceptionHandler(function (Throwable $e, Closure|Invokable $layer, mixed $passable): mixed {
    if ($e instanceof MyCustomException) {
        throw $e; // Allow the exception to propagate
    }

    throw new MyCustomException('This is a custom exception');
});

try {
    $onion->peel(1);
} catch (MyCustomException $e) {
    $e->getMessage(); // 'This is a custom exception'
}
```

This approach offers you flexibility in handling exceptions, allowing for tailored behavior based on your application's requirements.

## Usecases and Examples

Onion can be applied in various scenarios, making it a versatile tool for structuring data flow and functional composition.

Below are some examples that highlight how Onion simplifies and enhances certain workflows.

### PHP Pipe Operator

The [RFC to introduce a Pipe Operator in PHP](https://wiki.php.net/rfc/pipe-operator-v2) was once proposed but ultimately declined.
The operator would have allowed for more intuitive functional composition.

Onion achieves the exact same functionality, allowing you to stack operations in a clean and organized way, just like the proposed pipe operator.

Here’s an example from the [RFC](https://wiki.php.net/rfc/pipe-operator-v2), demonstrating how Onion can replace the pipe operator.

Original [RFC](https://wiki.php.net/rfc/pipe-operator-v2) Example:
```php
$result = 'Hello World'
    |> htmlentities(...)
    |> str_split(...)
    |> fn($x) => array_map(strtoupper(...), $x)
    |> fn($x) => array_filter($x, fn($v) => $v != 'O');
```

Re-written Using Onion:

```php
use function Aldemeery\Onion\onion;

$result = onion([
    htmlentities(...),
    str_split(...),
    fn ($v) => array_map(strtoupper(...), $v),
    fn ($x) => array_filter($x, fn ($v) => $v != 'O'),
])->peel('Hello World');
```

Similarly, other examples from the [RFC](https://wiki.php.net/rfc/pipe-operator-v2) can also be adapted using Onion in the same way, showcasing its flexibility in handling sequential transformations.

### PHP League's Pipeline

The PHP League offers a robust [implementation of the Pipeline pattern](https://github.com/thephpleague/pipeline), widely used to process data through a series of steps, or "stages", much like Onion.
Both Onion and PHP League's Pipeline share a similar goal: they allow developers to sequentially process data through layers, simplifying complex workflows.

However, while both tools provide similar core functionality, Onion offers a more functional syntax with a few distinct features like converting all exceptions to `LayerException`s, conditionally adding layers, and the ability to attach metadata to layers.

Here's an example based on the PHP League's Pipeline documentation and its Onion equivalent:

```php
use League\Pipeline\Pipeline;

$pipeline = (new Pipeline())
    ->pipe(new ConvertToPsr7Request())
    ->pipe(new ExecuteHttpRequest())
    ->pipe(new ParseJsonResponse())
    ->pipe(new ConvertToResponseDto());

$pipeline->process(new DeleteBlogPost($postId));
```

Using Onion:

```php
use function Aldemeery\Onion\onion;

$onion = onion([
    new ConvertToPsr7Request(),
    new ExecuteHttpRequest(),
    new ParseJsonResponse(),
    new ConvertToResponseDto(),
]);

$onion->peel(new DeleteBlogPost($postId));
```

### Laravel's Pipeline

Laravel provides its own implementation of the Pipeline pattern. However, it is slightly tailored specifically for its framework.

If you're looking for a solution that offers similar functionality without being tied to the Laravel framework, Onion is an excellent alternative.

Here's an example from the Laravel Pipeline documentation re-written using Onion:

```php
use App\Models\User;
use Illuminate\Support\Facades\Pipeline;

$user = Pipeline::send($user)
    ->through([
        GenerateProfilePhoto::class,
        ActivateSubscription::class,
        SendWelcomeEmail::class,
    ])
    ->then(fn (User $user) => $user);
```

Using Onion:

```php
use App\Models\User;
use function Aldemeery\Onion\onion;

$user = onion([
    new GenerateProfilePhoto(),
    new ActivateSubscription(),
    new SendWelcomeEmail(),
    fn (User $user): User => $user,
])->peel($user);
```

### PSR-15 Request Handlers

[PSR-15](https://www.php-fig.org/psr/psr-15/) defines a standardized interface for HTTP server request handlers, which are essential for any web application.
You can implement [PSR-15](https://www.php-fig.org/psr/psr-15/) request handlers as `Invokable` layers with Onion, allowing for a clean and modular handling of requests.

Here's an example:

```php
use Aldemeery\Onion\Interfaces\Invokable;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function Aldemeery\Onion\onion;

class AuthenticateRequest implements Invokable, RequestHandlerInterface
{
    public function __invoke(mixed $passable = null): ResponseInterface
    {
        $this->handle($passable);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Handle the request...
    }
}

class AuthorizeRequest implements Invokable, RequestHandlerInterface
{
    public function __invoke(mixed $passable = null): ResponseInterface
    {
        $this->handle($passable);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Handle the request...
    }
}

class ProcessRequest implements Invokable, RequestHandlerInterface
{
    public function __invoke(mixed $passable = null): ResponseInterface
    {
        $this->handle($passable);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Handle the request...
    }
}

class ServerRequest implements ServerRequestInterface
{
    // ...
}

$response = onion([
    new AuthenticateRequest(),
    new AuthorizeRequest(),
    new ProcessRequest(),
])->peel(new ServerRequest());
```
