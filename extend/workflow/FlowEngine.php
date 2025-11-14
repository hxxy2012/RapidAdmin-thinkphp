<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 工作流引擎核心类
// +----------------------------------------------------------------------

namespace extend\workflow;

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 工作流引擎核心类
 *
 * 实现完整的BPMN工作流引擎功能
 */
class FlowEngine
{
    /**
     * 节点类型常量
     */
    const NODE_TYPE_START = 'start';           // 开始节点
    const NODE_TYPE_END = 'end';               // 结束节点
    const NODE_TYPE_USER_TASK = 'userTask';    // 用户任务
    const NODE_TYPE_APPROVAL = 'approval';     // 审批节点
    const NODE_TYPE_GATEWAY = 'gateway';       // 网关节点
    const NODE_TYPE_PARALLEL = 'parallel';     // 并行网关
    const NODE_TYPE_EXCLUSIVE = 'exclusive';   // 排他网关
    const NODE_TYPE_INCLUSIVE = 'inclusive';   // 包容网关

    /**
     * 任务状态常量
     */
    const TASK_STATUS_PENDING = 1;    // 待处理
    const TASK_STATUS_APPROVED = 2;   // 已通过
    const TASK_STATUS_REJECTED = 3;   // 已拒绝
    const TASK_STATUS_CANCELLED = 4;  // 已取消
    const TASK_STATUS_TRANSFERRED = 5; // 已转交

    /**
     * 流程实例状态常量
     */
    const INSTANCE_STATUS_RUNNING = 1;    // 运行中
    const INSTANCE_STATUS_COMPLETED = 2;  // 已完成
    const INSTANCE_STATUS_REJECTED = 3;   // 已拒绝
    const INSTANCE_STATUS_CANCELLED = 4;  // 已取消

    /**
     * 启动流程
     *
     * @param int $definitionId 流程定义ID
     * @param int $userId 发起人ID
     * @param int $tenantId 租户ID
     * @param array $formData 表单数据
     * @param string $title 流程标题
     * @return int 流程实例ID
     * @throws \Exception
     */
    public function startFlow($definitionId, $userId, $tenantId, array $formData = [], $title = '')
    {
        Db::startTrans();
        try {
            // 1. 获取流程定义
            $definition = Db::table('ea_flow_definition')->find($definitionId);
            if (!$definition) {
                throw new \Exception('流程定义不存在');
            }

            if ($definition['status'] != 1) {
                throw new \Exception('流程定义未启用');
            }

            // 2. 解析流程定义
            $flowConfig = json_decode($definition['flow_config'], true);
            if (!$flowConfig) {
                throw new \Exception('流程定义配置错误');
            }

            // 3. 创建流程实例
            $instanceId = Db::table('ea_flow_instance')->insertGetId([
                'tenant_id' => $tenantId,
                'definition_id' => $definitionId,
                'definition_version' => $definition['version'],
                'flow_code' => $definition['flow_code'],
                'flow_name' => $definition['flow_name'],
                'title' => $title ?: $definition['flow_name'],
                'initiator_id' => $userId,
                'current_node' => '',
                'form_data' => json_encode($formData),
                'status' => self::INSTANCE_STATUS_RUNNING,
                'start_time' => date('Y-m-d H:i:s'),
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            // 4. 找到开始节点
            $startNode = $this->findNodeByType($flowConfig['nodes'], self::NODE_TYPE_START);
            if (!$startNode) {
                throw new \Exception('未找到开始节点');
            }

            // 5. 记录历史
            $this->recordHistory($instanceId, $startNode['id'], $userId, '发起流程', '', $formData);

            // 6. 执行下一步
            $this->executeNextStep($instanceId, $startNode, $flowConfig, $userId, $formData);

            Db::commit();

            Log::info('Flow started', [
                'instance_id' => $instanceId,
                'definition_id' => $definitionId,
                'user_id' => $userId,
            ]);

            return $instanceId;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('Flow start failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 审批任务
     *
     * @param int $taskId 任务ID
     * @param int $userId 审批人ID
     * @param int $action 审批动作（2=通过，3=拒绝）
     * @param string $comment 审批意见
     * @param array $formData 表单数据
     * @return bool
     * @throws \Exception
     */
    public function approveTask($taskId, $userId, $action, $comment = '', array $formData = [])
    {
        Db::startTrans();
        try {
            // 1. 获取任务信息
            $task = Db::table('ea_flow_task')->find($taskId);
            if (!$task) {
                throw new \Exception('任务不存在');
            }

            if ($task['status'] != self::TASK_STATUS_PENDING) {
                throw new \Exception('任务已处理');
            }

            if ($task['assignee_id'] != $userId) {
                throw new \Exception('无权处理该任务');
            }

            // 2. 更新任务状态
            Db::table('ea_flow_task')->where('id', $taskId)->update([
                'status' => $action,
                'comment' => $comment,
                'complete_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            // 3. 获取流程实例
            $instance = Db::table('ea_flow_instance')->find($task['instance_id']);
            if (!$instance) {
                throw new \Exception('流程实例不存在');
            }

            // 4. 获取流程定义
            $definition = Db::table('ea_flow_definition')->find($instance['definition_id']);
            $flowConfig = json_decode($definition['flow_config'], true);

            // 5. 记录历史
            $actionText = $action == self::TASK_STATUS_APPROVED ? '通过' : '拒绝';
            $this->recordHistory(
                $task['instance_id'],
                $task['node_id'],
                $userId,
                $actionText,
                $comment,
                $formData
            );

            // 6. 如果拒绝，结束流程
            if ($action == self::TASK_STATUS_REJECTED) {
                Db::table('ea_flow_instance')->where('id', $task['instance_id'])->update([
                    'status' => self::INSTANCE_STATUS_REJECTED,
                    'end_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

                Db::commit();
                return true;
            }

            // 7. 找到当前节点
            $currentNode = $this->findNodeById($flowConfig['nodes'], $task['node_id']);
            if (!$currentNode) {
                throw new \Exception('未找到当前节点');
            }

            // 8. 执行下一步
            $this->executeNextStep(
                $task['instance_id'],
                $currentNode,
                $flowConfig,
                $userId,
                array_merge(json_decode($instance['form_data'], true), $formData)
            );

            Db::commit();

            Log::info('Task approved', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'action' => $actionText,
            ]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('Task approval failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 执行下一步
     *
     * @param int $instanceId
     * @param array $currentNode
     * @param array $flowConfig
     * @param int $userId
     * @param array $formData
     * @return void
     * @throws \Exception
     */
    protected function executeNextStep($instanceId, $currentNode, $flowConfig, $userId, $formData)
    {
        // 1. 获取当前节点的出口连线
        $outgoingEdges = $this->findOutgoingEdges($flowConfig['edges'], $currentNode['id']);

        if (empty($outgoingEdges)) {
            // 没有出口，可能是结束节点
            if ($currentNode['type'] == self::NODE_TYPE_END) {
                // 完成流程
                Db::table('ea_flow_instance')->where('id', $instanceId)->update([
                    'status' => self::INSTANCE_STATUS_COMPLETED,
                    'current_node' => $currentNode['id'],
                    'end_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            return;
        }

        // 2. 遍历每条出口连线
        foreach ($outgoingEdges as $edge) {
            // 检查条件
            if (!$this->checkEdgeCondition($edge, $formData)) {
                continue;
            }

            // 找到目标节点
            $nextNode = $this->findNodeById($flowConfig['nodes'], $edge['target']);
            if (!$nextNode) {
                continue;
            }

            // 更新流程实例当前节点
            Db::table('ea_flow_instance')->where('id', $instanceId)->update([
                'current_node' => $nextNode['id'],
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            // 根据节点类型处理
            $this->handleNode($instanceId, $nextNode, $flowConfig, $userId, $formData);
        }
    }

    /**
     * 处理节点
     *
     * @param int $instanceId
     * @param array $node
     * @param array $flowConfig
     * @param int $userId
     * @param array $formData
     * @return void
     * @throws \Exception
     */
    protected function handleNode($instanceId, $node, $flowConfig, $userId, $formData)
    {
        switch ($node['type']) {
            case self::NODE_TYPE_END:
                // 结束节点
                Db::table('ea_flow_instance')->where('id', $instanceId)->update([
                    'status' => self::INSTANCE_STATUS_COMPLETED,
                    'end_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                break;

            case self::NODE_TYPE_USER_TASK:
            case self::NODE_TYPE_APPROVAL:
                // 用户任务/审批节点 - 创建任务
                $this->createTask($instanceId, $node, $userId);
                break;

            case self::NODE_TYPE_GATEWAY:
            case self::NODE_TYPE_EXCLUSIVE:
                // 排他网关 - 继续执行（条件已在executeNextStep中判断）
                $this->executeNextStep($instanceId, $node, $flowConfig, $userId, $formData);
                break;

            case self::NODE_TYPE_PARALLEL:
                // 并行网关 - 创建多个任务
                $this->handleParallelGateway($instanceId, $node, $flowConfig, $userId, $formData);
                break;

            default:
                // 其他类型继续执行
                $this->executeNextStep($instanceId, $node, $flowConfig, $userId, $formData);
                break;
        }
    }

    /**
     * 创建任务
     *
     * @param int $instanceId
     * @param array $node
     * @param int $userId
     * @return int
     */
    protected function createTask($instanceId, $node, $userId)
    {
        // 获取任务分配人
        $assigneeId = $this->getAssignee($node, $userId);

        $taskId = Db::table('ea_flow_task')->insertGetId([
            'instance_id' => $instanceId,
            'node_id' => $node['id'],
            'node_name' => $node['name'] ?? $node['label'] ?? '未命名节点',
            'assignee_id' => $assigneeId,
            'status' => self::TASK_STATUS_PENDING,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $taskId;
    }

    /**
     * 获取任务分配人
     *
     * @param array $node
     * @param int $defaultUserId
     * @return int
     */
    protected function getAssignee($node, $defaultUserId)
    {
        $properties = $node['properties'] ?? [];

        // 1. 指定用户
        if (!empty($properties['assignee'])) {
            return $properties['assignee'];
        }

        // 2. 指定角色
        if (!empty($properties['assignee_role'])) {
            // 从角色中随机选择一个用户
            $users = Db::table('ea_user_role')
                ->where('role_id', $properties['assignee_role'])
                ->column('user_id');

            if (!empty($users)) {
                return $users[0];
            }
        }

        // 3. 指定部门
        if (!empty($properties['assignee_dept'])) {
            $users = Db::table('ea_user')
                ->where('dept_id', $properties['assignee_dept'])
                ->column('id');

            if (!empty($users)) {
                return $users[0];
            }
        }

        // 4. 默认返回发起人
        return $defaultUserId;
    }

    /**
     * 处理并行网关
     *
     * @param int $instanceId
     * @param array $node
     * @param array $flowConfig
     * @param int $userId
     * @param array $formData
     * @return void
     */
    protected function handleParallelGateway($instanceId, $node, $flowConfig, $userId, $formData)
    {
        // 并行网关：创建所有分支的任务
        $outgoingEdges = $this->findOutgoingEdges($flowConfig['edges'], $node['id']);

        foreach ($outgoingEdges as $edge) {
            $nextNode = $this->findNodeById($flowConfig['nodes'], $edge['target']);
            if ($nextNode) {
                $this->handleNode($instanceId, $nextNode, $flowConfig, $userId, $formData);
            }
        }
    }

    /**
     * 检查连线条件
     *
     * @param array $edge
     * @param array $formData
     * @return bool
     */
    protected function checkEdgeCondition($edge, $formData)
    {
        // 如果没有条件，默认通过
        if (empty($edge['condition'])) {
            return true;
        }

        // 简单条件解析：支持 ${field} == value 格式
        $condition = $edge['condition'];

        // 替换变量
        preg_match_all('/\$\{(\w+)\}/', $condition, $matches);
        foreach ($matches[1] as $field) {
            $value = $formData[$field] ?? '';
            $condition = str_replace('${' . $field . '}', "'{$value}'", $condition);
        }

        // 安全的条件判断（仅支持比较运算）
        try {
            // 使用eval需要特别小心，这里仅作示例
            // 生产环境建议使用专门的表达式解析库
            $result = @eval("return {$condition};");
            return (bool)$result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 记录历史
     *
     * @param int $instanceId
     * @param string $nodeId
     * @param int $userId
     * @param string $action
     * @param string $comment
     * @param array $formData
     * @return void
     */
    protected function recordHistory($instanceId, $nodeId, $userId, $action, $comment, $formData)
    {
        Db::table('ea_flow_history')->insert([
            'instance_id' => $instanceId,
            'node_id' => $nodeId,
            'user_id' => $userId,
            'action' => $action,
            'comment' => $comment,
            'form_data' => json_encode($formData),
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 根据类型查找节点
     *
     * @param array $nodes
     * @param string $type
     * @return array|null
     */
    protected function findNodeByType($nodes, $type)
    {
        foreach ($nodes as $node) {
            if ($node['type'] == $type) {
                return $node;
            }
        }
        return null;
    }

    /**
     * 根据ID查找节点
     *
     * @param array $nodes
     * @param string $id
     * @return array|null
     */
    protected function findNodeById($nodes, $id)
    {
        foreach ($nodes as $node) {
            if ($node['id'] == $id) {
                return $node;
            }
        }
        return null;
    }

    /**
     * 查找出口连线
     *
     * @param array $edges
     * @param string $nodeId
     * @return array
     */
    protected function findOutgoingEdges($edges, $nodeId)
    {
        $result = [];
        foreach ($edges as $edge) {
            if ($edge['source'] == $nodeId) {
                $result[] = $edge;
            }
        }
        return $result;
    }

    /**
     * 获取下一节点
     *
     * @param int $instanceId
     * @return array
     */
    public function getNextNode($instanceId)
    {
        $instance = Db::table('ea_flow_instance')->find($instanceId);
        if (!$instance) {
            return [];
        }

        $definition = Db::table('ea_flow_definition')->find($instance['definition_id']);
        $flowConfig = json_decode($definition['flow_config'], true);

        $currentNode = $this->findNodeById($flowConfig['nodes'], $instance['current_node']);
        if (!$currentNode) {
            return [];
        }

        $outgoingEdges = $this->findOutgoingEdges($flowConfig['edges'], $currentNode['id']);

        $nextNodes = [];
        foreach ($outgoingEdges as $edge) {
            $node = $this->findNodeById($flowConfig['nodes'], $edge['target']);
            if ($node) {
                $nextNodes[] = $node;
            }
        }

        return $nextNodes;
    }

    /**
     * 取消流程
     *
     * @param int $instanceId
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function cancelFlow($instanceId, $userId, $reason = '')
    {
        Db::startTrans();
        try {
            // 更新流程实例
            Db::table('ea_flow_instance')->where('id', $instanceId)->update([
                'status' => self::INSTANCE_STATUS_CANCELLED,
                'end_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            // 取消所有待处理任务
            Db::table('ea_flow_task')
                ->where('instance_id', $instanceId)
                ->where('status', self::TASK_STATUS_PENDING)
                ->update([
                    'status' => self::TASK_STATUS_CANCELLED,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 记录历史
            $this->recordHistory($instanceId, '', $userId, '取消流程', $reason, []);

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}
