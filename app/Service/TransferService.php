<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InsufficientBalanceException;
use App\Exception\UnauthorizedTransferException;
use App\Repository\TransferRepository;
use App\Repository\UserRepository;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class TransferService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransferRepository $transferRepository,
        private readonly Client $client,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('transfer');
    }

    /**
     * @throws GuzzleException
     * @throws InsufficientBalanceException
     * @throws UnauthorizedTransferException
     */
    public function execute(float $value, int $payerId, int $payeeId): array
    {
        $this->logger->info('Iniciando transferência', [
            'payer' => $payerId,
            'payee' => $payeeId,
            'value' => $value,
        ]);

        $payer = $this->userRepository->findOrFail($payerId);
        $payee = $this->userRepository->findOrFail($payeeId);

        if ($payer->type === 'merchant') {
            $this->logger->warning('Tentativa de transferência por lojista', ['payer_id' => $payerId]);
            throw new UnauthorizedTransferException('Lojistas não podem enviar dinheiro');
        }

        if (! $payer->hasEnoughBalance($value)) {
            $this->logger->warning('Saldo insuficiente', [
                'payer_id' => $payerId,
                'balance' => $payer->balance,
                'value' => $value,
            ]);
            throw new InsufficientBalanceException();
        }

        if (! $this->authorizeTransfer()) {
            $this->logger->error('Transferência não autorizada pelo serviço externo');
            throw new UnauthorizedTransferException('Transferência não autorizada');
        }

        Db::beginTransaction();
        try {
            $transfer = $this->transferRepository->create([
                'value' => $value,
                'payer' => $payerId,
                'payee' => $payeeId,
                'status' => 'processing',
            ]);

            $this->userRepository->debit($payerId, $value);
            $this->userRepository->credit($payeeId, $value);

            $transfer = $this->transferRepository->update($transfer->id, ['status' => 'completed']);

            Db::commit();

            $this->notifyUser($transfer);

            $this->logger->info('Transferência realizada com sucesso', [
                'transfer_id' => $transfer->id,
            ]);

            return [
                'transfer_id' => $transfer->id,
                'value' => $value,
                'payer' => $payerId,
                'payee' => $payeeId,
            ];
        } catch (Exception $e) {
            Db::rollBack();
            $this->logger->error('Erro na transferência', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @throws GuzzleException
     */
    private function authorizeTransfer(): bool
    {
        try {
            $response = $this->client->get('https://util.devi.tools/api/v2/authorize');
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            $this->logger->error('Erro ao consultar serviço autorizador', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @throws GuzzleException
     */
    private function notifyUser(object $transfer): void
    {
        try {
            $this->client->post('https://util.devi.tools/api/v1/notify', [
                'json' => [
                    'transfer_id' => $transfer->id,
                ],
            ]);
        } catch (Exception $e) {
            $this->logger->error('Erro ao notificar usuário', [
                'error' => $e->getMessage(),
                'transfer_id' => $transfer->id,
            ]);
        }
    }
}
