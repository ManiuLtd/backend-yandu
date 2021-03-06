<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\ProjectOrder
 *
 * @property int $id
 * @property int $merchant_id 付款商户id
 * @property int $project_id 付款项目id
 * @property string $order_no 订单号
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $worker_id 工人id
 * @property int $status 状态:0未合作，1合作
 * @property int $pr_mer_id 项目所属用户id
 * @property int $partA_status 甲方项目状态
 * @property int $partB_status 乙方项目状态
 * @property string|null $remark 备注
 * @property-read \App\Models\OrderMerchant $orderMerchant
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereOrderNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder wherePartAStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder wherePartBStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder wherePrMerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectOrder whereWorkerId($value)
 * @mixin \Eloquent
 */
class ProjectOrder extends Model
{

    protected $table = 'project_order';


    public function orderMerchant()
    {
        return $this->hasOne('App\Models\OrderMerchant','order_num','order_no');
    }
}