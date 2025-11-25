<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'

type FeatureItem = {
    label: string
    description: string
    to: string
    badge?: string
}

type FeatureGroup = {
    key: string
    title: string
    subtitle: string
    items: FeatureItem[]
}

const featureGroups: FeatureGroup[] = [
    {
        key: 'management',
        title: '管理功能',
        subtitle: '会务基础配置与权限控制',
        items: [
            { label: '用户管理', description: '账号、权限、登录日志', to: '/backend/users' },
            { label: '会场管理', description: '会场配置与会期时间', to: '/backend/venues' },
            { label: '代表管理', description: '代表导入、国家分配、点名', to: '/backend/delegates' },
            { label: '日志管理', description: '操作与审计记录', to: '/backend/logs' },
        ],
    },
    {
        key: 'presidium',
        title: '主席团功能',
        subtitle: '文件流转、危机、消息调度',
        items: [
            { label: '文件审批', description: '查看与审批代表提交', to: '/backend/presidium/file-approval' },
            { label: '文件管理', description: '发布、分类、可见性', to: '/backend/presidium/file-management' },
            { label: '危机管理', description: '发布危机与追踪反馈', to: '/backend/presidium/crisis' },
            // { label: '时间轴管理', description: '事件记录与时间流速', to: '/backend/presidium/timeline' },
            { label: '消息管理', description: '广播通知与历史', to: '/backend/presidium/messages' },
        ],
    },
    {
        key: 'delegate',
        title: '选手功能',
        subtitle: '个人面板与互动工具',
        items: [
            { label: '个人面板', description: '个人资料与状态', to: '/backend/delegate/profile' },
            { label: '文件中心', description: '上传与查看文件', to: '/backend/delegate/documents' },
            { label: '消息中心', description: '私聊、群聊、广播', to: '/backend/delegate/messages' },
            { label: '危机响应', description: '查看危机与提交方案', to: '/backend/delegate/crisis-response' },
        ],
    },
]

const route = useRoute()
const activePath = computed(() => route.path)
const routePermissionMap: Record<string, string | string[]> = {
    '/backend/users': 'users:manage',
    '/backend/venues': ['users:manage', 'presidium:manage'],
    '/backend/delegates': 'delegates:manage',
    '/backend/logs': 'logs:read',
    '/backend/presidium/file-approval': 'presidium:manage',
    '/backend/presidium/file-management': 'presidium:manage',
    '/backend/presidium/crisis': 'crisis:dispatch',
    '/backend/presidium/timeline': 'timeline:update',
    '/backend/presidium/messages': 'messages:broadcast',
    '/backend/delegate/profile': 'delegate:self',
    '/backend/delegate/documents': 'documents:submit',
    '/backend/delegate/messages': 'messages:send',
    '/backend/delegate/crisis-response': 'delegate:self',
}

const userPermissions = ref<string[]>([])
const loadingPermissions = ref(false)
const permissionError = ref('')

const hasPermission = (route: string): boolean => {
    const requirement = routePermissionMap[route]
    if (!requirement) {
        return false
    }
    if (!userPermissions.value.length) {
        return false
    }
    if (Array.isArray(requirement)) {
        return requirement.some((perm) => userPermissions.value.includes(perm))
    }
    return userPermissions.value.includes(requirement)
}

const filteredFeatureGroups = computed(() =>
    featureGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => hasPermission(item.to)),
        }))
        .filter((group) => group.items.length > 0),
)

const totalFeatures = computed(() =>
    filteredFeatureGroups.value.reduce((sum, group) => sum + group.items.length, 0),
)

const fetchPermissions = async () => {
    loadingPermissions.value = true
    permissionError.value = ''
    try {
        const response = await fetch('/api/auth/profile', { credentials: 'include' })
        if (!response.ok) {
            throw new Error('无法获取权限信息，请重新登录')
        }
        const data = await response.json()
        userPermissions.value = Array.isArray(data?.permissions) ? data.permissions : []
    } catch (error) {
        console.error(error)
        permissionError.value = error instanceof Error ? error.message : '加载权限失败'
    } finally {
        loadingPermissions.value = false
    }
}

onMounted(() => {
    fetchPermissions()
})
</script>

<template>
    <div class="space-y-6 p-4 lg:p-8">
        <section class="grid gap-6 lg:grid-cols-[15rem,1fr]">
            <aside
                class="bg-base-100 border border-base-200 rounded-2xl p-4 lg:sticky lg:top-6 flex flex-col gap-4 h-[93vh]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-primary/70">导航</p>
                        <h2 class="text-lg font-semibold">功能列表</h2>
                    </div>
                    <span class="badge badge-outline">{{ totalFeatures }}</span>
                </div>
                <div class="space-y-6 overflow-y-auto pr-2 flex-1">
                    <div v-if="loadingPermissions" class="flex justify-center py-6">
                        <span class="loading loading-spinner loading-md"></span>
                    </div>
                    <div v-else-if="permissionError" class="alert alert-error alert-soft text-sm">
                        <span>{{ permissionError }}</span>
                    </div>
                    <div v-else-if="filteredFeatureGroups.length === 0" class="text-center text-sm text-base-content/60">
                        暂无可访问的后台功能，请联系管理员分配权限。
                    </div>
                    <div v-else v-for="group in filteredFeatureGroups" :key="group.key"
                        class="border border-base-300 rounded-xl p-4 bg-base-200/60">
                        <p class="text-sm font-semibold text-base-content/70">{{ group.title }}</p>
                        <p class="text-xs text-base-content/50 mb-3">{{ group.subtitle }}</p>
                        <ul class="menu menu-vertical gap-2">
                            <li v-for="item in group.items" :key="item.to">
                                <RouterLink :to="item.to" class="justify-start gap-3 rounded-lg w-100vw"
                                    :class="{ 'active bg-primary/10 text-primary': activePath.startsWith(item.to) }">
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ item.label }}</span>
                                        <span class="text-xs text-base-content/60">{{ item.description }}</span>
                                    </div>
                                </RouterLink>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
            <section
                class="bg-base-100 border border-base-200 rounded-2xl p-0 shadow-sm overflow-y-auto flex-1 h-[93vh]">
                <RouterView />
            </section>
        </section>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
