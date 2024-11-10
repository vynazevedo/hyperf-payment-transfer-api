<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: '/users')]
class UserController
{
    #[Inject]
    protected User $user;

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

        if (empty($data['cpf']) || strlen($data['cpf']) !== 11) {
            return $response->json([
                'status' => 'error',
                'message' => 'CPF inválido',
            ])->withStatus(422);
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            return $response->json([
                'status' => 'error',
                'message' => 'A senha deve ter no mínimo 6 caracteres',
            ])->withStatus(422);
        }

        if (empty($data['type']) || ! in_array($data['type'], ['common', 'merchant'])) {
            return $response->json([
                'status' => 'error',
                'message' => 'Tipo de usuário inválido',
            ])->withStatus(422);
        }

        if ($this->user->where('email', $data['email'])->exists()) {
            return $response->json([
                'status' => 'error',
                'message' => 'Este email já está em uso',
            ])->withStatus(422);
        }

        if ($this->user->where('cpf', $data['cpf'])->exists()) {
            return $response->json([
                'status' => 'error',
                'message' => 'Este CPF já está em uso',
            ])->withStatus(422);
        }

        try {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            $user = $this->user->create($data);

            return $response->json([
                'status' => 'success',
                'message' => 'Usuário criado com sucesso',
                'data' => $user,
            ])->withStatus(201);
        } catch (Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Erro ao criar usuário',
                'error' => $e->getMessage(),
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
            $user = $this->user->findOrFail($id);

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
            $user = $this->user->findOrFail($id);

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
        $users = $this->user->paginate($perPage);

        return $response->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }
}