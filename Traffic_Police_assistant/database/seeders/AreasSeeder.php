<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->areas() as $index => $area) {
            if ($area['city'] === 'Damascus') {
                $this->syncDamascusArea($area, $index);
                continue;
            }

            Area::query()->updateOrCreate(
                [
                    'name' => $area['name'],
                    'city' => $area['city'],
                ],
                $area + ['created_at' => now()->subDays(60 - ($index * 4))]
            );
        }
    }

    private function syncDamascusArea(array $area, int $index): void
    {
        $aliases = $this->damascusAliases()[$area['name']] ?? [$area['name']];
        $payload = $area + ['created_at' => now()->subDays(60 - ($index * 4))];

        $matches = Area::query()
            ->where('city', 'Damascus')
            ->whereIn('name', $aliases)
            ->orderBy('id')
            ->get();

        if ($matches->isNotEmpty()) {
            foreach ($matches as $match) {
                $match->fill($payload);
                $match->save();
            }

            return;
        }

        Area::query()->create($payload);
    }

    private function damascusAliases(): array
    {
        return [
            'المالكي' => ['المالكي', 'Al-Malki', 'Al-Malki', 'Al Maliki', 'Al-Maliki'],
            'أبو رمانة' => ['أبو رمانة', 'Abu Rummaneh'],
            'المزة' => ['المزة', 'Mazzeh'],
            'الحمراء' => ['الحمراء', 'Al-Hamra'],
            'المزرعة' => ['المزرعة', 'Al-Mazraa'],
            'القنوات' => ['القنوات', 'Al-Qanawat'],
            'جوبر' => ['جوبر', 'Jobar'],
            'ساروجة' => ['ساروجة', 'Sarouja'],
            'اليرموك' => ['اليرموك', 'Yarmouk'],
            'برزة' => ['برزة', 'ط¨ط±ط²ط©'],
            'مساكن برزة' => ['مساكن برزة', 'ظ…ط³ط§ظƒظ† ط¨ط±ط²ط©'],
            'القابون' => ['القابون', 'ط§ظ„ظ‚ط§ط¨ظˆظ†', 'Qaboun'],
            'ركن الدين' => ['ركن الدين', 'ط±ظƒظ† ط§ظ„ط¯ظٹظ†', 'Rukn al-Din'],
            'ساحة العباسيين' => ['ساحة العباسيين', 'ط³ط§ط­ط© ط§ظ„ط¹ط¨ط§ط³ظٹظٹظ†', 'Al-Qassaa'],
            'الميدان' => ['الميدان', 'ط§ظ„ظ…ظٹط¯ط§ظ†', 'Al-Midan', 'Midan'],
            'الشام الجديدة' => ['الشام الجديدة', 'ط§ظ„ط´ط§ظ… ط§ظ„ط¬ط¯ظٹط¯ط©'],
            'دمر الغربية' => ['دمر الغربية', 'ط¯ظ…ط± ط§ظ„ط؛ط±ط¨ظٹط©', 'Dummar'],
            'مزة جبل' => ['مزة جبل', 'ظ…ط²ط© ط¬ط¨ظ„'],
            'مزة 86' => ['مزة 86', 'ظ…ط²ط© 86'],
            'مزة فيلات غربية' => ['مزة فيلات غربية', 'ظ…ط²ط© ظپظٹظ„ط§طھ ط؛ط±ط¨ظٹط©'],
            'كفرسوسة' => ['كفرسوسة', 'ظƒظپط±ط³ظˆط³ط©', 'Kafr Sousa'],
            'ساحة الامويين' => ['ساحة الامويين', 'ط³ط§ط­ط© ط§ظ„ط§ظ…ظˆظٹظٹظ†'],
            'تنظيم كفرسوسة' => ['تنظيم كفرسوسة', 'طھظ†ط¸ظٹظ… ظƒظپط±ط³ظˆط³ط©'],
            'البرامكة' => ['البرامكة', 'ط§ظ„ط¨ط±ط§ظ…ظƒط©', 'Baramkeh'],
            'باب مصلى' => ['باب مصلى', 'ط¨ط§ط¨ ظ…طµظ„ظ‰'],
            'القدم' => ['القدم', 'ط§ظ„ظ‚ط¯ظ…'],
            'الشاغور' => ['الشاغور', 'ط§ظ„ط´ط§ط؛ظˆط±', 'Al-Shaghour'],
            'باب توما' => ['باب توما', 'ط¨ط§ط¨ طھظˆظ…ط§'],
        ];
    }

    /**
     * Syrian ADM2 districts with center coordinates.
     *
     * Source references:
     * - https://download.geonames.org/export/dump/SY.zip
     * - https://download.geonames.org/export/dump/readme.txt
     *
     * The values below were extracted from the GeoNames Syria country dump
     * where feature_class = A and feature_code = ADM2.
     *
     * @return array<int, array{name: string, city: string, center_lat: float, center_lng: float}>
     */
    private function areas(): array
    {
        return [
            ['name' => 'Al-Hasakah', 'city' => 'Al-Hasakah', 'center_lat' => 36.22578, 'center_lng' => 40.73446],
            ['name' => 'Al-Malikiyah', 'city' => 'Al-Hasakah', 'center_lat' => 36.94113, 'center_lng' => 41.90157],
            ['name' => 'Al-Qamishli', 'city' => 'Al-Hasakah', 'center_lat' => 36.85071, 'center_lng' => 41.17243],
            ['name' => 'Ras al-Ayn', 'city' => 'Al-Hasakah', 'center_lat' => 36.75664, 'center_lng' => 40.14643],
            ['name' => 'Al-Haffah', 'city' => 'Latakia', 'center_lat' => 35.59687, 'center_lng' => 36.11198],
            ['name' => 'Jableh', 'city' => 'Latakia', 'center_lat' => 35.29048, 'center_lng' => 36.0449],
            ['name' => 'Latakia', 'city' => 'Latakia', 'center_lat' => 35.72426, 'center_lng' => 35.9415],
            ['name' => 'Qardaha', 'city' => 'Latakia', 'center_lat' => 35.41713, 'center_lng' => 36.10613],
            ['name' => 'Fiq', 'city' => 'Quneitra', 'center_lat' => 32.79793, 'center_lng' => 35.72198],
            ['name' => 'Quneitra', 'city' => 'Quneitra', 'center_lat' => 33.08571, 'center_lng' => 35.78844],
            ['name' => 'Al-Thawrah', 'city' => 'Ar-Raqqah', 'center_lat' => 35.79843, 'center_lng' => 38.3455],
            ['name' => 'Ar-Raqqah', 'city' => 'Ar-Raqqah', 'center_lat' => 35.87204, 'center_lng' => 39.04706],
            ['name' => 'Tell Abyad', 'city' => 'Ar-Raqqah', 'center_lat' => 36.4828, 'center_lng' => 39.2136],
            ['name' => 'As-Suwayda', 'city' => 'As-Suwayda', 'center_lat' => 32.78127, 'center_lng' => 36.86502],
            ['name' => 'Salkhad', 'city' => 'As-Suwayda', 'center_lat' => 32.51779, 'center_lng' => 36.91954],
            ['name' => 'Shahba', 'city' => 'As-Suwayda', 'center_lat' => 33.04282, 'center_lng' => 36.72034],
            ['name' => 'Al-Sanamayn', 'city' => 'Daraa', 'center_lat' => 33.12559, 'center_lng' => 36.2754],
            ['name' => 'Daraa', 'city' => 'Daraa', 'center_lat' => 32.63144, 'center_lng' => 36.20352],
            ['name' => 'Izra', 'city' => 'Daraa', 'center_lat' => 32.90121, 'center_lng' => 36.16144],
            ['name' => 'Abu Kamal', 'city' => 'Deir ez-Zor', 'center_lat' => 34.54076, 'center_lng' => 40.51966],
            ['name' => 'Deir ez-Zor', 'city' => 'Deir ez-Zor', 'center_lat' => 35.44913, 'center_lng' => 40.19342],
            ['name' => 'Mayadin', 'city' => 'Deir ez-Zor', 'center_lat' => 34.8565, 'center_lng' => 40.30414],
            ['name' => 'Al-Nabk', 'city' => 'Rif Dimashq', 'center_lat' => 34.08307, 'center_lng' => 36.71223],
            ['name' => 'Al-Qutayfah', 'city' => 'Rif Dimashq', 'center_lat' => 33.80798, 'center_lng' => 36.86081],
            ['name' => 'Al-Tall', 'city' => 'Rif Dimashq', 'center_lat' => 33.71795, 'center_lng' => 36.32785],
            ['name' => 'Al-Zabadani', 'city' => 'Rif Dimashq', 'center_lat' => 33.70819, 'center_lng' => 36.11198],
            ['name' => 'Darayya', 'city' => 'Rif Dimashq', 'center_lat' => 33.4991, 'center_lng' => 36.19116],
            ['name' => 'Douma', 'city' => 'Rif Dimashq', 'center_lat' => 33.43262, 'center_lng' => 37.63855],
            ['name' => 'Markaz Rif Dimashq', 'city' => 'Rif Dimashq', 'center_lat' => 33.34658, 'center_lng' => 36.28752],
            ['name' => 'Qatana', 'city' => 'Rif Dimashq', 'center_lat' => 33.38916, 'center_lng' => 36.0036],
            ['name' => 'Yabrud', 'city' => 'Rif Dimashq', 'center_lat' => 33.95305, 'center_lng' => 36.44595],
            ['name' => 'Afrin', 'city' => 'Aleppo', 'center_lat' => 36.54891, 'center_lng' => 36.79295],
            ['name' => 'Al-Bab', 'city' => 'Aleppo', 'center_lat' => 36.30946, 'center_lng' => 37.53277],
            ['name' => 'Al-Safira', 'city' => 'Aleppo', 'center_lat' => 35.83309, 'center_lng' => 37.46345],
            ['name' => 'Ayn al-Arab', 'city' => 'Aleppo', 'center_lat' => 36.60224, 'center_lng' => 38.34816],
            ['name' => 'Azaz', 'city' => 'Aleppo', 'center_lat' => 36.49986, 'center_lng' => 37.18382],
            ['name' => 'Jarabulus', 'city' => 'Aleppo', 'center_lat' => 36.69092, 'center_lng' => 37.81411],
            ['name' => 'Manbij', 'city' => 'Aleppo', 'center_lat' => 36.06687, 'center_lng' => 37.91735],
            ['name' => 'Mount Simeon', 'city' => 'Aleppo', 'center_lat' => 35.99956, 'center_lng' => 37.08473],
            ['name' => 'Al-Salamiyah', 'city' => 'Hama', 'center_lat' => 35.14398, 'center_lng' => 37.59235],
            ['name' => 'Al-Suqaylabiyah', 'city' => 'Hama', 'center_lat' => 35.48102, 'center_lng' => 36.32441],
            ['name' => 'Hama', 'city' => 'Hama', 'center_lat' => 35.28577, 'center_lng' => 37.12626],
            ['name' => 'Masyaf', 'city' => 'Hama', 'center_lat' => 35.10335, 'center_lng' => 36.3392],
            ['name' => 'Mhardeh', 'city' => 'Hama', 'center_lat' => 35.30023, 'center_lng' => 36.54844],
            ['name' => 'Al-Mukharram', 'city' => 'Homs', 'center_lat' => 34.85057, 'center_lng' => 37.37688],
            ['name' => 'Al-Qusayr', 'city' => 'Homs', 'center_lat' => 34.54081, 'center_lng' => 36.55528],
            ['name' => 'Al-Rastan', 'city' => 'Homs', 'center_lat' => 34.871, 'center_lng' => 36.77245],
            ['name' => 'Homs', 'city' => 'Homs', 'center_lat' => 34.52942, 'center_lng' => 36.89511],
            ['name' => 'Tadmur', 'city' => 'Homs', 'center_lat' => 34.42401, 'center_lng' => 38.6458],
            ['name' => 'Talkalakh', 'city' => 'Homs', 'center_lat' => 34.71846, 'center_lng' => 36.27383],
            ['name' => 'Ariha', 'city' => 'Idlib', 'center_lat' => 35.76284, 'center_lng' => 36.55204],
            ['name' => 'Harem', 'city' => 'Idlib', 'center_lat' => 36.14014, 'center_lng' => 36.56535],
            ['name' => 'Idlib', 'city' => 'Idlib', 'center_lat' => 35.90691, 'center_lng' => 36.75677],
            ['name' => 'Jisr al-Shughur', 'city' => 'Idlib', 'center_lat' => 35.87756, 'center_lng' => 36.32901],
            ['name' => 'Maarrat al-Nu\'man', 'city' => 'Idlib', 'center_lat' => 35.53858, 'center_lng' => 36.79193],
            ['name' => 'Damascus', 'city' => 'Damascus', 'center_lat' => 33.51563, 'center_lng' => 36.28032],
            ['name' => 'المالكي', 'city' => 'Damascus', 'center_lat' => 33.5182553, 'center_lng' => 36.2712047],
            ['name' => 'كفرسوسة', 'city' => 'Damascus', 'center_lat' => 33.48461765471776, 'center_lng' => 36.267768120406416],
            ['name' => 'المزة', 'city' => 'Damascus', 'center_lat' => 33.5021550, 'center_lng' => 36.2456866],
            ['name' => 'أبو رمانة', 'city' => 'Damascus', 'center_lat' => 33.5179031, 'center_lng' => 36.2841402],
            ['name' => 'البرامكة', 'city' => 'Damascus', 'center_lat' => 33.50748850562386, 'center_lng' => 36.28847201435723],
            ['name' => 'الحمراء', 'city' => 'Damascus', 'center_lat' => 33.5131816, 'center_lng' => 36.3006549],
            ['name' => 'المزرعة', 'city' => 'Damascus', 'center_lat' => 34.7275710, 'center_lng' => 36.6570553],
            ['name' => 'القنوات', 'city' => 'Damascus', 'center_lat' => 33.5080612, 'center_lng' => 36.2847945],
            ['name' => 'جوبر', 'city' => 'Damascus', 'center_lat' => 33.5278282, 'center_lng' => 36.3342014],
            ['name' => 'ساروجة', 'city' => 'Damascus', 'center_lat' => 33.5173074, 'center_lng' => 36.2982652],
            ['name' => 'اليرموك', 'city' => 'Damascus', 'center_lat' => 33.4724791, 'center_lng' => 36.3048029],
            ['name' => 'حي الأمين', 'city' => 'Damascus', 'center_lat' => 33.5055802, 'center_lng' => 36.3142456],
            ['name' => 'نهر عيشة', 'city' => 'Damascus', 'center_lat' => 33.4853962, 'center_lng' => 36.2874182],
            ['name' => 'برزة', 'city' => 'Damascus', 'center_lat' => 33.55733414817135, 'center_lng' => 36.3118747861408],
            ['name' => 'مساكن برزة', 'city' => 'Damascus', 'center_lat' => 33.544172444030806, 'center_lng' => 36.32037202417442],
            ['name' => 'القابون', 'city' => 'Damascus', 'center_lat' => 33.54704259480129, 'center_lng' => 36.335903791092456],
            ['name' => 'ركن الدين', 'city' => 'Damascus', 'center_lat' => 33.540143160620765, 'center_lng' => 36.299315364467994],
            ['name' => 'ساحة العباسيين', 'city' => 'Damascus', 'center_lat' => 33.52564962903036, 'center_lng' => 36.30618841156575],
            ['name' => 'الميدان', 'city' => 'Damascus', 'center_lat' => 33.490755531710136, 'center_lng' => 36.297968044518484],
            ['name' => 'الشام الجديدة', 'city' => 'Damascus', 'center_lat' => 33.52938744770851, 'center_lng' => 36.23145780204516],
            ['name' => 'دمر الغربية', 'city' => 'Damascus', 'center_lat' => 33.5168025299054, 'center_lng' => 36.24322292856479],
            ['name' => 'مزة جبل', 'city' => 'Damascus', 'center_lat' => 33.505548886758675, 'center_lng' => 36.252091538576465],
            ['name' => 'مزة 86', 'city' => 'Damascus', 'center_lat' => 33.50656003517311, 'center_lng' => 36.242946068581766],
            ['name' => 'مزة فيلات غربية', 'city' => 'Damascus', 'center_lat' => 33.4955210255524, 'center_lng' => 36.23167844532863],
            ['name' => 'كفرسوسة', 'city' => 'Damascus', 'center_lat' => 33.48461765471776, 'center_lng' => 36.267768120406416],
            ['name' => 'ساحة الامويين', 'city' => 'Damascus', 'center_lat' => 33.51433011113321, 'center_lng' => 36.274784936786254],
            ['name' => 'تنظيم كفرسوسة', 'city' => 'Damascus', 'center_lat' => 33.500483713797514, 'center_lng' => 36.27860134709502],
            ['name' => 'البرامكة', 'city' => 'Damascus', 'center_lat' => 33.50748850562386, 'center_lng' => 36.28847201435723],
            ['name' => 'باب مصلى', 'city' => 'Damascus', 'center_lat' => 33.49775121057897, 'center_lng' => 36.296101136352036],
            ['name' => 'القدم', 'city' => 'Damascus', 'center_lat' => 33.47236554795264, 'center_lng' => 36.284134219444915],
            ['name' => 'الشاغور', 'city' => 'Damascus', 'center_lat' => 33.48902717709692, 'center_lng' => 36.311161620989296],
            ['name' => 'باب توما', 'city' => 'Damascus', 'center_lat' => 33.51128105787638, 'center_lng' => 36.31485550325062],
            ['name' => 'Al-Shaykh Badr', 'city' => 'Tartus', 'center_lat' => 35.04492, 'center_lng' => 36.09807],
            ['name' => 'Baniyas', 'city' => 'Tartus', 'center_lat' => 35.13698, 'center_lng' => 36.07864],
            ['name' => 'Duraykish', 'city' => 'Tartus', 'center_lat' => 34.93205, 'center_lng' => 36.12682],
            ['name' => 'Safita', 'city' => 'Tartus', 'center_lat' => 34.80419, 'center_lng' => 36.12293],
            ['name' => 'Tartus', 'city' => 'Tartus', 'center_lat' => 34.83904, 'center_lng' => 35.9716],
        ];
    }
}
