<?php 

namespace App\Traits;

trait PeriodHelper
{
  public function getPeriods() {
    $least_year = 2014;
    $least_year_num = 14;

    $current_year = date('Y');
    $current_year_num = substr($current_year,-2);

    $current_month = date('m');
    $current_year_month = $current_year . $current_month;

    $years_months = array();
    $years_months_string = null;

    for ($year=14; $year <= $current_year_num; $year++) { 
      $years_months["20".$year] = [];
      $years_months_string .= '20'. $year . ';';

      if($year !== (int)$current_year_num) {
        for ($month = 1; $month <= 12; $month++) { 
          (strlen($month) < 2) ? $month = 0 . $month : $month;
          array_push($years_months["20".$year], '20'.$year.$month);
          $years_months_string .= '20'.$year.$month. ';';
        }
      } else {
        for ($month = 1; $month <= $current_month; $month++) { 
          (strlen($month) < 2) ? $month = 0 . $month : $month;
          array_push($years_months["20".$year], '20'.$year.$month);
          $years_months_string .= '20'.$year.$month. ';';
        }
      }
    }
    
    $years_months_string = trim($years_months_string, ';');

    return array('periods'=>$years_months, 'years_months_string' => $years_months_string);
  }

  public function getPeriodYears() {
    $periods = $this->getPeriods();
    
  }
}

