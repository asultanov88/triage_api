<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Clinic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendLink;
use Exception;
use App\Http\Controllers\UserController;
use App\Http\Helper\HelperClass;

class ClinicController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth:api'], ['except' => []]);       
        
    }

    public function registerClinic(Request $request){ 

      
        $request->validate([

            'name' => 'required|max:100',
            'addressLineOne'=>'required|max:255',
            'city'=>'required|max:50',
            'zipCode'=>'required|max:15',
            'stateOrRegion'=>'required|max:50',
            'country'=>'required|max:50',
            'phone'=>'required|max:50',
            'clinicEmail'=>'required|unique:clinics|email|max:50',
            'website'=>'required|max:255',
            'language'=>'required|max:50',  
            'contactPhone'=>'required|max:50',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|unique:users|email|max:50',            
            'position' => 'required' 

        ]);  

      try {

        $help = new HelperClass;  
        $request =$help -> sanitize($request->all());
       

        $user = Auth::user();  
        $clinics = new Clinic();
        $users = new UserController();        

        $newClinic = $this->saveClinic($user, $clinics, $request);

        $users->addSuperUserForClinic($newClinic, $request, $user);        

        return response()->json('success', 200);


      } catch (exception $e) {

        return response()->json($e, 500);

      }

    }



    public function saveClinic($user, $clinics, $request){

        try {
            
        $clinics-> name = $request['name'];
        $clinics-> status = 'active';
        $clinics-> addressLineOne = $request['addressLineOne'];

        if(isset($request['addressLineTwo'])){

            $clinics-> addressLineTwo = $request['addressLineTwo'];

        }else{

            $clinics-> addressLineTwo = null;

        }

        $clinics-> city = $request['city'];
        $clinics-> zipCode = $request['zipCode'];
        $clinics-> stateOrRegion = $request['stateOrRegion'];
        $clinics-> country = $request['country'];
        $clinics-> phone = $request['phone'];
        $clinics-> clinicEmail = $request['clinicEmail'];
        $clinics-> website = $request['website'];
        $clinics-> language = $request['language'];
        $clinics-> contactPhone = $request['contactPhone'];
        $clinics-> contactEmail = $request['email'];
        $clinics-> contactName = $request['first_name'].' '.$request['last_name'];
        $clinics-> created_by = $user['id'];

        $clinics->save();

        $updatedClinics = new Clinic();

        $newClinic = $updatedClinics::where('clinicEmail', $request['clinicEmail'])->firstOrFail();
        
        return $newClinic;

        } catch (exception $e) {

            return $e;

        }


    }
    

public function clinicSearch(Request $request){

    $request -> validate([

        'keyword'=>"required|min:3"

    ]);

    $help = new HelperClass;  
    $request = $help -> sanitize($request->all());

    $clinics = new Clinic(); 
    
    try {
        if($request['keyword'] == '%all%'){

            $clinicsResult = $clinics::all();
            
            return response()->json($clinicsResult, 200);
    
        }else{
    
            $clinicsResult = $clinics::where('name', 'like', '%' . $request['keyword'] . '%')->get();
    
            return response()->json($clinicsResult, 200);  
    
        }
    } catch (exception $e) {

        return response()->json("error", 500);  

    }
}

public function assignClinic(Request $request){

    $request -> validate([

        "id" => "required|integer"

    ]);

    $help = new HelperClass;  
    $request = $help -> sanitize($request->all());

    $user = Auth::user();
    $clinics = new Clinic();
    $updateUser = new User();

    try {
        
        $clinicsResult = $clinics::where('id', $request['id'])->firstOrFail();

        $userId = $user['id'];

        $updateResult = $updateUser::where('id', $userId)->firstOrFail();

        $updateResult['clinic_id'] = $clinicsResult['id'];

        $updateResult['last_edited_by'] =  $userId;

        $updateResult -> update();

        return response()->json("success", 200);

    } catch (exception $e) {

         return response()->json("error", 500);
         
    }


    
}


}
