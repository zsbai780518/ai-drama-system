# AI 短剧制作系统 - API 接口文档

## 基础信息

- **基础 URL**: `https://api.yourdomain.com`
- **API 版本**: `v1`
- **认证方式**: JWT Token
- **数据格式**: JSON

## 认证说明

所有需要登录的接口，需在请求头中携带 Token：

```
Authorization: Bearer <token>
```

Token 过期时返回：
```json
{
  "code": 401,
  "msg": "登录已过期，请重新登录",
  "data": null
}
```

---

## 接口列表

### 一、用户模块

#### 1.1 发送验证码
```
POST /api/v1/sms/send
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| mobile | string | 是 | 手机号 |
| type | int | 是 | 类型 1 注册/登录 2 找回密码 |

**响应**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "expire": 300
  }
}
```

#### 1.2 手机号登录/注册
```
POST /api/v1/user/login-mobile
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| mobile | string | 是 | 手机号 |
| code | string | 是 | 验证码 |

**响应**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "user": {
      "id": 1001,
      "nickname": "用户昵称",
      "avatar": "https://...",
      "mobile": "138****0000",
      "member_level": 0,
      "balance": 0.00,
      "ai_points": 10
    }
  }
}
```

#### 1.3 微信授权登录
```
POST /api/v1/user/login-wechat
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | 微信登录 code |
| encryptedData | string | 否 | 加密数据（小程序） |
| iv | string | 否 | 加密向量（小程序） |

**响应**：同 1.2

#### 1.4 获取用户信息
```
GET /api/v1/user/profile
```

**响应**：
```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "mobile": "138****0000",
    "nickname": "用户昵称",
    "avatar": "https://...",
    "gender": 1,
    "member_level": 2,
    "member_expire": 1714320000,
    "balance": 100.00,
    "ai_points": 520,
    "free_points": 10
  }
}
```

#### 1.5 更新用户信息
```
PUT /api/v1/user/profile
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| nickname | string | 否 | 昵称 |
| avatar | string | 否 | 头像 URL |
| gender | int | 否 | 性别 |

#### 1.6 修改密码
```
PUT /api/v1/user/password
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| old_password | string | 是 | 原密码 |
| new_password | string | 是 | 新密码 |

---

### 二、AI 短剧制作模块

#### 2.1 生成剧本
```
POST /api/v1/ai/script/generate
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| play_type | int | 是 | 短剧类型 1 情感 2 反转 3 搞笑 4 职场 5 电商 |
| theme | string | 是 | 主题 |
| duration | int | 是 | 时长 (秒) |
| style | string | 否 | 风格 |
| characters | object | 否 | 人物设定 |
| twist_point | string | 否 | 反转点 |

**响应**：
```json
{
  "code": 200,
  "data": {
    "task_id": 10001,
    "status": 0,
    "progress": 0,
    "estimated_time": 30
  }
}
```

#### 2.2 查询 AI 任务进度
```
GET /api/v1/ai/task/:id
```

**响应**：
```json
{
  "code": 200,
  "data": {
    "id": 10001,
    "task_type": 1,
    "status": 1,
    "progress": 60,
    "result_url": "",
    "error_msg": ""
  }
}
```

#### 2.3 保存剧本
```
POST /api/v1/script/save
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| title | string | 是 | 剧本标题 |
| play_type | int | 是 | 短剧类型 |
| content | text | 是 | 剧本内容 |
| scenes | object | 否 | 分镜脚本 |

**响应**：
```json
{
  "code": 200,
  "data": {
    "id": 5001
  }
}
```

#### 2.4 获取剧本列表
```
GET /api/v1/script/list
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 (默认 1) |
| page_size | int | 否 | 每页数量 (默认 10) |
| status | int | 否 | 状态筛选 |

#### 2.5 生成配音
```
POST /api/v1/ai/audio/synthesize
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| script_id | int | 是 | 剧本 ID |
| voice | string | 是 | 音色 |
| speed | float | 否 | 语速 (0.5-2.0) |

#### 2.6 生成图像
```
POST /api/v1/ai/image/generate
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| prompt | string | 是 | 图像描述 |
| style | string | 否 | 风格 |
| width | int | 否 | 宽度 |
| height | int | 否 | 高度 |

#### 2.7 合成视频
```
POST /api/v1/ai/video/synthesize
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| script_id | int | 是 | 剧本 ID |
| audio_urls | array | 是 | 音频 URL 列表 |
| image_urls | array | 是 | 图像 URL 列表 |
| bgm_id | int | 否 | 背景音乐 ID |

#### 2.8 智能剪辑
```
POST /api/v1/ai/video/edit
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| video_url | string | 是 | 视频 URL |
| operations | array | 是 | 剪辑操作列表 |
| - type | string | 是 | 操作类型 crop/trim/filter/subtitle |
| - params | object | 是 | 操作参数 |

---

### 三、作品模块

#### 3.1 获取作品列表
```
GET /api/v1/works/list
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| page_size | int | 否 | 每页数量 |
| status | int | 否 | 状态筛选 |

**响应**：
```json
{
  "code": 200,
  "data": {
    "list": [
      {
        "id": 1001,
        "title": "作品标题",
        "cover_url": "https://...",
        "duration": 60,
        "play_type": 1,
        "status": 1,
        "view_count": 1234,
        "created_at": 1714320000
      }
    ],
    "total": 100,
    "page": 1,
    "page_size": 10
  }
}
```

#### 3.2 获取作品详情
```
GET /api/v1/works/:id
```

#### 3.3 删除作品
```
DELETE /api/v1/works/:id
```

#### 3.4 导出作品
```
POST /api/v1/works/:id/export
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| resolution | int | 是 | 清晰度 1 标清 2 高清 3 超清 |
| watermark | int | 是 | 是否水印 0 否 1 是 |

#### 3.5 分享作品
```
POST /api/v1/works/:id/share
```

---

### 四、素材模块

#### 4.1 获取素材列表
```
GET /api/v1/material/list
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | int | 否 | 类型 1 图片 2 音频 3 视频 |
| category | string | 否 | 分类 |
| is_member_only | int | 否 | 是否会员专享 |

#### 4.2 获取素材详情
```
GET /api/v1/material/:id
```

#### 4.3 收藏素材
```
POST /api/v1/material/:id/favorite
```

---

### 五、会员与支付模块

#### 5.1 获取会员套餐列表
```
GET /api/v1/member/packages
```

**响应**：
```json
{
  "code": 200,
  "data": [
    {
      "id": 1,
      "name": "月度会员",
      "price": 29.90,
      "original_price": 59.80,
      "duration_days": 30,
      "ai_points": 500,
      "daily_free_points": 50,
      "no_watermark": true
    }
  ]
}
```

#### 5.2 创建订单
```
POST /api/v1/order/create
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| package_id | int | 是 | 套餐 ID |
| pay_type | int | 是 | 支付方式 1 微信 2 支付宝 |

**响应**：
```json
{
  "code": 200,
  "data": {
    "order_no": "202604180001",
    "amount": 29.90,
    "pay_params": {
      "appid": "...",
      "timeStamp": "...",
      "nonceStr": "...",
      "package": "...",
      "signType": "RSA",
      "paySign": "..."
    }
  }
}
```

#### 5.3 查询订单状态
```
GET /api/v1/order/:order_no/status
```

#### 5.4 获取订单列表
```
GET /api/v1/order/list
```

#### 5.5 AI 点数充值
```
POST /api/v1/recharge/points
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| amount | int | 是 | 充值点数 |
| pay_type | int | 是 | 支付方式 |

#### 5.6 余额充值
```
POST /api/v1/recharge/balance
```

---

### 六、消息通知模块

#### 6.1 获取消息列表
```
GET /api/v1/notification/list
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | int | 否 | 类型筛选 |
| is_read | int | 否 | 是否已读 |

#### 6.2 标记消息已读
```
PUT /api/v1/notification/:id/read
```

#### 6.3 全部标记已读
```
PUT /api/v1/notification/read-all
```

---

### 七、剧本模板模块

#### 7.1 获取模板列表
```
GET /api/v1/template/list
```

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| play_type | int | 否 | 短剧类型 |
| is_hot | int | 否 | 是否热门 |

#### 7.2 获取模板详情
```
GET /api/v1/template/:id
```

#### 7.3 使用模板
```
POST /api/v1/template/:id/use
```

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未登录/登录过期 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |
| 1001 | AI 服务调用失败 |
| 1002 | AI 点数不足 |
| 1003 | 会员已过期 |
| 1004 | 内容审核未通过 |

---

## WebSocket 连接

用于实时接收 AI 任务进度推送：

```
wss://api.yourdomain.com/ws
```

**连接参数**：
- `token`: JWT Token

**消息格式**：
```json
{
  "type": "task_progress",
  "data": {
    "task_id": 10001,
    "progress": 60,
    "status": 1
  }
}
```
