<?php

namespace App\Controllers;

use App\Models\ImportHistoryModel;
use App\Models\OutletModel;

class ImportHistory extends BaseController
{
    public function index()
    {
        $role = (string) session()->get('role');
        $outletId = session()->get('outlet_id');
        $filters = [
            'outlet_id' => $this->request->getGet('outlet_id'),
            'month'     => $this->request->getGet('month'),
            'year'      => $this->request->getGet('year'),
        ];

        $yearQuery = db_connect()->table('import_history')
                                 ->select('YEAR(imported_at) AS year', false);

        if ($role !== 'super_admin') {
            $yearQuery->where('outlet_id', (int) $outletId);
        }

        $years = $yearQuery->groupBy('YEAR(imported_at)', false)
                           ->orderBy('year', 'DESC')
                           ->get()->getResultArray();

        return view('import_history/index', [
            'title'   => 'History Import',
            'history' => (new ImportHistoryModel())->filteredHistory(
                $role,
                $outletId === null ? null : (int) $outletId,
                $filters
            ),
            'outlets' => $role === 'super_admin' ? (new OutletModel())->orderBy('code')->findAll() : [],
            'activeOutlets' => $role === 'super_admin' ? (new OutletModel())->activeOutlets() : [],
            'years'   => array_column($years, 'year'),
            'filters' => $filters,
        ]);
    }
}
