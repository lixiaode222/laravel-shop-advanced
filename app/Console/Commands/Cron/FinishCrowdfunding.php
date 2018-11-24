<?php

namespace App\Console\Commands\Cron;

use App\Models\CrowdfundingProduct;
use App\Models\Order;
use Carbon\Carbon;
use App\Services\OrderService;
use Illuminate\Console\Command;

//结束众筹的命令
class FinishCrowdfunding extends Command
{
    //命令的名字
    protected $signature = 'cron:finish-crowdfunding';

    //命令描述
    protected $description = '结束众筹';

    //命令逻辑
    public function handle()
    {
        CrowdfundingProduct::query()
            //预加载商品数据
            ->with(['product'])
            //众筹结束世界早于当前时间
            ->where('end_at','<=',Carbon::now())
            //众筹状态为众筹中的
            ->where('status',CrowdfundingProduct::STATUS_FUNDING)
            //得到满足上面条件的众筹商品
            ->get()
            //遍历这些商品
            ->each(function (CrowdfundingProduct $crowdfunding){

                //如果众筹目标金额大于实际众筹金额
                if($crowdfunding->target_amount > $crowdfunding->total_amount){
                     //证明众筹失败，调用众筹失败的逻辑
                    $this->crowdfundingFaild($crowdfunding);
                }else{
                    //不然就是众筹成功，调用众筹成功的逻辑
                    $this->crowdfundingSucceed($crowdfunding);
                }
            });
    }

    //众筹成功的逻辑
    protected function crowdfundingSucceed(CrowdfundingProduct $crowdfunding){

          //只需要将众筹状态改为众筹成功就行
          $crowdfunding->update([
              'status' => CrowdfundingProduct::STATUS_SUCCESS,
          ]);
    }

    //众筹失败的逻辑
    protected function crowdfundingFailed(CrowdfundingProduct $crowdfunding){

         //将众筹逻辑改为众筹失败
        $crowdfunding->update([
            'status' => CrowdfundingProduct::STATUS_FAIL,
        ]);
        //触发失败退款逻辑任务
        dispatch(new RefundCrowdfundingOrders($crowdfunding));
    }
}
