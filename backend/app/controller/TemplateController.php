<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 剧本模板控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;

/**
 * 剧本模板控制器
 */
class TemplateController extends BaseController
{
    /**
     * 获取模板列表
     * GET /api/v1/template/list
     */
    public function getList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(50, max(1, Request::param('page_size', 20)));
        
        $playType = Request::param('play_type', '');
        $isHot = Request::param('is_hot', '');
        $isMemberOnly = Request::param('is_member_only', '');
        
        $where = ['status' => 1];
        
        if ($playType !== '') {
            $where['play_type'] = $playType;
        }
        if ($isHot !== '') {
            $where['is_hot'] = $isHot;
        }
        
        // 检查用户会员等级
        $userId = $this->authUserId ?? 0;
        $userLevel = 0;
        if ($userId) {
            $user = Db::name('user')->find($userId);
            if ($user && $user['member_level'] > 0 && $user['member_expire'] > time()) {
                $userLevel = $user['member_level'];
            }
        }
        
        // 非会员过滤会员专享模板
        if ($userLevel === 0) {
            $where['is_member_only'] = 0;
        }
        
        $list = Db::name('script_template')
            ->where($where)
            ->order('sort', 'asc')
            ->order('usage_count', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('script_template')->where($where)->count();
        
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
     * 获取模板详情
     * GET /api/v1/template/:id
     */
    public function getDetail($id)
    {
        $template = Db::name('script_template')->find($id);
        
        if (!$template || $template['status'] !== 1) {
            return json(['code' => 404, 'msg' => '模板不存在或已下架']);
        }
        
        // 检查会员权限
        if ($template['is_member_only']) {
            $userId = $this->authUserId ?? 0;
            $user = Db::name('user')->find($userId);
            
            if (!$user || $user['member_level'] === 0 || $user['member_expire'] < time()) {
                return json(['code' => 1003, 'msg' => '该模板需要会员才能查看']);
            }
        }
        
        return json([
            'code' => 200,
            'data' => $template
        ]);
    }

    /**
     * 使用模板
     * POST /api/v1/template/:id/use
     */
    public function useTemplate($id)
    {
        $userId = $this->authUserId;
        
        $template = Db::name('script_template')->find($id);
        if (!$template || $template['status'] !== 1) {
            return json(['code' => 404, 'msg' => '模板不存在']);
        }
        
        // 创建剧本草稿
        $scriptId = Db::name('script')->insertGetId([
            'user_id' => $userId,
            'title' => $template['title'] . '（改编）',
            'play_type' => $template['play_type'],
            'content' => $template['script_content'],
            'scenes' => $template['scenes'],
            'template_id' => $id,
            'status' => 0, // 草稿
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // 增加模板使用次数
        Db::name('script_template')->where('id', $id)->inc('usage_count')->update();
        
        return json([
            'code' => 200,
            'msg' => '模板使用成功',
            'data' => [
                'script_id' => $scriptId,
            ]
        ]);
    }
}
