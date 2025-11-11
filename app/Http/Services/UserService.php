<?php

namespace App\Http\Services;

use App\Models\User;
use App\Http\Interfaces\RepositoriesInterfaces\IFirstOrCreateRepository;

class UserService
{
     protected $userRepository;

    public function __construct(IFirstOrCreateRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // public function createuser(array $data): user
    // {
    //     //
    // }

    public function findOrCreate($data): User
    {
        return $this->userRepository->findOrCreate($data);
 
    }
}