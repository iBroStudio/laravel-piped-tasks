# Laravel Piped Tasks

Manage tasks workflows through Laravel Pipes.

## Concept

A **process** defines the order of **tasks** executed through a pipe.

Each process is associated with a **payload**. Payload is a mutable object passed to each task to retrieve, add, or update data.

Each steps of a process can be loggued through [Spatie laravel-activitylog](https://github.com/spatie/laravel-activitylog).

You can use external tasks inside a process with the **Resumable** feature.

## Installation

Install the package via composer:

```bash
composer require ibrostudio/laravel-piped-tasks
```

Then create the tables:

```bash
php artisan piped-tasks:install
```

## Usage

**1. Create process**

First you need to generate a process:
```bash
php artisan make:piped-process CreateOrderProcess
```

Name your process like this : **\<Action>\<Domain>Process**

***Note:*** It is possible to generate processes and tasks for a package.

**2. Define Payload**

You'll find the associated payload to your process in `App\Processes\Payloads` and its interface in `App\Processes\Payloads\Contracts`.

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
use IBroStudio\PipedTasks\PayloadAbstract;

final class CreateOrderPayload extends PayloadAbstract implements OrderPayload
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
use IBroStudio\PipedTasks\PayloadAbstract;

final class CreateOrderPayload extends PayloadAbstract implements OrderPayload
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
use IBroStudio\PipedTasks\PayloadAbstract;

final class RebillOrderPayload extends PayloadAbstract implements OrderPayload
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
php artisan make:piped-task MakePaymentTask
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

use App\Processes\Tasks;
use IBroStudio\PipedTasks\Models\Process;

class CreateOrderProcess extends Process
{
    protected array $tasks = [
        Tasks\CreateOrderTask::class,
        Tasks\MakePaymentTask::class,
        Tasks\GenerateInvoiceTask::class,
        Tasks\SendInvoiceToCustomerTask::class,
        Tasks\NewOrderNotificationTask::class,
    ];
}
```


**5. Execute process**
```php
<?php

use App\Processes\CreateOrderProcess;
use App\Processes\Payloads\CreateOrderPayload;

$process = CreateOrderProcess::process(['cart' => $cart]);
    
$process->getOrder();
```
Argument passed to the `process` static method is an array used to build the Payload.


## Processable models

You can link processes to any Eloquent model implementing the `Processable` interface and using the `IsProcessable` trait:

```php
<?php

namespace App\Models;

use IBroStudio\PipedTasks\Concerns\IsProcessable;
use IBroStudio\PipedTasks\Contracts\Processable;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model implements Processable
{
    use IsProcessable;
}
```

It allows to call process from the model:

```php
<?php

$cart->process(CreateOrderProcess::class);
```

And permits to access to the model in tasks:

```php
<?php

namespace App\Processes\Tasks;

use App\Processes\Payloads\Contracts\OrderPayload;
use Closure;

class CreateOrderTask
{
    public function __invoke(OrderPayload $payload, Closure $next): mixed
    {
        $cart = $payload->getProcess()->processable;

        return $next($payload);
    }
}
```

### Adding processable during process

If the processable model is created during a process, you can assign it to the process in a task:

```php
<?php

use App\Models\Order;
use App\Processes\CreateOrderProcess;

Order::callProcess(CreateOrderProcess::class)
```

```php
<?php

namespace App\Processes\Tasks;

use App\Models\Order;
use App\Processes\Payloads\Contracts\OrderPayload;
use Closure;

class CreateOrderTask
{
    public function __invoke(OrderPayload $payload, Closure $next): mixed
    {
        $payload->getProcess()->addProcessable(
            Order::create([...]);
        );

        return $next($payload);
    }
}
```

## Pause and resume processes

Sometimes an external task needs to be performed to complete a process. You can include it in your workflow by using `PauseProcess` and the `resumeUrl`:

1. Define a task in your process where you want to make your external call :

```php
<?php

namespace App\Processes\Tasks;

use App\Models\Order;use App\Processes\Payloads\Contracts\MyProcessPayload;use Closure;use IBroStudio\PipedTasks\Exceptions\PauseProcessException;

class CallExternalTask
{
    public function __invoke(MyProcessPayload $payload, Closure $next): mixed
    {
        // Here call your external service allowing to include a webhook url  
        // Webhook url to use to resume the process can be retrieved with $payload->getProcess()->resumeUrl()

        return new PauseProcessException;
    }
}
```

The `$process->resumeUrl()` method returns a **Laravel signed url**.


## Process within process

A process can be added as a task if it shares the same payload base.

## Process logs

To enable process logs, set `log_processes` key to `true` in config/piped-tasks.php.

[Spatie laravel-activitylog](https://github.com/spatie/laravel-activitylog) methods are available to retrieve logs:

```php
<?php

use Spatie\Activitylog\Models\Activity;

$log = Activity::all()->last();

$logs = Activity::inLog('process-name')->get();
```

By default, the Process name is used to name the log but you can customize it by adding `$logName` property to the Process:

```php
<?php

namespace App\Processes;

use App\Processes\Tasks;
use IBroStudio\PipedTasks\Models\Process;

class CreateOrderProcess extends Process
{
    public static ?string $logName = 'orders';
}
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
