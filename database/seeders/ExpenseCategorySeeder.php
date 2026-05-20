<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $categories = [
            ['code' => 'acpm',                  'name' => 'ACPM',                  'has_galones' => true,  'sort_order' => 1],
            ['code' => 'urea',                  'name' => 'UREA',                  'has_galones' => false, 'sort_order' => 2],
            ['code' => 'comision',              'name' => 'COMISIÓN',              'has_galones' => false, 'sort_order' => 3],
            ['code' => 'porcentaje',            'name' => 'PORCENTAJE',            'has_galones' => false, 'sort_order' => 4],
            ['code' => 'montallantas',          'name' => 'MONTALLANTAS',          'has_galones' => false, 'sort_order' => 5],
            ['code' => 'parqueaderos',          'name' => 'PARQUEADEROS',          'has_galones' => false, 'sort_order' => 6],
            ['code' => 'lavada_del_carro',      'name' => 'LAVADA DEL CARRO',      'has_galones' => false, 'sort_order' => 7],
            ['code' => 'lubricantes',           'name' => 'LUBRICANTES',           'has_galones' => false, 'sort_order' => 8],
            ['code' => 'engrasada',             'name' => 'ENGRASADA',             'has_galones' => false, 'sort_order' => 9],
            ['code' => 'electrico',             'name' => 'ELÉCTRICO',             'has_galones' => false, 'sort_order' => 10],
            ['code' => 'bascula',               'name' => 'BÁSCULA',               'has_galones' => false, 'sort_order' => 11],
            ['code' => 'embolada_de_llantas',   'name' => 'EMBOLADA DE LLANTAS',   'has_galones' => false, 'sort_order' => 12],
            ['code' => 'varios',                'name' => 'VARIOS',                'has_galones' => false, 'sort_order' => 13],
            ['code' => 'carpada',               'name' => 'CARPADA',               'has_galones' => false, 'sort_order' => 14],
            ['code' => 'descarpada',            'name' => 'DESCARPADA',            'has_galones' => false, 'sort_order' => 15],
            ['code' => 'viaticos',              'name' => 'VIÁTICOS',              'has_galones' => false, 'sort_order' => 16],
        ];

        foreach ($categories as $cat) {
            DB::table('expense_categories')->updateOrInsert(
                ['code' => $cat['code']],
                array_merge($cat, ['active' => true, 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
