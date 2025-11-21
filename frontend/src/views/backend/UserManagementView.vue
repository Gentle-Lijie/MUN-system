<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

type UserRole = 'admin' | 'chair' | 'delegate'
type UserStatus = 'active' | 'suspended'

type UserRecord = {
    id: string
    name: string
    role: UserRole
    email: string
    status: UserStatus
    lastLogin: string
}

const users = ref<UserRecord[]>([
    { id: 'U-001', name: '张三', role: 'chair', email: 'chair@mun.org', status: 'active', lastLogin: '2025-05-13 09:45' },
    { id: 'U-002', name: '李四', role: 'admin', email: 'admin@mun.org', status: 'active', lastLogin: '2025-05-11 21:32' },
    { id: 'U-003', name: '王五', role: 'delegate', email: 'delegate01@mun.org', status: 'suspended', lastLogin: '2025-04-30 13:08' },
    { id: 'U-004', name: '赵六', role: 'delegate', email: 'delegate02@mun.org', status: 'active', lastLogin: '2025-05-12 10:28' },
])

const roleFilter = ref<'all' | UserRole>('all')
const keyword = ref('')
const selectedUser = ref<UserRecord | null>(users.value[0] ?? null)

const filteredUsers = computed(() => {
    return users.value.filter((user) => {
        const roleOk = roleFilter.value === 'all' || user.role === roleFilter.value
        const keywordOk = keyword.value
            ? [user.name, user.email, user.id].some((field) => field.toLowerCase().includes(keyword.value.toLowerCase()))
            : true
        return roleOk && keywordOk
    })
})

const permissionsByRole: Record<UserRole, string[]> = {
    chair: ['文件审批', '危机发布', '时间轴调度', '消息广播'],
    admin: ['用户管理', '系统配置', '日志审计'],
    delegate: ['文件上传', '消息沟通', '危机反馈'],
}

const newUserForm = reactive({
    name: '',
    email: '',
    role: 'delegate' as UserRole,
})

const createUser = () => {
    if (!newUserForm.name || !newUserForm.email) return
    users.value.unshift({
        id: `U-${(users.value.length + 1).toString().padStart(3, '0')}`,
        name: newUserForm.name,
        email: newUserForm.email,
        role: newUserForm.role,
        status: 'active',
        lastLogin: '尚未登录',
    })
    selectedUser.value = users.value[0] ?? null
    newUserForm.name = ''
    newUserForm.email = ''
    newUserForm.role = 'delegate'
}

const toggleUserStatus = (record: UserRecord) => {
    record.status = record.status === 'active' ? 'suspended' : 'active'
}

const selectUser = (record: UserRecord) => {
    selectedUser.value = record
}
</script>

<template>
    <div class="p-6 space-y-6">
        <header class="border-b border-base-200 pb-4">
            <h2 class="text-2xl font-bold">用户管理</h2>
            <p class="text-base-content/70 text-sm">查看、搜索并管理后台用户，支持快速开停用和权限调整。</p>
        </header>

        <section class="grid gap-6 xl:grid-cols-[2fr,1fr]">
            <div class="space-y-4">
                <div class="join flex flex-wrap gap-3">
                    <label class="input input-bordered flex items-center gap-2 join-item grow min-w-[12rem]">
                        <input v-model="keyword" type="text" placeholder="按姓名/邮箱/ID 搜索" class="grow" />
                    </label>
                    <select v-model="roleFilter" class="select select-bordered join-item w-40">
                        <option value="all">全部角色</option>
                        <option value="chair">主席团</option>
                        <option value="admin">管理员</option>
                        <option value="delegate">代表</option>
                    </select>
                    <button class="btn btn-outline join-item">导出列表</button>
                </div>

                <div class="overflow-x-auto border border-base-200 rounded-xl">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>姓名</th>
                                <th>角色</th>
                                <th>状态</th>
                                <th>最近登录</th>
                                <th class="text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in filteredUsers" :key="user.id" @click="selectUser(user)"
                                class="cursor-pointer">
                                <td>{{ user.id }}</td>
                                <td class="font-semibold">{{ user.name }}</td>
                                <td>
                                    <span class="badge" :class="{
                                        'badge-primary': user.role === 'chair',
                                        'badge-secondary': user.role === 'admin',
                                        'badge-accent': user.role === 'delegate'
                                    }">
                                        {{ user.role === 'chair' ? '主席团' : user.role === 'admin' ? '管理员' : '代表' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge"
                                        :class="user.status === 'active' ? 'badge-success' : 'badge-warning'">
                                        {{ user.status === 'active' ? '启用' : '停用' }}
                                    </span>
                                </td>
                                <td>{{ user.lastLogin }}</td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-ghost" @click.stop="toggleUserStatus(user)">
                                        {{ user.status === 'active' ? '停用' : '启用' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="6" class="text-center text-base-content/60">暂无符合条件的用户</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="border border-base-200 rounded-xl p-4 bg-base-200/50" v-if="selectedUser">
                    <h3 class="font-semibold">当前选中</h3>
                    <p class="text-sm text-base-content/60 mb-3">快速查看用户信息与权限范围。</p>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-base-content/60">姓名：</span>{{ selectedUser.name }}</p>
                        <p><span class="text-base-content/60">邮箱：</span>{{ selectedUser.email }}</p>
                        <p>
                            <span class="text-base-content/60">角色：</span>
                            {{ selectedUser.role === 'chair' ? '主席团' : selectedUser.role === 'admin' ? '管理员' : '代表' }}
                        </p>
                        <p><span class="text-base-content/60">最近登录：</span>{{ selectedUser.lastLogin }}</p>
                    </div>
                    <p class="text-xs text-base-content/60 mt-4">权限能力</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        <li v-for="permission in permissionsByRole[selectedUser.role]" :key="permission">{{ permission
                            }}</li>
                    </ul>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary btn-sm">调整权限</button>
                        <button class="btn btn-outline btn-sm">重置密码</button>
                    </div>
                </div>

                <form class="border border-base-200 rounded-xl p-4 space-y-3" @submit.prevent="createUser">
                    <h3 class="font-semibold">创建新用户</h3>
                    <label class="form-control w-full">
                        <span class="label-text">姓名</span>
                        <input v-model="newUserForm.name" type="text" class="input input-bordered" placeholder="输入姓名" />
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text">邮箱</span>
                        <input v-model="newUserForm.email" type="email" class="input input-bordered"
                            placeholder="user@mun.org" />
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text">角色</span>
                        <select v-model="newUserForm.role" class="select select-bordered">
                            <option value="chair">主席团</option>
                            <option value="admin">管理员</option>
                            <option value="delegate">代表</option>
                        </select>
                    </label>
                    <button class="btn btn-primary w-full" type="submit">立即创建</button>
                </form>
            </div>
        </section>
    </div>
</template>
