<template>
  <view class="container">
    <!-- 顶部 Banner -->
    <view class="vip-banner">
      <view class="vip-title">👑 开通会员</view>
      <view class="vip-subtitle">解锁全部 AI 能力，创作无限制</view>
    </view>

    <!-- 会员权益 -->
    <view class="benefits-section">
      <text class="section-title">会员专属权益</text>
      <view class="benefits-list">
        <view class="benefit-item">
          <view class="benefit-icon">✨</view>
          <view class="benefit-info">
            <text class="benefit-name">无限 AI 剧本生成</text>
            <text class="benefit-desc">会员不限次数使用 AI 写剧本</text>
          </view>
        </view>
        <view class="benefit-item">
          <view class="benefit-icon">🎙️</view>
          <view class="benefit-info">
            <text class="benefit-name">高清配音合成</text>
            <text class="benefit-desc">多音色选择，情感化表达</text>
          </view>
        </view>
        <view class="benefit-item">
          <view class="benefit-icon">🎬</view>
          <view class="benefit-info">
            <text class="benefit-name">视频无水印导出</text>
            <text class="benefit-desc">专业品质，无平台标识</text>
          </view>
        </view>
        <view class="benefit-item">
          <view class="benefit-icon">🖼️</view>
          <view class="benefit-info">
            <text class="benefit-name">专享素材库</text>
            <text class="benefit-desc">海量高质量素材任意使用</text>
          </view>
        </view>
        <view class="benefit-item">
          <view class="benefit-icon">⚡</view>
          <view class="benefit-info">
            <text class="benefit-name">优先处理队列</text>
            <text class="benefit-desc">AI 任务优先处理，无需等待</text>
          </view>
        </view>
        <view class="benefit-item">
          <view class="benefit-icon">📺</view>
          <view class="benefit-info">
            <text class="benefit-name">超清画质导出</text>
            <text class="benefit-desc">支持 1080P 超清视频导出</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 套餐选择 -->
    <view class="packages-section">
      <text class="section-title">选择套餐</text>
      <view class="packages-list">
        <view 
          class="package-card" 
          :class="{ active: selectedPackage === item.id }"
          v-for="item in packages" 
          :key="item.id"
          @click="selectedPackage = item.id"
        >
          <view class="package-header">
            <text class="package-name">{{ item.name }}</text>
            <view class="package-tag" v-if="item.level === 3">超值</view>
          </view>
          <view class="package-price">
            <text class="price-symbol">¥</text>
            <text class="price-value">{{ item.price }}</text>
            <text class="price-original">¥{{ item.original_price }}</text>
          </view>
          <view class="package-info">
            <text class="package-days">{{ item.duration_days }}天</text>
            <text class="package-points">赠送{{ item.ai_points }}点数</text>
            <text class="package-daily">每日{{ item.daily_free_points }}免费点数</text>
          </view>
          <view class="package-features">
            <view class="feature-item" v-if="item.no_watermark">✓ 无水印导出</view>
            <view class="feature-item" v-if="item.exclusive_material">✓ 专享素材</view>
            <view class="feature-item" v-if="item.priority_process">✓ 优先处理</view>
          </view>
        </view>
      </view>
    </view>

    <!-- 支付方式 -->
    <view class="payment-section">
      <text class="section-title">支付方式</text>
      <view class="payment-list">
        <view 
          class="payment-item" 
          :class="{ active: payType === 1 }"
          @click="payType = 1"
        >
          <view class="payment-icon">💳</view>
          <text class="payment-name">微信支付</text>
          <view class="payment-radio" :class="{ checked: payType === 1 }"></view>
        </view>
        <view 
          class="payment-item" 
          :class="{ active: payType === 2 }"
          @click="payType = 2"
        >
          <view class="payment-icon">🔷</view>
          <text class="payment-name">支付宝</text>
          <view class="payment-radio" :class="{ checked: payType === 2 }"></view>
        </view>
      </view>
    </view>

    <!-- 开通按钮 -->
    <view class="action-bar">
      <view class="total-info">
        <text class="total-label">实付金额：</text>
        <text class="total-price">¥{{ selectedPackagePrice }}</text>
      </view>
      <view class="open-btn" @click="openVip()">
        立即开通
      </view>
    </view>
  </view>
</template>

<script>
import { getMemberPackages, createOrder } from '@/api/index.js'

export default {
  data() {
    return {
      packages: [],
      selectedPackage: 0,
      payType: 1, // 1 微信 2 支付宝
    }
  },
  
  computed: {
    selectedPackagePrice() {
      const pkg = this.packages.find(p => p.id === this.selectedPackage)
      return pkg ? pkg.price : 0
    },
  },
  
  onLoad() {
    this.loadPackages()
  },
  
  methods: {
    async loadPackages() {
      try {
        const res = await getMemberPackages()
        if (res.code === 200) {
          this.packages = res.data
          if (this.packages.length > 0) {
            this.selectedPackage = this.packages[0].id
          }
        }
      } catch (e) {
        console.error('加载套餐失败', e)
      }
    },
    
    async openVip() {
      if (!this.selectedPackage) {
        uni.showToast({ title: '请选择套餐', icon: 'none' })
        return
      }
      
      try {
        const res = await createOrder({
          order_type: 1,
          package_id: this.selectedPackage,
          pay_type: this.payType,
        })
        
        if (res.code === 200) {
          // 发起支付
          this.doPayment(res.data)
        }
      } catch (e) {
        console.error('创建订单失败', e)
      }
    },
    
    doPayment(orderData) {
      // TODO: 调用微信支付/支付宝
      uni.showModal({
        title: '支付提示',
        content: `订单创建成功，订单号：${orderData.order_no}，金额：¥${orderData.amount}`,
        showCancel: false,
        success: () => {
          // 跳转到支付结果页
          uni.navigateTo({
            url: `/pages/order/result?order_no=${orderData.order_no}`
          })
        }
      })
    },
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 140rpx;
}

.vip-banner {
  background: linear-gradient(135deg, #667eea, #764ba2);
  padding: 60rpx 30rpx 40rpx;
  text-align: center;
  
  .vip-title {
    font-size: 48rpx;
    font-weight: bold;
    color: #fff;
    display: block;
    margin-bottom: 10rpx;
  }
  
  .vip-subtitle {
    font-size: 28rpx;
    color: rgba(255,255,255,0.9);
  }
}

.section-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  padding: 30rpx 30rpx 20rpx;
  display: block;
}

.benefits-section {
  background: #fff;
  margin-bottom: 20rpx;
  
  .benefits-list {
    padding: 0 30rpx 30rpx;
    
    .benefit-item {
      display: flex;
      align-items: center;
      padding: 20rpx 0;
      border-bottom: 1rpx solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .benefit-icon {
        font-size: 50rpx;
        margin-right: 20rpx;
      }
      
      .benefit-info {
        flex: 1;
        
        .benefit-name {
          font-size: 28rpx;
          color: #333;
          display: block;
          margin-bottom: 6rpx;
        }
        
        .benefit-desc {
          font-size: 24rpx;
          color: #999;
        }
      }
    }
  }
}

.packages-section {
  background: #fff;
  margin-bottom: 20rpx;
  
  .packages-list {
    padding: 0 30rpx 30rpx;
    
    .package-card {
      background: #f9f9f9;
      border-radius: 16rpx;
      padding: 30rpx;
      margin-bottom: 20rpx;
      border: 3rpx solid transparent;
      
      &.active {
        border-color: #667eea;
        background: linear-gradient(135deg, #f0f4ff, #f5f3ff);
      }
      
      .package-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20rpx;
        
        .package-name {
          font-size: 32rpx;
          font-weight: bold;
          color: #333;
        }
        
        .package-tag {
          background: linear-gradient(90deg, #ff6b6b, #ff8e53);
          color: #fff;
          font-size: 22rpx;
          padding: 4rpx 12rpx;
          border-radius: 20rpx;
        }
      }
      
      .package-price {
        margin-bottom: 20rpx;
        
        .price-symbol {
          font-size: 32rpx;
          color: #ff4d4f;
          font-weight: bold;
        }
        
        .price-value {
          font-size: 64rpx;
          color: #ff4d4f;
          font-weight: bold;
        }
        
        .price-original {
          font-size: 28rpx;
          color: #999;
          text-decoration: line-through;
          margin-left: 10rpx;
        }
      }
      
      .package-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20rpx;
        padding-bottom: 20rpx;
        border-bottom: 1rpx solid #eee;
        
        text {
          font-size: 26rpx;
          color: #666;
        }
      }
      
      .package-features {
        .feature-item {
          font-size: 26rpx;
          color: #52c41a;
          margin-bottom: 10rpx;
        }
      }
    }
  }
}

.payment-section {
  background: #fff;
  margin-bottom: 20rpx;
  
  .payment-list {
    padding: 0 30rpx 30rpx;
    
    .payment-item {
      display: flex;
      align-items: center;
      padding: 25rpx;
      background: #f9f9f9;
      border-radius: 12rpx;
      margin-bottom: 15rpx;
      
      &.active {
        background: #e6f7ff;
      }
      
      .payment-icon {
        font-size: 40rpx;
        margin-right: 20rpx;
      }
      
      .payment-name {
        flex: 1;
        font-size: 30rpx;
        color: #333;
      }
      
      .payment-radio {
        width: 36rpx;
        height: 36rpx;
        border-radius: 50%;
        border: 3rpx solid #ddd;
        
        &.checked {
          border-color: #1890ff;
          background: #1890ff;
        }
      }
    }
  }
}

.action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 120rpx;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
  padding-bottom: env(safe-area-inset-bottom);
  border-top: 1rpx solid #eee;
  
  .total-info {
    .total-label {
      font-size: 28rpx;
      color: #666;
    }
    
    .total-price {
      font-size: 48rpx;
      color: #ff4d4f;
      font-weight: bold;
    }
  }
  
  .open-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    padding: 25rpx 60rpx;
    border-radius: 50rpx;
    font-size: 32rpx;
    font-weight: bold;
  }
}
</style>
