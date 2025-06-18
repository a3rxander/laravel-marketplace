<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->getPaginated($filters);
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
throw new ModelNotFoundException('User not found');
        }
        
        return $user;
    }

    public function getUserProfile(int $userId): User
    {
        return $this->userRepository->findByIdWithRelations($userId, ['addresses', 'seller']);
    }

    public function createUser(array $data): User
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Set default values
        $data['status'] = $data['status'] ?? 'active';
        $data['is_admin'] = $data['is_admin'] ?? false;
        $data['timezone'] = $data['timezone'] ?? 'UTC';
        $data['language'] = $data['language'] ?? 'en';

        return $this->userRepository->create($data);
    }

    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUserById($id);

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Remove null values to avoid overwriting with nulls
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        return $this->userRepository->update($user, $data);
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->getUserById($id);
        return $this->userRepository->delete($user);
    }

    public function updateUserStatus(int $id, string $status): User
    {
        $user = $this->getUserById($id);
        return $this->userRepository->update($user, ['status' => $status]);
    }

    public function updateLastLogin(int $userId): User
    {
        $user = $this->getUserById($userId);
        return $this->userRepository->update($user, [
            'last_login_at' => now()
        ]);
    }

    public function changePassword(int $userId, string $newPassword): User
    {
        $user = $this->getUserById($userId);
        return $this->userRepository->update($user, [
            'password' => Hash::make($newPassword)
        ]);
    }

    public function verifyEmail(int $userId): User
    {
        $user = $this->getUserById($userId);
        return $this->userRepository->update($user, [
            'email_verified_at' => now()
        ]);
    }

    public function getUsersByRole(string $role, array $filters = []): LengthAwarePaginator
    {
        $filters['role'] = $role;
        return $this->userRepository->getPaginated($filters);
    }

    public function searchUsers(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->userRepository->getPaginated($filters);
    }

    public function getActiveUsers(array $filters = []): LengthAwarePaginator
    {
        $filters['status'] = 'active';
        return $this->userRepository->getPaginated($filters);
    }

    public function getUserStats(): array
    {
        return [
            'total_users' => $this->userRepository->getTotalCount(),
            'active_users' => $this->userRepository->getCountByStatus('active'),
            'inactive_users' => $this->userRepository->getCountByStatus('inactive'),
            'suspended_users' => $this->userRepository->getCountByStatus('suspended'),
            'admins' => $this->userRepository->getAdminCount(),
            'sellers' => $this->userRepository->getSellerCount(),
            'customers' => $this->userRepository->getCustomerCount(),
            'recent_registrations' => $this->userRepository->getRecentRegistrations(7),
        ];
    }

    public function bulkUpdateStatus(array $userIds, string $status): int
    {
        return $this->userRepository->bulkUpdateStatus($userIds, $status);
    }

    public function exportUsers(array $filters = []): array
    {
        return $this->userRepository->getForExport($filters);
    }
}

/docker

docker/ elasticsearch

docker/ mysql

docker/nginx

docker/php

docker/scripts

 

/src

/ .env

/ .env.docker

/ docker-compose.yml

/ Dockerfile

/ Makefile

/README.md

/ structure.txt