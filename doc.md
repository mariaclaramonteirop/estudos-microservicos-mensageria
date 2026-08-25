
* **PHP 8.3**
* **Slim 4**
* **MySQL 8**
* **RabbitMQ**
* **Docker / Docker Compose**
* MVC
* Service Layer
* Repository Pattern
* 3 entidades: `Customer`, `Product`, `Order`
* 2 Workers
* RabbitMQ com Exchange + Queues
* REST API

---

# 🐰 Mini E-commerce — PHP + RabbitMQ

## 1. Arquitetura

```text
                         ┌─────────────────┐
                         │     Cliente     │
                         │ Postman / HTTP  │
                         └────────┬────────┘
                                  │
                                  │ HTTP
                                  ▼
                     ┌──────────────────────┐
                     │       PHP API        │
                     │       Slim 4         │
                     │                      │
                     │ Controller           │
                     │ Service              │
                     │ Repository           │
                     └───────┬───────┬──────┘
                             │       │
                             │       │ evento
                             ▼       ▼
                         ┌──────┐ ┌─────────────┐
                         │MySQL │ │  RabbitMQ   │
                         └──────┘ │             │
                                  │   Exchange  │
                                  └──────┬──────┘
                                         │
                              ┌──────────┴──────────┐
                              │                     │
                              ▼                     ▼
                     ┌────────────────┐    ┌────────────────┐
                     │ Notification   │    │   Inventory    │
                     │    Worker      │    │     Worker     │
                     └────────────────┘    └────────────────┘
```

---

# 2. Estrutura do projeto

```text
mini-ecommerce/
│
├── docker-compose.yml
│
├── database/
│   └── init.sql
│
├── api/
│   │
│   ├── Dockerfile
│   ├── composer.json
│   ├── .env
│   │
│   ├── public/
│   │   └── index.php
│   │
│   └── src/
│       │
│       ├── Controller/
│       │   ├── CustomerController.php
│       │   ├── ProductController.php
│       │   └── OrderController.php
│       │
│       ├── Entity/
│       │   ├── Customer.php
│       │   ├── Product.php
│       │   └── Order.php
│       │
│       ├── Repository/
│       │   ├── CustomerRepository.php
│       │   ├── ProductRepository.php
│       │   └── OrderRepository.php
│       │
│       ├── Service/
│       │   ├── CustomerService.php
│       │   ├── ProductService.php
│       │   └── OrderService.php
│       │
│       ├── Database/
│       │   └── Connection.php
│       │
│       └── RabbitMQ/
│           ├── RabbitMQConnection.php
│           └── EventPublisher.php
│
└── workers/
    │
    ├── Dockerfile
    ├── composer.json
    ├── .env
    │
    ├── NotificationWorker.php
    └── InventoryWorker.php
```

---

# 3. Modelagem

Temos três entidades.

```text
┌────────────────────┐
│      Customer      │
├────────────────────┤
│ id                 │
│ name               │
│ email              │
│ created_at         │
│ updated_at         │
└─────────┬──────────┘
          │
          │ 1:N
          │
          ▼
┌────────────────────┐
│       Order        │
├────────────────────┤
│ id                 │
│ customer_id        │
│ product_id         │
│ quantity           │
│ unit_price         │
│ total              │
│ status             │
│ created_at         │
│ updated_at         │
└─────────┬──────────┘
          │
          │ N:1
          │
          ▼
┌────────────────────┐
│      Product       │
├────────────────────┤
│ id                 │
│ name               │
│ price              │
│ stock              │
│ created_at         │
│ updated_at         │
└────────────────────┘
```

---

# 4. Banco de dados

`database/init.sql`

```sql
CREATE DATABASE IF NOT EXISTS ecommerce;

USE ecommerce;

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'created',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id),

    CONSTRAINT fk_orders_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
);
```

---

# 5. Docker Compose

Na raiz:

`docker-compose.yml`

```yaml
services:

  api:
    build:
      context: ./api
    container_name: ecommerce-api
    ports:
      - "8080:8080"
    volumes:
      - ./api:/var/www/html
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: ecommerce
      DB_USERNAME: ecommerce
      DB_PASSWORD: ecommerce

      RABBITMQ_HOST: rabbitmq
      RABBITMQ_PORT: 5672
      RABBITMQ_USER: guest
      RABBITMQ_PASSWORD: guest

    depends_on:
      - mysql
      - rabbitmq

  notification-worker:
    build:
      context: ./workers
    container_name: notification-worker
    command: php NotificationWorker.php
    volumes:
      - ./workers:/var/www/html
    environment:
      RABBITMQ_HOST: rabbitmq
      RABBITMQ_PORT: 5672
      RABBITMQ_USER: guest
      RABBITMQ_PASSWORD: guest
    depends_on:
      - rabbitmq

  inventory-worker:
    build:
      context: ./workers
    container_name: inventory-worker
    command: php InventoryWorker.php
    volumes:
      - ./workers:/var/www/html
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: ecommerce
      DB_USERNAME: ecommerce
      DB_PASSWORD: ecommerce

      RABBITMQ_HOST: rabbitmq
      RABBITMQ_PORT: 5672
      RABBITMQ_USER: guest
      RABBITMQ_PASSWORD: guest

    depends_on:
      - mysql
      - rabbitmq

  mysql:
    image: mysql:8.0
    container_name: ecommerce-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ecommerce
      MYSQL_USER: ecommerce
      MYSQL_PASSWORD: ecommerce
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql

  rabbitmq:
    image: rabbitmq:3-management
    container_name: ecommerce-rabbitmq
    restart: unless-stopped
    ports:
      - "5672:5672"
      - "15672:15672"

volumes:
  mysql_data:
```

---

# 6. Dockerfile da API

`api/Dockerfile`

```dockerfile
FROM php:8.3-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json .

RUN composer install --no-interaction

COPY . .

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
```

---

# 7. Composer da API

`api/composer.json`

```json
{
    "require": {
        "php": "^8.3",
        "slim/slim": "^4.15",
        "slim/psr7": "^1.7",
        "php-amqplib/php-amqplib": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

---

# 8. Conexão MySQL

`api/src/Database/Connection.php`

```php
<?php

namespace App\Database;

use PDO;

class Connection
{
    public static function get(): PDO
    {
        return new PDO(
            'mysql:host=' . getenv('DB_HOST') .
            ';port=' . getenv('DB_PORT') .
            ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
}
```

---

# 9. Entity Customer

`api/src/Entity/Customer.php`

```php
<?php

namespace App\Entity;

class Customer
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email
    ) {}
}
```

---

# 10. Entity Product

`api/src/Entity/Product.php`

```php
<?php

namespace App\Entity;

class Product
{
    public function __construct(
        public ?int $id,
        public string $name,
        public float $price,
        public int $stock
    ) {}
}
```

---

# 11. Entity Order

`api/src/Entity/Order.php`

```php
<?php

namespace App\Entity;

class Order
{
    public function __construct(
        public ?int $id,
        public int $customerId,
        public int $productId,
        public int $quantity,
        public float $unitPrice,
        public float $total,
        public string $status = 'created'
    ) {}
}
```

---

# 12. Customer Repository

`api/src/Repository/CustomerRepository.php`

```php
<?php

namespace App\Repository;

use App\Database\Connection;
use App\Entity\Customer;
use PDO;

class CustomerRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Connection::get();
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM customers WHERE id = ?'
        );

        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new Customer(
            $data['id'],
            $data['name'],
            $data['email']
        );
    }

    public function create(Customer $customer): Customer
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO customers (name, email)
             VALUES (?, ?)'
        );

        $stmt->execute([
            $customer->name,
            $customer->email
        ]);

        $customer->id = (int) $this->connection->lastInsertId();

        return $customer;
    }
}
```

---

# 13. Product Repository

`api/src/Repository/ProductRepository.php`

```php
<?php

namespace App\Repository;

use App\Database\Connection;
use App\Entity\Product;
use PDO;

class ProductRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Connection::get();
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM products WHERE id = ?'
        );

        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new Product(
            $data['id'],
            $data['name'],
            (float) $data['price'],
            $data['stock']
        );
    }

    public function create(Product $product): Product
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO products (name, price, stock)
             VALUES (?, ?, ?)'
        );

        $stmt->execute([
            $product->name,
            $product->price,
            $product->stock
        ]);

        $product->id = (int) $this->connection->lastInsertId();

        return $product;
    }

    public function decreaseStock(
        int $productId,
        int $quantity
    ): bool {

        $stmt = $this->connection->prepare(
            'UPDATE products
             SET stock = stock - ?
             WHERE id = ?
             AND stock >= ?'
        );

        $stmt->execute([
            $quantity,
            $productId,
            $quantity
        ]);

        return $stmt->rowCount() > 0;
    }
}
```

---

# 14. Order Repository

`api/src/Repository/OrderRepository.php`

```php
<?php

namespace App\Repository;

use App\Database\Connection;
use App\Entity\Order;
use PDO;

class OrderRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Connection::get();
    }

    public function create(Order $order): Order
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO orders
            (
                customer_id,
                product_id,
                quantity,
                unit_price,
                total,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $order->customerId,
            $order->productId,
            $order->quantity,
            $order->unitPrice,
            $order->total,
            $order->status
        ]);

        $order->id = (int) $this->connection->lastInsertId();

        return $order;
    }
}
```

---

# 15. RabbitMQ Connection

`api/src/RabbitMQ/RabbitMQConnection.php`

```php
<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConnection
{
    public static function create(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD')
        );
    }
}
```

---

# 16. Event Publisher

`api/src/RabbitMQ/EventPublisher.php`

```php
<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class EventPublisher
{
    public function publish(
        string $event,
        array $data
    ): void {

        $connection = RabbitMQConnection::create();

        $channel = $connection->channel();

        $exchange = 'orders.exchange';

        $channel->exchange_declare(
            $exchange,
            'topic',
            false,
            true,
            false
        );

        $message = new AMQPMessage(
            json_encode([
                'event' => $event,
                'data' => $data,
                'occurred_at' => date('c')
            ]),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        $channel->basic_publish(
            $message,
            $exchange,
            $event
        );

        $channel->close();
        $connection->close();
    }
}
```

---

# 17. Customer Service

`api/src/Service/CustomerService.php`

```php
<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;

class CustomerService
{
    public function __construct(
        private CustomerRepository $repository
    ) {}

    public function create(
        string $name,
        string $email
    ): Customer {

        if (empty($name)) {
            throw new \InvalidArgumentException(
                'Name is required'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'Invalid email'
            );
        }

        return $this->repository->create(
            new Customer(
                null,
                $name,
                $email
            )
        );
    }
}
```

---

# 18. Product Service

`api/src/Service/ProductService.php`

```php
<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;

class ProductService
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function create(
        string $name,
        float $price,
        int $stock
    ): Product {

        if ($price <= 0) {
            throw new \InvalidArgumentException(
                'Price must be greater than zero'
            );
        }

        if ($stock < 0) {
            throw new \InvalidArgumentException(
                'Stock cannot be negative'
            );
        }

        return $this->repository->create(
            new Product(
                null,
                $name,
                $price,
                $stock
            )
        );
    }
}
```

---

# 19. Order Service

Aqui está a parte mais importante.

`api/src/Service/OrderService.php`

```php
<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\RabbitMQ\EventPublisher;

class OrderService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function create(
        int $customerId,
        int $productId,
        int $quantity
    ): Order {

        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                'Quantity must be greater than zero'
            );
        }

        $customer = $this->customerRepository
            ->findById($customerId);

        if (!$customer) {
            throw new \RuntimeException(
                'Customer not found'
            );
        }

        $product = $this->productRepository
            ->findById($productId);

        if (!$product) {
            throw new \RuntimeException(
                'Product not found'
            );
        }

        if ($product->stock < $quantity) {
            throw new \RuntimeException(
                'Insufficient stock'
            );
        }

        $total = $product->price * $quantity;

        $order = new Order(
            null,
            $customerId,
            $productId,
            $quantity,
            $product->price,
            $total
        );

        $order = $this->orderRepository->create($order);

        $this->eventPublisher->publish(
            'order.created',
            [
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'total' => $total
            ]
        );

        return $order;
    }
}
```

---

# 20. Controller de Customer

`api/src/Controller/CustomerController.php`

```php
<?php

namespace App\Controller;

use App\Service\CustomerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CustomerController
{
    public function __construct(
        private CustomerService $service
    ) {}

    public function create(
        Request $request,
        Response $response
    ): Response {

        $data = $request->getParsedBody();

        try {

            $customer = $this->service->create(
                $data['name'] ?? '',
                $data['email'] ?? ''
            );

            $response->getBody()->write(
                json_encode([
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\Throwable $e) {

            $response->getBody()->write(
                json_encode([
                    'error' => $e->getMessage()
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
```

---

# 21. Controller de Product

`api/src/Controller/ProductController.php`

```php
<?php

namespace App\Controller;

use App\Service\ProductService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    public function __construct(
        private ProductService $service
    ) {}

    public function create(
        Request $request,
        Response $response
    ): Response {

        $data = $request->getParsedBody();

        try {

            $product = $this->service->create(
                $data['name'] ?? '',
                (float) ($data['price'] ?? 0),
                (int) ($data['stock'] ?? 0)
            );

            $response->getBody()->write(
                json_encode([
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\Throwable $e) {

            $response->getBody()->write(
                json_encode([
                    'error' => $e->getMessage()
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
```

---

# 22. Controller de Order

`api/src/Controller/OrderController.php`

```php
<?php

namespace App\Controller;

use App\Service\OrderService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController
{
    public function __construct(
        private OrderService $service
    ) {}

    public function create(
        Request $request,
        Response $response
    ): Response {

        $data = $request->getParsedBody();

        try {

            $order = $this->service->create(
                (int) $data['customer_id'],
                (int) $data['product_id'],
                (int) $data['quantity']
            );

            $response->getBody()->write(
                json_encode([
                    'id' => $order->id,
                    'customer_id' => $order->customerId,
                    'product_id' => $order->productId,
                    'quantity' => $order->quantity,
                    'unit_price' => $order->unitPrice,
                    'total' => $order->total,
                    'status' => $order->status
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\Throwable $e) {

            $response->getBody()->write(
                json_encode([
                    'error' => $e->getMessage()
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
```

---

# 23. Rotas

`api/routes/routes.php`

```php
<?php

use App\Controller\CustomerController;
use App\Controller\OrderController;
use App\Controller\ProductController;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\RabbitMQ\EventPublisher;
use App\Service\CustomerService;
use App\Service\OrderService;
use App\Service\ProductService;

$customerRepository = new CustomerRepository();
$productRepository = new ProductRepository();
$orderRepository = new OrderRepository();

$eventPublisher = new EventPublisher();

$customerService = new CustomerService(
    $customerRepository
);

$productService = new ProductService(
    $productRepository
);

$orderService = new OrderService(
    $customerRepository,
    $productRepository,
    $orderRepository,
    $eventPublisher
);

$customerController = new CustomerController(
    $customerService
);

$productController = new ProductController(
    $productService
);

$orderController = new OrderController(
    $orderService
);

$app->post(
    '/customers',
    [$customerController, 'create']
);

$app->post(
    '/products',
    [$productController, 'create']
);

$app->post(
    '/orders',
    [$orderController, 'create']
);
```

---

# 24. `index.php`

`api/public/index.php`

```php
<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

(require __DIR__ . '/../routes/routes.php')($app);

$app->run();
```

---

# 25. Workers

Agora começa a parte realmente interessante.

Os workers também precisam do RabbitMQ.

`workers/composer.json`

```json
{
    "require": {
        "php": "^8.3",
        "php-amqplib/php-amqplib": "^3.7"
    }
}
```

---

# 26. Dockerfile dos Workers

`workers/Dockerfile`

```dockerfile
FROM php:8.3-cli

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json .

RUN composer install --no-interaction

COPY . .
```

---

# 27. Notification Worker

`workers/NotificationWorker.php`

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_USER'),
    getenv('RABBITMQ_PASSWORD')
);

$channel = $connection->channel();

$exchange = 'orders.exchange';

$queue = 'order.notification';

$channel->exchange_declare(
    $exchange,
    'topic',
    false,
    true,
    false
);

$channel->queue_declare(
    $queue,
    false,
    true,
    false,
    false
);

$channel->queue_bind(
    $queue,
    $exchange,
    'order.created'
);

echo "Notification Worker started...\n";

$callback = function ($message) {

    $payload = json_decode(
        $message->body,
        true
    );

    echo "\n";
    echo "============================\n";
    echo "Notification Worker\n";
    echo "============================\n";

    echo "Event: {$payload['event']}\n";

    echo "Order ID: "
        . $payload['data']['order_id']
        . "\n";

    echo "Sending notification...\n";

    sleep(1);

    echo "Notification sent!\n";

    $message->ack();
};

$channel->basic_qos(
    null,
    1,
    null
);

$channel->basic_consume(
    $queue,
    '',
    false,
    false,
    false,
    false,
    $callback
);

while ($channel->is_consuming()) {
    $channel->wait();
}
```

---

# 28. Inventory Worker

`workers/InventoryWorker.php`

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_USER'),
    getenv('RABBITMQ_PASSWORD')
);

$channel = $connection->channel();

$exchange = 'orders.exchange';

$queue = 'order.inventory';

$channel->exchange_declare(
    $exchange,
    'topic',
    false,
    true,
    false
);

$channel->queue_declare(
    $queue,
    false,
    true,
    false,
    false
);

$channel->queue_bind(
    $queue,
    $exchange,
    'order.created'
);

echo "Inventory Worker started...\n";

$callback = function ($message) {

    $payload = json_decode(
        $message->body,
        true
    );

    echo "\n";
    echo "============================\n";
    echo "Inventory Worker\n";
    echo "============================\n";

    echo "Order ID: "
        . $payload['data']['order_id']
        . "\n";

    echo "Product ID: "
        . $payload['data']['product_id']
        . "\n";

    echo "Quantity: "
        . $payload['data']['quantity']
        . "\n";

    echo "Updating inventory...\n";

    sleep(1);

    echo "Inventory updated!\n";

    $message->ack();
};

$channel->basic_qos(
    null,
    1,
    null
);

$channel->basic_consume(
    $queue,
    '',
    false,
    false,
    false,
    false,
    $callback
);

while ($channel->is_consuming()) {
    $channel->wait();
}
```

---

# 29. Subindo o projeto

Na raiz:

```bash
docker compose build
```

Depois:

```bash
docker compose up
```

Ou:

```bash
docker compose up --build
```

Você deverá ter:

```text
ecommerce-api
ecommerce-mysql
ecommerce-rabbitmq
notification-worker
inventory-worker
```

---

# 30. RabbitMQ Management

Abra:

```text
http://localhost:15672
```

Login padrão:

```text
guest
```

Senha:

```text
guest
```

Você poderá visualizar:

```text
Exchanges
Queues
Connections
Channels
Consumers
Messages
```

Isso é muito bom para estudar porque você consegue **ver fisicamente as mensagens passando pelo sistema**.

---

# 31. Testando

Primeiro crie um Customer.

```http
POST http://localhost:8080/customers
```

```json
{
    "name": "Maria",
    "email": "maria@email.com"
}
```

Depois Product:

```http
POST http://localhost:8080/products
```

```json
{
    "name": "Notebook",
    "price": 3000,
    "stock": 10
}
```

Agora Order:

```http
POST http://localhost:8080/orders
```

```json
{
    "customer_id": 1,
    "product_id": 1,
    "quantity": 2
}
```

Resposta:

```json
{
    "id": 1,
    "customer_id": 1,
    "product_id": 1,
    "quantity": 2,
    "unit_price": 3000,
    "total": 6000,
    "status": "created"
}
```

E no terminal:

```text
Notification Worker

Event: order.created
Order ID: 1
Sending notification...
Notification sent!
```

Enquanto no outro:

```text
Inventory Worker

Order ID: 1
Product ID: 1
Quantity: 2
Updating inventory...
Inventory updated!
```

---

# 32. O que acabou de acontecer?

Uma única requisição:

```text
POST /orders
```

gerou:

```text
                OrderService
                     │
                     ▼
                  MySQL
                     │
                     ▼
               order.created
                     │
                     ▼
                 RabbitMQ
                /        \
               ▼          ▼
        Notification   Inventory
           Worker        Worker
```

Isso é **processamento assíncrono**.

A API não precisa ficar esperando:

```text
"enviei o e-mail"
"atualizei estoque"
"enviei outra notificação"
```

Ela cria o pedido e publica o evento.

---

# 33. Uma melhoria importante

Para fins didáticos, o projeto acima está funcional, mas eu **não pararia aqui**.

A próxima evolução seria:

```text
FASE 1
MVC + MySQL
       ↓
FASE 2
Repository + Service
       ↓
FASE 3
RabbitMQ
       ↓
FASE 4
Exchange + Routing Keys
       ↓
FASE 5
Workers
       ↓
FASE 6
ACK
       ↓
FASE 7
Retry
       ↓
FASE 8
Dead Letter Queue
       ↓
FASE 9
Docker
       ↓
FASE 10
Observabilidade
```

E aí teríamos uma arquitetura bem mais próxima de produção:

```text
                           ┌───────────────┐
                           │    Client     │
                           └───────┬───────┘
                                   │
                                   ▼
                           ┌───────────────┐
                           │   API / PHP   │
                           └───────┬───────┘
                                   │
                         ┌─────────┴─────────┐
                         ▼                   ▼
                      MySQL             RabbitMQ
                                           │
                                  ┌────────┼────────┐
                                  ▼        ▼        ▼
                              Notification Inventory  ...
                                  │        │
                                  ▼        ▼
                                ACK      ACK
                                  │        │
                                  └────┬───┘
                                       │
                                  erro/retry
                                       │
                                       ▼
                                  Dead Letter
                                      Queue
```

