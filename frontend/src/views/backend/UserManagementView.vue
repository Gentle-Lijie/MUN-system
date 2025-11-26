<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import FormField from '@/components/common/FormField.vue'

type UserRole = 'admin' | 'dais' | 'delegate' | 'observer'
type RoleFilter = 'all' | 'admin' | 'dais' | 'delegate'

type UserRecord = {
  id: number
  name: string
  email: string
  role: UserRole
  organization?: string | null
  phone?: string | null
  lastLogin?: string | null
  createdAt?: string | null
  updatedAt?: string | null
  permissions?: string[]
}

const users = ref<UserRecord[]>([])
const selectedUser = ref<UserRecord | null>(null)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const roleFilter = ref<RoleFilter>('all')
const keyword = ref('')
const committeeKeyword = ref('')
const creating = ref(false)
const resetting = ref(false)
const importing = ref(false)
const exporting = ref(false)
const importInputRef = ref<HTMLInputElement | null>(null)

const roleOptions: Array<{ label: string; value: RoleFilter }> = [
  { label: '全部角色', value: 'all' },
  { label: '主席团', value: 'dais' },
  { label: '管理员', value: 'admin' },
  { label: '代表', value: 'delegate' },
]

const roleLabelMap: Record<UserRole, string> = {
  admin: '管理员',
  dais: '主席团',
  delegate: '代表',
  observer: '观察员',
}

const permissionsModalOpen = ref(false)
const editingPermissions = ref<string[]>([])
const savingPermissions = ref(false)

const permissionGroups = {
  admin: ['users:manage', 'presidium:manage', 'delegates:manage', 'logs:read'],
  dais: ['presidium:manage', 'timeline:update', 'crisis:dispatch', 'messages:broadcast'],
  delegate: ['delegate:self', 'documents:submit', 'messages:send'],
  observer: ['observer:read', 'reports:view'],
}

const permissionLabels: Record<string, string> = {
  'users:manage': '用户管理',
  'presidium:manage': '主席团管理',
  'delegates:manage': '代表管理',
  'logs:read': '日志读取',
  'timeline:update': '时间轴更新',
  'crisis:dispatch': '危机发布',
  'messages:broadcast': '消息广播',
  'delegate:self': '代表个人',
  'documents:submit': '文件提交',
  'messages:send': '消息发送',
  'observer:read': '观察员读取',
  'reports:view': '报告查看',
}

const newUserForm = reactive({
  name: '',
  email: '',
  role: 'delegate' as UserRole,
  organization: '',
})

const filteredUsers = computed(() => users.value)

const setSelectedUser = (record: UserRecord | null) => {
  selectedUser.value = record
}

const getUserInitials = (name: string) => name.slice(-2).toUpperCase()

const getAvatarStyle = (name: string) => {
  const colors = [
    '#FF6B6B',
    '#4ECDC4',
    '#45B7D1',
    '#96CEB4',
    '#FFEAA7',
    '#DDA0DD',
    '#98D8C8',
    '#F7DC6F',
    '#BB8FCE',
    '#85C1E9',
  ]
  const hash = name.split('').reduce((a, b) => a + b.charCodeAt(0), 0)
  const colorIndex = hash % colors.length
  const backgroundColor = colors[colorIndex]
  const theme = document.documentElement.getAttribute('data-theme') || 'light'
  const color = theme === 'light' ? '#FFFFFF' : '#000000'
  return { backgroundColor, color }
}

const fetchUsers = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const params = new URLSearchParams()
    if (roleFilter.value !== 'all') params.append('role', roleFilter.value)
    if (keyword.value.trim()) params.append('search', keyword.value.trim())
    if (committeeKeyword.value.trim()) params.append('committee', committeeKeyword.value.trim())
    const query = params.toString() ? `?${params.toString()}` : ''
    const response = await fetch(`/api/users${query}`, { credentials: 'include' })
    if (!response.ok) {
      throw new Error('无法获取用户数据，请确认已登录管理员账户')
    }
    const data = await response.json()
    const resultItems: UserRecord[] = Array.isArray(data?.items) ? data.items : []
    users.value = resultItems
    setSelectedUser(resultItems[0] ?? null)
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '加载用户数据失败'
    users.value = []
    setSelectedUser(null)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  fetchUsers()
}

const createUser = async () => {
  if (!newUserForm.name.trim() || !newUserForm.email.trim()) {
    errorMessage.value = '姓名和邮箱不能为空'
    return
  }
  creating.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload: Record<string, string> = {
      name: newUserForm.name.trim(),
      email: newUserForm.email.trim().toLowerCase(),
      role: newUserForm.role,
    }
    if (newUserForm.organization.trim()) {
      payload.organization = newUserForm.organization.trim()
    }
    const response = await fetch('/api/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload),
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.message || '创建用户失败')
    }
    successMessage.value = '创建用户成功，默认密码为 123456'
    newUserForm.name = ''
    newUserForm.email = ''
    newUserForm.organization = ''
    newUserForm.role = 'delegate'
    await fetchUsers()
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '创建用户失败'
  } finally {
    creating.value = false
  }
}

const handleRowClick = (record: UserRecord) => {
  setSelectedUser(record)
}

const handleResetPassword = async () => {
  if (!selectedUser.value) return
  resetting.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    const response = await fetch(`/api/users/${selectedUser.value.id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ resetPassword: true }),
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.message || '重置密码失败')
    }
    successMessage.value = '密码已重置为 123456'
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '重置密码失败'
  } finally {
    resetting.value = false
  }
}

const triggerImport = () => {
  importInputRef.value?.click()
}

const handleImportFile = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return
  const file = files.item(0)
  if (!file) return
  target.value = ''
  importing.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('file', file)
    const response = await fetch('/api/users/import', {
      method: 'POST',
      credentials: 'include',
      body: formData,
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.message || '导入失败')
    }
    const result = await response.json()
    const errors =
      Array.isArray(result?.errors) && result.errors.length > 0
        ? `，其中 ${result.errors.length} 条记录未导入`
        : ''
    successMessage.value = `导入完成：新增 ${result.created} 条，更新 ${result.updated} 条${errors}`
    if (result?.errors?.length) {
      console.warn('Import warnings:', result.errors)
    }
    await fetchUsers()
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '导入失败'
  } finally {
    importing.value = false
  }
}

const handleExport = async () => {
  exporting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await fetch('/api/users/export', {
      credentials: 'include',
    })
    if (!response.ok) {
      throw new Error('导出失败，请稍后重试')
    }
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download =
      response.headers.get('Content-Disposition')?.split('filename=')[1]?.replace(/"/g, '') ||
      'users.csv'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    successMessage.value = '导出任务已开始'
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '导出失败'
  } finally {
    exporting.value = false
  }
}

const openPermissionsModal = () => {
  if (!selectedUser.value) return
  editingPermissions.value = [...(selectedUser.value.permissions || [])]
  permissionsModalOpen.value = true
}

const togglePermission = (perm: string) => {
  const index = editingPermissions.value.indexOf(perm)
  if (index > -1) {
    editingPermissions.value.splice(index, 1)
  } else {
    editingPermissions.value.push(perm)
  }
}

const toggleGroup = (group: string[]) => {
  const allInGroup = group.every((p) => editingPermissions.value.includes(p))
  if (allInGroup) {
    editingPermissions.value = editingPermissions.value.filter((p) => !group.includes(p))
  } else {
    group.forEach((p) => {
      if (!editingPermissions.value.includes(p)) {
        editingPermissions.value.push(p)
      }
    })
  }
}

const savePermissions = async () => {
  if (!selectedUser.value) return
  savingPermissions.value = true
  try {
    const response = await fetch(`/api/users/${selectedUser.value.id}/permissions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ permissions: editingPermissions.value }),
    })
    if (!response.ok) {
      throw new Error('保存权限失败')
    }
    selectedUser.value.permissions = [...editingPermissions.value]
    permissionsModalOpen.value = false
    successMessage.value = '权限已更新'
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '保存权限失败'
  } finally {
    savingPermissions.value = false
  }
}

onMounted(() => {
  fetchUsers()
})

watch(roleFilter, () => {
  fetchUsers()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">用户管理</h2>
      <p class="text-base-content/70 text-sm">
        实时对接后台，支持搜索、导入/导出以及管理员重置密码等操作。
      </p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[2fr,1fr]">
      <FormField legend="导入用户 CSV" label="选择 CSV 文件" fieldsetClass="hidden">
        <input
          ref="importInputRef"
          type="file"
          accept=".csv"
          class="file-input file-input-bordered"
          @change="handleImportFile"
        />
      </FormField>
      <div class="space-y-4">
        <div class="flex flex-wrap gap-3 items-center">
          <div class="flex flex-wrap gap-3 grow">
            <FormField
              legend="关键词"
              label="按姓名 / 邮箱 / 电话搜索"
              fieldsetClass="grow min-w-[12rem]"
            >
              <input
                v-model="keyword"
                type="text"
                placeholder="按姓名 / 邮箱 / 电话搜索"
                class="input input-bordered w-full"
                @keyup.enter="handleSearch"
              />
            </FormField>
            <FormField legend="学校筛选" label="按学校搜索" fieldsetClass="grow min-w-[12rem]">
              <input
                v-model="committeeKeyword"
                type="text"
                placeholder="按学校搜索"
                class="input input-bordered w-full"
                @keyup.enter="handleSearch"
              />
            </FormField>
            <FormField legend="角色筛选" label="选择角色" fieldsetClass="w-40">
              <select v-model="roleFilter" class="select select-bordered w-full">
                <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </FormField>
          </div>
          <div class="flex gap-2">
            <button class="btn btn-outline" :disabled="loading" @click="handleSearch">搜索</button>
            <button class="btn btn-ghost" :disabled="loading" @click="fetchUsers">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4.5 4.5v6h6M19.5 19.5v-6h-6M19.5 10.5A7.5 7.5 0 0012 3a7.5 7.5 0 00-7.5 7.5m15 6A7.5 7.5 0 0012 21a7.5 7.5 0 01-7.5-7.5"
                />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button class="btn btn-outline" :disabled="importing || loading" @click="triggerImport">
            <span v-if="importing" class="loading loading-spinner loading-xs"></span>
            <span>导入 CSV</span>
          </button>
          <button class="btn btn-outline" :disabled="exporting || loading" @click="handleExport">
            <span v-if="exporting" class="loading loading-spinner loading-xs"></span>
            <span>导出 CSV</span>
          </button>
        </div>

        <div v-if="errorMessage" class="alert alert-error alert-soft text-sm">
          <span>{{ errorMessage }}</span>
        </div>
        <div v-if="successMessage" class="alert alert-success alert-soft text-sm">
          <span>{{ successMessage }}</span>
        </div>

        <div class="overflow-x-auto border border-base-200 rounded-xl">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>头像</th>
                <th>ID</th>
                <th>姓名</th>
                <th>角色</th>
                <th>学校</th>
                <th>邮箱</th>
                <th>最近登录</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center">
                  <span class="loading loading-spinner loading-sm"></span>
                </td>
              </tr>
              <tr v-else-if="filteredUsers.length === 0">
                <td colspan="7" class="text-center text-base-content/60">暂无符合条件的用户</td>
              </tr>
              <tr
                v-for="user in filteredUsers"
                v-else
                :key="user.id"
                class="cursor-pointer"
                :class="selectedUser?.id === user.id ? 'bg-primary/10' : ''"
                @click="handleRowClick(user)"
              >
                >
                <td>
                  <div class="avatar placeholder">
                    <div
                      class="w-10 rounded-full flex justify-center items-center"
                      :style="getAvatarStyle(user.name)"
                    >
                      <span class="text-sm font-semibold">{{ getUserInitials(user.name) }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ user.id }}</td>
                <td class="font-semibold">{{ user.name }}</td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'badge-secondary': user.role === 'admin',
                      'badge-primary': user.role === 'dais',
                      'badge-accent': user.role === 'delegate',
                      'badge-neutral': user.role === 'observer',
                    }"
                  >
                    {{ roleLabelMap[user.role] || user.role }}
                  </span>
                </td>
                <td>{{ user.organization || '-' }}</td>
                <td>{{ user.email }}</td>
                <td>{{ user.lastLogin || '尚未登录' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="space-y-6">
        <div v-if="selectedUser" class="border border-base-200 rounded-xl p-4 bg-base-200/50">
          <h3 class="font-semibold">当前选中</h3>
          <p class="text-sm text-base-content/60 mb-3">快速查看用户信息与权限范围。</p>
          <div class="space-y-2 text-sm">
            <p><span class="text-base-content/60">姓名：</span>{{ selectedUser.name }}</p>
            <p><span class="text-base-content/60">邮箱：</span>{{ selectedUser.email }}</p>
            <p>
              <span class="text-base-content/60">角色：</span>{{ roleLabelMap[selectedUser.role] }}
            </p>
            <p>
              <span class="text-base-content/60">学校：</span
              >{{ selectedUser.organization || '未填写' }}
            </p>
            <p>
              <span class="text-base-content/60">联系电话：</span
              >{{ selectedUser.phone || '未填写' }}
            </p>
            <p>
              <span class="text-base-content/60">最近登录：</span
              >{{ selectedUser.lastLogin || '尚未登录' }}
            </p>
            <p>
              <span class="text-base-content/60">创建时间：</span
              >{{ selectedUser.createdAt || '-' }}
            </p>
          </div>
          <div class="mt-4 flex gap-2">
            <button
              class="btn btn-primary btn-sm"
              :disabled="resetting"
              @click="handleResetPassword"
            >
              <span v-if="resetting" class="loading loading-spinner loading-xs"></span>
              <span>重置密码</span>
            </button>
            <button class="btn btn-outline btn-sm" @click="openPermissionsModal">配置权限</button>
          </div>
        </div>

        <form class="border border-base-200 rounded-xl p-4 space-y-3" @submit.prevent="createUser">
          <h3 class="font-semibold">创建新用户</h3>
          <FormField legend="姓名" label="请输入姓名">
            <input
              v-model="newUserForm.name"
              type="text"
              class="input input-bordered"
              placeholder="输入姓名"
            />
          </FormField>
          <FormField legend="邮箱" label="用于登录的邮箱">
            <input
              v-model="newUserForm.email"
              type="email"
              class="input input-bordered"
              placeholder="user@mun.org"
            />
          </FormField>
          <FormField legend="角色" label="分配系统角色">
            <select v-model="newUserForm.role" class="select select-bordered">
              <option value="dais">主席团</option>
              <option value="admin">管理员</option>
              <option value="delegate">代表</option>
              <option value="observer">观察员</option>
            </select>
          </FormField>
          <FormField legend="学校/组织" label="输入所属单位">
            <input
              v-model="newUserForm.organization"
              type="text"
              class="input input-bordered"
              placeholder="如 UNSC / 秘书处"
            />
          </FormField>
          <button class="btn btn-primary w-full" type="submit" :disabled="creating">
            <span v-if="creating" class="loading loading-spinner loading-sm"></span>
            <span>立即创建</span>
          </button>
        </form>
      </div>
    </section>

    <!-- Permissions Modal -->
    <div class="modal" :class="{ 'modal-open': permissionsModalOpen }">
      <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-lg">配置权限 - {{ selectedUser?.name }}</h3>
        <p class="py-4">选择用户允许的权限，支持按组批量操作。</p>
        <div class="space-y-4">
          <fieldset
            v-for="(group, role) in permissionGroups"
            :key="role"
            class="fieldset border border-base-200 rounded-lg p-4"
          >
            <legend class="fieldset-legend text-base font-semibold mb-3">
              {{ roleLabelMap[role as UserRole] }} 权限
            </legend>
            <label class="label cursor-pointer justify-start gap-2 mb-2">
              <input
                type="checkbox"
                class="checkbox"
                :checked="group.every((p) => editingPermissions.includes(p))"
                @change="toggleGroup(group)"
              />
              <span class="label-text">全选/取消该角色权限</span>
            </label>
            <div class="grid grid-cols-2 gap-2 ml-6">
              <label
                v-for="perm in group"
                :key="perm"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input
                  type="checkbox"
                  class="checkbox checkbox-sm"
                  :checked="editingPermissions.includes(perm)"
                  @change="togglePermission(perm)"
                />
                <span class="text-sm">{{ permissionLabels[perm] || perm }}</span>
              </label>
            </div>
          </fieldset>
        </div>
        <div class="modal-action">
          <button class="btn" @click="permissionsModalOpen = false">取消</button>
          <button class="btn btn-primary" :disabled="savingPermissions" @click="savePermissions">
            <span v-if="savingPermissions" class="loading loading-spinner loading-xs"></span>
            <span>保存</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
