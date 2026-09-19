<?php

namespace App\Models;

use CodeIgniter\Model;

class OutletModel extends Model
{
    protected $table            = 'outlets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['code', 'name', 'is_active'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function activeOutlets(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('code', 'ASC')
                    ->findAll();
    }

    public function findById(int $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }
}
