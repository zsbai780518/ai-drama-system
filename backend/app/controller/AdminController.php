<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 后台管理控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;

/**
 * 后台管理控制器
 */
class AdminController extends BaseController
{
    /**
     * 管理员登录
     * POST /api/admin/login
     */
    public function login()
    {
        $validate = validate([
            'username' => 'require',
            'password' => 'require',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $username = Request::param('username');
        $password = Request::param('password');
        
        $admin = Db::name('admin')->where('username', $username)->find();
        
        if (!$admin || $admin['status'] !== 1) {
            return json(['code' => 400, 'msg' => '用户名或密码错误']);
        }
        
        if (!password_verify($password, $admin['password'])) {
            return json(['code' => 400, 'msg' => '用户名或密码错误']);
        }
        
        // 更新登录信息
        Db::name('admin')->where('id', $admin['id'])->update([
            'last_login_at' => time(),
            'last_login_ip' => Request::ip(),
        ]);
        
        // 生成 Token
        $token = md5($admin['id'] . time() . config('app.app_secret'));
        
        Session::set('admin_id', $admin['id']);
        Session::set('admin_token', $token);
        
        return json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'nickname' => $admin['nickname'],
                    'avatar' => $admin['avatar'],
                    'role_id' => $admin['role_id'],
                ]
            ]
        ]);
    }

    /**
     * 获取管理后台首页数据
     * GET /api/admin/dashboard
     */
    public function dashboard()
    {
        // 统计数据
        $stats = [
            'user_count' => Db::name('user')->count(),
            'user_today' => Db::name('user')->where('created_at', '>=', strtotime('today'))->count(),
            'work_count' => Db::name('short_play')->count(),
            'work_today' => Db::name('short_play')->where('created_at', '>=', strtotime('today'))->count(),
            'order_count' => Db::name('order')->where('pay_status', 1)->count(),
            'order_today' => Db::name('order')->where('pay_status', 1)->where('created_at', '>=', strtotime('today'))->count(),
            'revenue_total' => Db::name('order')->where('pay_status', 1)->sum('amount') ?: 0,
            'revenue_today' => Db::name('order')->where('pay_status', 1)->where('created_at', '>=', strtotime('today'))->sum('amount') ?: 0,
        ];
        
        // 最近订单
        $recentOrders = Db::name('order')
            ->alias('o')
            ->join('user u', 'o.user_id=u.id')
            ->field('o.*,u.nickname,u.mobile')
            ->order('o.created_at', 'desc')
            ->limit(10)
            ->select();
        
        // 待审核内容
        $pendingAudits = Db::name('content_audit')
            ->where('audit_status', 0)
            ->count();
        
        return json([
            'code' => 200,
            'data' => [
                'stats' => $stats,
                'recent_orders' => $recentOrders,
                'pending_audits' => $pendingAudits,
            ]
        ]);
    }

    /**
     * 用户管理 - 列表
     * GET /api/admin/user/list
     */
    public function userList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(100, max(1, Request::param('page_size', 20)));
        $keyword = Request::param('keyword', '');
        $status = Request::param('status', '');
        
        $where = [];
        if ($keyword !== '') {
            $where[] = ['mobile', 'like', "%{$keyword}%"];
            $where[] = ['nickname', 'like', "%{$keyword}%", 'or'];
        }
        if ($status !== '') {
            $where['status'] = $status;
        }
        
        $list = Db::name('user')
            ->where($where)
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('user')->where($where)->count();
        
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
     * 用户管理 - 封禁/解封
     * POST /api/admin/user/:id/status
     */
    public function userStatus($id)
    {
        $status = Request::param('status', 1);
        
        Db::name('user')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);
        
        return json(['code' => 200, 'msg' => '操作成功']);
    }

    /**
     * 作品管理 - 列表
     * GET /api/admin/work/list
     */
    public function workList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(100, max(1, Request::param('page_size', 20)));
        $status = Request::param('status', '');
        
        $where = [];
        if ($status !== '') {
            $where['status'] = $status;
        }
        
        $list = Db::name('short_play')
            ->alias('w')
            ->join('user u', 'w.user_id=u.id')
            ->field('w.*,u.nickname,u.mobile')
            ->where($where)
            ->order('w.created_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('short_play')->where($where)->count();
        
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
     * 内容审核 - 列表
     * GET /api/admin/audit/list
     */
    public function auditList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(100, max(1, Request::param('page_size', 20)));
        $status = Request::param('status', '0');
        
        $list = Db::name('content_audit')
            ->where('audit_status', $status)
            ->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('content_audit')->where('audit_status', $status)->count();
        
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
     * 内容审核 - 审核操作
     * POST /api/admin/audit/:id/verify
     */
    public function auditVerify($id)
    {
        $adminId = Session::get('admin_id');
        
        $validate = validate([
            'audit_status' => 'require|number|in:1,2',
            'audit_msg' => 'max:500',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $audit = Db::name('content_audit')->find($id);
        if (!$audit) {
            return json(['code' => 404, 'msg' => '审核记录不存在']);
        }
        
        Db::name('content_audit')->where('id', $id)->update([
            'audit_status' => Request::param('audit_status'),
            'audit_msg' => Request::param('audit_msg', ''),
            'auditor_id' => $adminId,
            'audited_at' => time(),
        ]);
        
        // 更新对应内容状态
        if ($audit['content_type'] === 2) { // 作品
            $playStatus = Request::param('audit_status') == 1 ? 3 : 4; // 通过->已发布，拒绝->已下架
            Db::name('short_play')->where('id', $audit['content_id'])->update([
                'status' => $playStatus,
            ]);
        }
        
        return json(['code' => 200, 'msg' => '审核完成']);
    }

    /**
     * 订单管理 - 列表
     * GET /api/admin/order/list
     */
    public function orderList()
    {
        $page = max(1, Request::param('page', 1));
        $pageSize = min(100, max(1, Request::param('page_size', 20)));
        $payStatus = Request::param('pay_status', '');
        
        $where = [];
        if ($payStatus !== '') {
            $where['pay_status'] = $payStatus;
        }
        
        $list = Db::name('order')
            ->alias('o')
            ->join('user u', 'o.user_id=u.id')
            ->field('o.*,u.nickname,u.mobile')
            ->where($where)
            ->order('o.created_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = Db::name('order')->where($where)->count();
        
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
     * 系统配置 - 获取
     * GET /api/admin/config
     */
    public function getConfig()
    {
        $configs = Db::name('system_config')->select();
        
        return json([
            'code' => 200,
            'data' => $configs
        ]);
    }

    /**
     * 系统配置 - 更新
     * POST /api/admin/config
     */
    public function updateConfig()
    {
        $configs = Request::param('configs', []);
        
        foreach ($configs as $key => $value) {
            Db::name('system_config')->updateOrCreate(
                ['config_key' => $key],
                [
                    'config_value' => $value,
                    'updated_at' => time(),
                ]
            );
        }
        
        return json(['code' => 200, 'msg' => '配置更新成功']);
    }

    /**
     * AI 服务商配置 - 列表
     * GET /api/admin/ai-provider/list
     */
    public function aiProviderList()
    {
        $list = Db::name('ai_provider_config')->select();
        
        return json([
            'code' => 200,
            'data' => $list
        ]);
    }

    /**
     * AI 服务商配置 - 更新
     * POST /api/admin/ai-provider/update
     */
    public function updateAiProvider()
    {
        $id = Request::param('id');
        
        $data = [
            'api_key' => Request::param('api_key', ''),
            'secret_key' => Request::param('secret_key', ''),
            'api_url' => Request::param('api_url', ''),
            'daily_limit' => Request::param('daily_limit', 0),
            'is_enabled' => Request::param('is_enabled', 1),
            'updated_at' => time(),
        ];
        
        Db::name('ai_provider_config')->where('id', $id)->update($data);
        
        return json(['code' => 200, 'msg' => '配置更新成功']);
    }

    /**
     * 管理员退出登录
     * POST /api/admin/logout
     */
    public function logout()
    {
        Session::delete('admin_id');
        Session::delete('admin_token');
        
        return json(['code' => 200, 'msg' => '退出成功']);
    }
}
