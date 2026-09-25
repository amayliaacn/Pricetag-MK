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

    public function delete(int $id)
    {
        $role = (string) session()->get('role');
        $outletId = session()->get('outlet_id');
        $historyModel = new ImportHistoryModel();
        $history = $historyModel->findVisibleImport(
            $id,
            $role,
            $outletId === null ? null : (int) $outletId
        );

        if (! $history) {
            return redirect()->to(base_url('import-history'))->with('error', 'Data import tidak ditemukan atau tidak dapat dihapus.');
        }

        $db = db_connect();
        $db->transStart();
        $db->table('price_tags')->where('import_id', $id)->delete();
        $historyModel->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(base_url('import-history'))->with('error', 'Data import gagal dihapus.');
        }

        return redirect()->to(base_url('import-history'))->with('success', 'Data file import dan produk terkait berhasil dihapus.');
    }
}
