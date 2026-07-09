<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlotterModel;

class PlotterModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            'AJP100-XS',
            'AJP200-XS',
            'AJP100-XE',
            'AJP200-XE',
            'HFP Econom 1',
            'HFP M2DQ',
            'AJP Mini 1',
            'AJP Mini 2',
            'AJP Maх',
            'AJP Tablet',
        ];

        foreach ($models as $modelName) {
            PlotterModel::create(['name' => $modelName]);
        }
    }
}