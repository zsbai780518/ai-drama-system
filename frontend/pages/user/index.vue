<template>
  <view class="container">
    <!-- 用户信息卡片 -->
    <view class="user-card">
      <view class="user-info">
        <image class="avatar" :src="userInfo.avatar || '/static/images/default-avatar.png'" mode="aspectFill" />
        <view class="user-detail">
          <text class="nickname">{{ userInfo.nickname || '点击登录' }}</text>
          <view class="member-tag" v-if="userInfo.member_level > 0">
            {{ memberLevelText }}
          </view>
          <text class="mobile" v-if="userInfo.mobile">{{ userInfo.mobile }}</text>
        </view>
        <view class="settings" @click="goToSettings()">
          <text class="settings-icon">⚙️</text>
        </view>
      </view>
      
      <!-- 会员信息 -->
      <view class="member-info" v-if="userInfo.member_level > 0 && userInfo.member_expire > Date.now()/1000">
        <view class="member-progress">
          <text class="member-text">会员有效期至：{{ formatDate(userInfo.member_expire) }}</text>
        </view>
      </view>
      <view class="member-apply" v-else @click="goToVip()">
        <text class="member-apply-text">👑 开通会员，享受更多权益</text>
      </view>
    </view>

    <!-- 数据统计 -->
    <view class="stats-row">
      <view class="stat-item" @click="goToWorks()">
        <text class="stat-value">{{ stats.works }}</text>
        <text class="stat-label">我的作品</text>
      </view>
      <view class="stat-divider"></view>
      <view class="stat-item" @click="goToDrafts()">
        <text class="stat-value">{{ stats.drafts }}</text>
        <text class="stat-label">草稿箱</text>
      </view>
      <view class="stat-divider"></view>
      <view class="stat-item">
        <text class="stat-value">{{ stats.favorite }}</text>
        <text class="stat-label">收藏夹</text>
      </view>
    </view>

    <!-- 资产信息 -->
    <view class="assets-card">
      <view class="card-title">我的资产</view>
      <view class="assets-row">
        <view class="asset-item">
          <view class="asset-icon">💰</view>
          <view class="asset-info">
            <text class="asset-value">¥{{ userInfo.balance || '0.00' }}</text>
            <text class="asset-label">余额</text>
          </view>
          <view class="asset-action" @click="goToRecharge('balance')">
            充值
          </view>
        </view>
        <view class="asset-divider"></view>
        <view class="asset-item">
          <view class="asset-icon">✨</view>
          <view class="asset-info">
            <text class="asset-value">{{ userInfo.ai_points || 0 }}</text>
            <text class="asset-label">AI 点数</text>
          </view>
          <view class="asset-action" @click="goToRecharge('points')">
            充值
          </view>
        </view>
        <view class="asset-divider"></view>
        <view class="asset-item">
          <view class="asset-icon">🎁</view>
          <view class="asset-info">
            <text class="asset-value">{{ userInfo.free_points || 0 }}</text>
            <text class="asset-label">免费点数</text>
          </view>
          <view class="asset-action" @click="showFreePointsRule()">
            说明
          </view>
        </view>
      </view>
    </view>

    <!-- 功能菜单 -->
    <view class="menu-section">
      <view class="menu-group">
        <view class="menu-item" @click="goTo('/pages/works/index')">
          <view class="menu-icon">🎬</view>
          <text class="menu-text">作品管理</text>
          <text class="menu-arrow">></text>
        </view>
        <view class="menu-item" @click="goTo('/pages/material/index')">
          <view class="menu-icon">📁</view>
          <text class="menu-text">素材中心</text>
          <text class="menu-arrow">></text>
        </view>
        <view class="menu-item" @click="goTo('/pages/create/template-list')">
          <view class="menu-icon">📝</view>
          <text class="menu-text">剧本模板</text>
          <text class="menu-arrow">></text>
        </view>
      </view>

      <view class="menu-group">
        <view class="menu-item" @click="goTo('/pages/member/index')">
          <view class="menu-icon">👑</view>
          <text class="menu-text">会员中心</text>
          <text class="menu-arrow">></text>
        </view>
        <view class="menu-item" @click="goTo('/pages/order/list')">
          <view class="menu-icon">📋</view>
          <text class="menu-text">订单记录</text>
          <text class="menu-arrow">></text>
        </view>
      </view>

      <view class="menu-group">
        <view class="menu-item" @click="goToNotification()">
          <view class="menu-icon">🔔</view>
          <text class="menu-text">消息通知</text>
          <view class="badge" v-if="unreadCount > 0">{{ unreadCount }}</view>
          <text class="menu-arrow">></text>
        </view>
        <view class="menu-item" @click="goTo('/pages/user/help')">
          <view class="menu-icon">❓</view>
          <text class="menu-text">帮助与反馈</text>
          <text class="menu-arrow">></text>
        </view>
        <view class="menu-item" @click="goTo('/pages/user/about')">
          <view class="menu-icon">ℹ️</view>
          <text class="menu-text">关于我们</text>
          <text class="menu-arrow">></text>
        </view>
      </view>
    </view>

    <!-- 退出登录 -->
    <view class="logout-btn" v-if="userInfo.id" @click="logout()">
      退出登录
    </view>
    <view class="login-btn" v-else @click="goToLogin()">
      立即登录
    </view>
  </view>
</template>

<script>
import { getUserProfile, getNotificationList } from '@/api/index.js'
import { getToken, clearToken } from '@/api/request.js'

export default {
  data() {
    return {
      userInfo: {
        id: 0,
        nickname: '',
        avatar: '',
        mobile: '',
        member_level: 0,
        member_expire: 0,
        balance: 0,
        ai_points: 0,
        free_points: 0,
      },
      stats: {
        works: 0,
        drafts: 0,
        favorite: 0,
      },
      unreadCount: 0,
    }
  },
  
  computed: {
    memberLevelText() {
      const map = { 1: '月度会员', 2: '季度会员', 3: '年度会员' }
      return map[this.userInfo.member_level] || '会员'
    },
  },
  
  onShow() {
    this.loadData()
  },
  
  methods: {
    async loadData() {
      const token = getToken()
      if (!token) {
        this.userInfo = { id: 0 }
        return
      }
      
      try {
        // 加载用户信息
        const userRes = await getUserProfile()
        if (userRes.code === 200) {
          this.userInfo = userRes.data
        }
        
        // 加载未读消息数
        const notifyRes = await getNotificationList({ is_read: 0, page_size: 1 })
        if (notifyRes.code === 200) {
          this.unreadCount = notifyRes.data.unread_count
        }
      } catch (e) {
        console.error('加载用户信息失败', e)
      }
    },
    
    goTo(url) {
      uni.navigateTo({ url })
    },
    
    goToLogin() {
      uni.navigateTo({ url: '/pages/user/login' })
    },
    
    goToSettings() {
      uni.navigateTo({ url: '/pages/user/settings' })
    },
    
    goToVip() {
      uni.navigateTo({ url: '/pages/vip/index' })
    },
    
    goToWorks() {
      uni.switchTab({ url: '/pages/works/index' })
    },
    
    goToDrafts() {
      uni.navigateTo({ url: '/pages/works/drafts' })
    },
    
    goToRecharge(type) {
      uni.navigateTo({ url: `/pages/member/recharge?type=${type}` })
    },
    
    goToNotification() {
      uni.navigateTo({ url: '/pages/notification/index' })
    },
    
    showFreePointsRule() {
      uni.showModal({
        title: '免费点数说明',
        content: '免费点数每日凌晨自动重置，当日有效，过期清零。会员用户可享受更高的每日免费点数额度。',
        showCancel: false
      })
    },
    
    async logout() {
      uni.showModal({
        title: '确认退出',
        content: '确定要退出登录吗？',
        success: async (res) => {
          if (res.confirm) {
            clearToken()
            this.userInfo = { id: 0 }
            uni.showToast({ title: '已退出登录', icon: 'success' })
          }
        }
      })
    },
    
    formatDate(timestamp) {
      if (!timestamp) return ''
      const date = new Date(timestamp * 1000)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
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

.user-card {
  background: linear-gradient(135deg, #1890ff, #096dd9);
  padding: 40rpx 30rpx 30rpx;
  margin-bottom: 20rpx;
  
  .user-info {
    display: flex;
    align-items: center;
    margin-bottom: 20rpx;
    
    .avatar {
      width: 120rpx;
      height: 120rpx;
      border-radius: 50%;
      border: 4rpx solid rgba(255,255,255,0.3);
      margin-right: 20rpx;
    }
    
    .user-detail {
      flex: 1;
      
      .nickname {
        font-size: 36rpx;
        font-weight: bold;
        color: #fff;
        display: block;
        margin-bottom: 10rpx;
      }
      
      .member-tag {
        display: inline-block;
        background: linear-gradient(90deg, #ffd700, #ffed4e);
        color: #333;
        font-size: 22rpx;
        padding: 4rpx 12rpx;
        border-radius: 20rpx;
        margin-right: 10rpx;
        margin-bottom: 10rpx;
      }
      
      .mobile {
        font-size: 24rpx;
        color: rgba(255,255,255,0.8);
      }
    }
    
    .settings {
      .settings-icon {
        font-size: 40rpx;
      }
    }
  }
  
  .member-apply {
    background: rgba(255,255,255,0.2);
    border-radius: 12rpx;
    padding: 20rpx;
    text-align: center;
    
    .member-apply-text {
      color: #fff;
      font-size: 28rpx;
    }
  }
}

.stats-row {
  display: flex;
  align-items: center;
  background: #fff;
  padding: 30rpx;
  margin-bottom: 20rpx;
  
  .stat-item {
    flex: 1;
    text-align: center;
    
    .stat-value {
      font-size: 40rpx;
      font-weight: bold;
      color: #333;
      display: block;
    }
    
    .stat-label {
      font-size: 26rpx;
      color: #999;
      margin-top: 10rpx;
      display: block;
    }
  }
  
  .stat-divider {
    width: 1rpx;
    height: 60rpx;
    background: #eee;
  }
}

.assets-card {
  background: #fff;
  padding: 30rpx;
  margin-bottom: 20rpx;
  
  .card-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
    display: block;
  }
  
  .assets-row {
    display: flex;
    align-items: center;
    
    .asset-item {
      flex: 1;
      display: flex;
      align-items: center;
      
      .asset-icon {
        font-size: 50rpx;
        margin-right: 15rpx;
      }
      
      .asset-info {
        flex: 1;
        
        .asset-value {
          font-size: 32rpx;
          font-weight: bold;
          color: #333;
          display: block;
        }
        
        .asset-label {
          font-size: 24rpx;
          color: #999;
        }
      }
      
      .asset-action {
        font-size: 26rpx;
        color: #1890ff;
        background: #e6f7ff;
        padding: 8rpx 20rpx;
        border-radius: 20rpx;
      }
    }
    
    .asset-divider {
      width: 1rpx;
      height: 60rpx;
      background: #eee;
      margin: 0 20rpx;
    }
  }
}

.menu-section {
  .menu-group {
    background: #fff;
    margin-bottom: 20rpx;
    
    .menu-item {
      display: flex;
      align-items: center;
      padding: 30rpx;
      border-bottom: 1rpx solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .menu-icon {
        font-size: 40rpx;
        margin-right: 20rpx;
      }
      
      .menu-text {
        flex: 1;
        font-size: 30rpx;
        color: #333;
      }
      
      .badge {
        background: #ff4d4f;
        color: #fff;
        font-size: 22rpx;
        padding: 4rpx 10rpx;
        border-radius: 20rpx;
        margin-right: 10rpx;
      }
      
      .menu-arrow {
        font-size: 28rpx;
        color: #999;
      }
    }
  }
}

.logout-btn, .login-btn {
  background: #fff;
  color: #ff4d4f;
  text-align: center;
  padding: 30rpx;
  border-radius: 12rpx;
  font-size: 30rpx;
  margin: 0 30rpx;
}

.login-btn {
  color: #1890ff;
}
</style>
