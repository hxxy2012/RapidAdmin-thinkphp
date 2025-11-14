-- ====================================================================
-- EnterprisePlus 企业级SaaS开发平台 - 数据库初始化脚本 (第三部分)
-- ====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ====================================================================
-- 九、导入导出相关表 (2张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_import_task (导入任务表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_import_task`;
CREATE TABLE `ea_import_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `task_name` varchar(100) NOT NULL COMMENT '任务名称',
  `import_type` varchar(50) NOT NULL COMMENT '导入类型: user/dept/product等',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `total_rows` int DEFAULT '0' COMMENT '总行数',
  `success_rows` int DEFAULT '0' COMMENT '成功行数',
  `fail_rows` int DEFAULT '0' COMMENT '失败行数',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=失败, 1=处理中, 2=成功, 3=部分成功',
  `error_file` varchar(500) DEFAULT NULL COMMENT '错误数据文件路径',
  `error_msg` text COMMENT '错误信息',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '操作人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `finish_time` datetime DEFAULT NULL COMMENT '完成时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导入任务表';

-- ----------------------------
-- Table structure for ea_export_task (导出任务表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_export_task`;
CREATE TABLE `ea_export_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `task_name` varchar(100) NOT NULL COMMENT '任务名称',
  `export_type` varchar(50) NOT NULL COMMENT '导出类型: user/order/report等',
  `file_format` varchar(20) DEFAULT 'xlsx' COMMENT '文件格式: xlsx/csv/pdf/json',
  `total_rows` int DEFAULT '0' COMMENT '总行数',
  `file_path` varchar(500) DEFAULT NULL COMMENT '文件路径',
  `file_size` bigint DEFAULT '0' COMMENT '文件大小(字节)',
  `download_url` varchar(500) DEFAULT NULL COMMENT '下载URL',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=失败, 1=处理中, 2=成功',
  `error_msg` text COMMENT '错误信息',
  `query_params` json DEFAULT NULL COMMENT '查询参数JSON',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '操作人ID',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间(7天后)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `finish_time` datetime DEFAULT NULL COMMENT '完成时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导出任务表';

-- ====================================================================
-- 十、代码生成相关表 (2张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_gen_table (代码生成表配置)
-- ----------------------------
DROP TABLE IF EXISTS `ea_gen_table`;
CREATE TABLE `ea_gen_table` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `table_name` varchar(100) NOT NULL COMMENT '表名',
  `table_comment` varchar(500) DEFAULT NULL COMMENT '表注释',
  `class_name` varchar(100) DEFAULT NULL COMMENT '类名',
  `module_name` varchar(50) DEFAULT 'admin' COMMENT '模块名',
  `business_name` varchar(50) DEFAULT NULL COMMENT '业务名',
  `function_name` varchar(50) DEFAULT NULL COMMENT '功能名',
  `author` varchar(50) DEFAULT 'EnterprisePlus' COMMENT '作者',
  `gen_type` tinyint DEFAULT '1' COMMENT '生成类型: 1=单表, 2=树表, 3=主子表, 4=审批表',
  `tree_code` varchar(50) DEFAULT NULL COMMENT '树编码字段',
  `tree_parent_code` varchar(50) DEFAULT NULL COMMENT '树父编码字段',
  `tree_name` varchar(50) DEFAULT NULL COMMENT '树名称字段',
  `parent_table_name` varchar(100) DEFAULT NULL COMMENT '父表名(主子表)',
  `parent_table_fk` varchar(50) DEFAULT NULL COMMENT '父表外键(主子表)',
  `sub_table_name` varchar(100) DEFAULT NULL COMMENT '子表名(主子表)',
  `sub_table_fk` varchar(50) DEFAULT NULL COMMENT '子表外键(主子表)',
  `gen_path` varchar(200) DEFAULT '/' COMMENT '生成路径',
  `options` json DEFAULT NULL COMMENT '其他选项JSON',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代码生成表配置';

-- ----------------------------
-- Table structure for ea_gen_table_column (代码生成字段配置)
-- ----------------------------
DROP TABLE IF EXISTS `ea_gen_table_column`;
CREATE TABLE `ea_gen_table_column` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `table_id` bigint unsigned NOT NULL COMMENT '表ID',
  `column_name` varchar(100) NOT NULL COMMENT '字段名',
  `column_comment` varchar(500) DEFAULT NULL COMMENT '字段注释',
  `column_type` varchar(100) DEFAULT NULL COMMENT '字段类型',
  `java_type` varchar(50) DEFAULT NULL COMMENT 'Java类型',
  `java_field` varchar(100) DEFAULT NULL COMMENT 'Java属性',
  `is_pk` tinyint DEFAULT '0' COMMENT '是否主键: 0=否, 1=是',
  `is_increment` tinyint DEFAULT '0' COMMENT '是否自增: 0=否, 1=是',
  `is_required` tinyint DEFAULT '0' COMMENT '是否必填: 0=否, 1=是',
  `is_insert` tinyint DEFAULT '1' COMMENT '是否插入字段: 0=否, 1=是',
  `is_edit` tinyint DEFAULT '1' COMMENT '是否编辑字段: 0=否, 1=是',
  `is_list` tinyint DEFAULT '1' COMMENT '是否列表字段: 0=否, 1=是',
  `is_query` tinyint DEFAULT '0' COMMENT '是否查询字段: 0=否, 1=是',
  `query_type` varchar(20) DEFAULT 'EQ' COMMENT '查询方式: EQ/NE/GT/LT/LIKE/BETWEEN',
  `html_type` varchar(20) DEFAULT 'input' COMMENT '显示类型: input/textarea/select/radio/checkbox/datetime/upload',
  `dict_type` varchar(100) DEFAULT NULL COMMENT '字典类型',
  `sort` int DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `idx_table_id` (`table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代码生成字段配置';

-- ====================================================================
-- 十一、安全相关表 (3张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_ip_blacklist (IP黑名单表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_ip_blacklist`;
CREATE TABLE `ea_ip_blacklist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为全局)',
  `ip` varchar(50) NOT NULL COMMENT 'IP地址',
  `ip_type` varchar(20) DEFAULT 'single' COMMENT 'IP类型: single/range/segment',
  `reason` varchar(500) DEFAULT NULL COMMENT '原因',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间(NULL永久)',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP黑名单表';

-- ----------------------------
-- Table structure for ea_ip_whitelist (IP白名单表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_ip_whitelist`;
CREATE TABLE `ea_ip_whitelist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为全局)',
  `ip` varchar(50) NOT NULL COMMENT 'IP地址',
  `ip_type` varchar(20) DEFAULT 'single' COMMENT 'IP类型: single/range/segment',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP白名单表';

-- ----------------------------
-- Table structure for ea_access_limit (访问限制记录表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_access_limit`;
CREATE TABLE `ea_access_limit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID',
  `limit_key` varchar(255) NOT NULL COMMENT '限流KEY(user:1/ip:192.168.1.1/api:/api/users)',
  `limit_type` varchar(20) NOT NULL COMMENT '限流类型: user/ip/api',
  `access_count` int DEFAULT '1' COMMENT '访问次数',
  `window_start` bigint NOT NULL COMMENT '时间窗口开始(时间戳)',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_limit_key_window` (`limit_key`, `window_start`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问限制记录表';

-- ====================================================================
-- 十二、插件/应用相关表 (2张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_plugin (插件表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_plugin`;
CREATE TABLE `ea_plugin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `plugin_name` varchar(100) NOT NULL COMMENT '插件名称',
  `plugin_code` varchar(50) NOT NULL COMMENT '插件编码',
  `plugin_version` varchar(20) DEFAULT '1.0.0' COMMENT '插件版本',
  `plugin_author` varchar(50) DEFAULT NULL COMMENT '插件作者',
  `plugin_desc` varchar(500) DEFAULT NULL COMMENT '插件描述',
  `plugin_path` varchar(200) DEFAULT NULL COMMENT '插件路径',
  `plugin_config` json DEFAULT NULL COMMENT '插件配置JSON',
  `install_time` datetime DEFAULT NULL COMMENT '安装时间',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=未安装, 1=已安装, 2=已启用, 3=已停用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件表';

-- ----------------------------
-- Table structure for ea_application (应用表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_application`;
CREATE TABLE `ea_application` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统应用)',
  `app_name` varchar(100) NOT NULL COMMENT '应用名称',
  `app_code` varchar(50) NOT NULL COMMENT '应用编码',
  `app_type` varchar(50) DEFAULT 'custom' COMMENT '应用类型: crm/erp/oa/project/custom',
  `app_icon` varchar(255) DEFAULT NULL COMMENT '应用图标',
  `app_desc` varchar(500) DEFAULT NULL COMMENT '应用描述',
  `app_version` varchar(20) DEFAULT '1.0.0' COMMENT '应用版本',
  `app_config` json DEFAULT NULL COMMENT '应用配置JSON',
  `install_time` datetime DEFAULT NULL COMMENT '安装时间',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_app_code` (`tenant_id`, `app_code`),
  KEY `idx_app_type` (`app_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='应用表';

-- ====================================================================
-- 十三、通知公告表 (1张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_notice (公告表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_notice`;
CREATE TABLE `ea_notice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统公告)',
  `notice_title` varchar(200) NOT NULL COMMENT '公告标题',
  `notice_type` tinyint DEFAULT '1' COMMENT '公告类型: 1=通知, 2=公告, 3=活动',
  `notice_content` text NOT NULL COMMENT '公告内容',
  `notice_level` tinyint DEFAULT '1' COMMENT '公告级别: 1=普通, 2=重要, 3=紧急',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `is_top` tinyint DEFAULT '0' COMMENT '是否置顶: 0=否, 1=是',
  `is_popup` tinyint DEFAULT '0' COMMENT '是否弹窗: 0=否, 1=是',
  `read_count` int DEFAULT '0' COMMENT '阅读次数',
  `attachments` json DEFAULT NULL COMMENT '附件JSON',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=草稿, 1=已发布, 2=已下线',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_publish_time` (`publish_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

-- ====================================================================
-- 初始化数据
-- ====================================================================

-- 初始化套餐数据
INSERT INTO `ea_package` (`package_name`, `package_code`, `price_month`, `price_year`, `max_users`, `max_storage`, `max_apps`, `max_workflows`, `features`, `sort`, `status`, `remark`) VALUES
('免费版', 'free', 0.00, 0.00, 10, 1073741824, 1, 0, '{"workflow": false, "form_designer": false, "api": false, "custom_domain": false, "backup": "none", "support": "community"}', 1, 1, '适合个人用户试用'),
('基础版', 'basic', 99.00, 999.00, 50, 10737418240, 5, 10, '{"workflow": true, "form_designer": true, "api": true, "custom_domain": false, "backup": "weekly", "support": "email"}', 2, 1, '适合小型团队'),
('专业版', 'pro', 299.00, 2999.00, 200, 53687091200, 20, 50, '{"workflow": true, "form_designer": true, "api": true, "custom_domain": true, "backup": "daily", "support": "ticket"}', 3, 1, '适合中型企业'),
('企业版', 'enterprise', 999.00, 9999.00, -1, 536870912000, -1, -1, '{"workflow": true, "form_designer": true, "api": true, "custom_domain": true, "backup": "realtime", "support": "dedicated"}', 4, 1, '适合大型企业');

-- 初始化系统配置
INSERT INTO `ea_config` (`tenant_id`, `config_group`, `config_key`, `config_value`, `config_type`, `config_name`, `remark`, `sort`) VALUES
(NULL, 'base', 'site_name', 'EnterprisePlus', 'string', '网站名称', '系统名称', 1),
(NULL, 'base', 'site_logo', '', 'string', '网站Logo', 'Logo URL', 2),
(NULL, 'base', 'copyright', 'Copyright © 2024 EnterprisePlus', 'string', '版权信息', '页脚版权', 3),
(NULL, 'security', 'password_min_length', '8', 'number', '密码最小长度', '密码策略', 1),
(NULL, 'security', 'password_expire_days', '90', 'number', '密码有效期(天)', '密码策略', 2),
(NULL, 'security', 'login_fail_limit', '5', 'number', '登录失败限制次数', '登录策略', 3),
(NULL, 'security', 'login_fail_lock_time', '30', 'number', '登录失败锁定时间(分钟)', '登录策略', 4),
(NULL, 'upload', 'upload_max_size', '10485760', 'number', '上传文件最大大小(字节)', '默认10MB', 1),
(NULL, 'upload', 'upload_allowed_ext', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip', 'string', '允许上传的文件类型', '逗号分隔', 2),
(NULL, 'upload', 'storage_type', 'local', 'string', '存储方式', 'local/oss/cos/qiniu', 3);

-- 初始化字典类型
INSERT INTO `ea_dict_type` (`tenant_id`, `dict_name`, `dict_type`, `status`, `remark`) VALUES
(NULL, '用户性别', 'sys_user_gender', 1, '用户性别列表'),
(NULL, '用户状态', 'sys_user_status', 1, '用户状态列表'),
(NULL, '菜单状态', 'sys_menu_status', 1, '菜单状态列表'),
(NULL, '系统状态', 'sys_common_status', 1, '通用状态列表'),
(NULL, '通知类型', 'sys_notice_type', 1, '通知类型列表'),
(NULL, '操作类型', 'sys_oper_type', 1, '操作类型列表');

-- 初始化字典数据
INSERT INTO `ea_dict_data` (`tenant_id`, `dict_type_id`, `dict_label`, `dict_value`, `dict_color`, `parent_id`, `sort`, `status`) VALUES
(NULL, 1, '未知', '0', 'info', 0, 1, 1),
(NULL, 1, '男', '1', 'blue', 0, 2, 1),
(NULL, 1, '女', '2', 'pink', 0, 3, 1),
(NULL, 2, '禁用', '0', 'danger', 0, 1, 1),
(NULL, 2, '正常', '1', 'success', 0, 2, 1),
(NULL, 4, '停用', '0', 'danger', 0, 1, 1),
(NULL, 4, '正常', '1', 'success', 0, 2, 1);

-- 初始化中国省市区数据(示例，完整数据需要单独导入)
INSERT INTO `ea_region` (`code`, `name`, `parent_code`, `level`, `sort`) VALUES
('110000', '北京市', '0', 1, 1),
('110100', '北京市', '110000', 2, 1),
('110101', '东城区', '110100', 3, 1),
('110102', '西城区', '110100', 3, 2),
('310000', '上海市', '0', 1, 2),
('310100', '上海市', '310000', 2, 1),
('310101', '黄浦区', '310100', 3, 1),
('310104', '徐汇区', '310100', 3, 2);

-- ====================================================================
-- 创建演示租户和管理员(可选)
-- ====================================================================

-- 插入演示租户
INSERT INTO `ea_tenant` (`tenant_code`, `tenant_name`, `industry`, `scale`, `package_id`, `expire_time`, `status`, `db_name`, `contact_name`, `contact_phone`, `contact_email`, `create_time`) VALUES
('demo', '演示租户', '互联网', 2, 2, DATE_ADD(NOW(), INTERVAL 1 YEAR), 1, 'tenant_demo', '张三', '13800138000', 'demo@example.com', NOW());

-- 插入演示租户的管理员用户
INSERT INTO `ea_user` (`tenant_id`, `username`, `real_name`, `password`, `phone`, `email`, `status`, `user_type`, `create_time`) VALUES
(1, 'admin', '系统管理员', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '13800138000', 'admin@demo.com', 1, 1, NOW());
-- 注意: 密码为 password 的bcrypt加密，实际使用时应该重新生成

-- 更新租户的管理员ID
UPDATE `ea_tenant` SET `admin_user_id` = 1 WHERE `id` = 1;

-- ====================================================================
-- 完成
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- 提示信息
SELECT '=====================================' as '';
SELECT 'EnterprisePlus 数据库初始化完成!' as 'Status';
SELECT '=====================================' as '';
SELECT '共创建60+张数据表' as 'Info';
SELECT '演示租户: demo' as 'Tenant';
SELECT '管理员账号: admin' as 'Username';
SELECT '管理员密码: password (请立即修改!)' as 'Password';
SELECT '=====================================' as '';
