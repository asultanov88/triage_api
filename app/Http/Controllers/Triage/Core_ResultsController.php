<?php

namespace App\Http\Controllers\Triage;

use App\triage\acss\Results as ACSS_Result;
use App\triage\demographics\Results as Demographics_Result;
use App\triage\risk_factor\Results as Risk_Factor_Result;
use App\triage\premature_ejaculation\Results as Premature_Ejaculation_Result;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Patient;
use Exception;

class Core_ResultsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api'], ['except' => []]);

    }

    public function createRecord(Request $request, $form){

    $request->validate([

        'patient_id'=>'required|integer',
        'timestamp'=>'required'
    ]);

        try {

            $patient_id = $request['patient_id'];

            $patient_table = new Patient;
            if(!$patient_table::where('id', '=', $patient_id)->first()){

                return response()->json('No such patient', 500);

            }

            //Capturing user ID.
            $user = Auth::user();
            $created_by = $user -> id;

            $results =  (Array)$request['results'];

            foreach($results as $result){

                $category_id = $result['category_id'];

                $responses = (Array)$result['response'];

                foreach($responses as $response){

                    $question_id = $response['question_id'];

                    $response_id = $response['response_id'];

                    //Source DB table gets set depending on $form.
                    $results_table = null;
                    if($form == 'acss'){
                        $results_table = new ACSS_Result;
                    }else if($form == 'demographics'){
                        $results_table = new Demographics_Result;
                    }else if($form == 'risk_factor'){
                        $results_table = new Risk_Factor_Result;
                    }else if($form == 'premature_ejaculation'){
                        $results_table = new Premature_Ejaculation_Result;
                    }

                    //preg_replace("/[^\d]/", "", *STRING) extracts only numbers from the string.
                    $results_table -> patient_id = preg_replace("/[^\d]/", "", $patient_id);
                    $results_table -> category_id = preg_replace("/[^\d]/", "", $category_id);
                    $results_table -> question_id = preg_replace("/[^\d]/", "", $question_id);
                    $results_table -> response_id = preg_replace("/[^\d]/", "", $response_id);
                    $results_table -> created_by = preg_replace("/[^\d]/", "", $created_by);
                    $results_table -> created_at = preg_replace("/[^\d]/", "", strtotime($request['timestamp']));
                    $results_table -> save();
                }

            }

            //A timestamp must be returned for any POST other than demographcis and risk_factor.
            $response = [
                'status' => 'success',
                'timestamp' =>  $request['timestamp']
            ];

            return response()->json($response, 200);

        } catch (exception $e) {

            if(app()->environment() == 'dev'){

                return $e;

           }else{

                return response()->json('error', 500);

           }
        }

    }
}
