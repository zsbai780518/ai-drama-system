-- ============================================
-- AI 短剧制作系统 - 数据库完整设计
-- 版本：1.0
-- 创建时间：2026-04-18
-- 数据库：MySQL 8.0
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------
-- 1. 用户表
-- --------------------------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户 ID',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码 (加密)',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像 URL',
  `gender` tinyint(1) NOT NULL DEFAULT 0 COMMENT '性别 0 未知 1 男 2 女',
  `member_level` tinyint(2) NOT NULL DEFAULT 0 COMMENT '会员等级 0 普通 1 月会员 2 季会员 3 年会员',
  `member_expire` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员过期时间戳',
  `balance` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '余额',
  `ai_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'AI 点数',
  `free_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '免费点数 (每日重置)',
  `last_free_reset` date DEFAULT NULL COMMENT '最后免费点数重置日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0 封禁 1 正常',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `last_login_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录 IP',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mobile` (`mobile`),
  KEY `idx_status` (`status`),
  KEY `idx_member_level` (`member_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- --------------------------------------------
-- 2. 用户登录记录表
-- --------------------------------------------
DROP TABLE IF EXISTS `user_login`;
CREATE TABLE `user_login` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `login_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '登录方式 1 手机号 2 微信 3 账号密码',
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方 openid',
  `unionid` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方 unionid',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户登录记录表';

-- --------------------------------------------
-- 3. 短剧作品表
-- --------------------------------------------
DROP TABLE IF EXISTS `short_play`;
CREATE TABLE `short_play` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '作品 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '作品标题',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '作品描述',
  `cover_url` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图 URL',
  `video_url` varchar(500) NOT NULL DEFAULT '' COMMENT '视频 URL',
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时长 (秒)',
  `play_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '短剧类型 1 情感 2 反转 3 搞笑 4 职场 5 电商',
  `play_style` varchar(50) NOT NULL DEFAULT '' COMMENT '风格标签',
  `script_content` text COMMENT '剧本内容',
  `resolution` tinyint(2) NOT NULL DEFAULT 1 COMMENT '清晰度 1 标清 2 高清 3 超清',
  `has_watermark` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否有水印',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0 制作中 1 已完成 2 审核中 3 已发布 4 已下架',
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '播放次数',
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞次数',
  `share_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享次数',
  `copyright_claim` tinyint(1) NOT NULL DEFAULT 0 COMMENT '原创声明 0 否 1 是',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `published_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发布时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_play_type` (`play_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短剧作品表';

-- --------------------------------------------
-- 4. AI 任务表
-- --------------------------------------------
DROP TABLE IF EXISTS `ai_task`;
CREATE TABLE `ai_task` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `task_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '任务类型 1 剧本生成 2 配音合成 3 图像生成 4 视频生成 5 智能剪辑 6 字幕生成',
  `task_name` varchar(100) NOT NULL DEFAULT '' COMMENT '任务名称',
  `task_params` text COMMENT '任务参数 (JSON)',
  `ai_provider` varchar(50) NOT NULL DEFAULT '' COMMENT 'AI 服务商 wenxin/tongyi/xunfei/tencent',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '进度 0-100',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0 排队中 1 处理中 2 已完成 3 失败',
  `result_url` varchar(500) NOT NULL DEFAULT '' COMMENT '结果地址',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `retry_count` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `cost_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '消耗点数',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始处理时间',
  `completed_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_type` (`task_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 任务表';

-- --------------------------------------------
-- 5. 剧本表
-- --------------------------------------------
DROP TABLE IF EXISTS `script`;
CREATE TABLE `script` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '剧本标题',
  `play_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '短剧类型',
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '预计时长 (秒)',
  `theme` varchar(200) NOT NULL DEFAULT '' COMMENT '主题',
  `characters` text COMMENT '人物设定 (JSON)',
  `style` varchar(50) NOT NULL DEFAULT '' COMMENT '风格',
  `twist_point` varchar(500) NOT NULL DEFAULT '' COMMENT '反转点',
  `content` text COMMENT '完整剧本内容',
  `scenes` text COMMENT '分镜脚本 (JSON)',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0 草稿 1 已完成 2 已使用',
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板 ID(0 表示原创)',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_play_type` (`play_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='剧本表';

-- --------------------------------------------
-- 6. 素材表
-- --------------------------------------------
DROP TABLE IF EXISTS `material`;
CREATE TABLE `material` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '类型 1 图片 2 音频 3 视频 4 字体 5 模板',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '素材地址',
  `thumbnail` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时长 (音频/视频)',
  `size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小 (字节)',
  `format` varchar(20) NOT NULL DEFAULT '' COMMENT '格式 mp3/mp4/png 等',
  `is_member_only` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否会员专享',
  `is_free` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否免费',
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0 下架 1 上架',
  `tags` varchar(200) NOT NULL DEFAULT '' COMMENT '标签',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_is_member_only` (`is_member_only`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材表';

-- --------------------------------------------
-- 7. 会员套餐表
-- --------------------------------------------
DROP TABLE IF EXISTS `member_package`;
CREATE TABLE `member_package` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `level` tinyint(2) NOT NULL DEFAULT 1 COMMENT '会员等级 1 月 2 季 3 年',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '价格',
  `original_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价',
  `duration_days` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期 (天)',
  `ai_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送 AI 点数',
  `daily_free_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '每日免费点数',
  `max_resolution` tinyint(2) NOT NULL DEFAULT 1 COMMENT '最高清晰度',
  `no_watermark` tinyint(1) NOT NULL DEFAULT 0 COMMENT '无水印',
  `exclusive_material` tinyint(1) NOT NULL DEFAULT 0 COMMENT '专享素材',
  `priority_process` tinyint(1) NOT NULL DEFAULT 0 COMMENT '优先处理',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员套餐表';

-- --------------------------------------------
-- 8. 订单表
-- --------------------------------------------
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订单 ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `order_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '类型 1 会员 2 AI 点数 3 余额充值',
  `package_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐 ID',
  `amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `original_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价',
  `pay_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '支付方式 1 微信 2 支付宝 3 余额',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态 0 待支付 1 已支付 2 已取消 3 已退款',
  `pay_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- --------------------------------------------
-- 9. 充值记录表
-- --------------------------------------------
DROP TABLE IF EXISTS `recharge`;
CREATE TABLE `recharge` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '类型 1 余额 2 AI 点数',
  `amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '金额/点数',
  `balance_before` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变更前余额',
  `balance_after` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变更后余额',
  `change_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '变更类型 1 充值 2 消费 3 赠送 4 退款 5 每日重置',
  `related_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联 ID(订单/AI 任务)',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值记录表';

-- --------------------------------------------
-- 10. 剧本模板表
-- --------------------------------------------
DROP TABLE IF EXISTS `script_template`;
CREATE TABLE `script_template` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '模板标题',
  `play_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '短剧类型',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `cover_url` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `script_content` text COMMENT '剧本内容',
  `scenes` text COMMENT '分镜脚本 (JSON)',
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
  `is_hot` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
  `is_member_only` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否会员专享',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_play_type` (`play_type`),
  KEY `idx_is_hot` (`is_hot`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='剧本模板表';

-- --------------------------------------------
-- 11. 系统配置表
-- --------------------------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '类型 string/json/text',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- --------------------------------------------
-- 12. AI 接口配置表
-- --------------------------------------------
DROP TABLE IF EXISTS `ai_provider_config`;
CREATE TABLE `ai_provider_config` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL DEFAULT '' COMMENT '服务商 wenxin/tongyi/xunfei/tencent',
  `service_type` varchar(50) NOT NULL DEFAULT '' COMMENT '服务类型 text/audio/image/video',
  `api_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'API Key',
  `secret_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'Secret Key',
  `api_url` varchar(255) NOT NULL DEFAULT '' COMMENT '接口地址',
  `daily_limit` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '每日调用限制',
  `current_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '今日已调用次数',
  `reset_date` date DEFAULT NULL COMMENT '重置日期',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `priority` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级 (数字越小优先级越高)',
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_provider` (`provider`),
  KEY `idx_service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 接口配置表';

-- --------------------------------------------
-- 13. 管理员表
-- --------------------------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色 ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `last_login_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_login_ip` varchar(50) NOT NULL DEFAULT '',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- --------------------------------------------
-- 14. 管理员角色表
-- --------------------------------------------
DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE `admin_role` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `permissions` text COMMENT '权限列表 (JSON)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色表';

-- --------------------------------------------
-- 15. 操作日志表
-- --------------------------------------------
DROP TABLE IF EXISTS `operation_log`;
CREATE TABLE `operation_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户 ID(0 表示管理员)',
  `user_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '用户类型 1 普通用户 2 管理员',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作类型',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '模块',
  `request_params` text COMMENT '请求参数',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP 地址',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- --------------------------------------------
-- 16. 内容审核表
-- --------------------------------------------
DROP TABLE IF EXISTS `content_audit`;
CREATE TABLE `content_audit` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '内容类型 1 剧本 2 作品 3 素材',
  `content_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容 ID',
  `audit_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '审核状态 0 待审核 1 通过 2 拒绝',
  `audit_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '审核意见',
  `auditor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核员 ID',
  `ai_audit_result` text COMMENT 'AI 审核结果 (JSON)',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `audited_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_audit_status` (`audit_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='内容审核表';

-- --------------------------------------------
-- 17. 消息通知表
-- --------------------------------------------
DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '类型 1 系统通知 2 任务进度 3 活动推送',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text COMMENT '内容',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
  `link_url` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `read_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消息通知表';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 初始化数据
-- ============================================

-- 初始化系统配置
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('app_name', 'AI 短剧制作系统', 'string', '应用名称'),
('app_version', '1.0.0', 'string', '应用版本'),
('oss_config', '{"driver":"aliyun","access_key":"","secret_key":"","bucket":"","endpoint":""}', 'json', 'OSS 存储配置'),
('wechat_config', '{"appid":"","secret":"","mch_id":"","key":""}', 'json', '微信支付配置'),
('daily_free_points', '10', 'string', '每日免费 AI 点数');

-- 初始化会员套餐
INSERT INTO `member_package` (`name`, `level`, `price`, `original_price`, `duration_days`, `ai_points`, `daily_free_points`, `max_resolution`, `no_watermark`, `exclusive_material`, `priority_process`, `status`, `sort`) VALUES
('月度会员', 1, 29.90, 59.80, 30, 500, 50, 3, 1, 1, 1, 1, 1),
('季度会员', 2, 79.90, 179.40, 90, 1500, 80, 3, 1, 1, 1, 1, 2),
('年度会员', 3, 299.00, 717.60, 365, 6000, 100, 3, 1, 1, 1, 1, 3);

-- 初始化管理员角色
INSERT INTO `admin_role` (`name`, `description`, `permissions`) VALUES
('超级管理员', '拥有所有权限', '*'),
('内容审核员', '负责内容审核', '["content.audit","content.view"]'),
('运营人员', '负责运营配置', '["material.manage","template.manage","user.view"]');
