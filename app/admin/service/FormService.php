<?php
namespace app\admin\service;

use think\facade\Db;
use think\facade\Log;
use extend\tenant\TenantContext;

class FormService
{
    public function getList(array $where = [], $page = 1, $limit = 15)
    {
        $query = Db::table('ea_form_definition')->where('tenant_id', TenantContext::getTenantId());
        if (!empty($where['form_name'])) {
            $query->where('form_name', 'like', '%' . $where['form_name'] . '%');
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }
        $total = $query->count();
        $list = $query->page($page, $limit)->order('id', 'desc')->select();
        return ['total' => $total, 'page' => $page, 'limit' => $limit, 'list' => $list];
    }

    public function getDetail($formId)
    {
        $form = Db::table('ea_form_definition')->find($formId);
        if (!$form) throw new \Exception('表单不存在');
        $form['form_config'] = json_decode($form['form_config'], true);
        return $form;
    }

    public function create(array $data)
    {
        Db::startTrans();
        try {
            $formId = Db::table('ea_form_definition')->insertGetId([
                'tenant_id' => TenantContext::getTenantId(),
                'form_code' => $data['form_code'],
                'form_name' => $data['form_name'],
                'form_config' => json_encode($data['form_config']),
                'version' => 1,
                'status' => $data['status'] ?? 1,
                'remark' => $data['remark'] ?? '',
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
            Log::info('Form created', ['form_id' => $formId]);
            return $formId;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function update($formId, array $data)
    {
        Db::startTrans();
        try {
            $form = Db::table('ea_form_definition')->find($formId);
            if (!$form) throw new \Exception('表单不存在');
            
            $updateData = ['update_time' => date('Y-m-d H:i:s')];
            if (isset($data['form_name'])) $updateData['form_name'] = $data['form_name'];
            if (isset($data['form_config'])) {
                $updateData['form_config'] = json_encode($data['form_config']);
                $updateData['version'] = $form['version'] + 1;
            }
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['remark'])) $updateData['remark'] = $data['remark'];
            
            Db::table('ea_form_definition')->where('id', $formId)->update($updateData);
            Db::commit();
            Log::info('Form updated', ['form_id' => $formId]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function delete($formId)
    {
        $form = Db::table('ea_form_definition')->find($formId);
        if (!$form) throw new \Exception('表单不存在');
        
        Db::table('ea_form_definition')->where('id', $formId)->delete();
        Log::info('Form deleted', ['form_id' => $formId]);
        return true;
    }

    public function saveFormData($formId, array $formData, $userId)
    {
        $dataId = Db::table('ea_form_data')->insertGetId([
            'form_id' => $formId,
            'data_json' => json_encode($formData),
            'creator_id' => $userId,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        return $dataId;
    }

    public function getFormData($formId, $page = 1, $limit = 15)
    {
        $query = Db::table('ea_form_data')->where('form_id', $formId);
        $total = $query->count();
        $list = $query->page($page, $limit)->order('id', 'desc')->select();
        foreach ($list as &$item) {
            $item['data_json'] = json_decode($item['data_json'], true);
        }
        return ['total' => $total, 'list' => $list];
    }
}
