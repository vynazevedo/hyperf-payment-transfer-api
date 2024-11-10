<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;
use Hyperf\Database\Model\Builder;

class UserRepository
{
    public function __construct(private readonly User $model)
    {
    }

    /**
     * Encontra um usuário pelo ID.
     */
    public function findOrFail(int $id): User
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Cria um novo usuário.
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * Debita um valor do saldo do usuário.
     */
    public function debit(int $userId, float $value): bool
    {
        $user = $this->findOrFail($userId);
        $user->balance -= $value;

        return $user->save();
    }

    /**
     * Credita um valor no saldo do usuário.
     */
    public function credit(int $userId, float $value): bool
    {
        $user = $this->findOrFail($userId);
        $user->balance += $value;
        return $user->save();
    }

    /**
     * Verifica se o email já existe.
     */
    public function emailExists(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    /**
     * Verifica se o CPF já existe.
     */
    public function cpfExists(string $cpf): bool
    {
        return $this->model->where('cpf', $cpf)->exists();
    }

    /**
     * Busca usuário por email.
     */
    public function findByEmail(string $email): Builder
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Busca usuário por CPF.
     */
    public function findByCpf(string $cpf): Builder
    {
        return $this->model->where('cpf', $cpf)->first();
    }

    /**
     * Lista usuários com paginação.
     */
    public function paginate(int $perPage = 15): object
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Verifica se usuário tem saldo suficiente.
     */
    public function hasEnoughBalance(int $userId, float $value): bool
    {
        $user = $this->findOrFail($userId);
        return (float) $user->balance >= $value;
    }

    /**
     * Atualiza um usuário.
     */
    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);
        $user->update($data);
        return $user;
    }
}
