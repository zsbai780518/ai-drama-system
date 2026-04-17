<template>
  <view class="container">
    <!-- 顶部导航 -->
    <view class="header">
      <view class="back" @click="goBack()">
        <text class="back-icon">←</text>
      </view>
      <text class="title">AI 短剧创作</text>
      <view class="placeholder"></view>
    </view>

    <!-- 创作类型选择 -->
    <view class="type-selector" v-if="!selectedType">
      <text class="section-title">选择创作类型</text>
      
      <view class="type-grid">
        <view class="type-card" @click="selectType('script')">
          <view class="type-icon">📝</view>
          <text class="type-name">AI 写剧本</text>
          <text class="type-desc">输入主题，一键生成完整剧本</text>
          <view class="type-cost">消耗 10 点数</view>
        </view>
        
        <view class="type-card" @click="selectType('audio')">
          <view class="type-icon">🎙️</view>
          <text class="type-name">AI 配音</text>
          <text class="type-desc">文本转语音，多音色可选</text>
          <view class="type-cost">消耗 5 点数</view>
        </view>
        
        <view class="type-card" @click="selectType('video')">
          <view class="type-icon">🎬</view>
          <text class="type-name">AI 成片</text>
          <text class="type-desc">完整短剧一键生成</text>
          <view class="type-cost">消耗 50 点数</view>
        </view>
        
        <view class="type-card" @click="selectType('image')">
          <view class="type-icon">🖼️</view>
          <text class="type-name">AI 绘图</text>
          <text class="type-desc">文生图，多种风格</text>
          <view class="type-cost">消耗 8 点数</view>
        </view>
      </view>
    </view>

    <!-- 剧本生成表单 -->
    <view class="form-container" v-if="selectedType === 'script'">
      <view class="form-section">
        <text class="form-label">短剧类型 <text class="required">*</text></text>
        <view class="radio-group">
          <view 
            class="radio-item" 
            :class="{ active: scriptForm.play_type === item.value }"
            v-for="item in playTypes" 
            :key="item.value"
            @click="scriptForm.play_type = item.value"
          >
            {{ item.label }}
          </view>
        </view>
      </view>

      <view class="form-section">
        <text class="form-label">主题 <text class="required">*</text></text>
        <textarea 
          class="textarea" 
          v-model="scriptForm.theme"
          placeholder="请输入短剧主题，例如：霸道总裁爱上我、逆袭打脸、豪门恩怨..."
          maxlength="200"
        />
      </view>

      <view class="form-section">
        <text class="form-label">预计时长（秒）<text class="required">*</text></text>
        <slider 
          :value="scriptForm.duration" 
          :min="30" 
          :max="300" 
          :step="10"
          show-value
          @change="e => scriptForm.duration = e.detail.value"
        />
      </view>

      <view class="form-section">
        <text class="form-label">风格</text>
        <input 
          class="input" 
          v-model="scriptForm.style"
          placeholder="例如：悬疑、甜宠、虐恋、搞笑..."
        />
      </view>

      <view class="form-section">
        <text class="form-label">反转点（可选）</text>
        <textarea 
          class="textarea" 
          v-model="scriptForm.twist_point"
          placeholder="描述剧情反转点，让故事更精彩"
          maxlength="500"
        />
      </view>

      <view class="action-btn" @click="submitScript">
        立即生成
      </view>
    </view>

    <!-- AI 配音表单 -->
    <view class="form-container" v-if="selectedType === 'audio'">
      <view class="form-section">
        <text class="form-label">配音文本 <text class="required">*</text></text>
        <textarea 
          class="textarea" 
          v-model="audioForm.text"
          placeholder="请输入需要配音的文本内容..."
          maxlength="5000"
        />
      </view>

      <view class="form-section">
        <text class="form-label">选择音色 <text class="required">*</text></text>
        <view class="voice-list">
          <view 
            class="voice-item" 
            :class="{ active: audioForm.voice === item.value }"
            v-for="item in voices" 
            :key="item.value"
            @click="audioForm.voice = item.value"
          >
            <view class="voice-icon">{{ item.icon }}</view>
            <view class="voice-info">
              <text class="voice-name">{{ item.name }}</text>
              <text class="voice-desc">{{ item.desc }}</text>
            </view>
            <view class="voice-play" @click.stop="playVoice(item)">
              ▶
            </view>
          </view>
        </view>
      </view>

      <view class="form-section">
        <text class="form-label">语速：{{ audioForm.speed }}x</text>
        <slider 
          :value="audioForm.speed" 
          :min="0.5" 
          :max="2.0" 
          :step="0.1"
          show-value
          @change="e => audioForm.speed = parseFloat(e.detail.value)"
        />
      </view>

      <view class="action-btn" @click="submitAudio">
        生成配音
      </view>
    </view>

    <!-- 任务进度 -->
    <view class="progress-modal" v-if="showProgress">
      <view class="progress-content">
        <text class="progress-title">正在生成中...</text>
        <view class="progress-bar">
          <view class="progress-fill" :style="{ width: progress + '%' }"></view>
        </view>
        <text class="progress-text">{{ progress }}%</text>
        <text class="progress-hint">AI 正在努力创作，请稍候</text>
        
        <view class="progress-actions" v-if="progress >= 100">
          <view class="action-btn primary" @click="viewResult">
            查看结果
          </view>
          <view class="action-btn" @click="createNew">
            继续创作
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { generateScript, synthesizeAudio, getTaskProgress } from '@/api/index.js'

export default {
  data() {
    return {
      selectedType: '',
      showProgress: false,
      progress: 0,
      currentTaskId: null,
      
      playTypes: [
        { value: 1, label: '情感' },
        { value: 2, label: '反转' },
        { value: 3, label: '搞笑' },
        { value: 4, label: '职场' },
        { value: 5, label: '电商' },
      ],
      
      scriptForm: {
        play_type: 1,
        theme: '',
        duration: 60,
        style: '',
        twist_point: '',
      },
      
      audioForm: {
        text: '',
        voice: 'female',
        speed: 1.0,
      },
      
      voices: [
        { value: 'female', name: '温柔女声', desc: '适合情感剧、旁白', icon: '👩' },
        { value: 'male', name: '沉稳男声', desc: '适合职场剧、纪录片', icon: '👨' },
        { value: 'girl', name: '可爱童声', desc: '适合动画、儿童剧', icon: '👧' },
        { value: 'old', name: '沧桑老人', desc: '适合回忆、历史剧', icon: '👴' },
      ],
    }
  },
  
  onLoad(options) {
    if (options.type) {
      this.selectType(options.type)
    }
  },
  
  methods: {
    selectType(type) {
      this.selectedType = type
    },
    
    goBack() {
      if (this.selectedType) {
        this.selectedType = ''
      } else {
        uni.navigateBack()
      }
    },
    
    async submitScript() {
      // 验证表单
      if (!this.scriptForm.theme) {
        uni.showToast({ title: '请输入主题', icon: 'none' })
        return
      }
      
      try {
        const res = await generateScript(this.scriptForm)
        if (res.code === 200) {
          this.currentTaskId = res.data.task_id
          this.showProgress = true
          this.watchProgress()
        }
      } catch (e) {
        console.error('提交失败', e)
      }
    },
    
    async submitAudio() {
      if (!this.audioForm.text) {
        uni.showToast({ title: '请输入配音文本', icon: 'none' })
        return
      }
      
      try {
        const res = await synthesizeAudio(this.audioForm)
        if (res.code === 200) {
          this.currentTaskId = res.data.task_id
          this.showProgress = true
          this.watchProgress()
        }
      } catch (e) {
        console.error('提交失败', e)
      }
    },
    
    watchProgress() {
      const timer = setInterval(async () => {
        if (!this.currentTaskId) {
          clearInterval(timer)
          return
        }
        
        try {
          const res = await getTaskProgress(this.currentTaskId)
          if (res.code === 200) {
            this.progress = res.data.progress
            
            if (res.data.status === 2 || res.data.status === 3) {
              // 完成或失败
              clearInterval(timer)
            }
          }
        } catch (e) {
          console.error('查询进度失败', e)
        }
      }, 2000)
    },
    
    viewResult() {
      // TODO: 跳转到结果页面
      uni.showToast({ title: '功能开发中', icon: 'none' })
    },
    
    createNew() {
      this.showProgress = false
      this.progress = 0
      this.currentTaskId = null
      this.selectedType = ''
    },
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  background: #fff;
  
  .back {
    .back-icon {
      font-size: 40rpx;
      color: #333;
    }
  }
  
  .title {
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
  }
  
  .placeholder {
    width: 40rpx;
  }
}

.type-selector {
  padding: 30rpx;
  
  .section-title {
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 30rpx;
    display: block;
  }
}

.type-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
}

.type-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  
  .type-icon {
    font-size: 80rpx;
    margin-bottom: 15rpx;
  }
  
  .type-name {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 10rpx;
  }
  
  .type-desc {
    font-size: 24rpx;
    color: #999;
    text-align: center;
    margin-bottom: 15rpx;
  }
  
  .type-cost {
    font-size: 22rpx;
    color: #ff6b6b;
    background: #fff0f0;
    padding: 4rpx 12rpx;
    border-radius: 20rpx;
  }
}

.form-container {
  padding: 30rpx;
}

.form-section {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  
  .form-label {
    font-size: 28rpx;
    color: #333;
    display: block;
    margin-bottom: 20rpx;
    
    .required {
      color: #ff4444;
    }
  }
}

.radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 15rpx;
}

.radio-item {
  padding: 15rpx 30rpx;
  background: #f5f5f5;
  border-radius: 30rpx;
  font-size: 28rpx;
  color: #666;
  
  &.active {
    background: #1890ff;
    color: #fff;
  }
}

.textarea {
  width: 100%;
  min-height: 200rpx;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 12rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}

.input {
  width: 100%;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 12rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}

.voice-list {
  .voice-item {
    display: flex;
    align-items: center;
    padding: 20rpx;
    background: #f5f5f5;
    border-radius: 12rpx;
    margin-bottom: 15rpx;
    
    &.active {
      background: #e6f7ff;
      border: 2rpx solid #1890ff;
    }
    
    .voice-icon {
      font-size: 60rpx;
      margin-right: 20rpx;
    }
    
    .voice-info {
      flex: 1;
      
      .voice-name {
        font-size: 28rpx;
        color: #333;
        display: block;
        font-weight: bold;
      }
      
      .voice-desc {
        font-size: 24rpx;
        color: #999;
      }
    }
    
    .voice-play {
      width: 60rpx;
      height: 60rpx;
      border-radius: 50%;
      background: #1890ff;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24rpx;
    }
  }
}

.action-btn {
  background: linear-gradient(135deg, #1890ff, #096dd9);
  color: #fff;
  text-align: center;
  padding: 30rpx;
  border-radius: 30rpx;
  font-size: 32rpx;
  font-weight: bold;
  margin-top: 30rpx;
  
  &.primary {
    background: linear-gradient(135deg, #52c41a, #389e0d);
  }
}

.progress-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  
  .progress-content {
    background: #fff;
    border-radius: 24rpx;
    padding: 60rpx 40rpx;
    width: 80%;
    text-align: center;
    
    .progress-title {
      font-size: 36rpx;
      font-weight: bold;
      color: #333;
      display: block;
      margin-bottom: 40rpx;
    }
    
    .progress-bar {
      height: 12rpx;
      background: #f0f0f0;
      border-radius: 6rpx;
      overflow: hidden;
      margin-bottom: 20rpx;
      
      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #1890ff, #52c41a);
        transition: width 0.3s;
      }
    }
    
    .progress-text {
      font-size: 48rpx;
      font-weight: bold;
      color: #1890ff;
      display: block;
      margin-bottom: 20rpx;
    }
    
    .progress-hint {
      font-size: 26rpx;
      color: #999;
      display: block;
      margin-bottom: 40rpx;
    }
    
    .progress-actions {
      display: flex;
      gap: 20rpx;
      
      .action-btn {
        flex: 1;
        margin-top: 0;
      }
    }
  }
}
</style>
