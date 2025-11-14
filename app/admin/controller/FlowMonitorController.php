<?php
namespace app\admin\controller;

use app\common\controller\BaseController;
use extend\tenant\TenantContext;
use think\Request;
use think\response\Json;
use think\facade\Db;

class FlowMonitorController extends BaseController
{
    public function statistics(Request $request): Json
    {
        try {
            $tenantId = TenantContext::getTenantId();
            
            $stats = [
                'total' => Db::table('ea_flow_instance')->where('tenant_id', $tenantId)->count(),
                'running' => Db::table('ea_flow_instance')->where('tenant_id', $tenantId)->where('status', 1)->count(),
                'completed' => Db::table('ea_flow_instance')->where('tenant_id', $tenantId)->where('status', 2)->count(),
                'rejected' => Db::table('ea_flow_instance')->where('tenant_id', $tenantId)->where('status', 3)->count(),
                'cancelled' => Db::table('ea_flow_instance')->where('tenant_id', $tenantId)->where('status', 4)->count(),
                'pending_tasks' => Db::table('ea_flow_task as t')
                    ->join('ea_flow_instance as i', 't.instance_id = i.id')
                    ->where('i.tenant_id', $tenantId)
                    ->where('t.status', 1)
                    ->count(),
            ];
            
            return $this->success('获取成功', $stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function list(Request $request): Json
    {
        try {
            $tenantId = TenantContext::getTenantId();
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);
            
            $query = Db::table('ea_flow_instance')->where('tenant_id', $tenantId);
            
            if ($request->has('status')) {
                $query->where('status', $request->param('status'));
            }
            
            if ($request->has('flow_code')) {
                $query->where('flow_code', $request->param('flow_code'));
            }
            
            if ($request->has('title')) {
                $query->where('title', 'like', '%' . $request->param('title') . '%');
            }
            
            $total = $query->count();
            $list = $query->page($page, $limit)->order('id', 'desc')->select();
            
            return $this->success('获取成功', ['total' => $total, 'list' => $list]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function flowStatistics(Request $request): Json
    {
        try {
            $tenantId = TenantContext::getTenantId();
            
            $flowStats = Db::table('ea_flow_instance')
                ->field('flow_code, flow_name, COUNT(*) as total, 
                    SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as running,
                    SUM(CASE WHEN status=2 THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status=3 THEN 1 ELSE 0 END) as rejected')
                ->where('tenant_id', $tenantId)
                ->group('flow_code, flow_name')
                ->select();
            
            return $this->success('获取成功', $flowStats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function timeStatistics(Request $request): Json
    {
        try {
            $tenantId = TenantContext::getTenantId();
            $days = $request->param('days', 7);
            
            $stats = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $nextDate = date('Y-m-d', strtotime("-{$i} days +1 day"));
                
                $count = Db::table('ea_flow_instance')
                    ->where('tenant_id', $tenantId)
                    ->where('start_time', '>=', $date)
                    ->where('start_time', '<', $nextDate)
                    ->count();
                
                $stats[] = ['date' => $date, 'count' => $count];
            }
            
            return $this->success('获取成功', $stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
