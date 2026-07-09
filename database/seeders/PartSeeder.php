<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Part;

class PartSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            'Планшет Digma Z10 серый 4G 10.1"',
            'Стандартный нож для плоттеров Cameo/Portrait/Curio/Craftrobo 45гр 0,1-1 черный',
            'Плата печатная Cutter_board_rev_D_Docs_12032021',
            '10.1inch HDMI LCD WAVESHAR электронные модули',
            '7inch HDMI LCD [C]/WAVESHAR// дисплей',
            'Микрокомпьютер Raspberry Pi 3',
            'Полуфабрикат боковины правой белой PS AJP100-XS/AJP200-XS 180*170*86мм AJ',
            'Полуфабрикат боковины левой белой PS AJP100-XS/AJP200-XS 180*170*86мм AJ',
            'Поперечная планка горизонтальная PS для AJP100-XS/AJP200-XS 298*28*6мм AJ',
            'Панель передняя белая PS для AJP100-XS/AJP200-XS 298*44*6мм AJ',
            'Панель задняя белая PS для AJP100-XS/AJP200-XS 298*90*6мм AJ',
            'Платформа верхняя белая PS для AJP100-XS/AJP200-XS 298*148*6 мм AJ',
            'Корпус планшета Digma 1314C 10.1"',
            'Комплект крючков и опор к корпусу планшета Digma CITI 1314C 10.1"',
            'Шильдик Armorjack на корпус',
            'Жгут основной для плоттеров AJP',
        ];

        foreach ($parts as $partName) {
            Part::create(['name' => $partName]);
        }
    }
}