<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OrganisationUnit;
use App\Traits\PeriodHelper;

class OutcomeController extends Controller
{
	use PeriodHelper;
	public function indexAction() {
		$organisation_units = OrganisationUnit::where('level', 2)->get();
		$periods = $this->getPeriods();
    $periods = $periods['periods'];
    $periodArr = [];
    foreach ($periods as $key => $value) {
    	$periodArr[$key] = '';
    	for ($i=0; $i < count($periods[$key]); $i++) { 
    		$periodArr[$key] .= $periods[$key][$i].';';
    	}
    	$periodArr[$key] = rtrim($periodArr[$key], ';');
    }
    dd($periodArr);
		$trend_analysis = [
			[
				'name' => 'Counseling',
				'month' => 'Counseling Given - April',
				'percent' => '80',
			],
			[
				'name' => 'IFA Distribution',
				'month' => 'IFA Distributed - April',
				'percent' => '50',
			],
			[
				'name' => 'Weight Measurement',
				'month' => 'Weight gained - April',
				'percent' => '60',
			],
		];

		return view('frontend.outcome.index', compact('trend_analysis', 'organisation_units'));
	}
}