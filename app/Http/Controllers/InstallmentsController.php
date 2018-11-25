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

    //分期详情页面
    public function show(Installment $installment){

        $this->authorize('own', $installment);

        //取出分期付款中的所有还款计划，并按还款顺序排序
        $items = $installment->items()->orderBy('sequence')->get();

        return view('installments.show',[
              'installment' => $installment,
              'items'       => $items,
               //下一个要还的分期   就是没付款的第一个
            'nextItem'    => $items->where('paid_at', null)->first(),
        ]);
    }
}
