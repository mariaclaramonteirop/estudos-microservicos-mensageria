# Estudos de Microsserviços e Mensageria

Projeto desenvolvido para **estudo prático de arquitetura de microsserviços, mensageria e comunicação entre serviços**, utilizando PHP 8, RabbitMQ e Docker.

## Sobre o projeto

A proposta é construir uma pequena API baseada em um cenário de **e-commerce**, utilizando três entidades principais:

* Customer
* Product
* Order

O projeto será utilizado para estudar, na prática:

* Arquitetura MVC
* Service Layer
* Repository Pattern
* DAO Pattern
* SOLID
* Injeção de dependência
* REST API
* Microsserviços
* Mensageria
* RabbitMQ
* Workers
* Docker
* Docker Compose
* Comunicação síncrona e assíncrona

> **Nota:** a primeira versão do projeto será desenvolvida como uma API modular. Posteriormente, os domínios serão separados em microsserviços independentes.

---

## Stack

| Tecnologia     | Utilização                    |
| -------------- | ----------------------------- |
| PHP 8          | Backend                       |
| Slim Framework | API REST                      |
| MySQL          | Persistência                  |
| RabbitMQ       | Mensageria                    |
| Docker         | Containerização               |
| Docker Compose | Orquestração                  |
| Composer       | Gerenciamento de dependências |

---

## Arquitetura inicial

A primeira versão utiliza uma única API organizada em camadas:

```text
                    ┌──────────────┐
                    │    Client    │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │  Controller  │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │   Service    │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Repository   │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │     DAO      │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │    MySQL     │
                    └──────────────┘
```

A mensageria funciona como uma segunda forma de comunicação:

```text
                    ┌──────────────┐
                    │   Service    │
                    └──────┬───────┘
                           │
                           │ Event
                           ▼
                    ┌──────────────┐
                    │  RabbitMQ    │
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    ▼              ▼
             Notification      Inventory
                Worker           Worker
```

---

## Entidades

### Customer

Representa os usuários/clientes da aplicação.

```text
Customer
├── id
├── name
├── email
└── created_at
```

### Product

Representa os produtos disponíveis.

```text
Product
├── id
├── name
├── price
├── stock
└── created_at
```

### Order

Representa os pedidos realizados.

```text
Order
├── id
├── customer_id
├── product_id
├── quantity
├── unit_price
├── total
├── status
└── created_at
```

---

## Repository + DAO

O projeto utiliza duas abstrações diferentes para acesso aos dados.

### Repository

Responsável pela interação da aplicação com as entidades e pelo contrato de persistência.

```text
Repository Interface
        │
        ▼
   Repository
```

### DAO

Responsável pelo acesso direto ao banco de dados.

```text
Repository
     │
     ▼
    DAO
     │
     ▼
   MySQL
```

Fluxo completo:

```text
Controller
    ↓
Service
    ↓
Repository
    ↓
DAO
    ↓
MySQL
```

Essa separação permite trocar a implementação de persistência sem alterar as regras de negócio.

---

## RabbitMQ

O RabbitMQ será utilizado para implementar **comunicação assíncrona baseada em eventos**.

Por exemplo, quando um pedido for criado:

```text
POST /orders
      │
      ▼
OrderService
      │
      ├── salva pedido
      │
      └── publica evento
              │
              ▼
          RabbitMQ
              │
        order.created
          ┌───┴───┐
          ▼       ▼
    Notification  Inventory
       Worker      Worker
```

Dessa forma, a API não precisa executar todas as tarefas relacionadas ao pedido de forma síncrona.

---

## Workers

O projeto possui dois consumidores:

### Notification Worker

Responsável por consumir eventos relacionados a pedidos e realizar o processamento de notificações.

```text
RabbitMQ
    │
    ▼
NotificationWorker
```

### Inventory Worker

Responsável pelo processamento relacionado ao estoque.

```text
RabbitMQ
    │
    ▼
InventoryWorker
```

---

## Docker

Todo o ambiente pode ser executado utilizando Docker.

Serviços previstos:

```text
┌─────────────────────────────┐
│          Docker             │
│                             │
│  ┌─────────┐  ┌──────────┐ │
│  │   API   │  │  MySQL   │ │
│  └─────────┘  └──────────┘ │
│                             │
│  ┌─────────┐                │
│  │RabbitMQ │                │
│  └─────────┘                │
│                             │
│  ┌──────────────┐           │
│  │   Workers    │           │
│  └──────────────┘           │
└─────────────────────────────┘
```

### Subindo o projeto

```bash
docker compose up --build
```

Para executar em segundo plano:

```bash
docker compose up -d --build
```

Para visualizar os containers:

```bash
docker compose ps
```

Para visualizar os logs:

```bash
docker compose logs -f
```

---

## RabbitMQ Management

O RabbitMQ possui uma interface web para gerenciamento.

```text
http://localhost:15672
```

Credenciais utilizadas no ambiente de desenvolvimento:

```text
Usuário: guest
Senha: guest
```

---

## Estrutura do projeto

```text
estudos-microservicos-mensageria/
│
├── api/
│   │
│   ├── public/
│   │   └── index.php
│   │
│   ├── src/
│   │   ├── controller/
│   │   │
│   │   ├── dao/
│   │   │   ├── CustomerDAO.php
│   │   │   ├── OrderDAO.php
│   │   │   └── ProductDAO.php
│   │   │
│   │   ├── entity/
│   │   │   ├── Customer.php
│   │   │   ├── Order.php
│   │   │   └── Product.php
│   │   │
│   │   ├── rabbitMQ/
│   │   ├── repository/
│   │   ├── service/
│   │   └── database/
│   │
│   ├── composer.json
│   └── Dockerfile
│
├── database/
│   └── init.sql
│
├── workers/
│   ├── InventoryWorker.php
│   ├── NotificationWorker.php
│   ├── composer.json
│   └── Dockerfile
│
├── .env
├── .gitignore
├── docker-compose.yml
└── README.md
```

---

## Roadmap de estudos

### Fase 1 — API

* [x] Definir entidades
* [ ] Criar estrutura MVC
* [ ] Criar conexão com MySQL
* [ ] Implementar DAO
* [ ] Implementar Repository
* [ ] Implementar Services
* [ ] Implementar Controllers
* [ ] Criar endpoints REST

### Fase 2 — Docker

* [ ] Criar Dockerfile da API
* [ ] Criar Dockerfile dos Workers
* [ ] Configurar MySQL
* [ ] Configurar variáveis de ambiente
* [ ] Criar Docker Compose

### Fase 3 — RabbitMQ

* [ ] Configurar conexão
* [ ] Criar Exchange
* [ ] Criar Queues
* [ ] Criar Routing Keys
* [ ] Publicar eventos
* [ ] Criar Consumers
* [ ] Implementar ACK

### Fase 4 — Microsserviços

Separar a aplicação em:

```text
services/
├── customer-service/
├── product-service/
└── order-service/
```

Cada serviço terá:

```text
Controller
Service
Repository
DAO
Database
```

e será responsável pelo seu próprio domínio e banco de dados.

### Fase 5 — Comunicação entre serviços

Estudar:

* HTTP entre serviços
* Eventos
* RabbitMQ
* Comunicação síncrona
* Comunicação assíncrona
* Event-driven architecture
* Consistência eventual

### Fase 6 — Resiliência

* [ ] ACK
* [ ] NACK
* [ ] Retry
* [ ] Dead Letter Exchange
* [ ] Dead Letter Queue
* [ ] Idempotência
* [ ] Tratamento de falhas

---

## Objetivo

O objetivo deste projeto não é criar um e-commerce completo, mas **estudar e visualizar na prática como uma aplicação pode evoluir de uma arquitetura monolítica para uma arquitetura baseada em microsserviços e mensageria**.

```text
Monólito
   │
   ▼
MVC + Service + Repository + DAO
   │
   ▼
RabbitMQ
   │
   ▼
Workers
   │
   ▼
Separação por domínio
   │
   ▼
Microsserviços
```

---

## Status

🚧 **Em desenvolvimento**

Projeto criado para fins de **estudo, experimentação e construção de conhecimento em arquitetura de software**.
