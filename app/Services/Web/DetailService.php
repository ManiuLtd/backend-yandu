<?php


namespace App\Services\Web;

use App\Models\ProjectOrder;
use App\Models\Worker;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Web\WeChatPayController;
use App\Models\Merchant;
use App\Models\OrderLog;
use App\Models\ProjectMerchant;
use App\Models\Project;

class DetailService
{

    /**
     * 获取商户信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMerchantInfo(Request $request)
    {
        $articleId  = $request->input('article_id');
        $merchantId = $request->input('merchant_id');
        $project    = Project::whereId($articleId)->first();
        $model       = ProjectOrder::whereMerchantId($merchantId)->whereProjectId($articleId)->wherePayStatus(1)->first();
        if ($model){
            return response()->json(['code'=>422,'message'=>'已经是意向商户']);
        }
        if ($project->merchant_id == $merchantId){
            return response()->json(['code'=>422,'message'=>'该项目为您自己发布，不能成为该项目意向商户']);
        }else{
            $merchantModel = Merchant::whereId($merchantId)->first();
            $worker = $merchantModel->workers()->get();
            if ($worker->count() < $project->people_num){
                return response()->json(['code'=>422,'message'=>'您商户下的施工人数小于该项目的用工最低人数']);
            }
            $data = $this->getData($merchantModel);
            $data['cash_deposit'] = exchangeToYuan($project->cash_deposit);
            $data['merchant_id']  = $merchantModel->id;
            $data['project_id']   = $project->id;
            return response()->json($data);
        }

    }

    public function getData($data)
    {
        $arr = [];
        $arr['code'] = 200;
        $arr['merchant_name'] = $data->company;
        $arr['worker']        = $this->getWorker($data->workers()->get());
        return $arr;
    }

    /**
     * 获取工人信息
     * @param $data
     * @return array
     */
    public function getWorker($data)
    {
        $arr = [];
        foreach ($data as $k){
            $arr[] =   "<div class=\"custom-control custom-control-alternative custom-checkbox col-md-3 \">".
                "<input class=\"custom-control-input\" name='worker[]' id=\" customCheck".$k->id."\" type=\"checkbox\" value='".$k->id."'>".
                "<label class=\"custom-control-label\" for=\" customCheck".$k->id."\">".
                "<span class=\"text-muted\">".$k->name."</span>".
                "</label>".
                "</div>";
        }
        return $arr;
    }


    /**
     * @param Request $request
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIntention(Request $request)
    {
        $projectId   = $request->input('project_id');
        $merchantId  = $request->input('merchant_id');
        $cashDeposit = $request->input('cash_deposit');
        $worker      = $request->input('worker');
        $project    = Project::whereId($projectId)->first();
        $model       = ProjectOrder::whereMerchantId($merchantId)->whereProjectId($projectId)->wherePayStatus(1)->first();
        if ($model){
            return response()->json(['message'=>'您已经是意向商户'],403);
        }
        if (empty($worker)){
            return response()->json(['message'=>'施工人元数量不能为空，请从新选择'],403);
        }
        if (count($worker) < $project->people_num){
            return response()->json(['message'=>'您提交施工人员小于项目最低要求，请从新选择'],403);
        }
        $checkOrder = ProjectOrder::whereMerchantId( \Auth::guard('admin')->user()->id)
            ->whereProjectId($projectId)
            ->wherePayStatus(0)
            ->where('created_at','>',date('Y-m-d H:i:s',time()-60*60) )
            ->first();
        if ($checkOrder){
            $qrCodePath = 'uploads/image/qrcode/order/' . $checkOrder->id . '.png';
            $data['qrcode'] = url($qrCodePath);
            return $data;
        }
        $orderId  = $this->newOrderStore($projectId,$cashDeposit,$worker);
        $orderCode= $this->qr($orderId);
        return $orderCode;

    }

    /**
     * 创建订单
     * @param $projectId
     * @param $cashDeposit
     * @param $worker
     * @return int
     */
    public function newOrderStore($projectId,$cashDeposit,$worker)
    {
        $model = new ProjectOrder();
        $model->merchant_id     = \Auth::guard('admin')->user()->id;
        $model->project_id      = $projectId;
        $model->money           = exchangeToFen($cashDeposit) ;
        $model->order_no        = date('YmdHis') . rand(10000, 99999);
        $model->channel         = 'WEB';
        $model->refund_trade_no = 0;
        $model->pay_status      = 0;
        $model->worker_id       = json_encode($worker);
        $model->save();
        OrderLog::addLog($model->id, '意向商户押金', \Auth::guard('admin')->user()->id);
        return $model->id;
    }

    /**
     * 获取微信支付二维码
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function qr($id)
    {
        $app = new WeChatPayController();
        $order = ProjectOrder::find($id);
        $result = $app->weChatPay()->order->unify([
            'body' => '缴纳项目保证金',
            'out_trade_no' => $order->order_no,
            'total_fee' =>  $order->money,
            'notify_url' => url('api/notify/order/2'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'NATIVE', // 请对应换成你的支付方式对应的值类型
        ]);

        $qrCodePath = 'uploads/image/qrcode/order/' . $id . '.png';
        QrCode::format('png')->size(300)->generate($result['code_url'], public_path($qrCodePath));

        $data['qrcode'] = url($qrCodePath);
        $data['out_trade_no'] = $order->order_no;
        return $data;
    }

    /**
     * 获取意向商户
     * @param $id
     * @return array|bool
     */
    public function getIntentionMerchant($id)
    {
        $inMerModel = ProjectOrder::whereProjectId($id)->wherePayStatus(1)->get();
        if (!$inMerModel){
            return false;
        }
        $data=[];
        foreach ($inMerModel as $item) {
            $i = [];
            $i['merchant_name'] = getMerchantName($item->merchant_id);
            $i['merchant_id']   = $item->merchant_id;
            $i['workers']       = $this->getWorkers(json_decode($item->worker_id));
            $data[] = $i;
        }

        return $data;
    }



    //获取意向商户在本项目中的施工人员
    public function getWorkers(array $id)
    {

        $data = [];
        foreach ($id as $item){
            $i =[];
            $workerModel      = Worker::whereId(intval($item))->first();
            $i['worker_name'] = $workerModel->name;
            $i['worker_id']   = $workerModel->id;
            $data[] =$i;
        }
        return $data;
    }

}