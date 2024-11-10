<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class Transfer extends Model
{
    /**
     * Nome da tabela
     */
    protected ?string $table = 'transfers';

    /**
     * Campos preenchíveis
     */
    protected array $fillable = [
        'value',
        'payer',
        'payee',
        'status'
    ];

    /**
     * Conversão de tipos
     */
    protected array $casts = [
        'value' => 'decimal:2',
        'payer' => 'integer',
        'payee' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Retorna o usuário pagador
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'payer');
    }

    /**
     * Retorna o usuário recebedor
     */
    public function payee()
    {
        return $this->belongsTo(User::class, 'payee');
    }
}
