<?php
// +----------------------------------------------------------------------
// | AI 短剧制作系统 - AI 服务层
// +----------------------------------------------------------------------

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * AI 服务层 - 统一调度各 AI 服务商
 */
class AiService
{
    /**
     * AI 服务商配置
     */
    protected $providers = [
        'text' => ['wenxin', 'tongyi', 'xunfei'],      // 文本生成
        'audio' => ['xunfei', 'tencent'],              // 语音合成
        'image' => ['wenxin', 'tongyi', 'jimeng'],     // 图像生成
        'video' => ['jimeng', 'jianying'],             // 视频生成
    ];

    /**
     * 生成剧本
     * @param array $params 剧本参数
     * @return array
     */
    public function generateScript(array $params): array
    {
        $defaultPrompt = $this->buildScriptPrompt($params);
        
        // 按优先级尝试 AI 服务商
        foreach ($this->providers['text'] as $provider) {
            try {
                if (!$this->isProviderAvailable($provider, 'text')) {
                    continue;
                }
                
                $result = $this->callTextApi($provider, $defaultPrompt, $params);
                if ($result && !empty($result['content'])) {
                    // 记录成功调用
                    $this->recordApiCall($provider, 'text', true);
                    return [
                        'success' => true,
                        'provider' => $provider,
                        'content' => $result['content'],
                        'scenes' => $result['scenes'] ?? [],
                    ];
                }
            } catch (\Exception $e) {
                Log::error("AI 剧本生成失败 [{$provider}]: " . $e->getMessage());
                $this->recordApiCall($provider, 'text', false);
                continue;
            }
        }
        
        return ['success' => false, 'error' => '所有 AI 服务商调用失败'];
    }

    /**
     * 语音合成
     * @param string $text 文本内容
     * @param array $options 语音选项
     * @return array
     */
    public function synthesizeSpeech(string $text, array $options = []): array
    {
        $defaultOptions = [
            'voice' => 'female',      // 音色
            'speed' => 1.0,           // 语速
            'volume' => 1.0,          // 音量
            'emotion' => 'normal',    // 情感
        ];
        $options = array_merge($defaultOptions, $options);

        foreach ($this->providers['audio'] as $provider) {
            try {
                if (!$this->isProviderAvailable($provider, 'audio')) {
                    continue;
                }
                
                $result = $this->callAudioApi($provider, $text, $options);
                if ($result && !empty($result['audio_url'])) {
                    $this->recordApiCall($provider, 'audio', true);
                    return [
                        'success' => true,
                        'provider' => $provider,
                        'audio_url' => $result['audio_url'],
                        'duration' => $result['duration'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("AI 语音合成失败 [{$provider}]: " . $e->getMessage());
                $this->recordApiCall($provider, 'audio', false);
                continue;
            }
        }
        
        return ['success' => false, 'error' => '所有语音服务商调用失败'];
    }

    /**
     * 图像生成
     * @param string $prompt 图像描述
     * @param array $options 图像选项
     * @return array
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $defaultOptions = [
            'width' => 1080,
            'height' => 1920,
            'style' => 'realistic',
            'count' => 1,
        ];
        $options = array_merge($defaultOptions, $options);

        foreach ($this->providers['image'] as $provider) {
            try {
                if (!$this->isProviderAvailable($provider, 'image')) {
                    continue;
                }
                
                $result = $this->callImageApi($provider, $prompt, $options);
                if ($result && !empty($result['image_url'])) {
                    $this->recordApiCall($provider, 'image', true);
                    return [
                        'success' => true,
                        'provider' => $provider,
                        'image_url' => $result['image_url'],
                        'width' => $options['width'],
                        'height' => $options['height'],
                    ];
                }
            } catch (\Exception $e) {
                Log::error("AI 图像生成失败 [{$provider}]: " . $e->getMessage());
                $this->recordApiCall($provider, 'image', false);
                continue;
            }
        }
        
        return ['success' => false, 'error' => '所有图像服务商调用失败'];
    }

    /**
     * 视频生成
     * @param array $frames 视频帧列表
     * @param array $options 视频选项
     * @return array
     */
    public function generateVideo(array $frames, array $options = []): array
    {
        $defaultOptions = [
            'fps' => 30,
            'resolution' => '1080p',
            'transition' => 'fade',
        ];
        $options = array_merge($defaultOptions, $options);

        foreach ($this->providers['video'] as $provider) {
            try {
                if (!$this->isProviderAvailable($provider, 'video')) {
                    continue;
                }
                
                $result = $this->callVideoApi($provider, $frames, $options);
                if ($result && !empty($result['video_url'])) {
                    $this->recordApiCall($provider, 'video', true);
                    return [
                        'success' => true,
                        'provider' => $provider,
                        'video_url' => $result['video_url'],
                        'duration' => $result['duration'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("AI 视频生成失败 [{$provider}]: " . $e->getMessage());
                $this->recordApiCall($provider, 'video', false);
                continue;
            }
        }
        
        return ['success' => false, 'error' => '所有视频服务商调用失败'];
    }

    /**
     * 构建剧本生成 Prompt
     */
    protected function buildScriptPrompt(array $params): string
    {
        $typeMap = [
            1 => '情感',
            2 => '反转',
            3 => '搞笑',
            4 => '职场',
            5 => '电商',
        ];
        
        $prompt = "请创作一个{$typeMap[$params['play_type']] ?? '短剧'}剧本。\n";
        $prompt .= "主题：{$params['theme']}\n";
        $prompt .= "时长：约{$params['duration']}秒\n";
        $prompt .= "风格：{$params['style']}\n";
        
        if (!empty($params['characters'])) {
            $prompt .= "人物：" . json_encode($params['characters'], JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if (!empty($params['twist_point'])) {
            $prompt .= "反转点：{$params['twist_point']}\n";
        }
        
        $prompt .= "\n要求：\n";
        $prompt .= "1. 剧情紧凑，节奏明快\n";
        $prompt .= "2. 对话生动，符合人物性格\n";
        $prompt .= "3. 包含完整的起承转合\n";
        $prompt .= "4. 输出格式：先输出剧情梗概，再输出分镜脚本（包含场景、画面描述、台词、时长）\n";
        
        return $prompt;
    }

    /**
     * 检查服务商是否可用
     */
    protected function isProviderAvailable(string $provider, string $type): bool
    {
        $cacheKey = "ai_provider:{$provider}:{$type}";
        $status = Cache::get($cacheKey);
        
        if ($status !== null) {
            return $status === 1;
        }
        
        // 查询数据库配置
        $config = Db::name('ai_provider_config')
            ->where('provider', $provider)
            ->where('service_type', $type)
            ->where('is_enabled', 1)
            ->find();
        
        if (!$config) {
            Cache::set($cacheKey, 0, 300);
            return false;
        }
        
        // 检查每日调用限制
        if ($config['daily_limit'] > 0 && $config['current_count'] >= $config['daily_limit']) {
            // 检查是否需要重置
            if ($config['reset_date'] != date('Y-m-d')) {
                Db::name('ai_provider_config')
                    ->where('id', $config['id'])
                    ->update(['current_count' => 0, 'reset_date' => date('Y-m-d')]);
            } else {
                Cache::set($cacheKey, 0, 300);
                return false;
            }
        }
        
        Cache::set($cacheKey, 1, 300);
        return true;
    }

    /**
     * 记录 API 调用
     */
    protected function recordApiCall(string $provider, string $type, bool $success): void
    {
        if (!$success) {
            return;
        }
        
        Db::name('ai_provider_config')
            ->where('provider', $provider)
            ->where('service_type', $type)
            ->inc('current_count')
            ->update(['reset_date' => date('Y-m-d')]);
        
        Cache::delete("ai_provider:{$provider}:{$type}");
    }

    /**
     * 调用文本 API（具体实现需根据各服务商文档）
     */
    protected function callTextApi(string $provider, string $prompt, array $params): array
    {
        // TODO: 实现各服务商的具体调用逻辑
        // 文心一言、通义千问、讯飞星火等
        return [];
    }

    /**
     * 调用音频 API
     */
    protected function callAudioApi(string $provider, string $text, array $options): array
    {
        // TODO: 实现讯飞、腾讯云等语音合成接口
        return [];
    }

    /**
     * 调用图像 API
     */
    protected function callImageApi(string $provider, string $prompt, array $options): array
    {
        // TODO: 实现文心一格、通义万相等图像生成接口
        return [];
    }

    /**
     * 调用视频 API
     */
    protected function callVideoApi(string $provider, array $frames, array $options): array
    {
        // TODO: 实现即梦 AI、剪映等视频生成接口
        return [];
    }
}
