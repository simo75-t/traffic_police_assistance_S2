<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->areas() as $index => $area) {
            Area::query()->updateOrCreate(
                [
                    'name' => $area['name'],
                    'city' => $area['city'],
                ],
                $area + ['created_at' => now()->subDays(60 - ($index * 4))]
            );
        }
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
            ['name' => 'Al-Shaykh Badr', 'city' => 'Tartus', 'center_lat' => 35.04492, 'center_lng' => 36.09807],
            ['name' => 'Baniyas', 'city' => 'Tartus', 'center_lat' => 35.13698, 'center_lng' => 36.07864],
            ['name' => 'Duraykish', 'city' => 'Tartus', 'center_lat' => 34.93205, 'center_lng' => 36.12682],
            ['name' => 'Safita', 'city' => 'Tartus', 'center_lat' => 34.80419, 'center_lng' => 36.12293],
            ['name' => 'Tartus', 'city' => 'Tartus', 'center_lat' => 34.83904, 'center_lng' => 35.9716],
        ];
    }
}
