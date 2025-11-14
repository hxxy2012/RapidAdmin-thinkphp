<?php
namespace app\admin\service;

use app\admin\model\Permission;
use extend\tenant\TenantContext;
use think\facade\Db;
use think\facade\Log;
use extend\auth\Auth;

class PermissionService
{
    public function getTree($parentId = 0, $tenantId = null)
    {
        $tenantId = $tenantId ?? TenantContext::getTenantId();
        $query = Permission::where('parent_id', $parentId)->where('status', 1)->order('sort', 'asc');
        
        if ($tenantId !== null) {
            $query->where(function($q) use ($tenantId) {
                $q->whereNull('tenant_id')->whereOr('tenant_id', $tenantId);
            });
        } else {
            $query->whereNull('tenant_id');
        }
        
        $permissions = $query->select()->toArray();
        foreach ($permissions as &$permission) {
            $permission['children'] = $this->getTree($permission['id'], $tenantId);
        }
        
        return $permissions;
    }

    public function getList(array $where = [])
    {
        $query = Permission::with(['parent']);
        if (!empty($where['permission_name'])) {
            $query->where('permission_name', 'like', '%' . $where['permission_name'] . '%');
        }
        if (!empty($where['permission_type'])) {
            $query->where('permission_type', $where['permission_type']);
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }
        return $query->order('sort', 'asc')->select()->toArray();
    }

    public function getDetail($permissionId)
    {
        $permission = Permission::with(['parent'])->find($permissionId);
        if (!$permission) throw new \Exception('权限不存在');
        return $permission->toArray();
    }

    public function create(array $data)
    {
        try {
            $tenantId = TenantContext::getTenantId();
            $permission = new Permission();
            $permission->tenant_id = $data['is_system'] ?? false ? null : $tenantId;
            $permission->parent_id = $data['parent_id'] ?? 0;
            $permission->permission_name = $data['permission_name'];
            $permission->permission_code = $data['permission_code'];
            $permission->permission_type = $data['permission_type'];
            $permission->sort = $data['sort'] ?? 0;
            $permission->status = $data['status'] ?? 1;
            $permission->save();
            
            Log::info('Permission created', ['permission_id' => $permission->id]);
            return $permission->id;
        } catch (\Exception $e) {
            Log::error('Permission creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update($permissionId, array $data)
    {
        try {
            $permission = Permission::find($permissionId);
            if (!$permission) throw new \Exception('权限不存在');
            
            $allowFields = ['parent_id', 'permission_name', 'permission_code', 'permission_type', 'sort', 'status'];
            foreach ($allowFields as $field) {
                if (isset($data[$field])) $permission->$field = $data[$field];
            }
            
            $result = $permission->save();
            $this->clearRelatedCache($permissionId);
            Log::info('Permission updated', ['permission_id' => $permissionId]);
            return $result !== false;
        } catch (\Exception $e) {
            Log::error('Permission update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete($permissionId)
    {
        $permission = Permission::find($permissionId);
        if (!$permission) throw new \Exception('权限不存在');
        if ($permission->hasChildren()) throw new \Exception('该权限下有子权限，不能删除');
        
        Db::startTrans();
        try {
            $permission->delete();
            Db::table('ea_role_permission')->where('permission_id', $permissionId)->delete();
            Db::commit();
            $this->clearRelatedCache($permissionId);
            Log::info('Permission deleted', ['permission_id' => $permissionId]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    protected function clearRelatedCache($permissionId)
    {
        $tenantId = TenantContext::getTenantId();
        $roleIds = Db::table('ea_role_permission')->where('permission_id', $permissionId)->column('role_id');
        foreach ($roleIds as $roleId) {
            $userIds = Db::table('ea_user_role')->where('role_id', $roleId)->column('user_id');
            foreach ($userIds as $userId) {
                Auth::clearCache($userId, $tenantId);
            }
        }
    }
}
