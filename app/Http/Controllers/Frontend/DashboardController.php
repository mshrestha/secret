<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

// Models
use App\Models\Data\BdhsExclusiveBreastfeeding;
use App\Models\Data\BdhsStunting;
use App\Models\Data\BdhsWasting;
use App\Models\Data\CcCrExclusiveBreastFeeding;
use App\Models\Data\CcCrTotalFemale;
use App\Models\Data\CcCrTotalMale;
use App\Models\Data\CcMrTotalPatient;
use App\Models\Data\ImciMale;
use App\Models\Data\ImciFemale;
use App\Models\Data\ImciCounselling;
use App\Models\Data\ImciStuntingPercent;
use App\Models\Data\ImciTotalChild;
use App\Models\Data\ImciWastingPercent;
use App\Models\Data\GeoJson;
use App\Models\OrganisationUnit;
use App\Models\Data\ANC1;
use App\Models\Data\ANC2;
use App\Models\Data\ANC3;
use App\Models\Data\ANC4;
use App\Models\Data\PNC1;
use App\Models\Data\PNC2;
use App\Models\Data\PNC3;
use App\Models\Data\PNC4;
use App\Models\Data\CcMrAncIfaDistribution;
use App\Models\Data\CcMrAncNutriCounsel;

// Traits
use App\Traits\PeriodHelper;
use App\Traits\OrganisationHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
	use PeriodHelper;
	use OrganisationHelper;

	public function indexAction() {
		$organisation_units = OrganisationUnit::where('level', 2)->get();
		$sidebarContents = $this->sidebarContents();
		$outcomes = $this->dashboardImpacts();
		$periods = [ 2018, 2017, 2016 ];
		$bangladesh_ou = ['dNLjKwsVjod', 'dNLjKwsVjod'];
		$current_period = $periods[0];
		$maternal_nutrition_data = [
			'maternal_nutrition_counseling' => floor($this->calculate_Maternal_nutrition_counseling_pergentage($bangladesh_ou, $current_period)),
			'ifa_distribution' => floor($this->calculate_IFA_distribution_percentage($bangladesh_ou, $current_period)),
			'weight_measured' => 0.01,
			'exclusive_breastfeeding' => 0.01
		];

		$child_nutrition_data = [
			'iycf_counselling' => floor($this->calculate_IYCF_counselling_percentage($bangladesh_ou, $current_period)),
			'supplements_distributed' => 0.01,
			'child_growth_monitoring' => 0.01,
			'minimum_acceptable_diet' => 0.01
		];

		return view('frontend.dashboard.index', compact('sidebarContents', 'outcomes', 'organisation_units', 'periods', 'maternal_nutrition_data', 'child_nutrition_data'));
	}

	public function ajaxCircularChart(Request $request) {
		$organisation_unit = explode('.', $request->organisation_unit);
		if($request->organisation_unit == 'R1GAfTe6Mkb.op5gbhjVCRk') {
			$maternal_nutrition_data = [
				'maternal_nutrition_counseling' => 0.01,
				'ifa_distribution' => 0.01,
				'weight_measured' => 0.01,
				'exclusive_breastfeeding' => 0.01
			];

			$child_nutrition_data = [
				'iycf_counselling' => 0.01,
				'supplements_distributed' => 0.01,
				'child_growth_monitoring' => 0.01,
				'minimum_acceptable_diet' => 0.01
			];
		} else {
			$maternal_nutrition_data = [
				'maternal_nutrition_counseling' => floor($this->calculate_Maternal_nutrition_counseling_pergentage($organisation_unit, $request->period)),
				'ifa_distribution' => floor($this->calculate_IFA_distribution_percentage($organisation_unit, $request->period)),
				'weight_measured' =>  0.01,
				// 'exclusive_breastfeeding' => 0
			];

			$child_nutrition_data = [
				'iycf_counselling' => floor($this->calculate_IYCF_counselling_percentage($organisation_unit, $request->period)),
				// 'supplements_distributed' => 0,
				'child_growth_monitoring' =>  0.01,
				// 'minimum_acceptable_diet' => 0
			];
		}
		// dd($maternal_nutrition_data);
		return [$maternal_nutrition_data, $child_nutrition_data];
	}

	public function calculate_IYCF_counselling_percentage($organisation_unit, $period) {
		//DHIS iycf counselling calculation
		//Numerator : Kazi-Central -> IMCI Counseling
		//Denominator : Kazi-Central->IMCI Male + IMCI Female
		
		$imci_male = ImciMale::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		$imci_female = ImciFemale::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		$imci_counselling = ImciCounselling::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();

		$dhis_numerator = $imci_counselling->value;
		$dhis_denominator = $imci_male->value + $imci_female->value;

		//DGFP IYCF counselling calculation
		//Numerator -> Counseling on IYCF, IFA,Vitamin-A & Hand washing
		//Denominator -> ANC1+ANC2+ANC3+ANC4 + PNC 1+ PNC 2+ PNC 3 + PNC4
		
		$iycf_counselling = ImciCounselling::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc1 = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc2 = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc3 = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc4 = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc1 = PNC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc2 = PNC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc3 = PNC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc4 = PNC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = $iycf_counselling;
		$dgfp_denominator = $anc1+$anc2+$anc3+$anc4+$pnc1+$pnc2+$pnc3+$pnc4;

		$iycf_counselling_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return $iycf_counselling_percent;
	}

	public function calculate_IFA_distribution_percentage($organisation_unit, $period) {
		//DHIS calculation
		//Numerator -> Kazi-Comm->cc_MR_ANC_IFA_Distribution
		//Denominator -> Kazi->comm-> cc_MR_ANC_1+2+3+4 
		
		$cc_mr_anc_ifa_distribution = CcMrAncIfaDistribution::where('organisation_unit', $organisation_unit[0])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		
		$anc1_dghs = ANC1::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc2_dghs = ANC2::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc3_dghs = ANC3::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc4_dghs = ANC4::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();

		$dhis_numerator = $cc_mr_anc_ifa_distribution->value;
		$dhis_denominator = $anc1_dghs->value + $anc2_dghs->value + $anc3_dghs->value + $anc4_dghs->value;


		//DGFP calculation
		//Numerator -> Number of Pregnant Woman received IFA 
		//Denominator -> ANC1 + ANC2 + ANC3 + ANC4
		$number_of_pregnant_woman_received_ifa = CcMrAncIfaDistribution::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc1_dgfp = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc2_dgfp = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc3_dgfp = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc4_dgfp = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = $number_of_pregnant_woman_received_ifa;
		$dgfp_denominator = $anc1_dgfp + $anc2_dgfp + $anc3_dgfp + $anc4_dgfp;

		$ifa_distribution_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return $ifa_distribution_percent;
	}

	public function calculate_Maternal_nutrition_counseling_pergentage($organisation_unit, $period) {
		// Dhis formula
		// Numerator: kazi->comm->cc_MR_ANC_Nutri_counsel
		// Denominator : Kazi->comm-> cc_MR_ANC_1+2+3+4
		
		$cc_mr_anc_nutri_counsel = CcMrAncNutriCounsel::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc1_dghs = ANC1::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc2_dghs = ANC2::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc3_dghs = ANC3::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc4_dghs = ANC4::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();

		$dhis_calculate = ($cc_mr_anc_nutri_counsel->value / ($anc1_dghs->value + $anc2_dghs->value + $anc3_dghs->value + $anc4_dghs->value)) * 100;

		$dhis_numerator = $cc_mr_anc_nutri_counsel->value;
		$dhis_denominator = $anc1_dghs->value + $anc2_dghs->value + $anc3_dghs->value + $anc4_dghs->value;

		// numerator = 0
		// denominator = ANC1+ANC2+ANC3+ANC4+PNC1+PNC2+PNC3+PNC4

		$period = $this->yearly_months($period);
		$anc1_dgfp = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$anc2_dgfp = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$anc3_dgfp = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$anc4_dgfp = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$pnc1_dgfp = PNC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$pnc2_dgfp = PNC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$pnc3_dgfp = PNC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$pnc4_dgfp = PNC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = 0;
		$dgfp_denominator = $anc1_dgfp + $anc2_dgfp + $anc3_dgfp + $anc4_dgfp + $pnc1_dgfp + $pnc2_dgfp + $pnc3_dgfp + $pnc4_dgfp;

		$maternal_nutrition_counselling_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return floor($maternal_nutrition_counselling_percent);
	}

	public function calculateMonthlyPercentage($data, $periodData, $ou) {
		$model = 'App\Models\Data\\' . $data['model'];
		$last_month = $model::where('organisation_unit', $ou)->orderBy('period', 'desc')->whereNull('category_option_combo')->first();
		$yearly = $model::where('organisation_unit', $ou)->where('period', date('Y'))->whereNull('category_option_combo')->orderBy('period', 'desc')->first();

		$percent = ($last_month->value/$yearly->value) * 100;

		$all_periods = $model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('period');
		$all_values = $model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('value');
		$month = $last_month->period_name;

		return compact('percent', 'all_periods', 'all_values', 'month');
	}

	public function dashboardImpacts() {
		$indicators = [
			'Stunting' => 'BdhsStunting',
			'Wasting' => 'BdhsWasting',
			'Anemia' => 'BdhsAnemia',
			'Exclusive Breastfeeding' => 'BdhsExclusiveBreastfeeding',
			'Vitamin A Supplements' => 'BdhsVitaminA',
			'Women Underweight' => 'BdhsBmi'
			
		];

		$dataSet = [];

		foreach($indicators as $indicator => $indicatorName) {
			$counter = 0;
			$dataSet[$indicator] = [];
			
				$ou = 'dNLjKwsVjod';
				
				$goal_model = 'App\Models\Data\\'.$indicators[$indicator];
				$datum = $goal_model::whereNull('organisation_unit')->orderBy('period', 'asc')->get();
				
				$datum_goal = $goal_model::whereNull('organisation_unit')->orderBy('period', 'desc')->first();
				// dd($datum_goal);
				$dataSet[$indicator]['title'] = $indicator;
				$dataSet[$indicator]['periods'] = $datum->pluck('period');
				$dataSet[$indicator]['goal_period'] = $datum_goal->period;
				$dataSet[$indicator]['goal_values'] = $datum_goal->value;
				$dataSet[$indicator]['min'] = 0;//$goal_model::min('value');
				$dataSet[$indicator]['max'] = 100;//m$goal_model::max('value');
				$dataSet[$indicator]['values'] = $datum->pluck('value');
				if($indicators[$indicator] == 'BdhsStunting') {
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 25;
					$dataSet[$indicator]['goal'] = 'Target goal 25% by 2021';
					$dataSet[$indicator]['direction'] = -1;
					$dataSet[$indicator]['goal_text'] = "Reduce stunting in children under-5 years from 36.1% (BDHS 2014) to 25 % by 2021";
					$dataSet[$indicator]['min'] = 100;//$goal_model::min('value');
					$dataSet[$indicator]['max'] = 0;//m$goal_model::max('value');
					$dataSet[$indicator]['id'] = 'Stunting';
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];

				}
				else if($indicators[$indicator] == 'BdhsWasting') {
					$dataSet[$indicator]['goal'] = 'Target goal < 10% by 2021';
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 10;
					$dataSet[$indicator]['direction'] = -1;
					$dataSet[$indicator]['goal_text'] = "Reduce wasting in children under-5 years";
					$dataSet[$indicator]['min'] = 100;//$goal_model::min('value');
					$dataSet[$indicator]['max'] = 0;//m$goal_model::max('value');
					$dataSet[$indicator]['id'] = 'Wasting';
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];
				}
				else if($indicators[$indicator] == 'BdhsAnemia') {
					$dataSet[$indicator]['goal'] = 'Target goal 25% by 2021';
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 25;
					$dataSet[$indicator]['direction'] = -1;
					$dataSet[$indicator]['goal_text'] = "Prevalence of Anemia in Women of Reproductive age";
					$dataSet[$indicator]['min'] = 100;//$goal_model::min('value');
					$dataSet[$indicator]['max'] = 0;//m$goal_model::max('value');
					$dataSet[$indicator]['id'] = 'Anemia';
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];
				}else if($indicators[$indicator] == 'BdhsVitaminA') {
					$dataSet[$indicator]['goal'] = 'Maintain goal of 99% by 2021';
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 1;
					$dataSet[$indicator]['direction'] = 1;
					$dataSet[$indicator]['goal_text'] = "Two-dosage coverage for 2016: 99%";
					$dataSet[$indicator]['min'] = 100;//$goal_model::min('value');
					$dataSet[$indicator]['max'] = 0;//m$goal_model::max('value');
					$dataSet[$indicator]['id'] = 'VitaminA';
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];
				}else if($indicators[$indicator] == 'BdhsBmi') {
					$dataSet[$indicator]['goal'] = 'Target goal 15% by 2021';
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 19;
					$dataSet[$indicator]['direction'] = 1;
					$dataSet[$indicator]['goal_text'] = "Prevalence of Women of Reproductive Age with BMI < 18.5";
					$dataSet[$indicator]['min'] = 100;//$goal_model::min('value');
					$dataSet[$indicator]['max'] = 0;//m$goal_model::max('value');
					$dataSet[$indicator]['id'] = 'Women-Underweighed';
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];
				}
				else {
					$dataSet[$indicator]['goal'] = 'Target goal 65% by 2021';
					$dataSet[$indicator]['direction'] = 1;
					$dataSet[$indicator]['limit'] = 100;
					$dataSet[$indicator]['target'] = 35;
					$dataSet[$indicator]['goal_text'] = "Increase prevalence of exclusive breastfeeding";
					$dataSet[$indicator]['incomplete'] = 100 - $dataSet[$indicator]['goal_values'];
					$dataSet[$indicator]['id'] = 'Exclusive-Breastfeeding';
				}
		}
		// dd($dataSet);

		return $dataSet;
	}

	public function getGeoJsons() {
		$geoJsons = GeoJson::all();

		$organisations = $geoJsons->pluck('organisation_unit');
		$coordinates = $geoJsons->pluck('coordinates');
		// dd(count($coordinates));
		$organisationsArray = [];
		foreach ($organisations as $key => $value) {
			$orgName = OrganisationUnit::where('central_api_id', $value)->first();
			$organisationsArray[$orgName->name]['key'] = $orgName->central_api_id.'-'.$orgName->community_api_id;
			$organisationsArray[$orgName->name]['coordinates'] = json_decode($coordinates[$key]);
		}
		// dd($organisationsArray['Barisal Division']);
		return $organisationsArray;
	}

	public function getPercentTrend(Request $request) {
		$data = $request->ids;
		$ids = explode("-", $data);
		$ou = '';
		$periods = $this->getPeriodYears();
		$flipped_period = array_flip($periods);

		$periodData = '';
		foreach ($flipped_period as $key => $value) {
			$periodData .= $value.';';
		}
		
		$periodData = rtrim($periodData, ';');
		$periodData = explode(";", $periodData);
		sort($periodData);
		$returnArr['child'] = $this->getChildPercent($periodData, $ids[0], $ids[1]);
		$returnArr['maternal'] = $this->getMaternalPercent($periodData, $ids[0], $ids[1]);
		return $returnArr;
	}

	public function sidebarContents() {
		$sidebarContents = [
			'Training' => 
			[
				'title' => 'Departments that have trained health workers on CCTN with P4P',
				'items' => [
					[
						'image' => 'training.svg',
						'percent' => "65%",
						'text' => 'Health workers trained'
					],
					[
						'image' => 'training-check.svg',
						'percent' => "65%",
						'text' => 'Health workers who succeeded'
					]
				]
			],
			'Quality Assessment' => 
			[
				'title' => 'Quality Control & Supportive Supervision',
				'items' => [
					[
						'image' => 'qa.svg',
						'percent' => "65%",
						'text' => 'Facilities receiving SS&M'
					],
					[
						'image' => 'qa-child.svg',
						'percent' => "65%",
						'text' => 'Facilities providing quality IYCF/Maternal counselling'
					],
					[
						'image' => 'qa-paper.svg',
						'percent' => "65%",
						'text' => 'Facilities providing quality Nut reporting'
					]
				]
			],
			'Supply Management' => 
			[
				'title' => 'Ensure adequate nutrition supplies to facilites',
				'items' => [
					[
						'image' => 'supplement.svg',
						'percent' => "65%",
						'text' => 'Facilities requiring IFA tablets'
					],
					[
						'image' => 'supplement-paper.svg',
						'percent' => "65%",
						'text' => 'Facilities requiring counselling materials'
					],
					[
						'image' => 'supplement-tape.svg',
						'percent' => "65%",
						'text' => 'Facilities requiring MUAC tapes'
					],
					[
						'image' => 'supplement-weight.svg',
						'percent' => "65%",
						'text' => 'Facilities requiring Scales / height board'
					],
					[
						'image' => 'supplement-bottle.svg',
						'percent' => "65%",
						'text' => 'Facilities requiring F-75 and F-100 therapeutic feeding'
					]
				]
			]
		];

		return $sidebarContents;
	}

	public function calculate_Maternal_nutrition_counseling_proportion_pergentage($organisation_unit, $period) {
		//Dhis formula
		// Numerator: kazi->comm->cc_MR_ANC_Nutri_counsel
		// Denominator : Kazi->comm-> cc_MR_ANC_1+2+3+4
		
		$cc_mr_anc_nutri_counsel = CcMrAncNutriCounsel::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$cc_val = 0;
		if($cc_mr_anc_nutri_counsel != null)
			$cc_val = $cc_mr_anc_nutri_counsel->value;
		$anc1_dghs = ANC1::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc1_val = 1;
		if($anc1_dghs != null)
			$anc1_val = $anc1_dghs->value;
		$anc2_dghs = ANC2::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc2_val = 1;
		if($anc2_dghs != null)
			$anc2_val = $anc2_dghs->value;
		$anc3_dghs = ANC3::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc3_val = 1;
		if($anc3_dghs != null)
			$anc3_val = $anc3_dghs->value;
		$anc4_dghs = ANC4::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc4_val = 1;
		if($anc4_dghs != null)
			$anc4_val = $anc4_dghs->value;
		$dhis_calculate = ($cc_val / ($anc1_val + $anc2_val + $anc3_val + $anc4_val)) * 100;
		$dhis_numerator = $cc_val;
		$dhis_denominator = ($anc1_val + $anc2_val + $anc3_val + $anc4_val);
		
		$period = $this->yearly_months('2018');
		$anc1_dgfp = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$anc2_dgfp = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$anc3_dgfp = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$anc4_dgfp = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[0])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$pnc1_dgfp = PNC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');
		$pnc2_dgfp = PNC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$pnc3_dgfp = PNC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$pnc4_dgfp = PNC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->whereIn('period', $period)
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = 0;
		$dgfp_denominator = $anc1_dgfp + $anc2_dgfp + $anc3_dgfp + $anc4_dgfp + $pnc1_dgfp + $pnc2_dgfp + $pnc3_dgfp + $pnc4_dgfp;

		$maternal_nutrition_counselling_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return floor($maternal_nutrition_counselling_percent);
	}

	public function calculate_IFA_distribution_proportion_percentage($organisation_unit, $period) {
		//DHIS calculation
		//Numerator -> Kazi-Comm->cc_MR_ANC_IFA_Distribution
		//Denominator -> Kazi->comm-> cc_MR_ANC_1+2+3+4 
		
		$cc_mr_anc_ifa_distribution = CcMrAncIfaDistribution::where('organisation_unit', $organisation_unit[0])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		
		$anc1_dghs = ANC1::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc1_dghs_val = 1;
		if($anc1_dghs != null)
			$anc1_dghs_val = $anc1_dghs->value;
		$anc2_dghs = ANC2::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc2_dghs_val = 0;
		if($anc2_dghs != null)
			$anc2_dghs_val = $anc2_dghs->value;
		$anc3_dghs = ANC3::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc3_dghs_val = 0;
		if($anc3_dghs != null)
			$anc3_dghs_val = $anc3_dghs->value;
		$anc4_dghs = ANC4::where('source', 'DGHS')
					->where('organisation_unit', $organisation_unit[0])
					->where('period', $period)
					->whereNull('category_option_combo')
					->first();
		$anc4_dghs_val = 0;
		if($anc4_dghs != null)
			$anc4_dghs_val = $anc4_dghs->value;
		$dhgs_val = 0;
		if($cc_mr_anc_ifa_distribution != null)
			$dhgs_val = $cc_mr_anc_ifa_distribution->value;
		$dhis_numerator = $dhgs_val;
		$dhis_denominator = $anc1_dghs_val + $anc2_dghs_val + $anc3_dghs_val + $anc4_dghs_val;


		//DGFP calculation
		//Numerator -> Number of Pregnant Woman received IFA 
		//Denominator -> ANC1 + ANC2 + ANC3 + ANC4
		$number_of_pregnant_woman_received_ifa = CcMrAncIfaDistribution::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc1_dgfp = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc2_dgfp = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc3_dgfp = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc4_dgfp = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = $number_of_pregnant_woman_received_ifa;
		$dgfp_denominator = $anc1_dgfp + $anc2_dgfp + $anc3_dgfp + $anc4_dgfp;

		$ifa_distribution_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return $ifa_distribution_percent;
	}

	public function calculate_IYCF_counselling_proportion_percentage($organisation_unit, $period) {
		//DHIS iycf counselling calculation
		//Numerator : Kazi-Central -> IMCI Counseling
		//Denominator : Kazi-Central->IMCI Male + IMCI Female
		
		$imci_male = ImciMale::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		$imci_female = ImciFemale::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		$imci_counselling = ImciCounselling::where('organisation_unit', $organisation_unit[1])
						->whereNull('category_option_combo')
						->where('source', 'DGHS')
						->where('period', $period)->first();
		$dhis_numerator = $imci_counselling->value;
		$dhis_denominator = $imci_male->value + $imci_female->value;

		//DGFP IYCF counselling calculation
		//Numerator -> Counseling on IYCF, IFA,Vitamin-A & Hand washing
		//Denominator -> ANC1+ANC2+ANC3+ANC4 + PNC 1+ PNC 2+ PNC 3 + PNC4
		$period = '2018';
		$iycf_counselling = ImciCounselling::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc1 = ANC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc2 = ANC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc3 = ANC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$anc4 = ANC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc1 = PNC1::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc2 = PNC2::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc3 = PNC3::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');
		$pnc4 = PNC4::where('source', 'DGFP')
					->where('organisation_unit', $organisation_unit[1])
					->where('period', 'LIKE', '%' . $period . '%')
					->whereNull('category_option_combo')
					->sum('value');

		$dgfp_numerator = $iycf_counselling;
		$dgfp_denominator = $anc1+$anc2+$anc3+$anc4+$pnc1+$pnc2+$pnc3+$pnc4;

		$iycf_counselling_percent = (($dhis_numerator + $dgfp_numerator) / ($dhis_denominator + $dgfp_denominator)) * 100;

		return $iycf_counselling_percent;
	}

	public function getMapData(Request $request) {
		$districts = ['xNcsJeRMUCM' => 'Barguna' , 'uOU0jtyD1PZ' => 'Barisal' , 'EdOWA8sKh2p' => 'Bhola' , 'WNCBZLbFD70' => 'Jhalokati', 'bEiL5HnmKZO' => 'Patuakhali', 'aLbPgj33QnT' => 'Pirojpur'];
		$data = $request->all();
		$datamodel = config('datamodel');
		$model = 'App\Models\Data\\' . $data['model'];
		$server = 'central';
		for ($i=0; $i < count($datamodel); $i++) { 
			if($datamodel[$i]['model'] == $data['model'])
				$server = $datamodel[$i]['server'];
		}
		// $pe = date('Y').date('m', strtotime('-1 month'));
		$flag = 1;
		if($data['model'] == 'BdhsWasting' || $data['model'] == 'BdhsStunting' || $data['model'] == 'BdhsBmi') {
			$pe = '2014';
		}
		else if($data['model'] == 'BdhsAnemia') {
			$pe = '2011';
		}
		else {
			$pe = '201804';
			$flag = 0;
		}
		
		if($flag == 1) {
			$organisations = $this->getOrganisationDivisons($server);
		} else {
			$organisations = $this->getOrganisations($server);
		}
		// dd($organisations);
		$category = NULL;
		if($data['model'] == 'CcMrWeightInKgAnc')
			$category = 'OJd05AWCFTk';
				
		$responseData = [];
		if($data['model'] == 'CcMrAncNutriCounsel') {
			// dd(count($organisations['organisation_unit_array']));
			for ($i=0; $i < count($organisations['organisation_unit_array']); $i++) { 
				$response = $this->calculate_Maternal_nutrition_counseling_proportion_pergentage([$organisations['organisation_unit_array'][$i], $organisations['organisation_unit_array'][$i]], '201804');
				if($response == '' || $response == null) {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = 0;
				}
				else {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = (string)$response;
				}
			}
		} 
		else if($data['model'] == 'CcMrAncIfaDistribution') {
			// dd(count($organisations['organisation_unit_array']));
			for ($i=0; $i < count($organisations['organisation_unit_array']); $i++) { 
				$response = $this->calculate_IFA_distribution_proportion_percentage([$organisations['organisation_unit_array'][$i], $organisations['organisation_unit_array'][$i]], '201804');
				if($response == '' || $response == null) {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = 0;
				}
				else {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = (string)$response;
				}
			}
		} 
		else if($data['model'] == 'ImciCounselling') {
			// dd(count($organisations['organisation_unit_array']));
			for ($i=0; $i < count($organisations['organisation_unit_array']); $i++) { 
				if($organisations['organisation_unit_array'][$i] == 'op5gbhjVCRk') {
					$organisations['organisation_unit_array'] = 'R1GAfTe6Mkb';
				}
				$response = $this->calculate_IYCF_counselling_proportion_percentage([$organisations['organisation_unit_array'][$i], $organisations['organisation_unit_array'][$i]], '201804');
				if($response == '' || $response == null) {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = 0;
				}
				else {
					$responseData[$i]['organisation_unit'] = $organisations['organisation_unit_array'][$i];
					$responseData[$i]['value'] = (string)$response;
				}
			}
		}
		else {
			$responseData = $model::whereIn('organisation_unit', $organisations['organisation_unit_array'])->where('period', $pe)->where('category_option_combo', $category)->get();
		}
		// dd($responseData);
		$centArr = [];
		$sum = 0;
		$centralDistricts = (config('static'))['centDistrict'];
		// dd($centralDistricts);
		$foundArr = [];
		$districtMinimal = [];
		for ($i=0; $i < count($centralDistricts); $i++) { 
			for ($j=0; $j < count($responseData); $j++) { 
				if($responseData[$j]['organisation_unit'] == $centralDistricts[$i]) {
					$districtMinimal[$districts[$centralDistricts[$i]]] = $responseData[$j]['value'];
					array_push($centArr, $responseData[$j]['value']);
					$sum += $responseData[$j]['value'];
				}
			}
			array_push($foundArr, $centralDistricts[$i]);
		}
		
		asort($districtMinimal);
		// $districtMinimal = array_flip($districtMinimal);

		// ksort($districtMinimal);
		// $districtMinimal = array_flip($districtMinimal);

		$dataArr = [];
		$valueArr = [];
		$val = 0;

		for ($i=0; $i < count($foundArr) ; $i++) { 
			$dataArr[$foundArr[$i]] = 0;
		}

		for ($i=0; $i < count($responseData); $i++) { 
			if($responseData[$i]['organisation_unit'] == 'mykF7AaZv9R') {
				if($sum != 0) {
					if($data['model'] == 'CcMrAncIfaDistribution' || $data['model'] == 'CcMrAncNutriCounsel') {
						$responseData[$i]['value'] = $sum / 6;
						$val = $responseData[$i]['value'];
					} else {
						$responseData[$i]['value'] = $sum / 6;
						$val = $responseData[$i]['value'];
					}
				}
			}
			$dataArr[$responseData[$i]['organisation_unit']] = $responseData[$i]['value'];
			if($responseData[$i]['organisation_unit'] != 'dNLjKwsVjod')
				array_push($valueArr, $responseData[$i]['value']);
		}
		
		$ranges = [];
		if($data['model'] == 'BdhsStunting') {
			$ranges = array('min'=>0, 'q1' => 20, 'q2' => 29, 'q3' => 39, 'max' => 100);
		} else if($data['model'] == 'BdhsWasting') {
			$ranges = array('min'=>0, 'q1' => 5, 'q2' => 9, 'q3' => 14, 'max' => 100);
		} else if($data['model'] == 'BdhsAnemia') {
			$ranges = array('min'=>0, 'q1' => 5, 'q2' => 19.9, 'q3' => 39.9, 'max' => 100);
		} else {
			$ranges = $this->getFourRanges($valueArr); 
		}

		$districtRanges = [];
		if($data['model'] != 'BdhsStunting' && $data['model'] != 'BdhsWasting' && $data['model'] != 'BdhsAnemia') {
			if($data['model'] == 'CcMrAncNutriCounsel' || $data['model'] == 'CcMrAncIfaDistribution') {
				$districtRanges = array('min'=>0, 'q1' => 39.9, 'q2' => 59.9, 'q3' => 80, 'max' => 100);
			} else {
				$districtRanges = $this->getFourRanges($centArr);
			}
		}
		$emptydistricts = false;
		// dd($districtRanges);
		if(count($districtRanges) <=0 )
			$emptydistricts = true;
		// dd($districtRanges);
		$text = 'People reached: ';
		$reverse = false;
		if(strcasecmp($data['model'], 'BdhsStunting') == 0){
			$text = 'Severity of Stunting by Prevalance: ';
			$reverse = true;
		} else if(strcasecmp($data['model'], 'BdhsWasting') == 0){
			$text = 'Severity of Wasting by Prevalance: ';
			$reverse = true;
		} else if(strcasecmp($data['model'], 'CcCrExclusiveBreastFeeding') == 0){
			$text = 'Children breastfed: ';
			$reverse = true;
		} else if(strcasecmp($data['model'], 'BdhsAnemia') == 0){
			$text = 'Severity of Anemia by Prevalance: ';
			$reverse = true;
		}

		// dd($districtRanges);
		// dd($districtMinimal);
		if(count($valueArr) <= 0){
			return array(
				'dataExists' => false,
			);
		}else{
			return array(
				'dataExists' => true,
				'modelData' => $responseData,
				'minimalData' => $dataArr,
				'server' => $server,
				'ranges' => $ranges,
				'districtRanges' => $districtRanges,
				'text' => $text,
				'emptydistricts' => $emptydistricts,
				'reverse' => $reverse,
				'minimalDistrict' => $districtMinimal
			);
		}
	}

	private function getChildPercent($periodData, $central_api_id, $community_api_id) {

		$data = config('data.child');
		
		// IMCI total children
		if($data['iycf_counselling'][0]['server'] == 'central') {
			$ou = $central_api_id;
		}else{
			$ou = $community_api_id;
		}
		$counselling_data = $this->calculateMonthlyPercentage($data['iycf_counselling'][0], $periodData, $ou);
		$counselling_percent = $counselling_data['percent'];
		$counselling_all_values = $counselling_data['all_values'];
		$counselling_all_periods = $counselling_data['all_periods'];
		$counselling_month_child = $counselling_data['month'];

		
		// Vitamin A supplimentation
		if($data['vitamin_a_supplementation'][0]['server'] == 'central') {
			$ou = $central_api_id;
		}else{
			$ou = $community_api_id;
		}
		$vitamin_a_supplimentation_data = $this->calculateMonthlyPercentage($data['vitamin_a_supplementation'][0], $periodData, $ou);
		$vitamin_a_supplementation_percent = $vitamin_a_supplimentation_data['percent'];
		$vitamin_a_supplementation_all_values = $vitamin_a_supplimentation_data['all_values'];
		$vitamin_a_supplementation_all_periods = $vitamin_a_supplimentation_data['all_periods'];
		$vitamin_a_supplementation_month = $vitamin_a_supplimentation_data['month'];

		$child_trend_analysis = [
			[
				'name' => 'IMCI Counselling',
				'title' => 'IMCI Counselling',
				'month' => 'IYCF counselling - '. $counselling_month_child,
				'percent' => round($counselling_percent),
				'periods' => $counselling_all_periods,
				'values' => $counselling_all_values,
				'labels' => (['IMCI Counselling given '. $counselling_month_child, 'IMCI Counselling yearly']),
			],
			// [
			// 	'name' => 'Child Growth',
			// 	'month' => 'Child growth monitoring',
			// 	'percent' => round($plw_who_receive_ifas_percent),
			// 	'periods' => $plw_who_receive_ifas_all_periods,
			// 	'values' => $plw_who_receive_ifas_all_values
			// ],
			[
				'name' => 'Supplements',
				'title' => 'Food Supplementation',
				'month' => 'Food Supplementation - '. $vitamin_a_supplementation_month,
				'percent' => round($vitamin_a_supplementation_percent),
				'periods' => $vitamin_a_supplementation_all_periods,
				'values' => $vitamin_a_supplementation_all_values,
				'labels' => (['Food Supplementation in '. $vitamin_a_supplementation_month, 'Food Supplementation yearly']),
			],
		];
		return $child_trend_analysis;
	}

	private function getMaternalPercent($periodData, $central_api_id, $community_api_id) {


		$data = config('data.maternal');
		$total_patient_last_month = CcMrTotalPatient::orderBy('period', 'desc')->where('organisation_unit', 'dNLjKwsVjod')->first();

		//Maternal counselling percentage
		$counselling_data = $data['maternal_counselling'][0];
		if($counselling_data['server'] == 'central') {
			$ou = $central_api_id;
		} else {
			$ou = $community_api_id;
		}
		$counselling_model = 'App\Models\Data\\' . $counselling_data['model'];
		$counselling_last_month = $counselling_model::where('organisation_unit', $ou)->orderBy('period', 'desc')->first();
		$counselling_percent = ($counselling_last_month->value/$total_patient_last_month->value) * 100;
		$counselling_all_periods = $counselling_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('period');
		$counselling_all_values = $counselling_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('value');
		$counselling_month_maternal = $counselling_last_month->period_name;

		//Plw who receive ifas
		$plw_who_receive_ifas_data = $data['plw_who_receive_ifas'][0];
		if($plw_who_receive_ifas_data['server'] == 'central') {
			$ou = $central_api_id;
		} else {
			$ou = $community_api_id;
		}
		$plw_who_receive_ifas_model = 'App\Models\Data\\' . $plw_who_receive_ifas_data['model'];
		$plw_who_receive_ifas_last_month = $plw_who_receive_ifas_model::where('organisation_unit', $ou)->orderBy('period', 'desc')->first();
		
		$plw_who_receive_ifas_percent = ($plw_who_receive_ifas_last_month->value/$total_patient_last_month->value) * 100;
		$plw_who_receive_ifas_all_periods = $plw_who_receive_ifas_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('period');
		$plw_who_receive_ifas_all_values = $plw_who_receive_ifas_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->whereNull('category_option_combo')->orderBy('period', 'asc')->pluck('value');
		$plw_who_receive_ifas_month = $plw_who_receive_ifas_last_month->period_name;

		//Pregnant women weighed
		$pregnant_women_weighed_data = $data['pregnant_women_weighed'][0];
		if($pregnant_women_weighed_data['server'] == 'central') {
			$ou = $central_api_id;
		} else {
			$ou = $community_api_id;
		}
		$pregnant_women_weighed_model = 'App\Models\Data\\' . $pregnant_women_weighed_data['model'];
		$pregnant_women_weighed_last_month = $pregnant_women_weighed_model::where('period', $total_patient_last_month->period)->where('organisation_unit', $ou)->orderBy('period', 'desc')->first();
		$pregnant_women_weighed_yearly = $pregnant_women_weighed_model::where('period', date('Y'))->where('organisation_unit', $ou)->orderBy('period', 'desc')->first();
		$pregnant_women_weighed_percent = ($pregnant_women_weighed_last_month->value/$pregnant_women_weighed_yearly->value) * 100;
		$pregnant_women_weighed_all_periods = $pregnant_women_weighed_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->orderBy('period', 'asc')->pluck('period');
		$pregnant_women_weighed_all_values = $pregnant_women_weighed_model::whereIn('period', $periodData)->where('organisation_unit', $ou)->orderBy('period', 'asc')->pluck('value');
		$pregnant_women_weighed_month = $pregnant_women_weighed_last_month->period_name;

		
		

		$maternal_trend_analysis = [
			[
				'name' => 'Counselling',
				'month' => 'Maternal Counselling Given - '. $counselling_month_maternal,
				'percent' => round($counselling_percent),
				'periods' => $counselling_all_periods,
				'values' => $counselling_all_values,
				'title' => 'Counselling',
				'labels' => (['Maternal Counselling Given '.$counselling_month_maternal, 'Total patient ' .$counselling_month_maternal]),
			],
			[
				'name' => 'IFA Distribution',
				'month' => 'PLW who receive IFA\'s - '. $plw_who_receive_ifas_month,
				'percent' => round($plw_who_receive_ifas_percent),
				'periods' => $plw_who_receive_ifas_all_periods,
				'values' => $plw_who_receive_ifas_all_values,
				'title' => 'IFA Distribution',
				'labels' => (['PLW who receive IFA\'s in '. $plw_who_receive_ifas_month, 'Total patient '.$plw_who_receive_ifas_month]),
			],
			[
				'name' => 'Weight Measurement',
				'month' => 'Pregnant women weighed - ' . $pregnant_women_weighed_month,
				'percent' => round($pregnant_women_weighed_percent),
				'periods' => $pregnant_women_weighed_all_periods,
				'values' => $pregnant_women_weighed_all_values,
				'title' => 'Weight Measurement',
				'labels' => (['Pregnant women weighed in ' .$pregnant_women_weighed_month, 'Pregnant women weighed yearly']),
			],
		];

		return $maternal_trend_analysis;
	}

	private function getThreeRanges($valueArray) {
		if(count($valueArray) > 0) {
			$min = min($valueArray);
			$max = max($valueArray);
			$step = ($max - $min) / 3;
			$q1 = $min + $step;
			$q2 = $max - $step;
			return array(
				'min' => (int)$min,
				'max' => (int)$max,
				'q1' => (int)$q1,
				'q2' => (int)$q2,
			);
		}else{
			return [];
		}
	}

	private function getFourRanges($valueArray) {
		if(count($valueArray) > 0) {
			$min = min($valueArray);
			$max = max($valueArray);
			$step = ($max - $min) / 4;
			$q1 = $min + $step;
			$q2 = $q1 + $step;
			$q3 = $max - $step;
			return array(
				'min' => (int)$min,
				'max' => (int)$max,
				'q1' => (int)$q1,
				'q2' => (int)$q2,
				'q3' => (int)$q3,
			);
		}else{
			return [];
		}
	}
}
