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
