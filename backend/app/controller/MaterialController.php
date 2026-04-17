<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 素材控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;

/**
 * 素材控制器
 */
class MaterialController extends BaseController
{
    /**
     * 获取素材列表
     * GET /api/v1/material/list
     */
    public function getList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(50, max(1, Request::param('page_size', 20)));
        
        $type = Request::param('type', '');
        $category = Request::param('category', '');
        $isMemberOnly = Request::param('is_member_only', '');
        $keyword = Request::param('keyword', '');
        
        $where = ['status' => 1];
        
        if ($type !== '') {
            $where['type'] = $type;
        }
        if ($category !== '') {
            $where['category'] = $category;
        }
        if ($isMemberOnly !== '') {
            $where['is_member_only'] = $isMemberOnly;
        }
        if ($keyword !== '') {
            $where[] = ['name', 'like', "%{$keyword}%"];
            $where[] = ['description', 'like', "%{$keyword}%"];
            $where[] = ['tags', 'like', "%{$keyword}%", 'or'];
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
        
        // 非会员不显示会员专享素材
        if ($userLevel === 0) {
            $where['is_member_only'] = 0;
        }
        
        $list = Db::name('material')
            ->where($where)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('material')->where($where)->count();
        
        return json([
            'code' => 200,
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'categories' => $this->getCategories(),
            ]
        ]);
    }

    /**
     * 获取素材详情
     * GET /api/v1/material/:id
     */
    public function getDetail($id)
    {
        $material = Db::name('material')->find($id);
        
        if (!$material || $material['status'] !== 1) {
            return json(['code' => 404, 'msg' => '素材不存在或已下架']);
        }
        
        // 检查会员权限
        if ($material['is_member_only']) {
            $userId = $this->authUserId ?? 0;
            $user = Db::name('user')->find($userId);
            
            if (!$user || $user['member_level'] === 0 || $user['member_expire'] < time()) {
                return json(['code' => 1003, 'msg' => '该素材需要会员才能查看']);
            }
        }
        
        // 增加下载计数
        Db::name('material')->where('id', $id)->inc('download_count')->update();
        
        return json([
            'code' => 200,
            'data' => $material
        ]);
    }

    /**
     * 获取分类列表
     */
    protected function getCategories()
    {
        return [
            ['id' => 'bgm', 'name' => '背景音乐', 'type' => 2],
            ['id' => 'scene', 'name' => '场景素材', 'type' => 1],
            ['id' => 'effect', 'name' => '特效素材', 'type' => 3],
            ['id' => 'font', 'name' => '字体素材', 'type' => 4],
            ['id' => 'template', 'name' => '模板素材', 'type' => 5],
        ];
    }
}
