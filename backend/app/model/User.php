<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 用户模型
// +----------------------------------------------------------------------

namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    protected $table = 'user';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = false;
    protected $createTime = false;
    protected $updateTime = false;
    
    /**
     * 获取器：会员等级文本
     */
    public function getMemberLevelTextAttr($value, $data)
    {
        $map = [
            0 => '普通用户',
            1 => '月度会员',
            2 => '季度会员',
            3 => '年度会员',
        ];
        return $map[$data['member_level']] ?? '未知';
    }
    
    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] === 1 ? '正常' : '封禁';
    }
    
    /**
     * 关联：登录记录
     */
    public function logins()
    {
        return $this->hasMany(UserLogin::class, 'user_id');
    }
    
    /**
     * 关联：作品
     */
    public function works()
    {
        return $this->hasMany(ShortPlay::class, 'user_id');
    }
    
    /**
     * 关联：订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
    
    /**
     * 范围查询：正常用户
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }
    
    /**
     * 范围查询：会员用户
     */
    public function scopeMember($query)
    {
        return $query->where('member_level', '>', 0)
                     ->where('member_expire', '>', time());
    }
}
