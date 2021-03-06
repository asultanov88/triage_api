<?php

namespace App\Http\Controllers\Vitals;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\HelperClass;
use Exception;
use App\Vitals;
use App\User;
use App\Clinic;
use App\Patient;
use Carbon\Carbon;

class VitalsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api'], ['except' => []]);       
        
    }

    public function calcBmi(Request $request){

        $request -> validate([

            'patient_id' => 'required|integer',            
            //height, weight must be passed in as double (i.e. 120.00)
            'height' => 'required|regex:/^[0-9]+(\.[0-9][0-9])/',
            'weight' => 'required|regex:/^[0-9]+(\.[0-9][0-9])/',
            'height_unit' => 'required',
            'weight_unit' => 'required'

        ]);

        try{

            $help = new HelperClass;
            $request =$help -> sanitize($request->all());

            $final_weight_kg = null;
            $final_height_cm = null;    

            //if in KG, no conversion needed. Value goes to the DB as DOUBLE.
            if($request['weight_unit'] == 'kg'){

                $final_weight_kg = $request['weight'];

            //If in LB, I convert the value to KG, then write to DB.    
            }else if($request['weight_unit'] == 'lb'){

                $final_weight_kg = $help -> lbToKg($request['weight']);
            }

            //if in CM, no conversion needed. Value goes to the DB as DOUBLE.
            if($request['height_unit'] == 'cm'){

                $final_height_cm = $request['height'];

            //If in INCH, I convert the value to CM, then write to DB.
            }else if($request['height_unit'] == 'inch'){

                $final_height_cm = $help -> inchToCm($request['height']);
            }

            //Calculating BMI here.
            $calc_bmi = $help -> calculateBmi((double)$final_height_cm, (double) $final_weight_kg);

            $bmi_result = []; 
            $bmi_result['height_cm'] =  $final_height_cm;
            $bmi_result['weight_kg'] =  $final_weight_kg;
            $bmi_result['bmi'] = $calc_bmi;

            return response()->json($bmi_result, 200);    

        } catch (exception $e) {

            if(app()->environment() == 'dev'){

                return $e;

           }else{

                return response()->json('error', 500);

           } 

        }        

    }

    public function postVitals(Request $request){

        $request -> validate([

            'patient_id' => 'required|integer',
            'height_cm' => 'required_with:weight_kg, bmi|regex:/^[0-9]+(\.[0-9][0-9])/',
            'weight_kg' => 'required_with:height_cm, bmi|regex:/^[0-9]+(\.[0-9][0-9])/',
            'bmi' => 'required_with:weight_kg, height_cm|regex:/^[0-9]+(\.[0-9][0-9])/',
            'temp' => 'required_with:temp_unit|regex:/^[0-9]+(\.[0-9])/',
            'temp_unit' => 'required_with:temp|in:c,f',
            'pulse' => 'nullable|integer|max:500',
            'resp_rate' => 'nullable|integer|max:200',
            'bp_systolic' => 'required_with:bp_diastolic|integer|max:400',        
            'bp_diastolic' => 'required_with:bp_systolic|integer|max:400'
    
        ]);

        try{

            $help = new HelperClass();
            $request =$help -> sanitize($request->all());
            //Converting from F to C.
            $final_temp_c = null;
            
            if(array_key_exists('temp', $request) && array_key_exists('temp_unit', $request)){

                if(strtolower($request['temp_unit']) == 'f'){
        
                    $final_temp_c = $help -> farToCel($request['temp']);
            
                }else if(strtolower($request['temp_unit']) == 'c'){
            
                    $final_temp_c = (double)number_format($request['temp'], 1);
            
                }            

            }
            
            //Capturing user ID.
            $user = Auth::user();  
            $created_by = $user -> id;
        
            $vitals = new Vitals();
        
            $vitals -> patient_id = $request['patient_id'];
            $vitals -> entered_by = $created_by;

            if(array_key_exists('bmi', $request)){

                $vitals -> height_cm = $request['height_cm'];
                $vitals -> weight_kg = $request['weight_kg'];
                $vitals -> calculated_bmi = $request['bmi'];

            }

            if($final_temp_c != null){

                $vitals -> temperature_c = $final_temp_c;

            }

            if(array_key_exists('pulse', $request)){

                $vitals -> pulse = $request['pulse']; 

            }

            if(array_key_exists('resp_rate', $request)){

                $vitals -> respiratory_rate = $request['resp_rate']; 

            }

            if(array_key_exists('bp_systolic', $request)){

                $vitals -> bp_systolic = $request['bp_systolic']; 
                $vitals -> bp_diastolic = $request['bp_diastolic']; 

            }
            
            $vitals -> save();
        
        
            return response()->json('success', 200);

        } catch(exception $e){

            if(app()->environment() == 'dev'){

                return $e;

           }else{

                return response()->json('error', 500);

           } 

        }

    }

    public function getVitals(Request $request){

        $request -> validate([

            'patient_id' => 'required|integer'

        ]);

            try{

                $help = new HelperClass();
                $request =$help -> sanitize($request->all());

                $vitals = new Vitals();

                $last_record = $vitals -> where('patient_id', $request['patient_id'])->latest('created_at', 'desc')->first();

                $patient = Patient::where('id', $request['patient_id'])->first();

                $entered_by = User::where('id', $last_record['entered_by'])->first();

                if($last_record != null){

                    $clinic_country = Clinic::where('id', Auth::User()->clinic_id)->first() -> country;

                    if(strtolower($clinic_country) == 'us'){
    
                        $height_inch = $help->cmToInch($last_record['height_cm']);
    
                        $weight_lb = $help->kgToLbs($last_record['weight_kg']);
    
                        $temp_f = $help->celToFar($last_record['temperature_c']);
    
                        $last_record['height'] =  $height_inch;
    
                        $last_record['weight'] =  $weight_lb;    
                        
                        $last_record['temp'] =   $temp_f;
    
                    }else{
    
                        $last_record['height'] =  $last_record['height_cm'];
    
                        $last_record['weight'] =  $last_record['weight_kg'];
    
                        $last_record['temp'] =    $last_record['temperature_c'];
    
                    }
    
                    $last_record['country'] =  $clinic_country;

                    $last_record['pt_first_name'] = $patient->first_name;

                    $last_record['pt_last_name'] = $patient->last_name;

                    $last_record['entered_by'] = $entered_by->first_name.' '.$entered_by->last_name;
    
                    unset($last_record['weight_kg']);
    
                    unset($last_record['height_cm']);
                   
                    unset($last_record['temperature_c']);

                    unset($last_record['patient_id']);
                    
                    return response()->json($last_record, 200);

                }else{

                    return response()->json('no record found', 200);

                }

            } catch(exception $e){

                if(app()->environment() == 'dev'){

                    return $e;
    
               }else{
    
                    return response()->json('error', 500);
    
               } 

        }

    }
}
