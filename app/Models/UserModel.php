<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['username', 'password', 'role', 'outlet_id', 'is_active'];

    protected $validationRules = [
        'outlet_id' => 'permit_empty|is_natural_no_zero|is_not_unique[outlets.id]',
    ];
    
    // Mengaktifkan fitur timestamps otomatis untuk created_at dan updated_at
    protected $useTimestamps    = true; 
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function validate($row): bool
    {
        if (is_object($row)) {
            $row = (array) $row;
        }

        $rules = [
            'outlet_id' => 'permit_empty|is_natural_no_zero|is_not_unique[outlets.id]',
        ];

        if (($row['role'] ?? null) === 'user') {
            $rules['outlet_id'] = 'required|is_natural_no_zero|is_not_unique[outlets.id]';
            $row['outlet_id'] ??= null;
        }

        $this->setValidationRules($rules);

        return parent::validate($row);
    }
}
