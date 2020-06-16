<?php

namespace App\Http\Controllers;


use App\Category;
use App\Department;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Category Data
             */
            $category = Category::onlyTrashed()
                ->where('category_id', $id)
                ->first();
            if ($category) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'category' => $category,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Trashed Category found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Category Data
             */
            $paginate = $request->query('paginate');
            $page = $request->query('page');

            if (!$paginate) $paginate = 10;
            if (!$page) $page = 0;
            if ($page == 1) $page = 0;
            $offset = (int) $paginate * $page;


            $total = Category::onlyTrashed()->count();
            $hasNext = ($total - ($offset + $paginate)) > 0;

            $categories = Category::onlyTrashed()->get()->skip($offset)->take($paginate);
            $response = null;
            if ($categories->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'categories' => $categories,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "No Trashed Category found";
            }
            return ResponseHelper($response);
        }
    }

    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Category Data
             */
            $category = Category::where('category_id', $id)
                ->first();
            if ($category) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'category' => $category,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Category found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Category Data
             */
            $paginate = $request->query('paginate');
            $page = $request->query('page');

            if (!$paginate) $paginate = 10;
            if (!$page) $page = 0;
            if ($page == 1) $page = 0;
            $offset = (int) $paginate * $page;


            $total = Category::all()->count();
            $hasNext = ($total - ($offset + $paginate)) > 0;

            $categories = Category::all()->skip($offset)->take($paginate);
            $response = null;
            if ($categories->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'categories' => $categories,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "No Category found";
            }
            return ResponseHelper($response);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories',
            'description' => 'string',
            'department_id' => 'required|string|exists:departments,department_id'
        ]);

        $category = new Category([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $category->save();
        $category->category_id = sha1('Category' . $category->id);
        $category->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Category Created Successfully";
        return ResponseHelper($response);
    }

    function Delete(Request $request, $id)
    {
        $validator = Validator::make(
            ['category_id' => $id],
            ['category_id' => 'required|exists:categories,category_id']
        );

        if ($validator) {
            $category = Category::where('category_id', $id)->first();
            if ($category) {
                $category->deleted_by_user_id = $request->user()->user_id;
                $category->active = false;
                $category->save();
                $category->delete();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Category Deleted Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Category found';
                return ResponseHelper($response);
            }
        }
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['category_id' => $id],
            ['category_id' => 'required|exists:categories,category_id']
        );

        if ($validator) {
            $category = Category::onlyTrashed()->where('category_id', $id)->first();
            if ($category) {
                $category->restore();
                $category->deleted_by_user_id = null;
                $category->save();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Category Restored Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Category found';
                return ResponseHelper($response);
            }
        }
    }


    function Update(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:categories,category_id',
            'field' => ['required', 'string', Rule::in(Category::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $category = Category::where('category_id', $request->id)->first();

        if ($category) {
            if ($request->field === 'department_id') {
                $department = Department::where('department_id', $request->value)->first();
                if (!$department) {
                    $response = [
                        "message" => "The given data was invalid.",
                        "errors" => [
                            "department_id" => [
                                "The selected field is invalid."
                            ]
                        ]
                    ];
                    return response($response, 422);
                }
            }

            $category->{$request->field} = $request->value;
            $category->modified_by_user_id = $request->user()->user_id;
            $category->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Category has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Category found';
            return ResponseHelper($response);
        }
    }
}
