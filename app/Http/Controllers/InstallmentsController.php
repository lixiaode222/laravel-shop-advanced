<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    //分期列表页面
    public function index(Request $request){

        $installments = Installment::query()
                      //找到用户自己的分期项
                      ->where('user_id',$request->user()->id)
                      ->paginate(10);

        return view('installments.index',compact('installments'));
    }
}
