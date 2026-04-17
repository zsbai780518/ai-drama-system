<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 作品控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;

/**
 * 作品控制器
 */
class WorksController extends BaseController
{
    /**
     * 获取作品列表
     * GET /api/v1/works/list
     */
    public function getList()
    {
        $userId = $this->authUserId;
        
        $page = max(1, Request::param('page', 1));
        $pageSize = min(50, max(1, Request::param('page_size', 10)));
        $status = Request::param('status', '');
        $playType = Request::param('play_type', '');
        
        $where = ['user_id' => $userId];
        if ($status !== '') {
            $where['status'] = $status;
        }
        if ($playType !== '') {
            $where['play_type'] = $playType;
        }
        
        $list = Db::name('short_play')
            ->where($where)
            ->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->field('id,title,cover_url,duration,play_type,status,view_count,like_count,created_at')
            ->select();
        
        $total = Db::name('short_play')->where($where)->count();
        
        // 格式化数据
        $list = $list->map(function($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'cover_url' => $item['cover_url'],
                'duration' => $item['duration'],
                'play_type' => $item['play_type'],
                'play_type_name' => $this->getPlayTypeName($item['play_type']),
                'status' => $item['status'],
                'status_name' => $this->getStatusName($item['status']),
                'view_count' => $item['view_count'],
                'like_count' => $item['like_count'],
                'created_at' => $item['created_at'],
                'created_at_str' => date('Y-m-d H:i', $item['created_at']),
            ];
        });
        
        return json([
            'code' => 200,
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]
        ]);
    }

    /**
     * 获取作品详情
     * GET /api/v1/works/:id
     */
    public function getDetail($id)
    {
        $userId = $this->authUserId;
        
        $work = Db::name('short_play')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$work) {
            return json(['code' => 404, 'msg' => '作品不存在']);
        }
        
        // 获取剧本内容
        $script = null;
        if ($work['script_content']) {
            $script = json_decode($work['script_content'], true);
        }
        
        return json([
            'code' => 200,
            'data' => [
                'id' => $work['id'],
                'title' => $work['title'],
                'description' => $work['description'],
                'cover_url' => $work['cover_url'],
                'video_url' => $work['video_url'],
                'duration' => $work['duration'],
                'play_type' => $work['play_type'],
                'play_type_name' => $this->getPlayTypeName($work['play_type']),
                'play_style' => $work['play_style'],
                'script_content' => $work['script_content'],
                'script' => $script,
                'resolution' => $work['resolution'],
                'has_watermark' => $work['has_watermark'],
                'status' => $work['status'],
                'status_name' => $this->getStatusName($work['status']),
                'view_count' => $work['view_count'],
                'like_count' => $work['like_count'],
                'share_count' => $work['share_count'],
                'copyright_claim' => $work['copyright_claim'],
                'created_at' => $work['created_at'],
                'published_at' => $work['published_at'],
            ]
        ]);
    }

    /**
     * 删除作品
     * DELETE /api/v1/works/:id
     */
    public function delete($id)
    {
        $userId = $this->authUserId;
        
        $work = Db::name('short_play')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$work) {
            return json(['code' => 404, 'msg' => '作品不存在']);
        }
        
        Db::name('short_play')->where('id', $id)->delete();
        
        // TODO: 删除关联的视频文件、封面图等
        
        return json(['code' => 200, 'msg' => '删除成功']);
    }

    /**
     * 导出作品
     * POST /api/v1/works/:id/export
     */
    public function export($id)
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'resolution' => 'require|number|in:1,2,3',
            'watermark' => 'require|number|in:0,1',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $work = Db::name('short_play')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$work) {
            return json(['code' => 404, 'msg' => '作品不存在']);
        }
        
        if ($work['status'] !== 1) {
            return json(['code' => 400, 'msg' => '作品尚未完成，无法导出']);
        }
        
        $resolution = Request::param('resolution');
        $watermark = Request::param('watermark');
        
        // 检查会员权限
        $user = Db::name('user')->find($userId);
        if ($watermark === 0 && $user['member_level'] === 0) {
            return json(['code' => 1003, 'msg' => '无水印导出需要开通会员']);
        }
        
        // TODO: 调用视频处理服务进行导出
        
        return json([
            'code' => 200,
            'msg' => '导出成功',
            'data' => [
                'download_url' => $work['video_url'],
                'resolution' => $resolution,
                'expires_in' => 3600,
            ]
        ]);
    }

    /**
     * 分享作品
     * POST /api/v1/works/:id/share
     */
    public function share($id)
    {
        $userId = $this->authUserId;
        
        $work = Db::name('short_play')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$work) {
            return json(['code' => 404, 'msg' => '作品不存在']);
        }
        
        // 增加分享计数
        Db::name('short_play')->where('id', $id)->inc('share_count')->update();
        
        // 生成分享链接
        $shareUrl = "https://app.yourdomain.com/works/share?id={$id}&uid={$userId}";
        
        return json([
            'code' => 200,
            'data' => [
                'share_url' => $shareUrl,
                'title' => $work['title'],
                'description' => $work['description'],
                'cover_url' => $work['cover_url'],
            ]
        ]);
    }

    /**
     * 点赞作品
     * POST /api/v1/works/:id/like
     */
    public function like($id)
    {
        $userId = $this->authUserId;
        
        $work = Db::name('short_play')->find($id);
        if (!$work) {
            return json(['code' => 404, 'msg' => '作品不存在']);
        }
        
        // 检查是否已点赞
        // TODO: 实现点赞记录表
        
        Db::name('short_play')->where('id', $id)->inc('like_count')->update();
        
        return json(['code' => 200, 'msg' => '点赞成功']);
    }

    /**
     * 获取类型名称
     */
    protected function getPlayTypeName($type)
    {
        $map = [
            1 => '情感',
            2 => '反转',
            3 => '搞笑',
            4 => '职场',
            5 => '电商',
        ];
        return $map[$type] ?? '短剧';
    }

    /**
     * 获取状态名称
     */
    protected function getStatusName($status)
    {
        $map = [
            0 => '制作中',
            1 => '已完成',
            2 => '审核中',
            3 => '已发布',
            4 => '已下架',
        ];
        return $map[$status] ?? '未知';
    }
}
