<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InsufficientBalanceException;
use App\Exception\UnauthorizedTransferException;
use App\Service\TransferService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: '/transfer')]
class TransferController
{
    public function __construct(private readonly TransferService $transferService)
    {
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

        try {
            $result = $this->transferService->execute(
                (float) $data['value'],
                (int) $data['payer'],
                (int) $data['payee']
            );

            return $response->json([
                'status' => 'success',
                'message' => 'Transferência realizada com sucesso',
                'data' => $result,
            ])->withStatus(201);
        } catch (InsufficientBalanceException $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Saldo insuficiente',
            ])->withStatus(422);
        } catch (UnauthorizedTransferException $e) {
            return $response->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ])->withStatus(422);
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Erro ao processar transferência',
            ])->withStatus(500);
        }
    }
}
