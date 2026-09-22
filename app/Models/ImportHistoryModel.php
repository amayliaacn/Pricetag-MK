<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportHistoryModel extends Model
{
    protected $table            = 'import_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['file_name', 'snapshot', 'outlet_id', 'imported_at', 'imported_by'];

    public function findVisibleImport(int $id, string $role, ?int $outletId): ?array
    {
        $builder = $this->select('import_history.*, outlets.code AS outlet_code, outlets.name AS outlet_name, users.username')
                        ->join('outlets', 'outlets.id = import_history.outlet_id')
                        ->join('users', 'users.id = import_history.imported_by');

        if ($role !== 'super_admin') {
            $builder->where('import_history.outlet_id', $outletId);
        }

        return $builder->where('import_history.id', $id)->first();
    }

    public function filteredHistory(string $role, ?int $outletId, array $filters = []): array
    {
        $builder = $this->select('import_history.*, outlets.code AS outlet_code, outlets.name AS outlet_name, users.username')
                        ->join('outlets', 'outlets.id = import_history.outlet_id')
                        ->join('users', 'users.id = import_history.imported_by');

        if ($role !== 'super_admin') {
            $builder->where('import_history.outlet_id', $outletId);
        } elseif (! empty($filters['outlet_id'])) {
            $builder->where('import_history.outlet_id', (int) $filters['outlet_id']);
        }

        if (! empty($filters['month']) && ! empty($filters['year'])) {
            $builder->where('MONTH(import_history.imported_at)', (int) $filters['month'])
                    ->where('YEAR(import_history.imported_at)', (int) $filters['year']);
        } elseif (! empty($filters['year'])) {
            $builder->where('YEAR(import_history.imported_at)', (int) $filters['year']);
        }

        return $builder->orderBy('import_history.imported_at', 'DESC')->findAll();
    }
}
