<?php 

namespace App\Http\Services\Citizen;

use App\Models\Violation;

class ViolationService{

    public function fatchViolations( array $data = []){

        $violations = Violation::where('plate_snapshot' , $data['plate'])
        ->orderBy('created_at')
        ->paginate(5);

            if (isset($params['search'])) {
            $violations->where('name', 'like', "%{$violations['search']}%");
        }

        return $violations ;
    }
}