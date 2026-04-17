<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 用户服务层
// +----------------------------------------------------------------------

namespace app\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * 用户服务层
 */
class UserService
{
    /**
     * 根据 ID 获取用户
     */
    public function getUserById($userId)
    {
        return Db::name('user')->find($userId);
    }

    /**
     * 扣除 AI 点数
     */
    public function deductPoints($userId, $points)
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        // 优先使用免费点数
        $freePointsToUse = min($user['free_points'], $points);
        $aiPointsToUse = $points - $freePointsToUse;
        
        $updateData = [];
        
        if ($freePointsToUse > 0) {
            $updateData['free_points'] = $user['free_points'] - $freePointsToUse;
        }
        
        if ($aiPointsToUse > 0) {
            if ($user['ai_points'] < $aiPointsToUse) {
                return false; // 点数不足
            }
            $updateData['ai_points'] = $user['ai_points'] - $aiPointsToUse;
        }
        
        if (!empty($updateData)) {
            Db::name('user')->where('id', $userId)->update($updateData);
            
            // 记录充值/消费记录
            Db::name('recharge')->insert([
                'user_id' => $userId,
                'type' => 2, // AI 点数
                'amount' => $points,
                'balance_before' => $user['ai_points'] + $user['free_points'],
                'balance_after' => $user['ai_points'] + $user['free_points'] - $points,
                'change_type' => 2, // 消费
                'remark' => 'AI 任务消耗',
                'created_at' => time(),
            ]);
        }
        
        return true;
    }

    /**
     * 增加 AI 点数
     */
    public function addPoints($userId, $points, $type = 3, $remark = '')
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        Db::name('user')->where('id', $userId)->inc('ai_points', $points)->update();
        
        // 记录
        Db::name('recharge')->insert([
            'user_id' => $userId,
            'type' => 2,
            'amount' => $points,
            'balance_before' => $user['ai_points'],
            'balance_after' => $user['ai_points'] + $points,
            'change_type' => $type, // 1 充值 2 消费 3 赠送 4 退款 5 每日重置
            'related_id' => 0,
            'remark' => $remark,
            'created_at' => time(),
        ]);
        
        return true;
    }

    /**
     * 重置每日免费点数
     */
    public function resetDailyPoints()
    {
        $dailyFreePoints = Db::name('system_config')
            ->where('config_key', 'daily_free_points')
            ->value('config_value');
        
        $dailyFreePoints = intval($dailyFreePoints ?: 10);
        
        // 获取所有需要重置的用户（昨天之前重置的）
        $users = Db::name('user')
            ->where('last_free_reset', '<', date('Y-m-d'))
            ->select();
        
        foreach ($users as $user) {
            Db::name('user')->where('id', $user['id'])->update([
                'free_points' => $dailyFreePoints,
                'last_free_reset' => date('Y-m-d'),
            ]);
            
            // 记录
            Db::name('recharge')->insert([
                'user_id' => $user['id'],
                'type' => 2,
                'amount' => $dailyFreePoints,
                'balance_before' => $user['free_points'],
                'balance_after' => $dailyFreePoints,
                'change_type' => 5, // 每日重置
                'remark' => '每日免费点数重置',
                'created_at' => time(),
            ]);
        }
        
        return count($users);
    }

    /**
     * 检查用户会员状态
     */
    public function checkMemberStatus($userId)
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['is_member' => false, 'level' => 0];
        }
        
        if ($user['member_level'] > 0 && $user['member_expire'] > time()) {
            return [
                'is_member' => true,
                'level' => $user['member_level'],
                'expire_time' => $user['member_expire'],
            ];
        }
        
        // 会员已过期，降级
        if ($user['member_level'] > 0) {
            Db::name('user')->where('id', $userId)->update([
                'member_level' => 0,
                'member_expire' => 0,
            ]);
        }
        
        return ['is_member' => false, 'level' => 0];
    }

    /**
     * 开通会员
     */
    public function activateMember($userId, $level, $days, $bonusPoints = 0)
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        $expireTime = time() + ($days * 86400);
        
        // 如果已有会员，累加时长
        if ($user['member_level'] > 0 && $user['member_expire'] > time()) {
            $expireTime = $user['member_expire'] + ($days * 86400);
        }
        
        Db::name('user')->where('id', $userId)->update([
            'member_level' => $level,
            'member_expire' => $expireTime,
            'ai_points' => $user['ai_points'] + $bonusPoints,
            'updated_at' => time(),
        ]);
        
        return true;
    }
}
