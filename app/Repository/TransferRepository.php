<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Transfer;

class TransferRepository
{
    public function __construct(private readonly Transfer $model)
    {
    }

    /**
     * @param array $data
     * @return object
     */
    public function create(array $data): object
    {
        return $this->model->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return object
     */
    public function update(int $id, array $data): object
    {
        $transfer = $this->model->findOrFail($id);
        $transfer->update($data);

        return $transfer;
    }

    /**
     * @param int $id
     * @return object
     */
    public function findOrFail(int $id): object
    {
        return $this->model->findOrFail($id);
    }
}
