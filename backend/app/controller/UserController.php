<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 用户控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use app\service\UserService;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cache;

/**
 * 用户控制器
 */
class UserController extends BaseController
{
    protected $userService;

    public function initialize()
    {
        parent::initialize();
        $this->userService = new UserService();
    }

    /**
     * 发送短信验证码
     * POST /api/v1/sms/send
     */
    public function sendSms()
    {
        $validate = validate([
            'mobile' => 'require|regex:/^1[3-9]\d{9}$/',
            'type' => 'require|number|in:1,2',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $mobile = Request::param('mobile');
        $type = Request::param('type');
        
        // 检查发送频率
        $cacheKey = "sms_limit:{$mobile}";
        if (Cache::get($cacheKey)) {
            return json(['code' => 400, 'msg' => '验证码发送过于频繁，请稍后再试']);
        }
        
        // 生成 6 位验证码
        $code = sprintf('%06d', mt_rand(0, 999999));
        
        // 存储验证码（5 分钟有效期）
        Cache::set("sms_code:{$mobile}", $code, 300);
        Cache::set($cacheKey, 1, 60); // 60 秒内不能重复发送
        
        // TODO: 调用短信服务商发送
        // SmsService::send($mobile, $code);
        
        // 开发环境直接返回验证码（生产环境注释）
        return json([
            'code' => 200,
            'msg' => '验证码已发送',
            'data' => [
                'expire' => 300,
                '_debug_code' => $code, // 生产环境删除
            ]
        ]);
    }

    /**
     * 手机号登录/注册
     * POST /api/v1/user/login-mobile
     */
    public function loginMobile()
    {
        $validate = validate([
            'mobile' => 'require|regex:/^1[3-9]\d{9}$/',
            'code' => 'require|length:6',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $mobile = Request::param('mobile');
        $code = Request::param('code');
        
        // 验证验证码
        $savedCode = Cache::get("sms_code:{$mobile}");
        if (!$savedCode || $savedCode !== $code) {
            return json(['code' => 400, 'msg' => '验证码错误']);
        }
        
        // 查找或创建用户
        $user = Db::name('user')->where('mobile', $mobile)->find();
        
        if (!$user) {
            // 新用户注册
            $userId = Db::name('user')->insertGetId([
                'mobile' => $mobile,
                'nickname' => '用户' . substr($mobile, -4),
                'avatar' => '',
                'ai_points' => 10, // 新用户赠送 10 点
                'free_points' => 10,
                'last_free_reset' => date('Y-m-d'),
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'last_login_at' => time(),
            ]);
            
            $user = Db::name('user')->find($userId);
            
            // 记录登录
            Db::name('user_login')->insert([
                'user_id' => $userId,
                'login_type' => 1,
                'created_at' => time(),
            ]);
        } else {
            if ($user['status'] === 0) {
                return json(['code' => 400, 'msg' => '账号已被封禁']);
            }
            
            // 更新登录信息
            Db::name('user')->where('id', $user['id'])->update([
                'last_login_at' => time(),
                'last_login_ip' => Request::ip(),
            ]);
        }
        
        // 生成 Token
        $token = $this->generateToken($user['id']);
        
        // 删除验证码
        Cache::delete("sms_code:{$mobile}");
        
        return json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nickname' => $user['nickname'],
                    'avatar' => $user['avatar'],
                    'mobile' => hideMobile($user['mobile']),
                    'member_level' => $user['member_level'],
                    'member_expire' => $user['member_expire'],
                    'balance' => $user['balance'],
                    'ai_points' => $user['ai_points'],
                    'free_points' => $user['free_points'],
                ]
            ]
        ]);
    }

    /**
     * 微信授权登录
     * POST /api/v1/user/login-wechat
     */
    public function loginWechat()
    {
        $validate = validate([
            'code' => 'require',
            'encryptedData' => 'requireWithout:iv',
            'iv' => 'requireWithout:encryptedData',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $code = Request::param('code');
        
        // TODO: 调用微信接口获取 openid 和用户信息
        // $wechatInfo = WechatService::getUserInfo($code);
        
        // 模拟微信登录（开发环境）
        $openid = 'wx_' . md5($code . time());
        
        // 查找或创建用户
        $login = Db::name('user_login')
            ->where('openid', $openid)
            ->where('login_type', 2)
            ->find();
        
        if ($login) {
            $user = Db::name('user')->find($login['user_id']);
        } else {
            // 新用户
            $userId = Db::name('user')->insertGetId([
                'mobile' => '',
                'nickname' => '微信用户' . substr($openid, -4),
                'avatar' => '',
                'ai_points' => 10,
                'free_points' => 10,
                'last_free_reset' => date('Y-m-d'),
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'last_login_at' => time(),
            ]);
            
            Db::name('user_login')->insert([
                'user_id' => $userId,
                'login_type' => 2,
                'openid' => $openid,
                'created_at' => time(),
            ]);
            
            $user = Db::name('user')->find($userId);
        }
        
        $token = $this->generateToken($user['id']);
        
        return json([
            'code' => 200,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nickname' => $user['nickname'],
                    'avatar' => $user['avatar'],
                    'member_level' => $user['member_level'],
                    'ai_points' => $user['ai_points'],
                ]
            ]
        ]);
    }

    /**
     * 获取用户信息
     * GET /api/v1/user/profile
     */
    public function getProfile()
    {
        $userId = $this->authUserId;
        $user = Db::name('user')->find($userId);
        
        if (!$user) {
            return json(['code' => 404, 'msg' => '用户不存在']);
        }
        
        // 检查是否需要重置每日免费点数
        if ($user['last_free_reset'] != date('Y-m-d')) {
            $dailyFreePoints = Db::name('system_config')
                ->where('config_key', 'daily_free_points')
                ->value('config_value');
            
            Db::name('user')->where('id', $userId)->update([
                'free_points' => intval($dailyFreePoints ?: 10),
                'last_free_reset' => date('Y-m-d'),
            ]);
            
            $user['free_points'] = intval($dailyFreePoints ?: 10);
        }
        
        // 检查会员是否过期
        if ($user['member_level'] > 0 && $user['member_expire'] < time()) {
            Db::name('user')->where('id', $userId)->update([
                'member_level' => 0,
                'member_expire' => 0,
            ]);
            $user['member_level'] = 0;
        }
        
        return json([
            'code' => 200,
            'data' => [
                'id' => $user['id'],
                'mobile' => hideMobile($user['mobile']),
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'gender' => $user['gender'],
                'member_level' => $user['member_level'],
                'member_expire' => $user['member_expire'],
                'balance' => $user['balance'],
                'ai_points' => $user['ai_points'],
                'free_points' => $user['free_points'],
            ]
        ]);
    }

    /**
     * 更新用户信息
     * PUT /api/v1/user/profile
     */
    public function updateProfile()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'nickname' => 'max:50',
            'avatar' => 'max:255|url',
            'gender' => 'number|in:0,1,2',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $data = [];
        if (Request::has('nickname')) {
            $data['nickname'] = Request::param('nickname');
        }
        if (Request::has('avatar')) {
            $data['avatar'] = Request::param('avatar');
        }
        if (Request::has('gender')) {
            $data['gender'] = Request::param('gender');
        }
        
        if (empty($data)) {
            return json(['code' => 400, 'msg' => '没有可更新的数据']);
        }
        
        $data['updated_at'] = time();
        
        Db::name('user')->where('id', $userId)->update($data);
        
        return json(['code' => 200, 'msg' => '更新成功']);
    }

    /**
     * 修改密码
     * PUT /api/v1/user/password
     */
    public function updatePassword()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'old_password' => 'require',
            'new_password' => 'require|min:6|max:20',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $user = Db::name('user')->find($userId);
        
        if (empty($user['password'])) {
            return json(['code' => 400, 'msg' => '请先设置密码']);
        }
        
        // 验证原密码
        if (!password_verify(Request::param('old_password'), $user['password'])) {
            return json(['code' => 400, 'msg' => '原密码错误']);
        }
        
        // 更新密码
        Db::name('user')->where('id', $userId)->update([
            'password' => password_encode(Request::param('new_password')),
            'updated_at' => time(),
        ]);
        
        return json(['code' => 200, 'msg' => '密码修改成功']);
    }

    /**
     * 生成 Token
     */
    protected function generateToken($userId)
    {
        $payload = [
            'uid' => $userId,
            'exp' => time() + 86400 * 7, // 7 天有效期
            'iat' => time(),
        ];
        
        // 简单 JWT 实现（生产环境建议使用 firebase/php-jwt）
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$header}.{$payload}", config('app.app_secret'));
        
        return "{$header}.{$payload}.{$signature}";
    }
}

/**
 * 隐藏手机号中间数字
 */
function hideMobile($mobile)
{
    if (empty($mobile)) return '';
    return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
}
