<template>
  <view class="container">
    <!-- 顶部 Banner -->
    <view class="banner">
      <image class="banner-img" src="/static/images/banner.png" mode="aspectFill" />
      <view class="banner-text">
        <text class="banner-title">AI 短剧制作</text>
        <text class="banner-subtitle">一键生成 · 零拍摄 · 低成本</text>
      </view>
    </view>

    <!-- 快捷入口 -->
    <view class="quick-entry">
      <view class="entry-item" @click="goToCreate('script')">
        <view class="entry-icon">📝</view>
        <text class="entry-text">AI 写剧本</text>
      </view>
      <view class="entry-item" @click="goToCreate('audio')">
        <view class="entry-icon">🎙️</view>
        <text class="entry-text">AI 配音</text>
      </view>
      <view class="entry-item" @click="goToCreate('video')">
        <view class="entry-icon">🎬</view>
        <text class="entry-text">AI 成片</text>
      </view>
      <view class="entry-item" @click="goToMaterial()">
        <view class="entry-icon">📁</view>
        <text class="entry-text">素材库</text>
      </view>
    </view>

    <!-- 热门模板 -->
    <view class="section">
      <view class="section-header">
        <text class="section-title">热门剧本模板</text>
        <text class="section-more" @click="goToTemplates()">查看全部 ></text>
      </view>
      <scroll-view class="template-list" scroll-x>
        <view class="template-item" v-for="(item, index) in templates" :key="index" @click="useTemplate(item)">
          <image class="template-cover" :src="item.cover" mode="aspectFill" />
          <view class="template-info">
            <text class="template-name">{{ item.title }}</text>
            <text class="template-usage">{{ item.usage_count }}次使用</text>
          </view>
          <view class="template-tag" v-if="item.is_hot">热门</view>
        </view>
      </scroll-view>
    </view>

    <!-- 我的作品 -->
    <view class="section">
      <view class="section-header">
        <text class="section-title">我的作品</text>
        <text class="section-more" @click="goToWorks()">查看全部 ></text>
      </view>
      <view class="works-grid" v-if="works.length > 0">
        <view class="works-item" v-for="(item, index) in works" :key="index" @click="viewWork(item)">
          <image class="works-cover" :src="item.cover_url" mode="aspectFill" />
          <view class="works-info">
            <text class="works-title">{{ item.title }}</text>
            <text class="works-meta">{{ item.duration }}s · {{ formatPlayType(item.play_type) }}</text>
          </view>
          <view class="works-status" :class="'status-' + item.status">
            {{ formatStatus(item.status) }}
          </view>
        </view>
      </view>
      <view class="empty-works" v-else @click="goToCreate()">
        <text class="empty-icon">🎬</text>
        <text class="empty-text">暂无作品，立即创作</text>
      </view>
    </view>

    <!-- 底部导航 -->
    <view class="tabbar">
      <view class="tabbar-item" :class="{ active: currentTab === 'home' }" @click="switchTab('home')">
        <text class="tabbar-icon">🏠</text>
        <text class="tabbar-text">首页</text>
      </view>
      <view class="tabbar-item" :class="{ active: currentTab === 'create' }" @click="switchTab('create')">
        <text class="tabbar-icon">✨</text>
        <text class="tabbar-text">创作</text>
      </view>
      <view class="tabbar-item" :class="{ active: currentTab === 'works' }" @click="switchTab('works')">
        <text class="tabbar-icon">📁</text>
        <text class="tabbar-text">作品</text>
      </view>
      <view class="tabbar-item" :class="{ active: currentTab === 'user' }" @click="switchTab('user')">
        <text class="tabbar-icon">👤</text>
        <text class="tabbar-text">我的</text>
      </view>
    </view>
  </view>
</template>

<script>
import { getTemplates, getMyWorks } from '@/api/index.js'

export default {
  data() {
    return {
      currentTab: 'home',
      templates: [],
      works: []
    }
  },
  onLoad() {
    this.loadData()
  },
  onShow() {
    this.loadData()
  },
  methods: {
    async loadData() {
      // 加载热门模板
      const templateRes = await getTemplates({ is_hot: 1, limit: 10 })
      if (templateRes.code === 200) {
        this.templates = templateRes.data
      }
      
      // 加载我的作品
      const worksRes = await getMyWorks({ limit: 6 })
      if (worksRes.code === 200) {
        this.works = worksRes.data
      }
    },
    
    goToCreate(type = '') {
      const url = type ? `/pages/create/index?type=${type}` : '/pages/create/index'
      uni.navigateTo({ url })
    },
    
    goToMaterial() {
      uni.navigateTo({ url: '/pages/material/index' })
    },
    
    goToTemplates() {
      uni.navigateTo({ url: '/pages/create/template-list' })
    },
    
    goToWorks() {
      uni.switchTab({ url: '/pages/works/index' })
    },
    
    useTemplate(template) {
      uni.navigateTo({ 
        url: `/pages/create/index?template_id=${template.id}` 
      })
    },
    
    viewWork(work) {
      uni.navigateTo({ 
        url: `/pages/works/detail?id=${work.id}` 
      })
    },
    
    switchTab(tab) {
      if (tab === 'home') {
        // 当前页
      } else if (tab === 'create') {
        uni.navigateTo({ url: '/pages/create/index' })
      } else if (tab === 'works') {
        uni.switchTab({ url: '/pages/works/index' })
      } else if (tab === 'user') {
        uni.switchTab({ url: '/pages/user/index' })
      }
      this.currentTab = tab
    },
    
    formatPlayType(type) {
      const map = { 1: '情感', 2: '反转', 3: '搞笑', 4: '职场', 5: '电商' }
      return map[type] || '短剧'
    },
    
    formatStatus(status) {
      const map = { 0: '制作中', 1: '已完成', 2: '审核中', 3: '已发布', 4: '已下架' }
      return map[status] || '未知'
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  padding-bottom: 120rpx;
  background: #f5f5f5;
  min-height: 100vh;
}

.banner {
  position: relative;
  height: 300rpx;
  overflow: hidden;
  
  .banner-img {
    width: 100%;
    height: 100%;
  }
  
  .banner-text {
    position: absolute;
    bottom: 30rpx;
    left: 30rpx;
    color: #fff;
    
    .banner-title {
      font-size: 48rpx;
      font-weight: bold;
      display: block;
    }
    
    .banner-subtitle {
      font-size: 28rpx;
      opacity: 0.9;
    }
  }
}

.quick-entry {
  display: flex;
  justify-content: space-around;
  padding: 40rpx 20rpx;
  background: #fff;
  margin-bottom: 20rpx;
  
  .entry-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    .entry-icon {
      font-size: 60rpx;
      margin-bottom: 10rpx;
    }
    
    .entry-text {
      font-size: 26rpx;
      color: #666;
    }
  }
}

.section {
  background: #fff;
  margin-bottom: 20rpx;
  padding: 30rpx;
  
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20rpx;
    
    .section-title {
      font-size: 32rpx;
      font-weight: bold;
      color: #333;
    }
    
    .section-more {
      font-size: 26rpx;
      color: #999;
    }
  }
}

.template-list {
  white-space: nowrap;
  
  .template-item {
    display: inline-block;
    width: 240rpx;
    margin-right: 20rpx;
    position: relative;
    
    .template-cover {
      width: 240rpx;
      height: 320rpx;
      border-radius: 12rpx;
    }
    
    .template-info {
      padding: 10rpx 0;
      
      .template-name {
        font-size: 28rpx;
        color: #333;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .template-usage {
        font-size: 22rpx;
        color: #999;
      }
    }
    
    .template-tag {
      position: absolute;
      top: 10rpx;
      right: 10rpx;
      background: linear-gradient(135deg, #ff6b6b, #ff8e53);
      color: #fff;
      font-size: 20rpx;
      padding: 4rpx 12rpx;
      border-radius: 20rpx;
    }
  }
}

.works-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
  
  .works-item {
    position: relative;
    background: #f9f9f9;
    border-radius: 12rpx;
    overflow: hidden;
    
    .works-cover {
      width: 100%;
      height: 300rpx;
    }
    
    .works-info {
      padding: 15rpx;
      
      .works-title {
        font-size: 28rpx;
        color: #333;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .works-meta {
        font-size: 22rpx;
        color: #999;
      }
    }
    
    .works-status {
      position: absolute;
      top: 10rpx;
      right: 10rpx;
      font-size: 20rpx;
      padding: 4rpx 12rpx;
      border-radius: 20rpx;
      
      &.status-0 { background: #1890ff; color: #fff; }
      &.status-1 { background: #52c41a; color: #fff; }
      &.status-2 { background: #faad14; color: #fff; }
      &.status-3 { background: #722ed1; color: #fff; }
      &.status-4 { background: #d9d9d9; color: #666; }
    }
  }
}

.empty-works {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60rpx 0;
  
  .empty-icon {
    font-size: 80rpx;
    margin-bottom: 20rpx;
  }
  
  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
}

.tabbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100rpx;
  background: #fff;
  display: flex;
  justify-content: space-around;
  align-items: center;
  border-top: 1rpx solid #eee;
  padding-bottom: env(safe-area-inset-bottom);
  
  .tabbar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    &.active .tabbar-icon {
      color: #1890ff;
    }
    
    .tabbar-icon {
      font-size: 40rpx;
      margin-bottom: 4rpx;
    }
    
    .tabbar-text {
      font-size: 22rpx;
      color: #999;
    }
  }
}
</style>
