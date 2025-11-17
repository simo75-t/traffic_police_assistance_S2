<?php 

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers;

use App\Http\Requests\PoliceOfficer\CreateViolationRequest;
use App\Http\Resources\PoliceOfficer\ViolationResource;
use App\Http\Services\PoliceOfficer\ViolationService;


class ViolationController {


    protected $violationService ;

    public function __construct(ViolationService $violationService){
        $this->violationService = $violationService;
    }
    public function index(){
        return $this->violationService->getViolationList();
    }

    public function create(CreateViolationRequest $request){
        $atrr =  $request->validated();
        $violation = $this->violationService->createViolation($atrr);
        return $violation->success(new ViolationResource($violation));

    }
}