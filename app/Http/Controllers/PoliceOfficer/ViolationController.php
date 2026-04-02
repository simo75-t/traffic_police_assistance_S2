<?php 

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller ;

use App\Http\Requests\PoliceOfficer\CreateViolationRequest;
use App\Http\Requests\PoliceOfficer\ViolationSearchRequest;
use App\Http\Resources\PoliceOfficer\ViolationResource;
use App\Http\Services\PoliceOfficer\ViolationService;

class ViolationController extends Controller
{

    protected $violationService ;

    public function __construct(ViolationService $violationService){
        $this->violationService = $violationService;
    }
    public function index(){
        $violations =  $this->violationService->getViolationList();
      return $this->success(
       ViolationResource::collection($violations) );
    }

    public function create(CreateViolationRequest $request){
        $atrr =  $request->validated();
        $violation = $this->violationService->createViolation($atrr);
        return $this->success(new ViolationResource($violation)) ;

    }


public function search(ViolationSearchRequest $request)
{
    $params = $request->only([
        'plate',
        'from',
        'to',
        'order_by',
        'order_direction',
        'per_page',
    ]);

    $violations = $this->violationService->getAllViolationList($params);

    return response()->json([
        'data' => $violations->items(),
        'meta' => [
            'current_page' => $violations->currentPage(),
            'last_page' => $violations->lastPage(),
            'per_page' => $violations->perPage(),
            'total' => $violations->total(),
        ],
    ]);
}


}