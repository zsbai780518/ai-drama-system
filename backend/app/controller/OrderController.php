<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - 订单与支付控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use app\service\UserService;
use think\facade\Db;
use think\facade\Request;

/**
 * 订单与支付控制器
 */
class OrderController extends BaseController
{
    protected $userService;

    public function initialize()
    {
        parent::initialize();
        $this->userService = new UserService();
    }

    /**
     * 获取会员套餐列表
     * GET /api/v1/member/packages
     */
    public function getPackages()
    {
        $packages = Db::name('member_package')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();
        
        return json([
            'code' => 200,
            'data' => $packages
        ]);
    }

    /**
     * 创建订单
     * POST /api/v1/order/create
     */
    public function createOrder()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'order_type' => 'require|number|in:1,2,3', // 1 会员 2AI 点数 3 余额
            'package_id' => 'number',
            'amount' => 'number', // AI 点数或余额充值金额
            'pay_type' => 'require|number|in:1,2,3', // 1 微信 2 支付宝 3 余额
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $orderType = Request::param('order_type');
        $payType = Request::param('pay_type');
        $packageId = Request::param('package_id', 0);
        $amount = Request::param('amount', 0);
        
        // 计算订单金额
        $finalAmount = 0;
        $originalAmount = 0;
        
        if ($orderType === 1 && $packageId > 0) {
            // 会员购买
            $package = Db::name('member_package')->find($packageId);
            if (!$package || $package['status'] !== 1) {
                return json(['code' => 400, 'msg' => '套餐不存在或已下架']);
            }
            $finalAmount = $package['price'];
            $originalAmount = $package['original_price'];
        } elseif ($orderType === 2) {
            // AI 点数充值
            if ($amount <= 0) {
                return json(['code' => 400, 'msg' => '充值金额必须大于 0']);
            }
            $finalAmount = $amount;
            $originalAmount = $amount;
        } elseif ($orderType === 3) {
            // 余额充值
            if ($amount <= 0) {
                return json(['code' => 400, 'msg' => '充值金额必须大于 0']);
            }
            $finalAmount = $amount;
            $originalAmount = $amount;
        }
        
        // 生成订单号
        $orderNo = date('YmdHis') . mt_rand(1000, 9999);
        
        // 创建订单
        $orderId = Db::name('order')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => $userId,
            'order_type' => $orderType,
            'package_id' => $packageId,
            'amount' => $finalAmount,
            'original_amount' => $originalAmount,
            'pay_type' => $payType,
            'pay_status' => 0, // 待支付
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // 准备支付参数
        $payParams = [];
        if ($payType === 1) {
            // 微信支付
            $payParams = $this->createWechatPayOrder($orderNo, $finalAmount);
        } elseif ($payType === 2) {
            // 支付宝
            $payParams = $this->createAlipayOrder($orderNo, $finalAmount);
        } elseif ($payType === 3) {
            // 余额支付
            $result = $this->payByBalance($userId, $orderId, $finalAmount);
            return json($result);
        }
        
        return json([
            'code' => 200,
            'msg' => '订单创建成功',
            'data' => [
                'order_no' => $orderNo,
                'amount' => $finalAmount,
                'pay_params' => $payParams,
            ]
        ]);
    }

    /**
     * 查询订单状态
     * GET /api/v1/order/:order_no/status
     */
    public function getOrderStatus($orderNo)
    {
        $userId = $this->authUserId;
        
        $order = Db::name('order')
            ->where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->find();
        
        if (!$order) {
            return json(['code' => 404, 'msg' => '订单不存在']);
        }
        
        return json([
            'code' => 200,
            'data' => [
                'order_no' => $order['order_no'],
                'order_type' => $order['order_type'],
                'amount' => $order['amount'],
                'pay_status' => $order['pay_status'],
                'pay_time' => $order['pay_time'],
                'created_at' => $order['created_at'],
            ]
        ]);
    }

    /**
     * 获取订单列表
     * GET /api/v1/order/list
     */
    public function getOrderList()
    {
        $userId = $this->authUserId;
        
        $page = max(1, Request::param('page', 1));
        $pageSize = min(50, max(1, Request::param('page_size', 10)));
        $payStatus = Request::param('pay_status', '');
        
        $where = ['user_id' => $userId];
        if ($payStatus !== '') {
            $where['pay_status'] = $payStatus;
        }
        
        $list = Db::name('order')
            ->where($where)
            ->order('created_at', 'desc')
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
     * 支付回调（微信/支付宝）
     * POST /api/v1/order/notify
     */
    public function notify()
    {
        // 获取回调数据
        $data = file_get_contents('php://input');
        Log::info('支付回调：' . $data);
        
        // TODO: 验证签名
        // TODO: 解析回调数据
        
        // 模拟处理（开发环境）
        $orderNo = Request::param('out_trade_no', '');
        $transactionId = Request::param('transaction_id', '');
        
        if (empty($orderNo)) {
            return json(['code' => 'FAIL', 'msg' => '订单号不存在']);
        }
        
        $order = Db::name('order')->where('order_no', $orderNo)->find();
        if (!$order || $order['pay_status'] !== 0) {
            return json(['code' => 'FAIL', 'msg' => '订单不存在或已支付']);
        }
        
        // 更新订单状态
        Db::name('order')->where('id', $order['id'])->update([
            'pay_status' => 1,
            'pay_time' => time(),
            'updated_at' => time(),
        ]);
        
        // 处理订单业务逻辑
        $this->handleOrderSuccess($order);
        
        return json(['code' => 'SUCCESS', 'msg' => 'OK']);
    }

    /**
     * 处理订单成功
     */
    protected function handleOrderSuccess($order)
    {
        $userId = $order['user_id'];
        
        if ($order['order_type'] === 1) {
            // 会员开通
            $package = Db::name('member_package')->find($order['package_id']);
            if ($package) {
                $user = Db::name('user')->find($userId);
                
                // 计算会员过期时间
                $expireTime = time() + ($package['duration_days'] * 86400);
                
                // 如果已有会员，累加时长
                if ($user['member_level'] > 0 && $user['member_expire'] > time()) {
                    $expireTime = $user['member_expire'] + ($package['duration_days'] * 86400);
                }
                
                Db::name('user')->where('id', $userId)->update([
                    'member_level' => $package['level'],
                    'member_expire' => $expireTime,
                    'ai_points' => $user['ai_points'] + $package['ai_points'],
                    'updated_at' => time(),
                ]);
                
                // 记录充值
                Db::name('recharge')->insert([
                    'user_id' => $userId,
                    'type' => 2, // AI 点数
                    'amount' => $package['ai_points'],
                    'balance_before' => $user['ai_points'],
                    'balance_after' => $user['ai_points'] + $package['ai_points'],
                    'change_type' => 3, // 赠送
                    'related_id' => $order['id'],
                    'remark' => "购买{$package['name']}赠送",
                    'created_at' => time(),
                ]);
            }
        } elseif ($order['order_type'] === 2) {
            // AI 点数充值
            $points = intval($order['amount']); // 假设 1 元=1 点数
            $user = Db::name('user')->find($userId);
            
            Db::name('user')->where('id', $userId)->update([
                'ai_points' => $user['ai_points'] + $points,
                'updated_at' => time(),
            ]);
            
            Db::name('recharge')->insert([
                'user_id' => $userId,
                'type' => 2,
                'amount' => $points,
                'balance_before' => $user['ai_points'],
                'balance_after' => $user['ai_points'] + $points,
                'change_type' => 1, // 充值
                'related_id' => $order['id'],
                'remark' => 'AI 点数充值',
                'created_at' => time(),
            ]);
        } elseif ($order['order_type'] === 3) {
            // 余额充值
            $balance = $order['amount'];
            $user = Db::name('user')->find($userId);
            
            Db::name('user')->where('id', $userId)->update([
                'balance' => $user['balance'] + $balance,
                'updated_at' => time(),
            ]);
            
            Db::name('recharge')->insert([
                'user_id' => $userId,
                'type' => 1,
                'amount' => $balance,
                'balance_before' => $user['balance'],
                'balance_after' => $user['balance'] + $balance,
                'change_type' => 1,
                'related_id' => $order['id'],
                'remark' => '余额充值',
                'created_at' => time(),
            ]);
        }
    }

    /**
     * 创建微信支付订单
     */
    protected function createWechatPayOrder($orderNo, $amount)
    {
        // TODO: 调用微信支付 API 创建订单
        // 这里返回模拟数据
        
        return [
            'appid' => config('wechat.appid'),
            'timeStamp' => (string)time(),
            'nonceStr' => md5(uniqid()),
            'package' => 'prepay_id=' . md5($orderNo),
            'signType' => 'RSA',
            'paySign' => md5($orderNo . config('wechat.key')),
        ];
    }

    /**
     * 创建支付宝订单
     */
    protected function createAlipayOrder($orderNo, $amount)
    {
        // TODO: 调用支付宝 API 创建订单
        return [
            'orderString' => 'alipay_trade_app_pay',
            'out_trade_no' => $orderNo,
            'total_amount' => $amount,
        ];
    }

    /**
     * 余额支付
     */
    protected function payByBalance($userId, $orderId, $amount)
    {
        $user = Db::name('user')->find($userId);
        
        if ($user['balance'] < $amount) {
            return ['code' => 400, 'msg' => '余额不足'];
        }
        
        // 扣除余额
        Db::name('user')->where('id', $userId)->update([
            'balance' => $user['balance'] - $amount,
            'updated_at' => time(),
        ]);
        
        // 更新订单状态
        Db::name('order')->where('id', $orderId)->update([
            'pay_status' => 1,
            'pay_type' => 3,
            'pay_time' => time(),
            'updated_at' => time(),
        ]);
        
        // 处理订单业务
        $order = Db::name('order')->find($orderId);
        $this->handleOrderSuccess($order);
        
        return [
            'code' => 200,
            'msg' => '支付成功',
            'data' => ['order_no' => $order['order_no']]
        ];
    }
}
