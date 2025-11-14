<?php
namespace app\admin\service;

use think\facade\Db;
use think\facade\Log;
use extend\tenant\TenantContext;

class FlowService
{
    public function getList(array $where = [], $page = 1, $limit = 15)
    {
        $query = Db::table('ea_flow_definition')->where('tenant_id', TenantContext::getTenantId());
        if (!empty($where['flow_name'])) {
            $query->where('flow_name', 'like', '%' . $where['flow_name'] . '%');
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }
        $total = $query->count();
        $list = $query->page($page, $limit)->order('id', 'desc')->select();
        return ['total' => $total, 'page' => $page, 'limit' => $limit, 'list' => $list];
    }

    public function getDetail($flowId)
    {
        $flow = Db::table('ea_flow_definition')->find($flowId);
        if (!$flow) throw new \Exception('流程不存在');
        $flow['flow_config'] = json_decode($flow['flow_config'], true);
        return $flow;
    }

    public function create(array $data)
    {
        Db::startTrans();
        try {
            $flowId = Db::table('ea_flow_definition')->insertGetId([
                'tenant_id' => TenantContext::getTenantId(),
                'flow_code' => $data['flow_code'],
                'flow_name' => $data['flow_name'],
                'flow_config' => json_encode($data['flow_config']),
                'form_id' => $data['form_id'] ?? 0,
                'version' => 1,
                'status' => $data['status'] ?? 0,
                'remark' => $data['remark'] ?? '',
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
            Log::info('Flow created', ['flow_id' => $flowId]);
            return $flowId;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function update($flowId, array $data)
    {
        Db::startTrans();
        try {
            $flow = Db::table('ea_flow_definition')->find($flowId);
            if (!$flow) throw new \Exception('流程不存在');
            
            $updateData = ['update_time' => date('Y-m-d H:i:s')];
            if (isset($data['flow_name'])) $updateData['flow_name'] = $data['flow_name'];
            if (isset($data['flow_config'])) {
                $updateData['flow_config'] = json_encode($data['flow_config']);
                $updateData['version'] = $flow['version'] + 1;
            }
            if (isset($data['form_id'])) $updateData['form_id'] = $data['form_id'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['remark'])) $updateData['remark'] = $data['remark'];
            
            Db::table('ea_flow_definition')->where('id', $flowId)->update($updateData);
            Db::commit();
            Log::info('Flow updated', ['flow_id' => $flowId]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function delete($flowId)
    {
        $flow = Db::table('ea_flow_definition')->find($flowId);
        if (!$flow) throw new \Exception('流程不存在');
        
        $instanceCount = Db::table('ea_flow_instance')->where('definition_id', $flowId)->count();
        if ($instanceCount > 0) throw new \Exception('该流程已有实例，不能删除');
        
        Db::table('ea_flow_definition')->where('id', $flowId)->delete();
        Log::info('Flow deleted', ['flow_id' => $flowId]);
        return true;
    }

    public function publish($flowId)
    {
        $flow = Db::table('ea_flow_definition')->find($flowId);
        if (!$flow) throw new \Exception('流程不存在');
        Db::table('ea_flow_definition')->where('id', $flowId)->update(['status' => 1, 'update_time' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function unpublish($flowId)
    {
        Db::table('ea_flow_definition')->where('id', $flowId)->update(['status' => 0, 'update_time' => date('Y-m-d H:i:s')]);
        return true;
    }
}
