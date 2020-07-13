<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function CalculatePaginationData($total, $page, $pagelength)
    {
        $defaults = [
            'pagelength' => 5,
            'page' => 1,
            'totalpagecount' => 0,
            'currentpagecount' => 1
        ];


        $output = [
            'pagelength' => 0,
            'offset' => 0,
            'hasNext' => 0,
        ];

        // Set Default Values
        if (!$pagelength) {
            $pagelength = $defaults['pagelength'];
        } else {
            $pagelength = (int) $pagelength;
            if ($pagelength <= 0) {
                $pagelength = $defaults['pagelength'];
            }
        }

        if (!$page) {
            $page = $defaults['page'];
        } else {
            $page = (int) $page;
            if ($page <= 0) {
                $page = $defaults['page'];
            }
        }


        $offset = $pagelength * ($page - 1);

        $output['pagelength'] = $pagelength;
        $output['offset'] = $offset;
        $output['hasNext'] = (($offset + $pagelength) >= $total) ? 0 : 1;
        $output['totalpagecount'] = (($total % $pagelength) <= 0) ? (int) ($total / $pagelength) : ((int) ($total / $pagelength) + 1);
        $output['currentpagecount'] = $page;



        return $output;
    }

    protected function FetchPagedRecords($Model, $options)
    {
        $page = array_key_exists('page', $options) ? $options['page'] : null;
        $pagelength = array_key_exists('pagelength', $options) ? $options['pagelength'] : null;
        $trashOnly = array_key_exists('trashOnly', $options) ? $options['trashOnly'] : false;
        $total = array_key_exists('total', $options) ? $options['total'] : null;


        if (!$trashOnly) {
            $total = $Model::all()->count();
        } else {
            $total = $Model::onlyTrashed()->count();
        }

        $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

        $pagelength =  $CalculatePaginationData['pagelength'];
        $offset = $CalculatePaginationData['offset'];
        $hasNext = $CalculatePaginationData['hasNext'];
        $totalpagecount = $CalculatePaginationData['totalpagecount'];
        $currentpagecount = $CalculatePaginationData['currentpagecount'];

        $records = null;
        if (!$trashOnly) {
            $records = $Model::all()->skip($offset)->take($pagelength);
        } else {
            $records = $Model::onlyTrashed()->get()->skip($offset)->take($pagelength);
        }

        if ($offset > 0) {
            $_records = [];
            foreach ($records as $record) {
                $_records[] = $record;
            }
            $records = $_records;
        }

        // if (get_class(new $Model) === 'App\Department') {
        //     $userLevels = config('QuestApp.UserLevels');
        //     $index = 0;
        //     foreach ($records as $record) {
        //         $hod = null;
        //         if ($record->hod_user_id) {
        //             $hod = User::where('user_id', $record->hod_user_id)->where('role', $userLevels['f'])->first();
        //         }
        //         $records[$index++] = array_merge($record->toArray(), ['hod' => $hod]);
        //     }
        // }

        $response = null;
        if (!count($records)) {
            $response = config('QuestApp.JsonResponse.404');
            if (!$trashOnly) {
                $response['data']['message'] = "No Record found";
            } else {
                $response['data']['message'] = "No Trashed Record found";
            }
        } else {
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = [
                'hasnext' => $hasNext,
                'currentpagecount' => $currentpagecount,
                'totalpagecount' => $totalpagecount,
                'records' => $records,
            ];
        }

        return $response;
    }
}
