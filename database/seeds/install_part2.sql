-- ====================================================================
-- EnterprisePlus 企业级SaaS开发平台 - 数据库初始化脚本 (第二部分)
-- ====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ====================================================================
-- 五、消息中心相关表 (5张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_message (消息表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_message`;
CREATE TABLE `ea_message` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `msg_type` tinyint DEFAULT '1' COMMENT '消息类型: 1=系统消息, 2=通知消息, 3=私信, 4=@提醒',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `sender_id` bigint unsigned DEFAULT NULL COMMENT '发送人ID',
  `receiver_type` tinyint DEFAULT '1' COMMENT '接收类型: 1=指定用户, 2=指定角色, 3=指定部门, 4=全部',
  `receiver_ids` json DEFAULT NULL COMMENT '接收人ID数组',
  `is_broadcast` tinyint DEFAULT '0' COMMENT '是否广播: 0=否, 1=是',
  `priority` tinyint DEFAULT '1' COMMENT '优先级: 1=普通, 2=重要, 3=紧急',
  `extra_data` json DEFAULT NULL COMMENT '扩展数据JSON',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_msg_type` (`msg_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息表';

-- ----------------------------
-- Table structure for ea_message_read (消息已读记录表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_message_read`;
CREATE TABLE `ea_message_read` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `message_id` bigint unsigned NOT NULL COMMENT '消息ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `is_read` tinyint DEFAULT '0' COMMENT '是否已读: 0=未读, 1=已读',
  `read_time` datetime DEFAULT NULL COMMENT '阅读时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_message_user` (`message_id`, `user_id`),
  KEY `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息已读记录表';

-- ----------------------------
-- Table structure for ea_email_log (邮件日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_email_log`;
CREATE TABLE `ea_email_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `template_id` bigint unsigned DEFAULT NULL COMMENT '模板ID',
  `to_email` varchar(500) NOT NULL COMMENT '收件人邮箱(多个用逗号分隔)',
  `cc_email` varchar(500) DEFAULT NULL COMMENT '抄送邮箱',
  `subject` varchar(200) NOT NULL COMMENT '主题',
  `content` text COMMENT '内容',
  `attachments` json DEFAULT NULL COMMENT '附件JSON',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=待发送, 1=发送成功, 2=发送失败',
  `error_msg` text COMMENT '错误信息',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件日志表';

-- ----------------------------
-- Table structure for ea_sms_log (短信日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_sms_log`;
CREATE TABLE `ea_sms_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `template_id` bigint unsigned DEFAULT NULL COMMENT '模板ID',
  `template_code` varchar(50) DEFAULT NULL COMMENT '模板编码',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `content` varchar(500) NOT NULL COMMENT '内容',
  `params` json DEFAULT NULL COMMENT '参数JSON',
  `platform` varchar(20) DEFAULT 'aliyun' COMMENT '平台: aliyun/tencent/rongcloud',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=待发送, 1=发送成功, 2=发送失败',
  `error_msg` text COMMENT '错误信息',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `cost` decimal(10,4) DEFAULT '0.0000' COMMENT '费用(元)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信日志表';

-- ----------------------------
-- Table structure for ea_webhook_log (Webhook日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_webhook_log`;
CREATE TABLE `ea_webhook_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `webhook_type` varchar(50) DEFAULT NULL COMMENT '类型: wechat/dingtalk/feishu/custom',
  `webhook_url` varchar(500) NOT NULL COMMENT 'Webhook URL',
  `method` varchar(10) DEFAULT 'POST' COMMENT '请求方法',
  `headers` json DEFAULT NULL COMMENT '请求头JSON',
  `payload` text COMMENT '请求体',
  `response` text COMMENT '响应内容',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=失败, 1=成功',
  `error_msg` text COMMENT '错误信息',
  `send_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `duration` int DEFAULT '0' COMMENT '耗时(毫秒)',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook日志表';

-- ====================================================================
-- 六、系统配置相关表 (8张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_config (系统配置表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_config`;
CREATE TABLE `ea_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统配置)',
  `config_group` varchar(50) NOT NULL COMMENT '配置分组: base/security/upload/email/sms/pay/oss/login/map/ai',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(20) DEFAULT 'string' COMMENT '配置类型: string/number/bool/json',
  `config_name` varchar(100) DEFAULT NULL COMMENT '配置名称',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_group_key` (`tenant_id`, `config_group`, `config_key`),
  KEY `idx_config_group` (`config_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ----------------------------
-- Table structure for ea_dict_type (字典类型表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_dict_type`;
CREATE TABLE `ea_dict_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统字典)',
  `dict_name` varchar(100) NOT NULL COMMENT '字典名称',
  `dict_type` varchar(100) NOT NULL COMMENT '字典类型',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_dict_type` (`tenant_id`, `dict_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';

-- ----------------------------
-- Table structure for ea_dict_data (字典数据表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_dict_data`;
CREATE TABLE `ea_dict_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `dict_type_id` bigint unsigned NOT NULL COMMENT '字典类型ID',
  `dict_label` varchar(100) NOT NULL COMMENT '字典标签',
  `dict_value` varchar(100) NOT NULL COMMENT '字典值',
  `dict_color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID(支持多级)',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_dict_type_id` (`dict_type_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';

-- ----------------------------
-- Table structure for ea_sensitive_word (敏感词表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_sensitive_word`;
CREATE TABLE `ea_sensitive_word` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `word` varchar(100) NOT NULL COMMENT '敏感词',
  `category` varchar(50) DEFAULT 'custom' COMMENT '分类: political/porn/violence/custom',
  `replace_word` varchar(100) DEFAULT '***' COMMENT '替换词',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_word` (`word`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='敏感词表';

-- ----------------------------
-- Table structure for ea_region (地区表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_region`;
CREATE TABLE `ea_region` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `code` varchar(20) NOT NULL COMMENT '地区编码',
  `name` varchar(100) NOT NULL COMMENT '地区名称',
  `parent_code` varchar(20) DEFAULT '0' COMMENT '父级编码',
  `level` tinyint DEFAULT '1' COMMENT '层级: 1=省, 2=市, 3=区',
  `sort` int DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_code` (`parent_code`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='地区表';

-- ----------------------------
-- Table structure for ea_file (文件表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_file`;
CREATE TABLE `ea_file` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_url` varchar(500) DEFAULT NULL COMMENT '文件URL',
  `file_size` bigint DEFAULT '0' COMMENT '文件大小(字节)',
  `file_type` varchar(50) DEFAULT NULL COMMENT '文件类型',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `extension` varchar(20) DEFAULT NULL COMMENT '扩展名',
  `md5` varchar(32) DEFAULT NULL COMMENT 'MD5值',
  `storage_type` varchar(20) DEFAULT 'local' COMMENT '存储方式: local/oss/cos/qiniu/minio',
  `category` varchar(50) DEFAULT 'other' COMMENT '分类: image/video/audio/document/archive/other',
  `uploader_id` bigint unsigned DEFAULT NULL COMMENT '上传人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_md5` (`md5`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件表';

-- ----------------------------
-- Table structure for ea_attachment (附件关联表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_attachment`;
CREATE TABLE `ea_attachment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `business_type` varchar(50) NOT NULL COMMENT '业务类型: flow/task/message/user等',
  `business_id` bigint unsigned NOT NULL COMMENT '业务ID',
  `file_id` bigint unsigned NOT NULL COMMENT '文件ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_business` (`business_type`, `business_id`),
  KEY `idx_file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件关联表';

-- ----------------------------
-- Table structure for ea_upload_chunk (分片上传记录表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_upload_chunk`;
CREATE TABLE `ea_upload_chunk` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `upload_id` varchar(100) NOT NULL COMMENT '上传ID',
  `file_md5` varchar(32) NOT NULL COMMENT '文件MD5',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `file_size` bigint NOT NULL COMMENT '文件大小',
  `chunk_total` int NOT NULL COMMENT '总分片数',
  `chunk_uploaded` int DEFAULT '0' COMMENT '已上传分片数',
  `chunk_info` json DEFAULT NULL COMMENT '分片信息JSON',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=上传中, 1=已完成, 2=已取消',
  `file_id` bigint unsigned DEFAULT NULL COMMENT '文件ID(完成后)',
  `uploader_id` bigint unsigned DEFAULT NULL COMMENT '上传人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_upload_id` (`upload_id`),
  KEY `idx_file_md5` (`file_md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分片上传记录表';

-- ====================================================================
-- 七、日志监控相关表 (6张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_log_operation (操作日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_operation`;
CREATE TABLE `ea_log_operation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `module` varchar(50) DEFAULT NULL COMMENT '模块',
  `action` varchar(100) DEFAULT NULL COMMENT '操作',
  `method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User-Agent',
  `request_params` text COMMENT '请求参数',
  `response_data` text COMMENT '响应数据',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=失败, 1=成功',
  `error_msg` text COMMENT '错误信息',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '操作人ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作人用户名',
  `duration` int DEFAULT '0' COMMENT '耗时(毫秒)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ----------------------------
-- Table structure for ea_log_login (登录日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_login`;
CREATE TABLE `ea_log_login` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `location` varchar(255) DEFAULT NULL COMMENT 'IP归属地',
  `browser` varchar(100) DEFAULT NULL COMMENT '浏览器',
  `os` varchar(100) DEFAULT NULL COMMENT '操作系统',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=失败, 1=成功',
  `msg` varchar(255) DEFAULT NULL COMMENT '提示信息',
  `login_type` varchar(20) DEFAULT 'password' COMMENT '登录类型: password/wechat/dingtalk',
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_username` (`tenant_id`, `username`),
  KEY `idx_login_time` (`login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';

-- ----------------------------
-- Table structure for ea_log_error (异常日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_error`;
CREATE TABLE `ea_log_error` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `error_type` varchar(50) DEFAULT 'system' COMMENT '错误类型: system/sql/api/queue',
  `error_message` text COMMENT '错误信息',
  `error_code` varchar(50) DEFAULT NULL COMMENT '错误代码',
  `error_file` varchar(500) DEFAULT NULL COMMENT '错误文件',
  `error_line` int DEFAULT NULL COMMENT '错误行号',
  `stack_trace` text COMMENT '堆栈跟踪',
  `request_url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `request_params` text COMMENT '请求参数',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_error_type` (`error_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常日志表';

-- ----------------------------
-- Table structure for ea_log_audit (审计日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_audit`;
CREATE TABLE `ea_log_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `audit_type` varchar(50) NOT NULL COMMENT '审计类型: data_change/permission_change/config_change',
  `table_name` varchar(100) DEFAULT NULL COMMENT '表名',
  `record_id` bigint unsigned DEFAULT NULL COMMENT '记录ID',
  `action` varchar(20) NOT NULL COMMENT '操作: insert/update/delete',
  `old_value` json DEFAULT NULL COMMENT '修改前值JSON',
  `new_value` json DEFAULT NULL COMMENT '修改后值JSON',
  `changed_fields` json DEFAULT NULL COMMENT '变更字段JSON',
  `user_id` bigint unsigned NOT NULL COMMENT '操作人ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作人用户名',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_table_record` (`table_name`, `record_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审计日志表';

-- ----------------------------
-- Table structure for ea_log_slow_query (慢查询日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_slow_query`;
CREATE TABLE `ea_log_slow_query` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `sql_text` text NOT NULL COMMENT 'SQL语句',
  `sql_md5` varchar(32) DEFAULT NULL COMMENT 'SQL MD5',
  `execution_time` int NOT NULL COMMENT '执行时间(毫秒)',
  `database_name` varchar(100) DEFAULT NULL COMMENT '数据库名',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_sql_md5` (`sql_md5`),
  KEY `idx_execution_time` (`execution_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='慢查询日志表';

-- ----------------------------
-- Table structure for ea_log_api (接口调用日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_log_api`;
CREATE TABLE `ea_log_api` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `api_name` varchar(100) DEFAULT NULL COMMENT '接口名称',
  `api_url` varchar(500) NOT NULL COMMENT '接口URL',
  `method` varchar(10) DEFAULT 'GET' COMMENT '请求方法',
  `request_headers` json DEFAULT NULL COMMENT '请求头JSON',
  `request_body` text COMMENT '请求体',
  `response_code` int DEFAULT NULL COMMENT '响应码',
  `response_body` text COMMENT '响应体',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=失败, 1=成功',
  `error_msg` text COMMENT '错误信息',
  `duration` int DEFAULT '0' COMMENT '耗时(毫秒)',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_api_name` (`api_name`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口调用日志表';

-- ====================================================================
-- 八、报表相关表 (3张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_report_category (报表分类表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_report_category`;
CREATE TABLE `ea_report_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `category_name` varchar(50) NOT NULL COMMENT '分类名称',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报表分类表';

-- ----------------------------
-- Table structure for ea_report (报表表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_report`;
CREATE TABLE `ea_report` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `report_name` varchar(100) NOT NULL COMMENT '报表名称',
  `report_code` varchar(50) NOT NULL COMMENT '报表编码',
  `report_type` tinyint DEFAULT '1' COMMENT '报表类型: 1=SQL报表, 2=图表报表, 3=复合报表',
  `sql_content` text COMMENT 'SQL内容',
  `chart_config` json DEFAULT NULL COMMENT '图表配置JSON',
  `params_config` json DEFAULT NULL COMMENT '参数配置JSON',
  `category_id` bigint unsigned DEFAULT NULL COMMENT '分类ID',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_report_code` (`tenant_id`, `report_code`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报表表';

-- ----------------------------
-- Table structure for ea_report_subscribe (报表订阅表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_report_subscribe`;
CREATE TABLE `ea_report_subscribe` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `report_id` bigint unsigned NOT NULL COMMENT '报表ID',
  `user_id` bigint unsigned NOT NULL COMMENT '订阅人ID',
  `subscribe_type` tinyint DEFAULT '1' COMMENT '订阅类型: 1=每日, 2=每周, 3=每月',
  `subscribe_time` varchar(20) DEFAULT '09:00' COMMENT '推送时间',
  `push_channel` varchar(50) DEFAULT 'email' COMMENT '推送渠道: email/wechat/dingtalk',
  `params` json DEFAULT NULL COMMENT '报表参数JSON',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报表订阅表';

-- ====================================================================
-- 继续...
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 1;
