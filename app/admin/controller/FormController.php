<?php
namespace app\admin\controller;

use app\admin\service\FormService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

class FormController extends BaseController
{
    protected $formService;
    
    public function __construct()
    {
        $this->formService = new FormService();
    }
    
    public function index(Request $request): Json
    {
        try {
            $where = [];
            if ($request->has('form_name')) $where['form_name'] = $request->param('form_name');
            if ($request->has('status')) $where['status'] = $request->param('status');
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            $result = $this->formService->getList($where, $page, $limit);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function read(Request $request): Json
    {
        try {
            $formId = $request->param('id');
            if (!$formId) return $this->error('表单ID不能为空');
            $result = $this->formService->getDetail($formId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function save(Request $request): Json
    {
        try {
            $data = $request->only(['form_code', 'form_name', 'form_config', 'status', 'remark']);
            $this->validateRequired($data, ['form_code' => '表单编码', 'form_name' => '表单名称', 'form_config' => '表单配置']);
            if (is_string($data['form_config'])) {
                $data['form_config'] = json_decode($data['form_config'], true);
            }
            $formId = $this->formService->create($data);
            return $this->success('创建成功', ['id' => $formId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function update(Request $request): Json
    {
        try {
            $formId = $request->param('id');
            if (!$formId) return $this->error('表单ID不能为空');
            $data = $request->only(['form_name', 'form_config', 'status', 'remark']);
            if (isset($data['form_config']) && is_string($data['form_config'])) {
                $data['form_config'] = json_decode($data['form_config'], true);
            }
            $this->formService->update($formId, $data);
            return $this->success('更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function delete(Request $request): Json
    {
        try {
            $formId = $request->param('id');
            if (!$formId) return $this->error('表单ID不能为空');
            $this->formService->delete($formId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function saveData(Request $request): Json
    {
        try {
            $formId = $request->param('form_id');
            $formData = $request->param('form_data', []);
            if (!$formId || empty($formData)) return $this->error('参数不完整');
            if (is_string($formData)) {
                $formData = json_decode($formData, true);
            }
            $dataId = $this->formService->saveFormData($formId, $formData, $this->getUserId());
            return $this->success('保存成功', ['id' => $dataId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function getData(Request $request): Json
    {
        try {
            $formId = $request->param('form_id');
            if (!$formId) return $this->error('表单ID不能为空');
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            $result = $this->formService->getFormData($formId, $page, $limit);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
