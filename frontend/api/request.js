/**
 * HTTP 请求封装
 */

// API 基础地址（根据环境配置）
const BASE_URL = 'https://api.yourdomain.com/api/v1'

// Token 存储 key
const TOKEN_KEY = 'ai_drama_token'

/**
 * 获取存储的 Token
 */
function getToken() {
  try {
    return uni.getStorageSync(TOKEN_KEY)
  } catch (e) {
    return ''
  }
}

/**
 * 保存 Token
 */
function saveToken(token) {
  try {
    uni.setStorageSync(TOKEN_KEY, token)
  } catch (e) {
    console.error('保存 Token 失败', e)
  }
}

/**
 * 清除 Token
 */
function clearToken() {
  try {
    uni.removeStorageSync(TOKEN_KEY)
  } catch (e) {
    console.error('清除 Token 失败', e)
  }
}

/**
 * 请求封装
 */
export function request(options) {
  return new Promise((resolve, reject) => {
    // 显示加载提示
    const loading = uni.showLoading({
      title: '加载中...',
      mask: true
    })

    // 构建完整 URL
    let url = options.url
    if (!url.startsWith('http')) {
      url = BASE_URL + url
    }

    // 准备请求头
    const header = {
      'Content-Type': 'application/json',
      ...options.header
    }

    // 添加 Token
    const token = getToken()
    if (token) {
      header['Authorization'] = `Bearer ${token}`
    }

    // 发起请求
    uni.request({
      url,
      method: options.method || 'GET',
      data: options.data || {},
      header,
      timeout: options.timeout || 30000,
      success: (res) => {
        uni.hideLoading()
        
        const { statusCode, data } = res
        
        // HTTP 状态码处理
        if (statusCode !== 200) {
          handleError(statusCode, data)
          reject(new Error(`HTTP Error: ${statusCode}`))
          return
        }
        
        // 业务状态码处理
        if (data.code === 200) {
          resolve(data)
        } else if (data.code === 401) {
          // Token 过期，清除并跳转登录
          clearToken()
          uni.showToast({
            title: '登录已过期',
            icon: 'none'
          })
          setTimeout(() => {
            uni.reLaunch({ url: '/pages/user/login' })
          }, 1500)
          reject(new Error('登录已过期'))
        } else {
          // 其他业务错误
          uni.showToast({
            title: data.msg || '请求失败',
            icon: 'none',
            duration: 2000
          })
          reject(new Error(data.msg || '请求失败'))
        }
      },
      fail: (err) => {
        uni.hideLoading()
        console.error('请求失败', err)
        
        let errorMsg = '网络请求失败'
        if (err.errMsg) {
          if (err.errMsg.includes('timeout')) {
            errorMsg = '请求超时，请检查网络'
          } else if (err.errMsg.includes('fail')) {
            errorMsg = '网络连接失败，请检查网络'
          }
        }
        
        uni.showToast({
          title: errorMsg,
          icon: 'none',
          duration: 2000
        })
        reject(err)
      }
    })
  })
}

/**
 * 错误处理
 */
function handleError(statusCode, data) {
  const errorMap = {
    400: '请求参数错误',
    401: '请先登录',
    403: '无权限访问',
    404: '资源不存在',
    500: '服务器内部错误',
    502: '服务暂时不可用',
    503: '服务维护中',
  }
  
  const msg = data?.msg || errorMap[statusCode] || '请求失败'
  
  uni.showToast({
    title: msg,
    icon: 'none',
    duration: 2000
  })
}

/**
 * 上传文件
 */
export function uploadFile(options) {
  return new Promise((resolve, reject) => {
    const loading = uni.showLoading({
      title: '上传中...',
      mask: true
    })
    
    let url = options.url
    if (!url.startsWith('http')) {
      url = BASE_URL + url
    }
    
    const token = getToken()
    
    uni.uploadFile({
      url,
      filePath: options.filePath,
      name: options.name || 'file',
      formData: options.formData || {},
      header: {
        'Authorization': token ? `Bearer ${token}` : ''
      },
      success: (res) => {
        uni.hideLoading()
        
        if (res.statusCode === 200) {
          try {
            const data = JSON.parse(res.data)
            if (data.code === 200) {
              resolve(data)
            } else {
              reject(new Error(data.msg))
            }
          } catch (e) {
            reject(new Error('响应解析失败'))
          }
        } else {
          reject(new Error(`上传失败：${res.statusCode}`))
        }
      },
      fail: (err) => {
        uni.hideLoading()
        reject(err)
      }
    })
  })
}

/**
 * 下载文件
 */
export function downloadFile(options) {
  return new Promise((resolve, reject) => {
    const loading = uni.showLoading({
      title: '下载中...',
      mask: true
    })
    
    let url = options.url
    if (!url.startsWith('http')) {
      url = BASE_URL + url
    }
    
    const token = getToken()
    
    uni.downloadFile({
      url,
      header: {
        'Authorization': token ? `Bearer ${token}` : ''
      },
      success: (res) => {
        uni.hideLoading()
        
        if (res.statusCode === 200) {
          resolve(res.tempFilePath)
        } else {
          reject(new Error(`下载失败：${res.statusCode}`))
        }
      },
      fail: (err) => {
        uni.hideLoading()
        reject(err)
      }
    })
  })
}

// 导出 Token 管理方法
export { getToken, saveToken, clearToken }
