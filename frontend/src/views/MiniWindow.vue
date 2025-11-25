<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { api, type MessageRecord, type MessageTarget } from '@/services/api'
import FormField from '@/components/common/FormField.vue'

    // 立即提供全局测试函数，即使组件还未完全加载
    ; (window as any).testMUNNotification = (count = 1) => {
        console.log('🔔 测试通知功能...')

        if (!('Notification' in window)) {
            console.error('❌ 此浏览器不支持系统通知')
            return
        }

        if (Notification.permission === 'granted') {
            console.log(`✅ 发送测试通知: ${count} 条新消息`)
            const notification = new Notification('MUN 新消息', {
                body: `系统通知: 您有 ${count} 条新消息`,
                icon: '/favicon.ico',
                tag: 'mun-test-notification',
                requireInteraction: false,
            })
            setTimeout(() => notification.close(), 5000)
        } else if (Notification.permission === 'denied') {
            console.error('❌ 通知权限已被拒绝')
            console.log('💡 解决方案：在浏览器地址栏点击锁图标 → 站点设置 → 通知 → 允许')
            console.log('🔄 或者刷新页面重新请求权限')
        } else {
            console.warn('⚠️ 通知权限未设置')
            console.log('💡 请点击页面顶部的蓝色"启用通知"按钮')
        }
    }

type TabKey = 'files' | 'messages' | 'messageList'

const tabs: { key: TabKey; label: string }[] = [
    { key: 'files', label: '提交文件' },
    { key: 'messages', label: '发送消息' },
    { key: 'messageList', label: '消息列表' },
]

const activeTab = ref<TabKey>('files')

const userProfile = ref<{ role: string } | null>(null)

const messageList = ref<MessageRecord[]>([])
const messageLoading = ref(false)
const messageError = ref('')
const messagePagination = reactive({ page: 1, pageSize: 10, total: 0 })
const lastMessageCount = ref(0)
const lastMessageContent = ref('')  // 跟踪最新消息的内容
const refreshInterval = ref<number | null>(null)
const notificationPermission = ref<NotificationPermission>('default')

const fileForm = reactive({
    title: '',
    type: 'position_paper',
    description: '',
    file: null as File | null,
})

const messageForm = reactive({
    target: 'dias' as 'everyone' | 'dias',
    content: '',
})

const submitting = ref(false)

onMounted(async () => {
    try {
        userProfile.value = await api.getProfile()
        // Set default target based on role
        if (userProfile.value.role === 'delegate') {
            messageForm.target = 'dias'
        } else {
            messageForm.target = 'everyone'
        }

        // 检查当前通知权限状态（不自动请求）
        notificationPermission.value = Notification.permission
        console.log('🚀 MiniWindow 组件初始化完成')
        console.log('🔐 当前通知权限状态:', Notification.permission)
        console.log('👤 用户角色:', userProfile.value?.role || '未知')

            // 将测试函数挂载到全局对象，方便在控制台中调用
            ; (window as any).testMUNNotification = testNotification
        console.log('✅ MUN通知测试函数已挂载，可在控制台使用 testMUNNotification()')
        console.log('💡 使用示例: testMUNNotification() 或 testMUNNotification(5)')

        // Load messages for the list tab
        await fetchMessages()

        // 每秒自动刷新消息列表
        console.log('⏰ 设置自动刷新定时器: 每秒检查一次新消息')
        refreshInterval.value = setInterval(fetchMessages, 10000)
    } catch (error) {
        console.error('Failed to load user profile:', error)
    }
})

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value)
    }
    // 清理全局测试函数
    if ((window as any).testMUNNotification) {
        delete (window as any).testMUNNotification
    }
})

const targetLabelMap: Record<MessageTarget, string> = {
    everyone: '全体广播',
    committee: '指定会场',
    dias: '主席团通道',
    delegate: '代表私信',
}

function formatTime(time: string): string {
    return new Date(time).toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function targetDisplay(message: MessageRecord): string {
    if (message.targetMeta?.recipientName) {
        return message.targetMeta.recipientName
    }
    if (message.targetMeta?.committeeName) {
        return `${message.targetMeta.committeeName}${message.targetMeta.committeeCode ? ` (${message.targetMeta.committeeCode})` : ''}`
    }
    return ''
}

// 请求通知权限
async function requestNotificationPermission() {
    console.log('🔄 开始请求通知权限...')

    if (!('Notification' in window)) {
        console.warn('❌ 此浏览器不支持系统通知')
        return false
    }

    console.log('📊 当前权限状态:', Notification.permission)

    if (Notification.permission === 'granted') {
        console.log('✅ 权限已授予')
        notificationPermission.value = 'granted'
        return true
    }

    if (Notification.permission === 'denied') {
        console.log('❌ 权限已被拒绝')
        notificationPermission.value = 'denied'
        return false
    }

    try {
        console.log('🔄 正在请求用户授权...')
        const permission = await Notification.requestPermission()
        console.log('📊 用户响应:', permission)

        notificationPermission.value = permission

        if (permission === 'granted') {
            console.log('✅ 通知权限已成功启用！')
            return true
        } else {
            console.log('❌ 用户拒绝了通知权限')
            return false
        }
    } catch (error) {
        console.error('❌ 请求通知权限时发生错误:', error)
        return false
    }
}

// 处理启用通知按钮点击
async function handleEnableNotifications() {
    console.log('🔔 用户点击了启用通知按钮')
    const success = await requestNotificationPermission()

    if (success) {
        // 可以在这里发送一个测试通知
        console.log('🎉 通知权限设置成功！')
    } else {
        console.log('😞 通知权限设置失败')
    }
}

// 显示新消息通知
function showNewMessageNotification(messageOrCount: MessageRecord | number) {
    const timestamp = new Date().toLocaleTimeString()
    console.log(`🔔 [${timestamp}] 开始创建通知...`)

    // 检查权限：优先使用组件状态，如果不可用则直接检查浏览器权限
    const hasPermission = (typeof notificationPermission !== 'undefined' && notificationPermission.value === 'granted') ||
        Notification.permission === 'granted'

    console.log(`🔐 [${timestamp}] 权限检查:`, {
        componentPermission: notificationPermission?.value || 'undefined',
        browserPermission: Notification.permission,
        hasPermission: hasPermission,
        notificationSupported: 'Notification' in window
    })

    if (!hasPermission) {
        console.warn(`🚫 [${timestamp}] 没有通知权限，无法显示通知`)
        return
    }

    let title: string
    let body: string
    let notificationType: string

    if (typeof messageOrCount === 'number') {
        // 测试用的数字参数
        title = 'MUN 新消息'
        body = `系统通知: 您有 ${messageOrCount} 条新消息`
        notificationType = 'test'
    } else {
        // 实际的消息对象
        const message = messageOrCount
        const contentPreview = message.content.length > 30 ?
            message.content.substring(0, 30) + '...' : message.content
        const senderName = message.sender?.name || '未知发送者'

        title = 'MUN 新消息'
        body = `${senderName}: ${contentPreview}`
        notificationType = 'real_message'
    }

    console.log(`📝 [${timestamp}] 通知内容:`, {
        type: notificationType,
        title: title,
        body: body,
        bodyLength: body.length
    })

    try {
        const notification = new Notification(title, {
            body: body,
            icon: '/favicon.ico',
            tag: 'mun-new-message',
            requireInteraction: false,
        })

        console.log(`✅ [${timestamp}] 通知对象创建成功:`, {
            title: notification.title,
            body: notification.body,
            tag: notification.tag
        })

        // 点击通知时切换到消息列表标签页（如果组件已加载）
        notification.onclick = () => {
            console.log(`👆 [${timestamp}] 用户点击了通知`)
            if (typeof activeTab !== 'undefined') {
                console.log(`🔄 [${timestamp}] 切换到消息列表标签页`)
                activeTab.value = 'messageList'
            }
            window.focus()
            notification.close()
        }

        // 8秒后自动关闭（给用户更多时间阅读）
        setTimeout(() => {
            console.log(`⏰ [${timestamp}] 通知自动关闭`)
            notification.close()
        }, 8000)

        console.log(`🎉 [${timestamp}] 通知已成功显示！`)

    } catch (error) {
        console.error(`❌ [${timestamp}] 创建通知失败:`, error)
    }
}// 测试通知功能的函数，可以在控制台中调用
function testNotification(count = 1) {
    console.log('🔔 测试通知功能...')

    if (!('Notification' in window)) {
        console.error('❌ 此浏览器不支持系统通知')
        return
    }

    // 检查组件是否已完全加载
    if (typeof notificationPermission === 'undefined' || notificationPermission.value === undefined) {
        console.warn('⚠️ MiniWindow 组件可能还未完全加载，请稍后再试')
        console.log('💡 尝试直接请求权限并发送通知...')

        // 直接请求权限并发送通知
        if (Notification.permission === 'granted') {
            console.log(`✅ 发送测试通知: ${count} 条新消息`)
            showNewMessageNotification(count)
        } else if (Notification.permission === 'denied') {
            console.error('❌ 通知权限已被拒绝，请在浏览器设置中重新启用')
        } else {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log(`✅ 权限已获得，发送测试通知: ${count} 条新消息`)
                    showNewMessageNotification(count)
                } else {
                    console.error('❌ 用户拒绝了通知权限')
                }
            })
        }
        return
    }

    if (notificationPermission.value === 'denied') {
        console.error('❌ 通知权限已被拒绝，请在浏览器设置中重新启用')
        return
    }

    if (notificationPermission.value === 'default') {
        console.log('⚠️ 正在请求通知权限...')
        requestNotificationPermission().then(granted => {
            if (granted) {
                console.log('✅ 通知权限已获得，现在发送测试通知')
                showNewMessageNotification(count)
            } else {
                console.error('❌ 用户拒绝了通知权限')
            }
        })
        return
    }

    if (notificationPermission.value === 'granted') {
        console.log(`✅ 发送测试通知: ${count} 条新消息`)
        showNewMessageNotification(count)
        return
    }
}

async function fetchMessages() {
    const timestamp = new Date().toLocaleTimeString()
    console.log(`🔄 [${timestamp}] 开始获取消息列表...`)

    messageLoading.value = true
    messageError.value = ''
    try {
        const response = await api.getMessages({
            page: messagePagination.page,
            pageSize: messagePagination.pageSize,
        })

        console.log(`📊 [${timestamp}] API响应:`, {
            total: response.total,
            itemsCount: response.items.length,
            currentPage: messagePagination.page,
            pageSize: messagePagination.pageSize
        })

        // 检查是否有新消息（比较最新消息的内容）
        const currentLatestMessage = response.items.length > 0 ? response.items[0] : null
        const currentMessageContent = currentLatestMessage ?
            `${currentLatestMessage.sender?.name || '未知'}:${currentLatestMessage.content}` : ''

        console.log(`📝 [${timestamp}] 最新消息分析:`, {
            hasMessage: !!currentLatestMessage,
            sender: currentLatestMessage?.sender?.name || '未知',
            contentLength: currentLatestMessage?.content?.length || 0,
            contentPreview: currentLatestMessage?.content?.substring(0, 50) + '...' || '无内容',
            messageHash: currentMessageContent.substring(0, 100)
        })

        const previousContent = lastMessageContent.value
        const hasNewMessage = lastMessageContent.value && currentMessageContent !== lastMessageContent.value

        console.log(`🔍 [${timestamp}] 消息比较结果:`, {
            previousContent: previousContent || '首次加载',
            currentContent: currentMessageContent || '无消息',
            hasNewMessage: hasNewMessage,
            contentChanged: currentMessageContent !== previousContent
        })

        messageList.value = response.items
        messagePagination.total = response.total
        lastMessageCount.value = response.total

        // 更新最新消息内容
        if (currentMessageContent) {
            lastMessageContent.value = currentMessageContent
            console.log(`💾 [${timestamp}] 已更新消息跟踪器:`, currentMessageContent.substring(0, 100))
        }

        // 检查通知触发条件
        const currentTab = activeTab.value
        const hasPermission = (typeof notificationPermission !== 'undefined' && notificationPermission.value === 'granted') ||
            Notification.permission === 'granted'

        console.log(`🚨 [${timestamp}] 通知条件检查:`, {
            hasNewMessage: hasNewMessage,
            hasCurrentMessage: !!currentLatestMessage,
            currentTab: currentTab,
            isOnMessageListTab: currentTab === 'messageList',
            shouldShowNotification: hasNewMessage && currentLatestMessage && currentTab !== 'messageList',
            hasPermission: hasPermission,
            notificationPermission: notificationPermission?.value || 'undefined',
            browserPermission: Notification.permission
        })

        // 如果有新消息且当前不在消息列表页面，显示系统通知
        if (hasNewMessage && currentLatestMessage && activeTab.value !== 'messageList') {
            console.log(`🔔 [${timestamp}] 触发新消息通知:`, {
                sender: currentLatestMessage.sender?.name,
                contentPreview: currentLatestMessage.content.substring(0, 50) + '...',
                notificationType: 'real_message'
            })
            showNewMessageNotification(currentLatestMessage)
        } else {
            console.log(`🔕 [${timestamp}] 不触发通知，原因:`, {
                noNewMessage: !hasNewMessage,
                noCurrentMessage: !currentLatestMessage,
                onMessageListTab: currentTab === 'messageList'
            })
        }
    } catch (error) {
        console.error(`❌ [${timestamp}] 获取消息失败:`, error)
        messageError.value = '加载消息失败'
    } finally {
        messageLoading.value = false
        console.log(`✅ [${timestamp}] 消息获取完成\n`)
    }
}

function changeMessagePage(delta: number) {
    messagePagination.page += delta
    fetchMessages()
}

function setTab(key: TabKey) {
    activeTab.value = key
}

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    fileForm.file = target.files?.[0] || null
}

const submitFile = async () => {
    if (!fileForm.title || !fileForm.file) return

    submitting.value = true
    try {
        // Upload file first
        const uploadResult = await api.uploadFile(fileForm.file)

        // Then submit the file info
        await api.submitFile({
            title: fileForm.title,
            type: fileForm.type,
            description: fileForm.description || undefined,
            content_path: uploadResult.fileUrl,
        })

        // Reset form
        Object.assign(fileForm, {
            title: '',
            type: 'position_paper',
            description: '',
            file: null,
        })

        // Reset file input
        const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement
        if (fileInput) fileInput.value = ''

        alert('文件提交成功！')
    } catch (error) {
        console.error('Failed to submit file:', error)
        alert('文件提交失败，请重试')
    } finally {
        submitting.value = false
    }
}

const sendMessage = async () => {
    if (!messageForm.content) return

    submitting.value = true
    try {
        await api.sendMessage({
            target: messageForm.target,
            content: messageForm.content,
        })

        alert('消息发送成功！')
        messageForm.content = ''
    } catch (error) {
        console.error('Failed to send message:', error)
        alert('消息发送失败，请重试')
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <section class="mx-auto max-w-xl">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body flex flex-col gap-8">
                <div class="flex justify-center">
                    <div class="tabs tabs-border">
                        <template v-for="(tab, idx) in tabs" :key="tab.key">
                            <button class="tab" :class="{ 'tab-active': activeTab === tab.key }"
                                @click="setTab(tab.key)">
                                {{ tab.label }}
                            </button>
                            <span v-if="idx < tabs.length - 1" class="mx-4"></span>
                        </template>
                    </div>
                </div>

                <!-- 通知权限请求区域 -->
                <div v-if="notificationPermission === 'default'" class="alert alert-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold">启用消息通知</h3>
                        <div class="text-xs">接收新消息提醒，点击下方按钮启用</div>
                    </div>
                    <button class="btn btn-sm btn-primary" @click="handleEnableNotifications">
                        启用通知
                    </button>
                </div>

                <div v-if="notificationPermission === 'granted'" class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>消息通知已启用 ✓</span>
                </div>

                <div v-if="notificationPermission === 'denied'" class="alert alert-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    <div>
                        <h3 class="font-bold">通知权限被拒绝</h3>
                        <div class="text-xs">请在浏览器地址栏点击锁图标重新启用通知权限</div>
                    </div>
                </div>

                <div v-if="activeTab === 'files'" class="flex flex-col gap-6">
                    <h2 class="text-xl font-semibold text-center">上传文件给主席团</h2>
                    <FormField legend="文件标题" label="请输入文件标题" fieldsetClass="fieldset-primary">
                        <input v-model="fileForm.title" type="text" class="input input-bordered w-full"
                            placeholder="文件标题" required />
                    </FormField>
                    <FormField legend="文件类型" label="请选择文件类型" fieldsetClass="fieldset-primary">
                        <select v-model="fileForm.type" class="select select-bordered w-full">
                            <option value="position_paper">立场文件</option>
                            <option value="working_paper">工作文件</option>
                            <option value="draft_resolution">决议草案</option>
                            <option value="press_release">新闻稿</option>
                            <option value="other">其他</option>
                        </select>
                    </FormField>
                    <FormField legend="选择文件" label="上传要提交的附件" fieldsetClass="fieldset-primary"
                        description="支持多种格式">
                        <input type="file" class="file-input file-input-primary file-input-bordered w-full"
                            @change="handleFileChange" required />
                    </FormField>
                    <FormField legend="备注/致辞" label="可附上背景说明" fieldsetClass="fieldset-primary"
                        description="选填">
                        <textarea v-model="fileForm.description" class="textarea h-24 w-full"
                            placeholder="可附上背景说明"></textarea>
                    </FormField>
                    <button class="btn btn-primary w-full" :disabled="submitting || !fileForm.title || !fileForm.file"
                        @click="submitFile">
                        <span v-if="submitting" class="loading loading-spinner loading-sm"></span>
                        提交
                    </button>
                </div>

                <div v-else-if="activeTab === 'messages'" class="flex flex-col gap-6">
                    <h2 class="text-xl font-semibold text-center">发送即时消息</h2>
                    <FormField legend="目标频道" label="选择消息接收方" fieldsetClass="fieldset-primary">
                        <select v-model="messageForm.target" class="select select-bordered w-full">
                            <option v-if="userProfile?.role !== 'delegate'" value="everyone">全体代表</option>
                            <option value="dias">主席团</option>
                        </select>
                    </FormField>
                    <FormField legend="消息内容" label="输入广播内容" fieldsetClass="fieldset-primary">
                        <textarea v-model="messageForm.content" class="textarea textarea-bordered w-full" rows="4"
                            placeholder="输入广播内容" required></textarea>
                    </FormField>
                    <button class="btn btn-primary w-full" :disabled="submitting || !messageForm.content"
                        @click="sendMessage">
                        <span v-if="submitting" class="loading loading-spinner loading-sm"></span>
                        发送
                    </button>
                </div>

                <div v-else-if="activeTab === 'messageList'" class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold text-center">消息历史</h2>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-base-content/70">共 {{ messagePagination.total }} 条消息</span>
                        <button class="btn btn-outline btn-xs" @click="fetchMessages" :disabled="messageLoading">
                            <span v-if="messageLoading" class="loading loading-spinner loading-xs"></span>
                            刷新
                        </button>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2">
                        <div v-if="messageLoading" class="text-center py-4">
                            <span class="loading loading-spinner loading-sm"></span>
                        </div>
                        <div v-else-if="messageError" class="text-center text-error text-sm py-4">
                            {{ messageError }}
                        </div>
                        <div v-else-if="messageList.length === 0" class="text-center text-base-content/60 text-sm py-4">
                            暂无消息
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="message in messageList" :key="message.id"
                                class="border border-base-200 rounded-lg p-3 bg-base-100">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="text-xs text-base-content/70">{{ formatTime(message.time) }}</div>
                                    <div class="badge badge-outline badge-xs">{{ targetLabelMap[message.target] }}</div>
                                </div>
                                <div class="text-sm font-medium mb-1">
                                    {{ message.sender?.name || '系统' }}
                                    <span class="text-xs text-base-content/60">· {{ message.sender?.role || 'system'
                                        }}</span>
                                </div>
                                <div v-if="targetDisplay(message)" class="text-xs text-base-content/60 mb-2">
                                    至: {{ targetDisplay(message) }}
                                </div>
                                <div class="text-sm leading-relaxed">{{ message.content }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="messagePagination.total > messagePagination.pageSize" class="flex justify-center gap-2">
                        <button class="btn btn-xs" :disabled="messagePagination.page <= 1"
                            @click="changeMessagePage(-1)">
                            上一页
                        </button>
                        <span class="text-xs self-center text-base-content/70">
                            {{ messagePagination.page }}
                        </span>
                        <button class="btn btn-xs"
                            :disabled="messagePagination.page >= Math.ceil(messagePagination.total / messagePagination.pageSize)"
                            @click="changeMessagePage(1)">
                            下一页
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
