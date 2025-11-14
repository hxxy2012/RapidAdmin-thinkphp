<?php
namespace app\admin\controller;

use app\admin\service\FlowService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

class FlowController extends BaseController
{
    protected $flowService;
    
    public function __construct()
    {
        $this->flowService = new FlowService();
    }
    
    public function index(Request $request): Json
    {
        try {
            $where = [];
            if ($request->has('flow_name')) $where['flow_name'] = $request->param('flow_name');
            if ($request->has('status')) $where['status'] = $request->param('status');
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            $result = $this->flowService->getList($where, $page, $limit);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function read(Request $request): Json
    {
        try {
            $flowId = $request->param('id');
            if (!$flowId) return $this->error('流程ID不能为空');
            $result = $this->flowService->getDetail($flowId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function save(Request $request): Json
    {
        try {
            $data = $request->only(['flow_code', 'flow_name', 'flow_config', 'form_id', 'status', 'remark']);
            $this->validateRequired($data, ['flow_code' => '流程编码', 'flow_name' => '流程名称', 'flow_config' => '流程配置']);
            if (is_string($data['flow_config'])) {
                $data['flow_config'] = json_decode($data['flow_config'], true);
            }
            $flowId = $this->flowService->create($data);
            return $this->success('创建成功', ['id' => $flowId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function update(Request $request): Json
    {
        try {
            $flowId = $request->param('id');
            if (!$flowId) return $this->error('流程ID不能为空');
            $data = $request->only(['flow_name', 'flow_config', 'form_id', 'status', 'remark']);
            if (isset($data['flow_config']) && is_string($data['flow_config'])) {
                $data['flow_config'] = json_decode($data['flow_config'], true);
            }
            $this->flowService->update($flowId, $data);
            return $this->success('更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function delete(Request $request): Json
    {
        try {
            $flowId = $request->param('id');
            if (!$flowId) return $this->error('流程ID不能为空');
            $this->flowService->delete($flowId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function publish(Request $request): Json
    {
        try {
            $flowId = $request->param('id');
            if (!$flowId) return $this->error('流程ID不能为空');
            $this->flowService->publish($flowId);
            return $this->success('发布成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function unpublish(Request $request): Json
    {
        try {
            $flowId = $request->param('id');
            if (!$flowId) return $this->error('流程ID不能为空');
            $this->flowService->unpublish($flowId);
            return $this->success('停用成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
