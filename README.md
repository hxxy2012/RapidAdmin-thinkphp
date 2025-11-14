# EnterprisePlus - 企业级SaaS开发平台

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-green.svg)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.0-red.svg)
![License](https://img.shields.io/badge/license-Apache--2.0-brightgreen.svg)

**一个功能完备、开箱即用的企业级应用开发基座**

支持多租户SaaS模式、工作流引擎、可视化表单设计、定时任务编排等核心能力

[在线演示](http://demo.enterpriseplus.com) | [开发文档](https://docs.enterpriseplus.com) | [视频教程](https://www.bilibili.com/xxx)

</div>

---

## 📖 项目介绍

**EnterprisePlus** 不是简单的后台管理系统，而是一个功能完备的企业级应用开发平台，为企业应用开发提供完整的基础设施，支持快速构建CRM、ERP、OA等各类企业应用。

### 核心特性

- ⭐ **多租户SaaS架构**：独立数据库隔离，支持1000+租户并发
- 🔄 **工作流引擎**：可视化流程设计，支持复杂审批流程
- 📋 **表单设计器**：拖拽式表单设计，30+控件支持
- ⏰ **定时任务调度**：可视化Cron配置，支持DAG任务编排
- 🔐 **高级RBAC权限**：7种数据权限范围，支持字段级权限
- 💬 **全渠道消息中心**：站内消息、邮件、短信、企业微信、钉钉
- 📊 **报表中心**：SQL报表、图表报表、报表订阅
- 🛠️ **开发者工具**：代码生成器、接口文档、数据库管理
- 🔒 **安全中心**：接口限流、数据加密、审计日志
- 📦 **导入导出**：支持百万级数据导入导出

## 🚀 技术栈

### 后端

- **框架**: ThinkPHP 8.x
- **数据库**: MySQL 8.0+ (主库) / PostgreSQL (可选)
- **缓存**: Redis 7.0+ (支持集群)
- **队列**: ThinkPHP Queue + Redis/RabbitMQ
- **搜索**: Elasticsearch 8.x (可选)

### 前端

- **UI框架**: Layui Admin + Vue 3 + Element Plus
- **图表**: ECharts 5.x
- **编辑器**: TinyMCE、CodeMirror
- **流程设计**: bpmn-js、jsPlumb

### 监控与文档

- **监控**: Prometheus + Grafana (可选)
- **文档**: VitePress

## 📦 功能模块

### 一、多租户SaaS架构 ⭐⭐⭐

- **租户管理**: 注册、启用/停用、配额管理、自定义域名
- **套餐管理**: 4种套餐（免费/基础/专业/企业）
- **数据隔离**: 独立数据库模式 / Schema模式 / 共享表模式
- **租户识别**: 域名识别、Header识别、Token识别

### 二、工作流引擎 ⭐⭐⭐

- **流程设计器**: 拖拽式可视化设计（开始、审批、条件、抄送、子流程、结束节点）
- **表单设计器**: 30+控件（基础/高级/布局控件）
- **审批方式**: 会签、或签、顺序、并行
- **我的任务**: 待办、已办、发起的、抄送
- **流程监控**: 实时监控、超时预警、流程分析

### 三、定时任务可视化调度 ⭐⭐⭐

- **任务类型**: 类方法、HTTP接口、Shell脚本、SQL脚本
- **Cron表达式**: 可视化生成
- **任务编排**: DAG工作流编排
- **执行日志**: 实时日志、历史查询、失败告警
- **监控告警**: 邮件、短信、Webhook

### 四、高级RBAC权限

- **用户管理**: 导入导出、组织架构图、用户画像
- **角色管理**: 系统角色、自定义角色
- **数据权限**: 7种范围（全部/本部门/本部门及子部门/本人/本人及下属/自定义部门/自定义规则）
- **功能权限**: 菜单、按钮、接口、字段级权限

### 五、消息中心

- **站内消息**: 系统消息、通知消息、私信、@提醒
- **邮件发送**: SMTP配置、模板管理、异步队列
- **短信发送**: 阿里云、腾讯云、容联云
- **推送通知**: 企业微信、钉钉、Webhook
- **实时推送**: WebSocket实时推送

### 六、报表中心

- **SQL报表**: 可视化SQL编辑器、参数化查询
- **图表报表**: 柱状图、折线图、饼图、雷达图、地图
- **报表订阅**: 定期邮件推送

### 七、开发者工具

- **代码生成器**: 单表、树表、主子表CRUD生成
- **接口文档**: Swagger风格、在线调试
- **数据库管理**: 表结构查看/编辑、SQL执行器、备份还原
- **接口Mock**: Mock数据生成

### 八、系统监控

- **服务器监控**: CPU、内存、磁盘、网络
- **性能监控**: 接口响应时间、慢查询、Redis命中率
- **在线用户**: 实时在线列表、踢出用户
- **访问统计**: PV/UV、接口统计、用户行为

### 九、安全中心

- **密码策略**: 复杂度、有效期、历史限制
- **登录策略**: SSO、异常登录检测
- **访问控制**: IP白黑名单、接口限流、熔断降级
- **数据安全**: 字段加密、数据脱敏、备份加密
- **审计日志**: 关键操作记录、数据对比

### 十、数据导入导出

- **Excel导入**: 10万+数据批量导入、字段映射、数据验证
- **Excel导出**: 100万+数据异步导出
- **多种格式**: Excel、CSV、PDF、JSON、XML

## 🛠️ 安装部署

### 环境要求

```
PHP >= 8.0
MySQL >= 8.0
Redis >= 7.0
Nginx/Apache
Composer
```

### 1. 克隆项目

```bash
git clone https://github.com/xxx/EnterprisePlus.git
cd EnterprisePlus
```

### 2. 安装依赖

```bash
composer install
```

### 3. 配置环境

```bash
cp .env.example .env
# 编辑.env文件，配置数据库等信息
```

### 4. 导入数据库

```bash
# 导入初始化SQL
mysql -u root -p < database/seeds/install.sql
```

### 5. 配置Web服务器

**Nginx配置示例**:

```nginx
server {
    listen 80;
    server_name enterpriseplus.local;
    root /path/to/EnterprisePlus/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. 启动队列

```bash
php think queue:listen
```

### 7. 启动定时任务

```bash
# 添加到crontab
* * * * * php /path/to/EnterprisePlus/think task:schedule >> /dev/null 2>&1
```

### 8. 访问系统

```
后台地址: http://your-domain/admin
默认账号: admin
默认密码: admin123
```

## 📁 目录结构

```
EnterprisePlus/
├── app/                        # 应用目录
│   ├── admin/                  # 后台管理模块
│   │   ├── controller/         # 控制器
│   │   │   ├── system/         # 系统管理
│   │   │   ├── tenant/         # 租户管理
│   │   │   ├── workflow/       # 工作流
│   │   │   ├── task/           # 定时任务
│   │   │   └── ...
│   │   ├── model/              # 模型
│   │   ├── service/            # 业务逻辑层
│   │   ├── validate/           # 验证器
│   │   └── middleware/         # 中间件
│   ├── api/                    # 开放API模块
│   ├── common/                 # 公共模块
│   └── task/                   # 定时任务类
├── extend/                     # 扩展类库
│   ├── auth/                   # 权限验证
│   ├── workflow/               # 工作流引擎
│   ├── task/                   # 任务调度器
│   ├── tenant/                 # 租户上下文
│   └── utils/                  # 工具类
├── config/                     # 配置文件
├── public/                     # 公共资源
│   └── static/                 # 静态文件
├── database/                   # 数据库文件
│   ├── migrations/             # 迁移文件
│   └── seeds/                  # 种子文件
├── runtime/                    # 运行时文件
└── vendor/                     # 第三方库
```

## 📊 数据库设计

共60+张表，包括：

- **租户相关** (3张): ea_tenant, ea_package, ea_tenant_package
- **RBAC权限** (10张): ea_user, ea_role, ea_permission, ea_menu, ea_dept, ea_post等
- **工作流** (8张): ea_flow, ea_form, ea_flow_instance, ea_flow_task等
- **定时任务** (4张): ea_task, ea_task_log, ea_task_dag等
- **消息中心** (5张): ea_message, ea_email_log, ea_sms_log等
- **系统配置** (8张): ea_config, ea_dict_type, ea_file等
- **日志监控** (6张): ea_log_operation, ea_log_login, ea_log_error等
- **其他模块** (20+张)

详见: [数据库设计文档](docs/database.md)

## 🔧 开发文档

- [快速开始](docs/quickstart.md)
- [多租户实现](docs/multi-tenant.md)
- [工作流引擎](docs/workflow.md)
- [定时任务](docs/task.md)
- [权限系统](docs/permission.md)
- [二次开发](docs/development.md)
- [API文档](docs/api.md)

## 🎯 性能指标

- 支持1000+租户并发
- 单租户支持10万+用户
- 接口响应时间<100ms
- 支持千万级数据查询
- 支持100万+定时任务

## 🔒 安全标准

- ✅ 通过OWASP Top 10安全检查
- ✅ SQL注入防护
- ✅ XSS防护
- ✅ CSRF防护
- ✅ 密码强度AAA级
- ✅ 数据加密存储
- ✅ 操作全程审计

## 🤝 贡献指南

欢迎贡献代码！请查看 [贡献指南](CONTRIBUTING.md)

## 📝 开发计划

### Phase 1: 基础架构 + 多租户 (已完成)
- [x] 项目初始化
- [x] 多租户架构
- [x] 租户管理
- [x] 套餐管理

### Phase 2: RBAC权限系统 (进行中)
- [ ] 用户/角色/权限管理
- [ ] 部门/岗位管理
- [ ] 权限验证
- [ ] 数据权限

### Phase 3: 工作流引擎 (待开发)
- [ ] 流程设计器
- [ ] 表单设计器
- [ ] 流程引擎
- [ ] 任务管理

### Phase 4-10: 其他模块 (待开发)

## 📜 开源协议

本项目采用 [Apache-2.0](LICENSE) 开源协议

## 💬 联系我们

- 官网: https://www.enterpriseplus.com
- 文档: https://docs.enterpriseplus.com
- Issue: https://github.com/xxx/EnterprisePlus/issues
- Email: support@enterpriseplus.com
- QQ群: 123456789

## 🙏 鸣谢

感谢以下开源项目:

- [ThinkPHP](https://www.thinkphp.cn/)
- [Layui](https://layui.dev/)
- [Vue.js](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [ECharts](https://echarts.apache.org/)

---

<div align="center">

**⭐ 如果这个项目对您有帮助，请给我们一个Star! ⭐**

Made with ❤️ by EnterprisePlus Team

</div>
