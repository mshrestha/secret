<?php

namespace App\Http\Controllers\Helper;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Traits\CurlHelper;

class DataElementController extends Controller
{
  use CurlHelper;

  public function getDataElements() {
  	$url = config('static.centralBaseUrl').config('static.datasetEP').'/'.config('static.centralDataSet')[1].'.json?paging=false&fields=dataSetElements[dataElement[name,id]]';
  	$responses = $this->callUrl($url);
  	$responses = json_decode($responses);
  	$dataElements = [];
  	$dataSetElements = $responses->dataSetElements;
  	sort($dataSetElements);
  	for ($i=0; $i < count($dataSetElements); $i++) { 
  		$dataElement = $dataSetElements[$i]->dataElement;
  		// $dataElements[$i]['id'] = $dataElement->id;
  		// $dataElements[$i]['name'] = $dataElement->name;
  		$dataElements[$dataElement->id] = $dataElement->name;
  	}
  	return array('programmes'=>$dataElements);
  }

  public function getDataElementsAll() {
    $dataElements = [];
    $dataSets = array_merge(config('static.centralDataSet'),config('static.communityDataSet'));
    // dd($dataSets);
    for ($j=0; $j < count($dataSets); $j++) { 
      if($j < 2)
        $url = config('static.centralBaseUrl').config('static.datasetEP').'/'.$dataSets[$j].'.json?paging=false&fields=name,dataSetElements[dataElement[name,id]]';
      else
        $url = config('static.communityBaseUrl').config('static.datasetEP').'/'.$dataSets[$j].'.json?paging=false&fields=name,dataSetElements[dataElement[name,id]]';
      $responses = $this->callUrl($url);
      $responses = json_decode($responses);
      
      
      $dataSetElements = $responses->dataSetElements;
      $currDataSet = $responses->name;
      // print_r($dataSetElements);
      $dataElements[$currDataSet] = [];
      sort($dataSetElements);
      for ($i=0; $i < count($dataSetElements); $i++) { 
        $dataElement = $dataSetElements[$i]->dataElement;
        // $dataElements[$i]['id'] = $dataElement->id;
        // $dataElements[$i]['name'] = $dataElement->name;
        $dataElements[$currDataSet][$dataElement->id] = $dataElement->name;
      }
      // print_r($dataElements);
    }
    dd($dataElements);
    return array('programmes'=>$dataElements);
  }

  // public function getDataElementsAll() {
  //   $dx0 = '';
  //   $dx1 = '';
  //   $maternalDataElements = config('static.maternalDataElements');
  //   $childDataElements = config('static.childDataElements');
  //   foreach ($maternalDataElements as $key => $value) {
  //     if($value == 0) {
  //       $dx0 .= $key.';';
  //     } else if($value == 1) {
  //       $dx1 .= $key.';';
  //     }
  //   }

  //   foreach ($childDataElements as $key => $value) {
  //     if($value == 0) {
  //       $dx0 .= $key.';';
  //     } else if($value == 1) {
  //       $dx1 .= $key.';';
  //     }
  //   }
    
  //   $dx0 = rtrim($dx0, ';');
  //   $dx1 = rtrim($dx1, ';');
  //   $dv0 = $this->getDataValues($dx0, 0);
  //   $dv1 = $this->getDataValues($dx1, 1);
    
  // }

  public function getDataElementsMaternal() {
    $dataElements = [];
    $dataDiction = [];
    $dataSets = config('static.maternalDataElements');
    $dx0 = '';
    $dx1 = '';
    foreach ($dataSets as $key => $value) {
      if($value == 1) {
        $dx1 .= $value.';';
      }else if($value == 0) {
        $dx0 .= $value.';';
      }
    }
      $url = config('static.centralBaseUrl').config('static.dataValueEP').'?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&dimension=ou:'.$platformDiction['division'].'&displayProperty=NAME';
    // dd($url);
    $responses = $this->callUrl($url);
    $responses = json_decode($responses);
    $dataValues = $responses->dataValues;
    $this->getDataValues($dataElements);
    return array('programmesJoint'=>$dataElements, 'programmes'=>$dataDiction);
  }

  public function getDataElementsChildren() {
    $dataElements = [];
    // $dataSetsMaternal = array_merge(config('static.centralDataSet')[0],config('static.communityDataSet')[0]);
    $dataSets = array(config('static.centralDataSet')[1],config('static.communityDataSet')[1]);
    // dd($dataSets);
    for ($j=0; $j < count($dataSets); $j++) { 
      $baseUrl = 0;
      if($j < 1){
        $baseUrl = 0;
        $url = config('static.centralBaseUrl').config('static.datasetEP').'/'.$dataSets[$j].'.json?paging=false&fields=name,dataSetElements[dataElement[name,id]]';
      }
      else{
        $baseUrl = 1;
        $url = config('static.communityBaseUrl').config('static.datasetEP').'/'.$dataSets[$j].'.json?paging=false&fields=name,dataSetElements[dataElement[name,id]]';
      }
      $responses = $this->callUrl($url);
      $responses = json_decode($responses);

      $dataSetElements = $responses->dataSetElements;
      $currDataSet = $responses->name;
      // $dataElements[$currDataSet] = [];
      sort($dataSetElements);
      for ($i=0; $i < count($dataSetElements); $i++) { 
        $dataElement = $dataSetElements[$i]->dataElement;
        $dataElementName = str_replace('_', " ", $dataElement->name);
        if((strpos(strtolower($dataElementName), 'counsel') !== false)) {
          $dataElements['Counsel'][] = $dataElement->id.'-'.$baseUrl;
          $dataDiction[$dataElement->id] = $dataElement->name;
        }else if((strpos(strtolower($dataElementName), 'growth') !== false)) {
          $dataElements['Growth'][] = $dataElement->id.'-'.$baseUrl;
          $dataDiction[$dataElement->id] = $dataElement->name;
        }else if((strpos(strtolower($dataElementName), 'suppliment') !== false)) {
          $dataElements['Suppliment'][] = $dataElement->id.'-'.$baseUrl;
          $dataDiction[$dataElement->id] = $dataElement->name;
        }
        // $dataElements[$currDataSet][$dataElement->id] = $dataElement->name;
      }
    }
    $this->getDataValues($dataElements);
    return array('programmesJoint'=>$dataElements, 'programmes'=>$dataDiction);
  }


  public function getDataElementsJoint() {
    $dataElements = [];
    $dataDiction = [];
    for ($j=0; $j < count(config('static.centralDataSet')); $j++) { 
      $url = config('static.centralBaseUrl').config('static.datasetEP').'/'.config('static.centralDataSet')[$j].'.json?paging=false&fields=dataSetElements[dataElement[name,id]]';
      $responses = $this->callUrl($url);
      $responses = json_decode($responses);
      
      // var_dump($url);
      $dataSetElements = $responses->dataSetElements;
      sort($dataSetElements);
      for ($i=0; $i < count($dataSetElements); $i++) { 
        $dataElement = $dataSetElements[$i]->dataElement;
        
        if((strpos(strtolower($dataElement->name), 'underweight') !== false)) {
          $dataElements['Underweight'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'wasting') !== false)) {
          $dataElements['Wasting'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'stunting') !== false)){
          $dataElements['Stunting'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'low birth') !== false)){
          $dataElements['Low Birth Weight'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'breast') !== false)){
          $dataElements['Breast Feed within 1 hour of birth'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'anemia') !== false)){
          $dataElements['Anemia'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'iodine') !== false)){
          $dataElements['Iodine'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        } else if ((strpos(strtolower($dataElement->name), 'injury') !== false)){
          $dataElements['Injury'][] = $dataElement->id;
          $dataDiction[$dataElement->id] = $dataElement->name;
        }
        // $dataElements[$i]['id'] = $dataElement->id;
        // $dataElements[$i]['name'] = $dataElement->name;

      }
    }
    // dd($dataDiction);
    return array('programmesJoint'=>$dataElements, 'programmes'=>$dataDiction);
  }


  public function getDataElementCategory(Request $request) {
  	$data = $request->all();
  	$dataElement = $data['dataElement'];
  	$url = config('static.centralBaseUrl').config('static.dataelementEP').'/'.$dataElement.'.json';
  	$responses = $this->callUrl($url);
  	$responses = json_decode($responses);
  	// dd($responses->categoryCombo);
  	// if($responses.has('categoryCombo')) {
  		$catetegoryCombo = $responses->categoryCombo;
	  	// dd($catetegoryCombo);
	  	$newUrl = config('static.centralBaseUrl').config('static.categoryComboEP').'/'.$catetegoryCombo->id.'.json?fields=:all,categoryOptionCombos[id,name]';
	  	$responses1 = $this->callUrl($newUrl);
	  	$responses1 = json_decode($responses1);
	  	if(isset($responses1->categoryOptionCombos)) {
		  	$catetegoryOptionCombos = $responses1->categoryOptionCombos;
		  	$affectedArr = [];
		  	for ($i=0; $i < count($catetegoryOptionCombos); $i++) { 
		  		// $affectedArrs[$i]['id'] = $catetegoryOptionCombos[$i]->id;
		  		// $affectedArrs[$i]['name'] = $catetegoryOptionCombos[$i]->name;
          
		  		$affectedArrs[$catetegoryOptionCombos[$i]->id] = $catetegoryOptionCombos[$i]->name;
		  	}
		  	// usort($affectedArrs, $this->build_sorter('name'));
		  	return array('exists'=>'true', 'affectedArrs' => $affectedArrs);
	  	}else {
	  		return array('exists'=>false);
	  	}
  }


  public function getDataElementCategoryJoint(Request $request) {
    $data = $request->all();
    $dataElement = $data['dataElement'];
    $splitdataElements = explode(',',$dataElement);
    for ($j=0; $j < count($splitdataElements); $j++) { 
      $url = config('static.centralBaseUrl').config('static.dataelementEP').'/'.$splitdataElements[$j].'.json';
      $responses = $this->callUrl($url);
      $responses = json_decode($responses);
      // dd($responses->categoryCombo);
      // if($responses.has('categoryCombo')) {
      $catetegoryCombo = $responses->categoryCombo;
      // dd($catetegoryCombo);
      $newUrl = config('static.centralBaseUrl').config('static.categoryComboEP').'/'.$catetegoryCombo->id.'.json?fields=:all,categoryOptionCombos[id,name]';
      $responses1 = $this->callUrl($newUrl);
      $responses1 = json_decode($responses1);
      if(isset($responses1->categoryOptionCombos)) {
        $catetegoryOptionCombos = $responses1->categoryOptionCombos;
        $affectedArr = [];
        for ($i=0; $i < count($catetegoryOptionCombos); $i++) { 
          // $affectedArrs[$i]['id'] = $catetegoryOptionCombos[$i]->id;
          // $affectedArrs[$i]['name'] = $catetegoryOptionCombos[$i]->name;
          if($catetegoryOptionCombos[$i]->name !='' ) {
            $affectedArrs[$catetegoryOptionCombos[$i]->id] = $catetegoryOptionCombos[$i]->name;
          }
        }
        // usort($affectedArrs, $this->build_sorter('name'));
        // dd($affectedArrs);
        return array('exists'=>'true', 'affectedArrs' => $affectedArrs);
      }else {
        return array('exists'=>false);
      } 
    } 
  }

  

  function build_sorter($key) {
    return function ($a, $b) use ($key) {
      return strnatcmp($a[$key], $b[$key]);
    };
  }

  public function getDataValues($dx, $server) {
    $baseUrl = '';
    if($server == 0) {
      $baseUrl = config('static.centralBaseUrl');
    } else if($server == 1) {
      $baseUrl = config('static.communityBaseUrl');
    }
    $pe = 'LAST_MONTH';
    $ou = 'dNLjKwsVjod';
    $url = $baseUrl.config('static.dataValueEP').'?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&dimension=ou:'.$ou.'&displayProperty=NAME';
    $responses = $this->callUrl($url);
    $responses = json_decode($responses);
    dd($responses);
  }

  public function getDataValueSet(Request $request) {
  	$data = $request->all();
  	$platformDiction = $data['platformDiction'];
  	
  	$dx = '';
  	if($platformDiction['affected'] == -1)
  		$dx = $platformDiction['programme'];
  	else
  		$dx = $platformDiction['programme'].'.'.$platformDiction['affected'];

  	$pe = 'LAST_MONTH';
  	if(strcasecmp('last_month', $platformDiction['period']) == 0 || strcasecmp('last_6_month', $platformDiction['period']) == 0 ) {
  		$pe = $platformDiction['period'];
  	}else{
  		$platformDiction['period'] = str_replace(',', ';', $platformDiction['period']);
  		$pe = $platformDiction['period'];
  	}
  	$url = config('static.centralBaseUrl').config('static.dataValueEP').'?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&dimension=ou:'.$platformDiction['division'].'&displayProperty=NAME';
  	// dd($url);
  	$responses = $this->callUrl($url);
  	$responses = json_decode($responses);
  	$dataValues = $responses->dataValues;
  	return array('dataValueSets'=>$responses);
  }

  public function getDataValueSetJoint(Request $request) {
    $data = $request->all();
    $platformDiction = $data['platformDiction'];
    
    $dx = '';
    $programmes = explode(",", $platformDiction['programme']);
    // dd($programmes);
    if($platformDiction['affected'] == -1)
      for ($i=0; $i < count($programmes); $i++) { 
        if($i == count($programmes)-1)
          $dx .= $programmes[$i];
        else
          $dx .= $programmes[$i].';';
      }
    else
      for ($i=0; $i < count($programmes); $i++) { 
        if($i == count($programmes) -1)
          $dx .= $programmes[$i].'.'.$platformDiction['affected'];
        else
          $dx .= $programmes[$i].'.'.$platformDiction['affected'].';';
      }

    $pe = 'LAST_MONTH';
    if(strcasecmp('last_month', $platformDiction['period']) == 0 || strcasecmp('last_6_month', $platformDiction['period']) == 0 ) {
      $pe = $platformDiction['period'];
    }else{
      $platformDiction['period'] = str_replace(',', ';', $platformDiction['period']);
      $pe = $platformDiction['period'];
    }
    $url = config('static.centralBaseUrl').config('static.dataValueEP').'?dimension=dx:'.$dx.'&dimension=pe:'.$pe.'&dimension=ou:'.$platformDiction['division'].'&displayProperty=NAME';
    // dd($url);
    $responses = $this->callUrl($url);
    $responses = json_decode($responses);
    $dataValues = $responses->dataValues;
    
    $periods = [];
    $values = [];
    // dd($dataValues);
    for($i = 0; $i < count($dataValues); $i++) {
    $temp = $dataValues[$i];
      for($j = $i + 1; $j < count($dataValues); $j++) {
        if(strcasecmp($dataValues[$i]->period, $dataValues[$j]->period) > 0){
          $temp = $dataValues[$i];
          $dataValues[$i] = $dataValues[$j];
          $dataValues[$j] = $temp;
        }
      }
    }//end for
    // dd($dataValues);
    for ($i=0; $i < count($dataValues); $i++) { 
      $currData = $dataValues[$i];
      // dd($currData);
      if(!in_array($currData->period, $periods)) {
        array_push($periods, $currData->period);
      }
      $values[$currData->dataElement][] = $currData;
    }
    
    sort($periods);
    // dd($values);
    return array('periods'=>$periods, 'dataValueSets'=>$values);
  }

  function sortByPeriod($x, $y) {
    return $x['period'] - $y['period'];
  }

                                
}