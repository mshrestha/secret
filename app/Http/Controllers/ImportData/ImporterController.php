<?php

namespace App\Http\Controllers\ImportData;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\CurlHelper;
use App\Traits\PeriodHelper;

use App\Models\Data\ImciWasting;
use App\Models\Data\ImciStunting;
use App\Models\Data\ImciCounselling;
use App\Models\Data\ImciMale;
use App\Models\Data\ImciFemale;
use App\Models\Data\ImciWastingPercent;
use App\Models\Data\ImciStuntingPercent;
use App\Models\Data\ImciTotalChild;
use App\Models\Data\ImciExclusiveBreastFeeding;
use App\Models\Data\CcCrAdditionalFoodSuppliment;
use App\Models\Data\CcMrAncIfaDistribution;
use App\Models\Data\CcMrAncNutriCounsel;
use App\Models\Data\CcMrCounsellingAnc;
use App\Models\Data\CcMrWeightInKgAnc;
use App\Models\Data\CcCrExclusiveBreastFeeding;
use App\Models\Data\CcCrTotalMale;
use App\Models\Data\CcCrTotalFemale;
use App\Models\Data\GeoJson;

use App\Models\OrganisationUnit;

use Maatwebsite\Excel\Facades\Excel;

class ImporterController extends Controller
{
    use CurlHelper;
    use PeriodHelper;

    public function import() {
        // dd($period);
    	$data = config('datamodel');
    	$flag = 0;
    	for ($k=0; $k < count($data); $k++) {
    		$currData = $data[$k];
    		// dd($currData);
            $save_array = [];
            $ou = '';
            if($currData['server'] == 'central') {
            	$ou = config('static.centralOrganisation');
            }else if($currData['server'] == 'community') {
            	$ou = config('static.communityOrganisation');
            }

            // $pe = '201803;201804';
            $this->getPeriods();
            // dd($pe);
            $pe = $pe['years_months_string'];
            for($j = 0; $j < count($ou); $j++) {
                $baseUrl = config('static.centralBaseUrl');
                if($currData['server'] == 'central')
                    $baseUrl = config('static.centralBaseUrl');
                else if($currData['server'] == 'community')
                    $baseUrl = config('static.communityBaseUrl');
                $url = $baseUrl.config('static.analyticsEP').'?dimension=dx:'.$currData['api_id'].'&dimension=pe:LAST_MONTH&filter=ou:'.$ou[$j].'&displayProperty=NAME&outputIdScheme=UID&skipData=True';

                $responses = $this->callUrl($url);
                $responses = json_decode($responses);
                
                // dd($url);
                dd($responses);
                $metaData = $responses->metaData;
                
                $co = $metaData->dimensions->co;

                $dx = $currData['api_id'].';';
                if(count($co) > 0) {
                	if($co != 'dCWAvZ8hcrs') {
                		$flag = 1;
	                    for($i = 0; $i < count($co); $i++) {
	                        $dx .= $currData['api_id'].'.'.$co[$i].';';
	                    }
                	}
                }
                // dd($co);
                $dx = rtrim($dx, ';');
                $url = $baseUrl.config('static.analyticsEP').'.json?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&filter=ou:'.$ou[$j].'&displayProperty=NAME&outputIdScheme=UID';
                $responses = $this->callUrl($url);
                $responses = json_decode($responses);
                // dd($url);
                // dd($responses);
                $metaData = $responses->metaData;
                $rows = $responses->rows;
                foreach ($rows as $keyrows => $row) {
                    $unit = [];
                    // $ouId = -1;
                    // if($ou[$j] == 'op5gbhjVCRk') {
                    // 	$orgUnit = OrganizationUnit::where('id','R1GAfTe6Mkb')->first();
                    // 	$ouId = $orgUnit->id;
                    // }
                    $unit['organisation_unit'] = $ou[$j];
                    foreach ($row as $key => $value) {
                        if($key == 0) {
                        	
                        	if($flag == 1) {
	                        	$co = explode('.',$value);
	                        	if(count($co) > 1) {
	                        		$co = $co[1];
	                        		// print_r($co); echo $url.'<br /><br />';
	                        	}else{
	                        		$co = NULL;
	                        	}

                            	// $flag = 0;

                            }else{
                            	$co = NULL;
                            }
                            // $unit['name'] = $metaData->items->$value->name;
                            $unit['category_option_combo'] = $co;
                            
                        }
                        else if($key == 1) {
                            $unit['period'] = $value?:'';
                            $unit['period_name'] = $metaData->items->$value->name;
                        }
                        else if($key == 2) {
                            $unit['value'] = $value;
                        }
                    }
                    $unit['source'] = $currData['source'];
                    $unit['server'] = $currData['server'];
                    $unit['import_date'] = date('Y-m-d');
                    $unit['created_at'] = date('Y-m-d H:i:s');
                    $unit['updated_at'] = date('Y-m-d H:i:s');
                    array_push($save_array,$unit);
                    // dd($save_array);
                }
                    
            }
            // dd($save_array);
            $model = 'App\Models\Data\\'.$currData['model'];
            $model::insert($save_array);
            echo 'ok';
    	}
        dd('done');
    }

    public function scheduleImport() {
        // dd($period);
        $data = config('datamodel');
        $flag = 0;
        for ($k=0; $k < count($data); $k++) {
            $currData = $data[$k];
            // dd($currData);
            $save_array = [];
            $ou = '';
            if($currData['server'] == 'central') {
                $ou = config('static.centralOrganisation');
            }else if($currData['server'] == 'community') {
                $ou = config('static.communityOrganisation');
            }

            $pe = 'LAST_MONTH';
            for($j = 0; $j < count($ou); $j++) {
                $baseUrl = config('static.centralBaseUrl');
                if($currData['server'] == 'central')
                    $baseUrl = config('static.centralBaseUrl');
                else if($currData['server'] == 'community')
                    $baseUrl = config('static.communityBaseUrl');
                $url = $baseUrl.config('static.analyticsEP').'?dimension=dx:'.$currData['api_id'].'&dimension=pe:LAST_MONTH&filter=ou:'.$ou[$j].'&displayProperty=NAME&outputIdScheme=UID&skipData=True';

                $responses = $this->callUrl($url);
                $responses = json_decode($responses);
                
                // dd($url);
                // dd($responses);
                $metaData = $responses->metaData;
                
                $co = $metaData->dimensions->co;

                $dx = $currData['api_id'].';';
                if(count($co) > 0) {
                    if($co != 'dCWAvZ8hcrs') {
                        $flag = 1;
                        for($i = 0; $i < count($co); $i++) {
                            $dx .= $currData['api_id'].'.'.$co[$i].';';
                        }
                    }
                }
                // dd($co);
                $dx = rtrim($dx, ';');
                $url = $baseUrl.config('static.analyticsEP').'.json?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&filter=ou:'.$ou[$j].'&displayProperty=NAME&outputIdScheme=UID';
                $responses = $this->callUrl($url);
                $responses = json_decode($responses);
                // dd($url);
                // dd($responses);
                $metaData = $responses->metaData;
                $rows = $responses->rows;
                foreach ($rows as $keyrows => $row) {
                    $unit = [];
                    // $ouId = -1;
                    // if($ou[$j] == 'op5gbhjVCRk') {
                    //  $orgUnit = OrganizationUnit::where('id','R1GAfTe6Mkb')->first();
                    //  $ouId = $orgUnit->id;
                    // }
                    $unit['organisation_unit'] = $ou[$j];
                    foreach ($row as $key => $value) {
                        if($key == 0) {
                            
                            if($flag == 1) {
                                $co = explode('.',$value);
                                if(count($co) > 1) {
                                    $co = $co[1];
                                    // print_r($co); echo $url.'<br /><br />';
                                }else{
                                    $co = NULL;
                                }

                                // $flag = 0;

                            }else{
                                $co = NULL;
                            }
                            // $unit['name'] = $metaData->items->$value->name;
                            $unit['category_option_combo'] = $co;
                            
                        }
                        else if($key == 1) {
                            $unit['period'] = $value?:'';
                            $unit['period_name'] = $metaData->items->$value->name;
                        }
                        else if($key == 2) {
                            $unit['value'] = $value;
                        }
                    }
                    $unit['source'] = $currData['source'];
                    $unit['server'] = $currData['server'];
                    $unit['import_date'] = date('Y-m-d');
                    $unit['created_at'] = date('Y-m-d H:i:s');
                    $unit['updated_at'] = date('Y-m-d H:i:s');
                    array_push($save_array,$unit);
                    // dd($save_array);
                }
                    
            }
            
            $model = 'App\Models\Data\\'.$currData['model'];
            $model::insert($save_array);
        }
        // dd($save_array);
        dd('done');
    }

    public function mapImport() {
        $baseUrls = array(config('static.centralBaseUrl'), config('static.communityBaseUrl'));
        $url = 'https://communitydhis.mohfw.gov.bd/nationalcc/api/26/geoFeatures.json?ou=ou:dNLjKwsVjod;LEVEL-2&displayProperty=NAME';
        // foreach ($baseUrls as $key => $value) {
        // $url = $value.'geoFeatures.json?ou=ou:dNLjKwsVjod;LEVEL-2&displayProperty=NAME';
        $responses = $this->callUrl($url);
        $responses = json_decode($responses);
        
        $save_array = [];
        // dd($responses);
        foreach ($responses as $key => $value) {
            $unit = [];
            $unit['code'] = $value->code;
            $unit['organisation_unit'] = $value->id;
            $unit['coordinates'] =$value->co;
            $unit['import_date'] = date('Y-m-d H:i:s');
            if((strpos(strtolower($url), 'centraldhis') !== false)) {
                $unit['server'] = 'central';
            }else if((strpos(strtolower($url), 'communitydhis') !== false)) {
                $unit['server'] = 'community';
            }
            $unit['source'] = 'DHIS';
            array_push($save_array, $unit);
        }
        $model = new GeoJson();
        $model::insert($save_array);    
        // }
        // $url = 'https://communitydhis.mohfw.gov.bd/nationalcc/api/26/geoFeatures.json?ou=ou:dNLjKwsVjod;LEVEL-2&displayProperty=NAME';
        // $responses = $this->callUrl($url);
        // $responses = json_decode($responses);
        // dd($responses);
        dd('done');
    }

    public function importDGFPCsv() {
        $address = 'FPMIS2017.xlsx';
        Excel::load($address, function($reader) {
            $results = $reader->get();
            $data = config('datamodel');
            $dataArray['cc_cr_exclusive_breast_feeding'] = [];
            $dataArray['cc_mr_anc_ifa_distribution'] = [];
            $dataArray['imci_counselling'] = [];
            $dataArray['imci_stunting'] = [];
            $dataArray['imci_wasting'] = [];
            foreach ($results as $result) {
                if($result['district'] != '') {
                    if (strpos(strtolower($result['district']), 'district') === false) {
                        $unit = [];
                        $organization = OrganisationUnit::where('name', $result['district'])->first();
                        $ou = $organization->central_api_id;
                        $periods = explode(' ', $result['month']);
                        $pe = $periods[1].$this->getMonth($periods[0]);
                        $source = 'DGFP';
                        $unit['organisation_unit'] = $ou;
                        $unit['category_option_combo'] = NULL;
                        $unit['period'] = $pe;
                        $unit['period_name'] = $result['month'];
                        $unit['source'] = 'DGFP';
                        $unit['server'] = 'central';
                        $unit['import_date'] = date('Y-m-d');
                        $unit['created_at'] = date('Y-m-d H:i:s');
                        $unit['updated_at'] = date('Y-m-d H:i:s');
                        
                        $unit['value'] = $result['counseling_on_iycf_ifa_vitamin_a_hand_washing'];
                        array_push($dataArray['imci_counselling'], $unit);

                        $unit['value'] = $result['received_ifa_pregnant_child_mother'];
                        array_push($dataArray['cc_mr_anc_ifa_distribution'], $unit);

                        $unit['value'] = $result['exclusive_breast_feeding_up_to_6_months'];
                        array_push($dataArray['cc_cr_exclusive_breast_feeding'], $unit);

                        $unit['value'] = $result['identifying_child_stunting'];
                        array_push($dataArray['imci_stunting'], $unit);

                        $unit['value'] = $result['identifying_child_wasting'];
                        array_push($dataArray['imci_wasting'], $unit);
                    }
                }
            }
            
            foreach ($dataArray as $key => $value) {
                for ($i=0; $i < count($data); $i++) { 
                    if($data[$i]['table'] == $key) {
                        $model = 'App\Models\Data\\'.$data[$i]['model'];
                        $model::insert($dataArray[$key]);   
                    }
                }
            }
        });
    }

    private function getMonth($monthName) {
        switch (strtolower($monthName)) {
            case 'january':
                return '01';
                break;

            case 'february':
                return '02';
                break;

            case 'march':
                return '03';
                break;

            case 'april':
                return '04';
                break;

            case 'may':
                return '05';
                break;

            case 'june':
                return '06';
                break;

            case 'july':
                return '07';
                break;

            case 'august':
                return '08';
                break;

            case 'september':
                return '09';
                break;

            case 'october':
                return '10';
                break;

            case 'november':
                return '11';
                break;

            case 'december':
                return '12';
                break;
        }
    }

}