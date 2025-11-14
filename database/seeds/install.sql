-- ====================================================================
-- EnterprisePlus 企业级SaaS开发平台 - 数据库初始化脚本
-- 版本: 1.0.0
-- 数据库: MySQL 8.0+
-- 字符集: utf8mb4
-- 表前缀: ea_
-- ====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ====================================================================
-- 一、租户相关表 (3张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_tenant (租户表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_tenant`;
CREATE TABLE `ea_tenant` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_code` varchar(50) NOT NULL COMMENT '租户标识(唯一)',
  `tenant_name` varchar(100) NOT NULL COMMENT '租户名称',
  `industry` varchar(50) DEFAULT NULL COMMENT '行业类型',
  `scale` tinyint DEFAULT '1' COMMENT '规模: 1=1-50人, 2=51-200人, 3=201-500人, 4=500+',
  `package_id` bigint unsigned DEFAULT NULL COMMENT '套餐ID',
  `expire_time` datetime DEFAULT NULL COMMENT '到期时间',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=试用, 1=正式, 2=过期, 3=停用',
  `db_name` varchar(100) DEFAULT NULL COMMENT '数据库名',
  `domain` varchar(100) DEFAULT NULL COMMENT '自定义域名',
  `logo` varchar(255) DEFAULT NULL COMMENT '租户Logo',
  `max_users` int DEFAULT '10' COMMENT '最大用户数',
  `used_storage` bigint DEFAULT '0' COMMENT '已用存储(字节)',
  `max_storage` bigint DEFAULT '1073741824' COMMENT '最大存储(字节,默认1GB)',
  `contact_name` varchar(50) DEFAULT NULL COMMENT '联系人姓名',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `contact_email` varchar(100) DEFAULT NULL COMMENT '联系邮箱',
  `admin_user_id` bigint unsigned DEFAULT NULL COMMENT '管理员用户ID',
  `theme_color` varchar(20) DEFAULT '#1890ff' COMMENT '主题色',
  `system_name` varchar(100) DEFAULT 'EnterprisePlus' COMMENT '系统名称',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_code` (`tenant_code`),
  KEY `idx_status` (`status`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='租户表';

-- ----------------------------
-- Table structure for ea_package (套餐表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_package`;
CREATE TABLE `ea_package` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `package_name` varchar(50) NOT NULL COMMENT '套餐名称',
  `package_code` varchar(30) NOT NULL COMMENT '套餐编码(free/basic/pro/enterprise)',
  `price_month` decimal(10,2) DEFAULT '0.00' COMMENT '月付价格',
  `price_year` decimal(10,2) DEFAULT '0.00' COMMENT '年付价格',
  `max_users` int DEFAULT '10' COMMENT '最大用户数(-1不限)',
  `max_storage` bigint DEFAULT '1073741824' COMMENT '最大存储(字节)',
  `max_apps` int DEFAULT '1' COMMENT '最大应用数(-1不限)',
  `max_workflows` int DEFAULT '0' COMMENT '最大工作流数(-1不限)',
  `features` json DEFAULT NULL COMMENT '功能权限JSON',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_package_code` (`package_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套餐表';

-- ----------------------------
-- Table structure for ea_tenant_package (租户套餐关联表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_tenant_package`;
CREATE TABLE `ea_tenant_package` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `package_id` bigint unsigned NOT NULL COMMENT '套餐ID',
  `start_time` datetime NOT NULL COMMENT '开始时间',
  `end_time` datetime NOT NULL COMMENT '结束时间',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `pay_type` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `order_no` varchar(50) DEFAULT NULL COMMENT '订单号',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=待支付, 1=已支付, 2=已过期',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='租户套餐关联表';

-- ====================================================================
-- 二、RBAC权限相关表 (10张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_user (用户表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_user`;
CREATE TABLE `ea_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `real_name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `gender` tinyint DEFAULT '0' COMMENT '性别: 0=未知, 1=男, 2=女',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `id_card` varchar(18) DEFAULT NULL COMMENT '身份证号',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `wechat` varchar(50) DEFAULT NULL COMMENT '微信号',
  `qq` varchar(20) DEFAULT NULL COMMENT 'QQ号',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `dept_id` bigint unsigned DEFAULT NULL COMMENT '部门ID',
  `post_ids` json DEFAULT NULL COMMENT '岗位ID数组',
  `leader_id` bigint unsigned DEFAULT NULL COMMENT '直属上级ID',
  `level` varchar(20) DEFAULT NULL COMMENT '职级',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `user_type` tinyint DEFAULT '1' COMMENT '用户类型: 1=内部, 2=外部, 3=合作伙伴',
  `entry_date` date DEFAULT NULL COMMENT '入职日期',
  `leave_date` date DEFAULT NULL COMMENT '离职日期',
  `login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int DEFAULT '0' COMMENT '登录次数',
  `pwd_update_time` datetime DEFAULT NULL COMMENT '密码修改时间',
  `pwd_expire_days` int DEFAULT '90' COMMENT '密码有效期(天)',
  `extra_fields` json DEFAULT NULL COMMENT '扩展字段JSON',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_username` (`tenant_id`, `username`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ----------------------------
-- Table structure for ea_role (角色表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_role`;
CREATE TABLE `ea_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `role_name` varchar(50) NOT NULL COMMENT '角色名称',
  `role_code` varchar(50) NOT NULL COMMENT '角色编码',
  `role_type` tinyint DEFAULT '2' COMMENT '角色类型: 1=系统角色, 2=自定义角色',
  `data_scope` tinyint DEFAULT '1' COMMENT '数据权限范围: 1=全部, 2=本部门, 3=本部门及子部门, 4=仅本人, 5=仅本人及下属, 6=自定义部门, 7=自定义规则',
  `dept_ids` json DEFAULT NULL COMMENT '自定义部门ID数组(data_scope=6时)',
  `data_rule` varchar(500) DEFAULT NULL COMMENT '自定义数据规则(data_scope=7时)',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- ----------------------------
-- Table structure for ea_permission (权限表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_permission`;
CREATE TABLE `ea_permission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统权限)',
  `permission_name` varchar(50) NOT NULL COMMENT '权限名称',
  `permission_code` varchar(100) NOT NULL COMMENT '权限标识',
  `permission_type` tinyint DEFAULT '1' COMMENT '权限类型: 1=菜单, 2=按钮, 3=接口',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

-- ----------------------------
-- Table structure for ea_menu (菜单表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_menu`;
CREATE TABLE `ea_menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT '租户ID(NULL为系统菜单)',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID',
  `menu_name` varchar(50) NOT NULL COMMENT '菜单名称',
  `menu_type` tinyint DEFAULT '1' COMMENT '菜单类型: 1=目录, 2=菜单, 3=按钮',
  `icon` varchar(100) DEFAULT NULL COMMENT '图标',
  `path` varchar(200) DEFAULT NULL COMMENT '路由路径',
  `component` varchar(200) DEFAULT NULL COMMENT '组件路径',
  `permission` varchar(100) DEFAULT NULL COMMENT '权限标识',
  `sort` int DEFAULT '0' COMMENT '排序',
  `visible` tinyint DEFAULT '1' COMMENT '是否可见: 0=隐藏, 1=显示',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单表';

-- ----------------------------
-- Table structure for ea_dept (部门表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_dept`;
CREATE TABLE `ea_dept` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID',
  `dept_name` varchar(50) NOT NULL COMMENT '部门名称',
  `dept_code` varchar(50) DEFAULT NULL COMMENT '部门编码',
  `dept_type` tinyint DEFAULT '4' COMMENT '部门类型: 1=公司, 2=分公司, 3=事业部, 4=部门, 5=小组',
  `leader_id` bigint unsigned DEFAULT NULL COMMENT '负责人ID',
  `vice_leader_ids` json DEFAULT NULL COMMENT '副负责人ID数组',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `fax` varchar(50) DEFAULT NULL COMMENT '传真',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';

-- ----------------------------
-- Table structure for ea_post (岗位表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_post`;
CREATE TABLE `ea_post` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `post_name` varchar(50) NOT NULL COMMENT '岗位名称',
  `post_code` varchar(50) NOT NULL COMMENT '岗位编码',
  `post_series` varchar(50) DEFAULT NULL COMMENT '岗位序列(技术/产品/管理/销售)',
  `post_level` varchar(20) DEFAULT NULL COMMENT '职级(P5/P6/M1/M2)',
  `description` text COMMENT '岗位说明',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='岗位表';

-- ----------------------------
-- Table structure for ea_user_role (用户角色关联表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_user_role`;
CREATE TABLE `ea_user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- ----------------------------
-- Table structure for ea_role_permission (角色权限关联表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_role_permission`;
CREATE TABLE `ea_role_permission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `permission_id` bigint unsigned NOT NULL COMMENT '权限ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
  KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- ----------------------------
-- Table structure for ea_role_menu (角色菜单关联表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_role_menu`;
CREATE TABLE `ea_role_menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `menu_id` bigint unsigned NOT NULL COMMENT '菜单ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';

-- ----------------------------
-- Table structure for ea_role_dept (角色部门关联表-数据权限)
-- ----------------------------
DROP TABLE IF EXISTS `ea_role_dept`;
CREATE TABLE `ea_role_dept` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `dept_id` bigint unsigned NOT NULL COMMENT '部门ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_dept` (`role_id`, `dept_id`),
  KEY `idx_dept_id` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色部门关联表';

-- ====================================================================
-- 三、工作流相关表 (8张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_flow_category (流程分类表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_flow_category`;
CREATE TABLE `ea_flow_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `category_name` varchar(50) NOT NULL COMMENT '分类名称',
  `category_code` varchar(50) DEFAULT NULL COMMENT '分类编码',
  `parent_id` bigint unsigned DEFAULT '0' COMMENT '父级ID',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程分类表';

-- ----------------------------
-- Table structure for ea_flow (流程定义表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_flow`;
CREATE TABLE `ea_flow` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `flow_name` varchar(100) NOT NULL COMMENT '流程名称',
  `flow_key` varchar(50) NOT NULL COMMENT '流程标识(唯一)',
  `category_id` bigint unsigned DEFAULT NULL COMMENT '分类ID',
  `form_id` bigint unsigned DEFAULT NULL COMMENT '关联表单ID',
  `flow_json` json DEFAULT NULL COMMENT '流程配置JSON',
  `version` int DEFAULT '1' COMMENT '版本号',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=草稿, 1=已发布, 2=停用',
  `remark` varchar(500) DEFAULT NULL COMMENT '说明',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_flow_key` (`tenant_id`, `flow_key`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程定义表';

-- ----------------------------
-- Table structure for ea_form (表单定义表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_form`;
CREATE TABLE `ea_form` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `form_name` varchar(100) NOT NULL COMMENT '表单名称',
  `form_key` varchar(50) NOT NULL COMMENT '表单标识(唯一)',
  `form_json` json DEFAULT NULL COMMENT '表单配置JSON',
  `table_name` varchar(100) DEFAULT NULL COMMENT '数据存储表名',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=草稿, 1=已发布, 2=停用',
  `version` int DEFAULT '1' COMMENT '版本号',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_form_key` (`tenant_id`, `form_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单定义表';

-- ----------------------------
-- Table structure for ea_flow_instance (流程实例表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_flow_instance`;
CREATE TABLE `ea_flow_instance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `flow_id` bigint unsigned NOT NULL COMMENT '流程ID',
  `flow_version` int DEFAULT '1' COMMENT '流程版本',
  `form_data_id` bigint unsigned DEFAULT NULL COMMENT '表单数据ID',
  `instance_no` varchar(50) NOT NULL COMMENT '流程编号',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1=审批中, 2=已通过, 3=已拒绝, 4=已撤回, 5=已终止',
  `current_node_id` varchar(50) DEFAULT NULL COMMENT '当前节点ID',
  `starter_id` bigint unsigned NOT NULL COMMENT '发起人ID',
  `start_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `duration` int DEFAULT '0' COMMENT '耗时(秒)',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_instance_no` (`instance_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_flow_id` (`flow_id`),
  KEY `idx_status` (`status`),
  KEY `idx_starter_id` (`starter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程实例表';

-- ----------------------------
-- Table structure for ea_flow_task (流程任务表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_flow_task`;
CREATE TABLE `ea_flow_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `instance_id` bigint unsigned NOT NULL COMMENT '流程实例ID',
  `node_id` varchar(50) NOT NULL COMMENT '节点ID',
  `node_name` varchar(100) NOT NULL COMMENT '节点名称',
  `task_type` tinyint DEFAULT '1' COMMENT '任务类型: 1=审批, 2=抄送',
  `assignee_id` bigint unsigned NOT NULL COMMENT '处理人ID',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1=待处理, 2=已同意, 3=已拒绝, 4=已转交, 5=已撤回',
  `opinion` text COMMENT '审批意见',
  `attachments` json DEFAULT NULL COMMENT '附件JSON',
  `handle_time` datetime DEFAULT NULL COMMENT '处理时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_assignee_status` (`assignee_id`, `status`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程任务表';

-- ----------------------------
-- Table structure for ea_flow_record (流程流转记录表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_flow_record`;
CREATE TABLE `ea_flow_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `instance_id` bigint unsigned NOT NULL COMMENT '流程实例ID',
  `task_id` bigint unsigned DEFAULT NULL COMMENT '任务ID',
  `node_id` varchar(50) DEFAULT NULL COMMENT '节点ID',
  `node_name` varchar(100) DEFAULT NULL COMMENT '节点名称',
  `action` varchar(20) NOT NULL COMMENT '操作: start/approve/reject/transfer/withdraw/terminate',
  `operator_id` bigint unsigned NOT NULL COMMENT '操作人ID',
  `operator_name` varchar(50) DEFAULT NULL COMMENT '操作人姓名',
  `opinion` text COMMENT '意见',
  `attachments` json DEFAULT NULL COMMENT '附件JSON',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程流转记录表';

-- 注意: ea_form_data_{form_key} 表为动态创建，由表单设计器根据表单配置自动生成

-- ====================================================================
-- 四、定时任务相关表 (4张)
-- ====================================================================

-- ----------------------------
-- Table structure for ea_task_group (任务分组表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_task_group`;
CREATE TABLE `ea_task_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `group_name` varchar(50) NOT NULL COMMENT '分组名称',
  `group_code` varchar(50) DEFAULT NULL COMMENT '分组编码',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务分组表';

-- ----------------------------
-- Table structure for ea_task (定时任务表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_task`;
CREATE TABLE `ea_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `task_name` varchar(100) NOT NULL COMMENT '任务名称',
  `task_group_id` bigint unsigned DEFAULT NULL COMMENT '分组ID',
  `task_type` tinyint DEFAULT '1' COMMENT '任务类型: 1=类方法, 2=HTTP请求, 3=Shell脚本, 4=SQL脚本',
  `invoke_target` varchar(500) NOT NULL COMMENT '调用目标(类名@方法名/URL/脚本路径)',
  `cron_expression` varchar(100) NOT NULL COMMENT 'Cron表达式',
  `params` json DEFAULT NULL COMMENT '参数JSON',
  `retry_count` int DEFAULT '0' COMMENT '重试次数',
  `retry_interval` int DEFAULT '60' COMMENT '重试间隔(秒)',
  `timeout` int DEFAULT '3600' COMMENT '超时时间(秒)',
  `concurrent` tinyint DEFAULT '0' COMMENT '是否并发: 0=禁止, 1=允许',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `last_time` datetime DEFAULT NULL COMMENT '最后执行时间',
  `next_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `exec_count` int DEFAULT '0' COMMENT '执行次数',
  `fail_count` int DEFAULT '0' COMMENT '失败次数',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_next_time` (`next_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='定时任务表';

-- ----------------------------
-- Table structure for ea_task_log (任务执行日志表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_task_log`;
CREATE TABLE `ea_task_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `task_id` bigint unsigned NOT NULL COMMENT '任务ID',
  `task_name` varchar(100) DEFAULT NULL COMMENT '任务名称',
  `invoke_target` varchar(500) DEFAULT NULL COMMENT '调用目标',
  `params` json DEFAULT NULL COMMENT '参数JSON',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0=失败, 1=成功, 2=执行中',
  `result` text COMMENT '返回结果',
  `error_msg` text COMMENT '错误信息',
  `start_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `duration` int DEFAULT '0' COMMENT '耗时(毫秒)',
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务执行日志表';

-- ----------------------------
-- Table structure for ea_task_dag (任务编排DAG表)
-- ----------------------------
DROP TABLE IF EXISTS `ea_task_dag`;
CREATE TABLE `ea_task_dag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `dag_name` varchar(100) NOT NULL COMMENT 'DAG名称',
  `dag_code` varchar(50) NOT NULL COMMENT 'DAG编码',
  `dag_json` json DEFAULT NULL COMMENT 'DAG配置JSON',
  `cron_expression` varchar(100) DEFAULT NULL COMMENT 'Cron表达式',
  `status` tinyint DEFAULT '1' COMMENT '状态: 0=停用, 1=启用',
  `last_time` datetime DEFAULT NULL COMMENT '最后执行时间',
  `next_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_dag_code` (`tenant_id`, `dag_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务编排DAG表';

-- ====================================================================
-- 由于SQL过长，继续在下一部分...
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 1;
