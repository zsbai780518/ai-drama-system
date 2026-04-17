<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - JWT 认证中间件
// +----------------------------------------------------------------------

namespace app\middleware;

use think\facade\Db;
use think\Response;

/**
 * JWT 认证中间件
 */
class AuthMiddleware
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        // 获取 Authorization 头
        $authHeader = $request->header('Authorization', '');
        
        if (empty($authHeader)) {
            return $this->errorResponse('请先登录', 401);
        }
        
        // 解析 Bearer Token
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->errorResponse('Token 格式错误', 401);
        }
        
        $token = $matches[1];
        
        // 验证 Token
        $tokenInfo = $this->verifyToken($token);
        
        if (!$tokenInfo['valid']) {
            return $this->errorResponse($tokenInfo['msg'], 401);
        }
        
        // 将用户 ID 注入请求
        $request->authUserId = $tokenInfo['uid'];
        
        return $next($request);
    }
    
    /**
     * 验证 Token
     */
    protected function verifyToken($token)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return ['valid' => false, 'msg' => 'Token 格式错误'];
        }
        
        // 解码 payload
        $payload = json_decode(base64_decode($parts[1]), true);
        
        if (!$payload) {
            return ['valid' => false, 'msg' => 'Token 解析失败'];
        }
        
        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'msg' => '登录已过期'];
        }
        
        // 验证签名
        $signature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", config('app.app_secret'));
        
        if ($signature !== $parts[2]) {
            return ['valid' => false, 'msg' => 'Token 签名无效'];
        }
        
        // 检查用户是否存在
        $user = Db::name('user')->find($payload['uid']);
        
        if (!$user) {
            return ['valid' => false, 'msg' => '用户不存在'];
        }
        
        if ($user['status'] !== 1) {
            return ['valid' => false, 'msg' => '账号已被封禁'];
        }
        
        return [
            'valid' => true,
            'uid' => $payload['uid'],
            'exp' => $payload['exp'],
        ];
    }
    
    /**
     * 错误响应
     */
    protected function errorResponse($msg, $code = 401)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => null,
        ])->code($code);
    }
}
