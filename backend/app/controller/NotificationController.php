<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 通知控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;

/**
 * 通知控制器
 */
class NotificationController extends BaseController
{
    /**
     * 获取消息列表
     * GET /api/v1/notification/list
     */
    public function getList()
    {
        $userId = $this->authUserId;
        
        $page = max(1, Request::param('page', 1));
        $pageSize = min(50, max(1, Request::param('page_size', 20)));
        $type = Request::param('type', '');
        $isRead = Request::param('is_read', '');
        
        $where = ['user_id' => $userId];
        
        if ($type !== '') {
            $where['type'] = $type;
        }
        if ($isRead !== '') {
            $where['is_read'] = $isRead;
        }
        
        $list = Db::name('notification')
            ->where($where)
            ->order('is_read', 'asc')
            ->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('notification')->where($where)->count();
        
        // 未读数
        $unreadCount = Db::name('notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
        
        return json([
            'code' => 200,
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'unread_count' => $unreadCount,
            ]
        ]);
    }

    /**
     * 标记消息已读
     * PUT /api/v1/notification/:id/read
     */
    public function markRead($id)
    {
        $userId = $this->authUserId;
        
        $notification = Db::name('notification')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$notification) {
            return json(['code' => 404, 'msg' => '消息不存在']);
        }
        
        Db::name('notification')->where('id', $id)->update([
            'is_read' => 1,
            'read_at' => time(),
        ]);
        
        return json(['code' => 200, 'msg' => '已标记为已读']);
    }

    /**
     * 全部标记已读
     * PUT /api/v1/notification/read-all
     */
    public function markAllRead()
    {
        $userId = $this->authUserId;
        
        Db::name('notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => time(),
            ]);
        
        return json(['code' => 200, 'msg' => '全部已读']);
    }

    /**
     * 删除消息
     * DELETE /api/v1/notification/:id
     */
    public function delete($id)
    {
        $userId = $this->authUserId;
        
        $notification = Db::name('notification')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$notification) {
            return json(['code' => 404, 'msg' => '消息不存在']);
        }
        
        Db::name('notification')->where('id', $id)->delete();
        
        return json(['code' => 200, 'msg' => '删除成功']);
    }
}
