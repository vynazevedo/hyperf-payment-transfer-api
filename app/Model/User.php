<?php

declare(strict_types=1);

namespace App\Model;

use App\Constants\UserType;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected ?string $table = 'users';

    public bool $timestamps = true;

    protected array $fillable = [
        'name',
        'email',
        'cpf',
        'password',
        'type',
        'balance',
    ];

    protected array $hidden = [
        'password',
    ];

    protected array $casts = [
        'id' => 'integer',
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Verifica se é usuário comum
     */
    public function isCommon(): bool
    {
        return $this->type === UserType::COMMON;
    }

    /**
     * Verifica se é lojista
     */
    public function isMerchant(): bool
    {
        return $this->type === UserType::MERCHANT;
    }

    /**
     * Verifica se tem saldo suficiente
     */
    public function hasEnoughBalance(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }
}
