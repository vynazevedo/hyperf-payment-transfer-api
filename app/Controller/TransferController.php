<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Transfer;
use App\Model\User;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Log\LoggerInterface;

#[Controller(prefix: '/transfer')]
class TransferController
{
    #[Inject]
    protected User $user;

    #[Inject]
    protected Transfer $transfer;

    #[Inject]
    protected Client $client;

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('transfer');
    }

    /**
     * @throws GuzzleException
     */
    #[RequestMapping(path: '', methods: 'POST')]
    public function transfer(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = $request->all();

        if (empty($data['value']) || ! is_numeric($data['value']) || $data['value'] <= 0) {
            return $response->json([
                'status' => 'error',
                'message' => 'Valor inválido para transferência',
            ])->withStatus(422);
        }

        if (empty($data['payer']) || ! is_numeric($data['payer'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'Pagador inválido',
            ])->withStatus(422);
        }

        if (empty($data['payee']) || ! is_numeric($data['payee'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'Recebedor inválido',
            ])->withStatus(422);
        }

        try {
            $payer = $this->user->findOrFail($data['payer']);
            $payee = $this->user->findOrFail($data['payee']);

            if ($payer->type === 'merchant') {
                return $response->json([
                    'status' => 'error',
                    'message' => 'Lojistas não podem enviar dinheiro',
                ])->withStatus(422);
            }

            if (! $payer->hasEnoughBalance($data['value'])) {
                return $response->json([
                    'status' => 'error',
                    'message' => 'Saldo insuficiente',
                ])->withStatus(422);
            }

            try {
                $authResponse = $this->client->get('https://util.devi.tools/api/v2/authorize');
                if ($authResponse->getStatusCode() !== 200) {
                    throw new Exception('Transferência não autorizada');
                }
            } catch (Exception $e) {
                return $response->json([
                    'status' => 'error',
                    'message' => 'Serviço de autorização indisponível',
                ])->withStatus(503);
            }

            Db::beginTransaction();
            try {
                $transfer = $this->transfer->create([
                    'value' => $data['value'],
                    'payer' => $payer->id,
                    'payee' => $payee->id,
                    'status' => 'processing',
                ]);

                $payer->balance -= $data['value'];
                $payer->save();

                $payee->balance += $data['value'];
                $payee->save();

                $transfer->status = 'completed';
                $transfer->save();

                Db::commit();

                try {
                    $this->logger->info('Consultando serviço autorizador');
                    $authResponse = $this->client->get('https://util.devi.tools/api/v2/authorize');

                    if ($authResponse->getStatusCode() !== 200) {
                        $this->logger->error('Serviço autorizador negou a transferência', [
                            'status_code' => $authResponse->getStatusCode(),
                        ]);
                        throw new Exception('Transferência não autorizada');
                    }

                    $this->logger->info('Transferência autorizada');
                } catch (Exception $e) {
                    $this->logger->error('Erro no serviço autorizador', [
                        'error' => $e->getMessage(),
                    ]);
                    return $response->json([
                        'status' => 'error',
                        'message' => 'Serviço de autorização indisponível',
                    ])->withStatus(503);
                }

                return $response->json([
                    'status' => 'success',
                    'message' => 'Transferência realizada com sucesso',
                    'data' => [
                        'transfer_id' => $transfer->id,
                        'value' => $data['value'],
                        'payer' => $payer->id,
                        'payee' => $payee->id,
                    ],
                ])->withStatus(201);
            } catch (Exception $e) {
                Db::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Erro ao processar transferência',
                'error' => $e->getMessage(),
            ])->withStatus(500);
        }
    }
}
