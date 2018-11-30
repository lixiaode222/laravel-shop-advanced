<?php

namespace App\Console\Commands\Cron;

use App\Models\Installment;
use App\Models\InstallmentItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

//计算分期项逾期费
class CalculateInstallmentFine extends Command
{
    //命令名字
    protected $signature = 'cron:calculate-installment-fine';

    //命令描述
    protected $description = '计算分期付款逾期费';

    //命令逻辑
    public function handle()
    {
        //得到要计算逾期费的分期项
        InstallmentItem::query()
                        //预加载分期付款数据，避免N+1问题
                        ->with(['installment'])
                        //分期项对应的分期的状态应该为还款中
                        ->whereHas('installment',function ($query){
                             $query->where('status',Installment::STATUS_REPAYING);
                        })
                        //当前时间要大于还款的时间，怎么逾期了
                        ->where('due_date','<=',Carbon::now())
                        //而且要分期项时未付款的
                        ->whereNull('paid_at')
                        //使用chunkById，避免一次性查询太多记录
                        ->chunkById(1000,function ($items){
                              //遍历查出来逾期的分期项
                              foreach ($items as $item){
                                  //通过Carbon对象的diffInDays直接得到逾期天数
                                  $overdueDays = Carbon::now()->diffInDays($item->due_date);
                                  //本金与手续费之和
                                  $base = big_number($item->base)->add($item->fee)->getValue();
                                  //计算逾期费用
                                  $fine = big_number($base)
                                          ->multiply($overdueDays)
                                          ->multiply($item->installment->fine_rate)
                                          ->divide(100)
                                          ->getValue();
                                  // 避免逾期费高于本金与手续费之和，使用 compareTo 方法来判断
                                  // 如果 $fine 大于 $base，则 compareTo 会返回 1，相等返回 0，小于返回 -1
                                  $fine = big_number($fine)->compareTo($base) === 1 ? $base : $fine ;
                                  //写入数据库
                                  $item->update([
                                      'fine' => $fine,
                                  ]);
                              }
                        });
    }
}
