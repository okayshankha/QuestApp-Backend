<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Statistics extends Controller
{
    function DashboardOverview(Request $request)
    {
        $_overview = [
            'Students' => [
                'count' => 250,
                'activity_rate_change' => '+9.5%', //Tally the count of this month with previous month
                'most_active_of_the_month' => 'June 10',
                'help' => 'Total number of Students in the system. Activity Rate Change of previous month with current month is shown with \'%\' ratio',
                'activity' => [1, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30]  //Student activity per day
            ],
            'Departments' => [
                'count' => 30,
                'activity_rate_change' => '+9.5%', //Tally the count of this month with previous month
                'most_active_of_the_month' => 'Computer Science',
                'help' => 'Total number of Departments in the system. Activity Rate Change of previous month with current month is shown with \'%\' ratio',
                'activity' => [1, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30]  //Department wise student or teacher activity per day
            ],
            'Examinations' => [
                'count' => 85,
                'activity_rate_change' => '-2.5%', //Tally the count of this month with previous month
                'most_active_of_the_month' => 'Class Assesment on C++',
                'help' => 'Total number of Examinations in the system. Activity Rate Change of previous month with current month is shown with \'%\' ratio',
                'activity' => [1, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 39, 30, 2, 5, 20, 4, 9, 30]  //Exam obtained per day
            ],
            'My_Contribution' => [
                'count' => 85,
                'activity_rate_change' => '+2.5%', //Tally the count of this month with previous month
                'most_active_of_the_month' => 'June 10',
                'help' => 'Total number of Contribution(self) in the system. Activity Rate Change of previous month with current month is shown with \'%\' ratio',
                'activity' => [1, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30, 2, 5, 20, 4, 9, 30]  //Exam obtained per day
            ],
        ];

        $overview = config('QuestApp.JsonResponse.success');
        $overview['data']['message'] = $_overview;
        return ResponseHelper($overview);
    }

    function NameAvailability(Request $request, $entity, $value)
    {
        Validator::extend('not_exists', function ($attribute, $value, $parameters) {
            return DB::table($parameters[0])
                ->where($parameters[1], '=', $value)
                ->andWhere($parameters[2], '<>', $value)
                ->count() < 1;
        });


        $validator = Validator::make(
            ['department_name' => $value],
            ['department_name' => 'required|not_exists:departments,name']
        );
    }
}
