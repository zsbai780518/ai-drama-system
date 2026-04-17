<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - AI 任务控制器
// +----------------------------------------------------------------------

namespace app\controller;

use app\BaseController;
use app\service\AiService;
use app\service\UserService;
use think\facade\Db;
use think\facade\Request;
use think\facade\Log;

/**
 * AI 任务控制器
 */
class AiController extends BaseController
{
    protected $aiService;
    protected $userService;

    public function initialize()
    {
        parent::initialize();
        $this->aiService = new AiService();
        $this->userService = new UserService();
    }

    /**
     * 生成剧本
     * POST /api/v1/ai/script/generate
     */
    public function generateScript()
    {
        $userId = $this->authUserId;
        
        // 参数验证
        $validate = validate([
            'play_type' => 'require|number|between:1,5',
            'theme' => 'require|max:200',
            'duration' => 'require|number|between:30,300',
            'style' => 'max:50',
            'twist_point' => 'max:500',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $params = Request::param();
        
        // 检查 AI 点数
        $user = $this->userService->getUserById($userId);
        $costPoints = 10; // 剧本生成消耗点数
        
        if ($user['ai_points'] < $costPoints && $user['free_points'] < $costPoints) {
            return json([
                'code' => 1002,
                'msg' => 'AI 点数不足，请充值或开通会员',
                'data' => ['need_points' => $costPoints]
            ]);
        }
        
        // 创建 AI 任务
        $taskId = Db::name('ai_task')->insertGetId([
            'user_id' => $userId,
            'task_type' => 1, // 剧本生成
            'task_name' => 'AI 剧本生成',
            'task_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress' => 0,
            'status' => 0, // 排队中
            'cost_points' => $costPoints,
            'created_at' => time(),
        ]);
        
        // 异步执行 AI 任务
        $this->asyncExecuteTask($taskId);
        
        return json([
            'code' => 200,
            'msg' => '任务已提交，正在生成中',
            'data' => [
                'task_id' => $taskId,
                'status' => 0,
                'progress' => 0,
                'estimated_time' => 30, // 预计 30 秒
            ]
        ]);
    }

    /**
     * 查询任务进度
     * GET /api/v1/ai/task/:id
     */
    public function getTaskProgress($id)
    {
        $userId = $this->authUserId;
        
        $task = Db::name('ai_task')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$task) {
            return json(['code' => 404, 'msg' => '任务不存在']);
        }
        
        return json([
            'code' => 200,
            'data' => [
                'id' => $task['id'],
                'task_type' => $task['task_type'],
                'task_name' => $task['task_name'],
                'status' => $task['status'],
                'progress' => $task['progress'],
                'result_url' => $task['result_url'],
                'error_msg' => $task['error_msg'],
                'created_at' => $task['created_at'],
                'completed_at' => $task['completed_at'],
            ]
        ]);
    }

    /**
     * 语音合成
     * POST /api/v1/ai/audio/synthesize
     */
    public function synthesizeAudio()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'text' => 'require|max:5000',
            'voice' => 'require',
            'speed' => 'float|between:0.5,2.0',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $params = Request::param();
        $costPoints = 5; // 配音合成消耗点数
        
        // 创建任务
        $taskId = Db::name('ai_task')->insertGetId([
            'user_id' => $userId,
            'task_type' => 2, // 配音合成
            'task_name' => 'AI 配音合成',
            'task_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress' => 0,
            'status' => 0,
            'cost_points' => $costPoints,
            'created_at' => time(),
        ]);
        
        $this->asyncExecuteTask($taskId);
        
        return json([
            'code' => 200,
            'data' => [
                'task_id' => $taskId,
                'estimated_time' => 15,
            ]
        ]);
    }

    /**
     * 图像生成
     * POST /api/v1/ai/image/generate
     */
    public function generateImage()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'prompt' => 'require|max:1000',
            'style' => 'max:50',
            'width' => 'number',
            'height' => 'number',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $params = Request::param();
        $costPoints = 8; // 图像生成消耗点数
        
        $taskId = Db::name('ai_task')->insertGetId([
            'user_id' => $userId,
            'task_type' => 3, // 图像生成
            'task_name' => 'AI 图像生成',
            'task_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress' => 0,
            'status' => 0,
            'cost_points' => $costPoints,
            'created_at' => time(),
        ]);
        
        $this->asyncExecuteTask($taskId);
        
        return json([
            'code' => 200,
            'data' => [
                'task_id' => $taskId,
                'estimated_time' => 20,
            ]
        ]);
    }

    /**
     * 视频合成
     * POST /api/v1/ai/video/synthesize
     */
    public function synthesizeVideo()
    {
        $userId = $this->authUserId;
        
        $validate = validate([
            'script_id' => 'require|number',
            'audio_urls' => 'require|array',
            'image_urls' => 'require|array',
        ]);
        
        if (!$validate->check(Request::param())) {
            return json(['code' => 400, 'msg' => $validate->getError()]);
        }
        
        $params = Request::param();
        $costPoints = 50; // 视频合成消耗点数
        
        $taskId = Db::name('ai_task')->insertGetId([
            'user_id' => $userId,
            'task_type' => 4, // 视频生成
            'task_name' => 'AI 视频合成',
            'task_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress' => 0,
            'status' => 0,
            'cost_points' => $costPoints,
            'created_at' => time(),
        ]);
        
        $this->asyncExecuteTask($taskId);
        
        return json([
            'code' => 200,
            'data' => [
                'task_id' => $taskId,
                'estimated_time' => 120, // 视频合成耗时较长
            ]
        ]);
    }

    /**
     * 异步执行 AI 任务
     */
    protected function asyncExecuteTask($taskId)
    {
        // 使用消息队列异步执行
        // 这里简化处理，实际应该推送到队列
        
        $task = Db::name('ai_task')->where('id', $taskId)->find();
        if (!$task) return;
        
        // 更新状态为处理中
        Db::name('ai_task')->where('id', $taskId)->update([
            'status' => 1,
            'started_at' => time(),
        ]);
        
        // 调用 AI 服务
        try {
            $result = $this->executeAiTask($task);
            
            if ($result['success']) {
                // 任务成功
                Db::name('ai_task')->where('id', $taskId)->update([
                    'status' => 2,
                    'progress' => 100,
                    'result_url' => $result['url'] ?? '',
                    'completed_at' => time(),
                ]);
                
                // 扣除 AI 点数
                $this->userService->deductPoints($task['user_id'], $task['cost_points']);
                
                // 通过 WebSocket 推送进度
                $this->pushProgress($task['user_id'], $taskId, 100, 2);
            } else {
                throw new \Exception($result['error'] ?? 'AI 服务调用失败');
            }
        } catch (\Exception $e) {
            Log::error("AI 任务执行失败 [{$taskId}]: " . $e->getMessage());
            
            $retryCount = $task['retry_count'] + 1;
            if ($retryCount < 3) {
                // 重试
                Db::name('ai_task')->where('id', $taskId)->update([
                    'retry_count' => $retryCount,
                    'status' => 0, // 重新排队
                ]);
                $this->asyncExecuteTask($taskId);
            } else {
                // 失败
                Db::name('ai_task')->where('id', $taskId)->update([
                    'status' => 3,
                    'error_msg' => $e->getMessage(),
                    'completed_at' => time(),
                ]);
                
                $this->pushProgress($task['user_id'], $taskId, 0, 3, $e->getMessage());
            }
        }
    }

    /**
     * 执行具体的 AI 任务
     */
    protected function executeAiTask($task)
    {
        $params = json_decode($task['task_params'], true);
        
        switch ($task['task_type']) {
            case 1: // 剧本生成
                return $this->aiService->generateScript($params);
            
            case 2: // 语音合成
                return $this->aiService->synthesizeSpeech(
                    $params['text'] ?? '',
                    ['voice' => $params['voice'] ?? 'female']
                );
            
            case 3: // 图像生成
                return $this->aiService->generateImage(
                    $params['prompt'] ?? '',
                    [
                        'width' => $params['width'] ?? 1080,
                        'height' => $params['height'] ?? 1920,
                    ]
                );
            
            case 4: // 视频合成
                return $this->aiService->generateVideo(
                    $params['image_urls'] ?? [],
                    ['audio_urls' => $params['audio_urls'] ?? []]
                );
            
            default:
                return ['success' => false, 'error' => '未知任务类型'];
        }
    }

    /**
     * 推送进度到 WebSocket
     */
    protected function pushProgress($userId, $taskId, $progress, $status, $errorMsg = '')
    {
        // TODO: 实现 WebSocket 推送
        // 这里可以集成 WebSocket 服务
    }
}
