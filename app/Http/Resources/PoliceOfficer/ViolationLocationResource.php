<?php

namespace App\Http\Resources\PoliceOfficer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $city = $this->city ?? $this->cityRecord;

        return [
            'id'          => $this->id,
            'city'        => $city ? [
                'id' => $city->id,
                'name' => $city->name,
            ] : null,
            'street_name' => $this->street_name,
            'landmark'    => $this->landmark,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'city_name' => $this->city,
        ];
    }
}
