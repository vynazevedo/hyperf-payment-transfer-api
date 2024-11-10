<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Exception;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: '/users')]
class UserController
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return PsrResponseInterface
     */
    #[RequestMapping(path: '', methods: 'POST')]
    public function store(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = $request->all();

        if (empty($data['name'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'O nome é obrigatório',
            ])->withStatus(422);
        }

        if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $response->json([
                'status' => 'error',
                'message' => 'Email inválido',
            ])->withStatus(422);
        }

        if ($this->userRepository->emailExists($data['email'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'Email já cadastrado',
            ])->withStatus(422);
        }

        if (empty($data['cpf']) || strlen($data['cpf']) !== 11) {
            return $response->json([
                'status' => 'error',
                'message' => 'CPF inválido',
            ])->withStatus(422);
        }

        if ($this->userRepository->cpfExists($data['cpf'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'CPF já cadastrado',
            ])->withStatus(422);
        }

        try {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $user = $this->userRepository->create($data);

            return $response->json([
                'status' => 'success',
                'message' => 'Usuário criado com sucesso',
                'data' => $user,
            ])->withStatus(201);
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Erro ao criar usuário',
            ])->withStatus(500);
        }
    }

    /**
     * @param int $id
     * @param ResponseInterface $response
     * @return PsrResponseInterface
     */
    #[RequestMapping(path: '{id}', methods: 'GET')]
    public function show(int $id, ResponseInterface $response): PsrResponseInterface
    {
        try {
            $user = $this->userRepository->findOrFail($id);

            return $response->json([
                'status' => 'success',
                'data' => $user,
            ]);
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado',
            ])->withStatus(404);
        }
    }

    /**
     * @param int $id
     * @param ResponseInterface $response
     * @return PsrResponseInterface
     */
    #[RequestMapping(path: '{id}/balance', methods: 'GET')]
    public function balance(int $id, ResponseInterface $response): PsrResponseInterface
    {
        try {
            $user = $this->userRepository->findOrFail($id);

            return $response->json([
                'status' => 'success',
                'data' => [
                    'user_id' => $user->id,
                    'balance' => $user->balance,
                ],
            ]);
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado',
            ])->withStatus(404);
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return PsrResponseInterface
     */
    #[RequestMapping(path: '', methods: 'GET')]
    public function index(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $perPage = (int) $request->input('per_page', 15);
        $users = $this->userRepository->paginate($perPage);

        return $response->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }
}
