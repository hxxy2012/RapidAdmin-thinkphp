<?php
namespace app\admin\controller;

use app\common\controller\BaseController;
use extend\workflow\FlowEngine;
use think\Request;
use think\response\Json;
use think\facade\Db;

class TaskController extends BaseController
{
    protected $flowEngine;
    
    public function __construct()
    {
        $this->flowEngine = new FlowEngine();
    }
    
    public function myPending(Request $request): Json
    {
        try {
            $userId = $this->getUserId();
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            
            $query = Db::table('ea_flow_task as t')
                ->join('ea_flow_instance as i', 't.instance_id = i.id')
                ->where('t.assignee_id', $userId)
                ->where('t.status', 1)
                ->field('t.*, i.title, i.flow_name, i.initiator_id');
            
            $total = $query->count();
            $list = $query->page($page, $limit)->order('t.id', 'desc')->select();
            
            return $this->success('获取成功', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function myCompleted(Request $request): Json
    {
        try {
            $userId = $this->getUserId();
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            
            $query = Db::table('ea_flow_task as t')
                ->join('ea_flow_instance as i', 't.instance_id = i.id')
                ->where('t.assignee_id', $userId)
                ->whereIn('t.status', [2, 3])
                ->field('t.*, i.title, i.flow_name, i.initiator_id');
            
            $total = $query->count();
            $list = $query->page($page, $limit)->order('t.complete_time', 'desc')->select();
            
            return $this->success('获取成功', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function myInitiated(Request $request): Json
    {
        try {
            $userId = $this->getUserId();
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            
            $query = Db::table('ea_flow_instance')
                ->where('initiator_id', $userId)
                ->order('id', 'desc');
            
            $total = $query->count();
            $list = $query->page($page, $limit)->select();
            
            return $this->success('获取成功', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function approve(Request $request): Json
    {
        try {
            $taskId = $request->param('task_id');
            $action = $request->param('action'); // 2=通过, 3=拒绝
            $comment = $request->param('comment', '');
            $formData = $request->param('form_data', []);
            
            if (!$taskId || !$action) return $this->error('参数不完整');
            if (!in_array($action, [2, 3])) return $this->error('审批动作无效');
            
            if (is_string($formData)) {
                $formData = json_decode($formData, true);
            }
            
            $this->flowEngine->approveTask($taskId, $this->getUserId(), $action, $comment, $formData);
            
            return $this->success('审批成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function detail(Request $request): Json
    {
        try {
            $taskId = $request->param('id');
            if (!$taskId) return $this->error('任务ID不能为空');
            
            $task = Db::table('ea_flow_task as t')
                ->join('ea_flow_instance as i', 't.instance_id = i.id')
                ->where('t.id', $taskId)
                ->field('t.*, i.title, i.flow_name, i.form_data, i.initiator_id, i.status as instance_status')
                ->find();
            
            if (!$task) return $this->error('任务不存在');
            
            $task['form_data'] = json_decode($task['form_data'], true);
            
            $history = Db::table('ea_flow_history')
                ->where('instance_id', $task['instance_id'])
                ->order('id', 'asc')
                ->select();
            
            $task['history'] = $history;
            
            return $this->success('获取成功', $task);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
