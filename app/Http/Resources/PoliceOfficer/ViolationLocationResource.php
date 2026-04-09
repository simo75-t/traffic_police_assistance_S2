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
        $cityRelation = $this->cityRecord;
        $cityName = null;
        $cityPayload = null;

        if ($cityRelation) {
            $cityPayload = [
                'id' => $cityRelation->id,
                'name' => $cityRelation->name,
            ];
            $cityName = $cityRelation->name;
        } elseif (is_string($this->city) && trim($this->city) !== '') {
            $cityName = $this->city;
            $cityPayload = [
                'id' => null,
                'name' => $this->city,
            ];
        }

        return [
            'id'          => $this->id,
            'city'        => $cityPayload,
            'street_name' => $this->street_name,
            'landmark'    => $this->landmark,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'city_name' => $cityName,
        ];
    }
}
