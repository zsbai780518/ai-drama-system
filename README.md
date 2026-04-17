# AI 短剧制作系统

> 全流程 AI 驱动的轻量化短剧制作 SaaS 系统

## 📖 项目简介

本项目是一款基于 AI 技术的短剧自动化制作系统，实现从剧本生成、角色设定、音视频生成、后期剪辑到发布导出的全流程 AI 自动化制作。

**核心价值**：零拍摄、低成本、高效率、一键生成

**目标用户**：个人短剧创作者、自媒体博主、中小影视工作室、电商短视频团队

## 🛠️ 技术栈

### 后端
- **服务器架构**: LNMP (Linux + Nginx + MySQL + PHP)
- **后端框架**: ThinkPHP 6
- **数据库**: MySQL 8.0 + Redis
- **AI 服务**: 文心一言/通义千问/讯飞星火/腾讯云

### 前端
- **跨端框架**: UniApp (Vue3)
- **适配端**: 微信小程序、H5、APP (Android/iOS)
- **UI 组件**: UniUi、ColorUi

### 基础设施
- **对象存储**: 阿里云 OSS / 腾讯云 COS
- **消息队列**: Redis Stream / RabbitMQ
- **实时通信**: WebSocket

## 📁 项目结构

```
ai-drama-system/
├── backend/                 # ThinkPHP6 后端
│   ├── app/
│   │   ├── controller/     # 控制器
│   │   ├── model/          # 模型
│   │   ├── service/        # 业务逻辑层
│   │   ├── middleware/     # 中间件
│   │   └── validate/       # 验证器
│   ├── config/             # 配置文件
│   ├── database/           # 数据库迁移
│   └── runtime/            # 运行时目录
├── frontend/               # UniApp 前端
│   ├── pages/              # 页面
│   │   ├── index/          # 首页
│   │   ├── user/           # 用户中心
│   │   ├── create/         # AI 创作
│   │   ├── material/       # 素材中心
│   │   ├── works/          # 作品管理
│   │   ├── member/         # 会员中心
│   │   └── vip/            # VIP 套餐
│   ├── components/         # 组件
│   ├── store/              # 状态管理 (Pinia)
│   ├── api/                # API 请求
│   └── static/             # 静态资源
├── docs/                   # 文档
│   ├── api/                # API 接口文档
│   ├── database/           # 数据库设计
│   └── deploy/             # 部署文档
└── scripts/                # 脚本工具
```

## 🚀 快速开始

### 环境要求

- PHP >= 8.0
- MySQL >= 8.0
- Redis >= 5.0
- Node.js >= 16
- Nginx >= 1.20

### 后端部署

```bash
# 1. 克隆项目
cd backend

# 2. 安装依赖
composer install

# 3. 配置环境变量
cp .env.example .env
# 编辑 .env 文件，配置数据库、Redis 等

# 4. 导入数据库
mysql -u root -p ai_drama < docs/database/schema.sql

# 5. 设置目录权限
chmod -R 755 runtime
chmod -R 755 public

# 6. 启动服务 (开发环境)
php think run

# 生产环境配置 Nginx 反向代理
```

### 前端部署

```bash
# 1. 进入前端目录
cd frontend

# 2. 安装依赖
npm install

# 3. 配置 API 地址
# 编辑 src/config/index.js

# 4. 开发模式
npm run dev:h5          # H5
npm run dev:mp-weixin   # 微信小程序
npm run dev:app         # APP

# 5. 生产构建
npm run build:h5
npm run build:mp-weixin
npm run build:app
```

## 📋 核心功能

### 1. AI 剧本生成
- 支持情感、反转、搞笑、职场、电商等多种短剧类型
- 输入主题、风格、人物设定，一键生成完整剧本
- 提供爆款剧本模板，支持套用修改

### 2. 角色与场景设定
- AI 自动生成角色人设、形象描述
- 海量场景素材库，支持 AI 自定义生成
- 多音色、多风格配音选择

### 3. 音视频 AI 生成
- 台词一键转配音，支持情感化表达
- 根据场景描述 AI 生成视频画面
- 音视频自动同步合成

### 4. 智能后期剪辑
- 自动识别配音生成字幕
- 智能匹配背景音乐
- 多种转场特效、滤镜一键添加

### 5. 作品导出与发布
- 支持 MP4 格式导出，多清晰度可选
- 一键分享至微信、抖音、快手
- 版权保护与原创声明

## 💰 商业化模式

1. **会员付费**: 月度/季度/年度会员，享受无限次 AI 生成、高清导出、无水印等权益
2. **AI 点数**: 非会员按次计费，点数充值购买
3. **定制服务**: 企业级定制、私有化部署
4. **广告变现**: 免费用户端接入轻量化广告

## 📄 文档

- [API 接口文档](docs/api/README.md)
- [数据库设计](docs/database/schema.sql)
- [部署文档](docs/deploy/README.md)

## ⚠️ 注意事项

1. **AI 接口配置**: 需提前申请各 AI 服务商的 API Key
2. **内容合规**: 建立 AI 生成内容审核机制，过滤违规内容
3. **版权合规**: 使用正版 AI 模型、背景音乐、素材
4. **资质合规**: 办理网络文化经营许可证、ICP 备案

## 📝 开发计划

| 阶段 | 内容 | 周期 |
|------|------|------|
| 第一阶段 | 需求梳理与架构搭建 | 1-2 周 |
| 第二阶段 | 后端核心功能开发 | 3-4 周 |
| 第三阶段 | 前端功能开发 | 3-4 周 |
| 第四阶段 | 系统联调与优化 | 2 周 |
| 第五阶段 | 测试与上线 | 1-2 周 |

**总研发周期**: 12-14 周

## 📞 联系方式

如有问题或合作意向，请联系项目负责人。

---

**License**: MIT
**Version**: 1.0.0
**Created**: 2026-04-18
