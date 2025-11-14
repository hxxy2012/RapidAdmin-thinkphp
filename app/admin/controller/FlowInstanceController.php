<?php
namespace app\admin\controller;

use app\common\controller\BaseController;
use extend\workflow\FlowEngine;
use extend\tenant\TenantContext;
use think\Request;
use think\response\Json;
use think\facade\Db;

class FlowInstanceController extends BaseController
{
    protected $flowEngine;
    
    public function __construct()
    {
        $this->flowEngine = new FlowEngine();
    }
    
    public function start(Request $request): Json
    {
        try {
            $definitionId = $request->param('definition_id');
            $formData = $request->param('form_data', []);
            $title = $request->param('title', '');
            
            if (!$definitionId) return $this->error('流程定义ID不能为空');
            
            if (is_string($formData)) {
                $formData = json_decode($formData, true);
            }
            
            $instanceId = $this->flowEngine->startFlow(
                $definitionId,
                $this->getUserId(),
                $this->getTenantId(),
                $formData,
                $title
            );
            
            return $this->success('流程启动成功', ['instance_id' => $instanceId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function detail(Request $request): Json
    {
        try {
            $instanceId = $request->param('id');
            if (!$instanceId) return $this->error('流程实例ID不能为空');
            
            $instance = Db::table('ea_flow_instance')->find($instanceId);
            if (!$instance) return $this->error('流程实例不存在');
            
            $instance['form_data'] = json_decode($instance['form_data'], true);
            
            $tasks = Db::table('ea_flow_task')
                ->where('instance_id', $instanceId)
                ->order('id', 'asc')
                ->select();
            
            $history = Db::table('ea_flow_history')
                ->where('instance_id', $instanceId)
                ->order('id', 'asc')
                ->select();
            
            $instance['tasks'] = $tasks;
            $instance['history'] = $history;
            
            return $this->success('获取成功', $instance);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function cancel(Request $request): Json
    {
        try {
            $instanceId = $request->param('id');
            $reason = $request->param('reason', '');
            
            if (!$instanceId) return $this->error('流程实例ID不能为空');
            
            $this->flowEngine->cancelFlow($instanceId, $this->getUserId(), $reason);
            
            return $this->success('流程取消成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function getNextNode(Request $request): Json
    {
        try {
            $instanceId = $request->param('instance_id');
            if (!$instanceId) return $this->error('流程实例ID不能为空');
            
            $nextNodes = $this->flowEngine->getNextNode($instanceId);
            
            return $this->success('获取成功', $nextNodes);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
