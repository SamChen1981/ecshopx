<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;
use app\api\library\Token;

class AccountLog extends BaseModel
{
    protected $table = 'account_log';

    public $timestamps = false;

    protected $appends = ['id', 'change', 'reason', 'created_at', 'status'];

    protected $visible = ['id', 'change', 'reason', 'created_at', 'status'];


    public static function getPayPointsList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $status = isset($status) ? $status : 0;

        switch ($status) {
            case 0:
                $model = AccountLog::where('pay_points', '<>', 0)->where('user_id', $uid);
                break;

            case 1:
                $model = AccountLog::where('pay_points', '>', 0)->where('user_id', $uid);
                break;

            case 2:
                $model = AccountLog::where('pay_points', '<', 0)->where('user_id', $uid);
                break;

            default:
                return self::formatError(self::NOT_FOUND);
        }

        $total = $model->count();
        $data = $model->orderBy('change_time', 'DESC')
            ->paginate($per_page)
            ->toArray();

        return self::formatBody(['history' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }


    /**
     * 记录帐户变动
     * @param float $user_money 可用余额变动
     * @param float $frozen_money 冻结余额变动
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型：系统
     * @return  boolean
     */
    public static function logAccountChange($user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = 99, $uid = false)
    {
        if (!$uid) {
            $uid = Token::authorization();
        }

        $flag = 0;
        /* 更新用户信息 */
        if ($member = Member::where('user_id', $uid)->first()) {
            $member->user_money += $user_money;
            $member->frozen_money += $frozen_money;
            $member->rank_points += $rank_points;
            $member->pay_points += $pay_points;
            $flag = $member->save();
        }

        if ($flag) {
            /* 插入帐户变动记录 */
            $model = new AccountLog;
            $model->user_id = $uid;
            $model->pay_points = $pay_points;
            $model->change_desc = $change_desc;
            $model->user_money = $user_money;
            $model->rank_points = $rank_points;
            $model->frozen_money = $frozen_money;
            $model->change_type = $change_type;
            $model->change_time = time();

            if ($model->save()) {
                return true;
            }
        }
        return false;
    }

    public function getIdAttribute()
    {
        return $this->attributes['log_id'];
    }

    public function getChangeAttribute()
    {
        return $this->attributes['pay_points'];
    }

    public function getReasonAttribute()
    {
        return $this->attributes['change_desc'];
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['change_time'];
    }

    public function getUpdatedAtAttribute()
    {
        return null;
    }

    public function getStatusAttribute()
    {
        if ($this->attributes['pay_points'] > 0) {
            return 1;
        } else {
            return 2;
        }
    }
}
