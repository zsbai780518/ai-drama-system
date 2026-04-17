/**
 * AI 短剧制作系统 - API 请求封装
 */

import { request } from './request.js'

// ==================== 用户模块 ====================

/**
 * 发送短信验证码
 */
export function sendSms(mobile, type = 1) {
  return request({
    url: '/sms/send',
    method: 'POST',
    data: { mobile, type }
  })
}

/**
 * 手机号登录/注册
 */
export function loginMobile(mobile, code) {
  return request({
    url: '/user/login-mobile',
    method: 'POST',
    data: { mobile, code }
  })
}

/**
 * 微信授权登录
 */
export function loginWechat(code, encryptedData = '', iv = '') {
  return request({
    url: '/user/login-wechat',
    method: 'POST',
    data: { code, encryptedData, iv }
  })
}

/**
 * 获取用户信息
 */
export function getUserProfile() {
  return request({
    url: '/user/profile',
    method: 'GET'
  })
}

/**
 * 更新用户信息
 */
export function updateUserProfile(data) {
  return request({
    url: '/user/profile',
    method: 'PUT',
    data
  })
}

// ==================== AI 任务模块 ====================

/**
 * 生成剧本
 */
export function generateScript(params) {
  return request({
    url: '/ai/script/generate',
    method: 'POST',
    data: params
  })
}

/**
 * 查询任务进度
 */
export function getTaskProgress(taskId) {
  return request({
    url: `/ai/task/${taskId}`,
    method: 'GET'
  })
}

/**
 * 生成配音
 */
export function synthesizeAudio(params) {
  return request({
    url: '/ai/audio/synthesize',
    method: 'POST',
    data: params
  })
}

/**
 * 生成图像
 */
export function generateImage(params) {
  return request({
    url: '/ai/image/generate',
    method: 'POST',
    data: params
  })
}

/**
 * 视频合成
 */
export function synthesizeVideo(params) {
  return request({
    url: '/ai/video/synthesize',
    method: 'POST',
    data: params
  })
}

// ==================== 剧本模块 ====================

/**
 * 保存剧本
 */
export function saveScript(data) {
  return request({
    url: '/script/save',
    method: 'POST',
    data
  })
}

/**
 * 获取剧本列表
 */
export function getScriptList(params = {}) {
  return request({
    url: '/script/list',
    method: 'GET',
    data: params
  })
}

/**
 * 获取剧本详情
 */
export function getScriptDetail(id) {
  return request({
    url: `/script/${id}`,
    method: 'GET'
  })
}

// ==================== 作品模块 ====================

/**
 * 获取作品列表
 */
export function getWorksList(params = {}) {
  return request({
    url: '/works/list',
    method: 'GET',
    data: params
  })
}

/**
 * 获取作品详情
 */
export function getWorkDetail(id) {
  return request({
    url: `/works/${id}`,
    method: 'GET'
  })
}

/**
 * 删除作品
 */
export function deleteWork(id) {
  return request({
    url: `/works/${id}`,
    method: 'DELETE'
  })
}

/**
 * 导出作品
 */
export function exportWork(id, params) {
  return request({
    url: `/works/${id}/export`,
    method: 'POST',
    data: params
  })
}

// ==================== 素材模块 ====================

/**
 * 获取素材列表
 */
export function getMaterialList(params = {}) {
  return request({
    url: '/material/list',
    method: 'GET',
    data: params
  })
}

/**
 * 获取素材详情
 */
export function getMaterialDetail(id) {
  return request({
    url: `/material/${id}`,
    method: 'GET'
  })
}

// ==================== 模板模块 ====================

/**
 * 获取模板列表
 */
export function getTemplates(params = {}) {
  return request({
    url: '/template/list',
    method: 'GET',
    data: params
  })
}

/**
 * 获取模板详情
 */
export function getTemplateDetail(id) {
  return request({
    url: `/template/${id}`,
    method: 'GET'
  })
}

/**
 * 使用模板
 */
export function useTemplate(id) {
  return request({
    url: `/template/${id}/use`,
    method: 'POST'
  })
}

// ==================== 会员与支付模块 ====================

/**
 * 获取会员套餐列表
 */
export function getMemberPackages() {
  return request({
    url: '/member/packages',
    method: 'GET'
  })
}

/**
 * 创建订单
 */
export function createOrder(data) {
  return request({
    url: '/order/create',
    method: 'POST',
    data
  })
}

/**
 * 查询订单状态
 */
export function getOrderStatus(orderNo) {
  return request({
    url: `/order/${orderNo}/status`,
    method: 'GET'
  })
}

/**
 * 获取订单列表
 */
export function getOrderList(params = {}) {
  return request({
    url: '/order/list',
    method: 'GET',
    data: params
  })
}

/**
 * AI 点数充值
 */
export function rechargePoints(data) {
  return request({
    url: '/recharge/points',
    method: 'POST',
    data
  })
}

// ==================== 消息通知模块 ====================

/**
 * 获取消息列表
 */
export function getNotificationList(params = {}) {
  return request({
    url: '/notification/list',
    method: 'GET',
    data: params
  })
}

/**
 * 标记消息已读
 */
export function markNotificationRead(id) {
  return request({
    url: `/notification/${id}/read`,
    method: 'PUT'
  })
}

/**
 * 全部标记已读
 */
export function markAllRead() {
  return request({
    url: '/notification/read-all',
    method: 'PUT'
  })
}
