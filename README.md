# API de Transferência de Pagamento

Este projeto nasceu de um desafio técnico que encontrei e decidi implementar como parte dos meus estudos em desenvolvimento backend com PHP. O objetivo principal é aprimorar meus conhecimentos em arquiteturas modernas e boas práticas de programação.

## Stack Tecnológica

- PHP 8.1+
- Hyperf 3.1
- MySQL/MariaDB
- Redis
- Swoole
- Docker

## Funcionalidades

- Autenticação segura de usuários
- Controle de acesso baseado em funções (Usuário/Lojista)
- Processamento de transferências em tempo real
- Rollback automático de transações em falhas
- Integração com serviço externo para autorização de transferências
- Sistema de notificação assíncrono
- Rastreamento do histórico de transações
- Validações de segurança integradas
- Suporte a transações de banco de dados
- Arquitetura orientada a eventos

## Requisitos

- Docker e Docker Compose
- PHP >= 8.1
- Extensão PHP Swoole
- Composer
- Git

## Início Rápido

1. Clone o repositório:
```bash
git clone https://github.com/vynazevedo/hyperf-payment-transfer-api.git
cd hyperf-payment-transfer-api
```

2. Copie o arquivo de ambiente:
```bash
cp .env.example .env
```

3. Construa e inicie os containers:
```bash
docker-compose up -d
```

4. Instale as dependências:
```bash
docker-compose exec hyperf composer install
```

5. Execute as migrações:
```bash
docker-compose exec hyperf php bin/hyperf.php migrate
```

6. Inicie o servidor:
```bash
docker-compose exec hyperf php bin/hyperf.php start
```

A API estará disponível em `http://localhost:9501`

## Documentação da API

### Transferir Dinheiro

```http
POST /transfer
Content-Type: application/json

{
    "value": 100.00,
    "payer": 4,
    "payee": 15
}
```

#### Resposta de Sucesso
```json
{
    "message": "Transferência realizada com sucesso",
    "transaction_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

#### Resposta de Erro
```json
{
    "error": "Saldo insuficiente",
    "code": "INSUFFICIENT_BALANCE"
}
```

### Criar Usuário

```http
POST /users
Content-Type: application/json

{
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "cpf": "12345678900",
    "password": "senha_segura",
    "type": "common"
}
```

## Estrutura do Projeto

```
.
├── app/
│   ├── Controller/      # Controladores HTTP
│   ├── Model/          # Modelos de Banco de Dados
│   ├── Service/        # Lógica de Negócio
│   ├── Repository/     # Camada de Acesso a Dados
│   ├── Exception/      # Exceções Personalizadas
│   └── Event/          # Manipuladores de Eventos
├── config/             # Arquivos de Configuração
├── test/              # Testes Unitários/Integração
├── migrations/        # Migrações de Banco de Dados
└── docker/            # Configuração Docker
```

## Testes

Execute a suíte de testes:

```bash
docker-compose exec hyperf composer test
```

## Desenvolvimento

1. Instale as dependências de desenvolvimento:
```bash
docker-compose exec hyperf composer install --dev
```

2. Execute as correções de estilo de código:
```bash
docker-compose exec hyperf composer cs-fix
```

3. Execute a análise estática:
```bash
docker-compose exec hyperf composer analyse
```

## Variáveis de Ambiente

Principais variáveis de ambiente que precisam ser configuradas:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=payment_transfer
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=localhost
REDIS_PORT=6379

AUTHORIZATION_SERVICE_URL=https://util.devi.tools/api/v2/authorize
NOTIFICATION_SERVICE_URL=https://util.devi.tools/api/v1/notify
```

## Monitoramento e Logs

A aplicação usa o sistema de logs integrado do Hyperf. Os logs são escritos em:
- `/runtime/logs/hyperf.log` para logs da aplicação
- `/runtime/logs/sql.log` para queries do banco de dados (em desenvolvimento)

## Considerações de Segurança

- Todas as senhas são criptografadas usando bcrypt
- Transações de banco de dados garantem consistência dos dados
- Validação de entrada em todos os endpoints
- Limitação de taxa em endpoints sensíveis
- Proteção CORS ativada
- Proteção contra injeção SQL via prepared statements
