# Laravel Piped Tasks

Manage tasks workflows through Laravel Pipes.

## Concept

A **process** defines the order of **tasks** executed through a pipe.

Each process is associated with a **payload**. Payload is a mutable object passed to each task to retrieve, add, or update data.

## Installation

Install the package via composer:

```bash
composer require ibrostudio/laravel-piped-tasks
```

## Usage

**1. Create process**

First you need to generate a process:
```bash
php artisan make:piped-process CreateOrderProcess
```

Name your process like this : **\<Action>\<Domain>Process**

**2. Define Payload**

You'll find the associated payload to your process in `App\Processes\Payloads` and its interface in `App\Processes\Payloads\Contracts`
Add properties and methods according to your workflow:
```php
<?php

namespace App\Processes\Payloads\Contracts;

use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

interface TenantPayload
{
    public function getCart(): Cart;
  
    public function setOrder(Order $order): void;

    public function getOrder(): Order|null;
  
    public function setPayment(Payment $payment): void;

    public function getPayment(): Payment|null;
  
    public function setInvoice(Invoice $invoice): void;

    public function getInvoice(): Invoice|null
}

---------------------------

namespace App\Processes\Payloads;

use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

final class CreateOrderPayload implements Payload, OrderPayload
{
  public function __construct(
    protected Cart $cart,
    protected ?Order $order = null,
    protected ?Payment $payment = null,
    protected ?Invoice $invoice = null,
  )
  {}
  
  public function getCart(): Cart
  {
    return $this->cart;
  }
  
  public function setOrder(Order $order): void
  {
    $this->order = $order;
  }

  public function getOrder(): Order|null
  {
    return $this->order;
  }
  
  public function setPayment(Payment $payment): void
  {
    $this->payment = $payment;
  }

  public function getPayment(): Payment|null
  {
    return $this->payment;
  }
  
  public function setInvoice(Invoice $invoice): void
  {
    $this->invoice = $invoice;
  }

  public function getInvoice(): Invoice|null
  {
    return $this->invoice;
  }
}
```

For reusability, methods can be shared by payloads by placing them in traits:
```php
<?php

namespace App\Processes\Payloads\Concerns;

use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

trait OrderPayloadMethods
{
    public function getCart(): Cart
  {
    return $this->cart;
  }
  
  public function setOrder(Order $order): void
  {
    $this->order = $order;
  }

  public function getOrder(): Order|null
  {
    return $this->order;
  }
  (...)
  
  ---------------------------

namespace App\Processes\Payloads;

use App\Processes\Payloads\Concerns\OrderPayloadMethods;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

final class CreateOrderPayload implements Payload, OrderPayload
{
    use OrderPayloadMethods;
    
    public function __construct(
    protected Cart $cart,
    protected ?Order $order = null,
    protected ?Payment $payment = null,
    protected ?Invoice $invoice = null,
  )
  {}
}

  ---------------------------

namespace App\Processes\Payloads;

use App\Processes\Payloads\Concerns\OrderPayloadMethods;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

final class RebillOrderPayload implements Payload, OrderPayload
{
    use OrderPayloadMethods;
    
    public function __construct(
    protected Order $order,
    protected ?Payment $payment = null,
    protected ?Invoice $invoice = null,
  )
  {}
}
```

**3. Create tasks**

Generate your tasks with this command:
```bash
php artisan make:piped-process MakePaymentTask
```

Name your process like this : **\<Action>\<Domain>Task**

For convenience and reusability, tasks use actions (from Spatie's [Laravel Queuable Action](https://github.com/spatie/laravel-queueable-action)):
```php
<?php

namespace App\Processes\Tasks;

use App\Actions\MakePaymentAction;
use App\Processes\Payloads\Contracts\OrderPayload;
use IBroStudio\User\Actions\CreateUserAction;
use IBroStudio\User\Processes\Payloads\Contracts\UserPayload;
use Closure;

final readonly class MakePaymentTask
{
    public function __construct(
        private MakePaymentAction $action,
    ) {}

    public function __invoke(OrderPayload $payload, Closure $next): mixed
    {
        $payload->setPayment(
            $this->action->execute($payload->getOrder())
        );

        return $next($payload);
    }
}

---------------------------

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use Spatie\QueueableAction\QueueableAction;

final class MakePaymentAction
{
    use QueueableAction;

    public function execute(Order $order): Payment
    {
        $payment = 'Process payment and return model';
        
        return $payment;
    }
}
```

**4. Add tasks to the process**

Under the hood, process uses Michael Rubel's [Laravel Enhanced Pipeline](https://github.com/michael-rubel/laravel-enhanced-pipeline) and supports all features from it like DB transaction or events:

```php
<?php

namespace App\Processes;

use App\Processes\Payloads\CreateOrderPayload;use App\Processes\Tasks\CreateOrderTask;use App\Processes\Tasks\GenerateInvoiceTask;use App\Processes\Tasks\MakePaymentTask;use App\Processes\Tasks\NewOrderNotificationTask;use App\Processes\Tasks\SendInvoiceToCustomerTask;use Closure;use IBroStudio\PipedTasks\Contracts\Payload;use IBroStudio\PipedTasks\Process;

class CreateOrderProcess extends Process
{
    protected array $tasks = [
        CreateOrderTask::class,
        MakePaymentTask::class,
        GenerateInvoiceTask::class,
        SendInvoiceToCustomerTask::class,
        NewOrderNotificationTask::class,
    ];

    protected bool $withTransaction = true;

    public function onSuccess(): static
    {
        $this->onSuccess = function (CreateOrderPayload|Payload $payload) {
            //

            return $payload;
        };

        return $this;
    }

    public function onFailure(): static
    {
        $this->onFailure = function (CreateOrderPayload|Payload $payload, $exception) {
            //

            return $payload;
        };

        return $this;
    }

    public function __invoke(Payload $payload, Closure $next): mixed
    {
        $this->run($payload);

        return $next($payload);
    }
}
```


**5. Execute process**
```php
<?php

use App\Processes\CreateOrderProcess;
use App\Processes\Payloads\CreateOrderPayload;

$process = (new CreateOrderProcess)
    ->run(
        new CreateOrderPayload(
            cart: 'your cart model'
        )
    );
    
$process->getOrder();
```
or in a simplier way:
```php
<?php

use App\Processes\CreateOrderProcess;

$process = CreateOrderProcess::handleWith(['your cart model']);
    
$process->getOrder();
```

## Append / prepend tasks

Via the tasks array in the config file piped-tasks.php, it is possible to add tasks to a process. It allows you to dynamically modify a process using to the Config::set() method:

First, publish the config file:
```bash
php artisan vendor:publish --tag=piped-tasks
```
Add your process class and append or prepend your(s) task(s) class(es):
```php
<?php

declare(strict_types=1);

return [

    'tasks' => [

        Process::class => [
            'prepend' => [
                FirstTask::class,
                SecondTask::class,
            ],
            'append' => [
                LastTask::class,
            ],
        ]
    ],

];
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
