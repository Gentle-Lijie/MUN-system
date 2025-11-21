<script setup lang="ts">
import { reactive, ref } from 'vue'

type MessageChannel = '全体广播' | '主席团内线' | '指定会场'

type MessageRecord = {
  id: string
  channel: MessageChannel
  sender: string
  content: string
  time: string
}

const history = ref<MessageRecord[]>([
  { id: 'M-001', channel: '全体广播', sender: '秘书处', content: '请各会场于10:30完成点名。', time: '09:45' },
  { id: 'M-002', channel: '主席团内线', sender: '危机组', content: '安理会将在13:20引入新危机。', time: '10:05' },
])

const messageForm = reactive({
  channel: '全体广播' as MessageChannel,
  target: '',
  content: '',
})

const sendMessage = () => {
  if (!messageForm.content) return
  history.value.unshift({
    id: `M-${(history.value.length + 1).toString().padStart(3, '0')}`,
    channel: messageForm.channel,
    sender: '当前用户',
    content: messageForm.content,
    time: new Date().toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }),
  })
  messageForm.content = ''
  messageForm.target = ''
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">消息管理</h2>
      <p class="text-sm text-base-content/70">支持广播消息、内部通知与历史记录查询。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.4fr,1fr]">
      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4 space-y-3">
          <h3 class="font-semibold">发送消息</h3>
          <label class="form-control">
            <span class="label-text">渠道</span>
            <select v-model="messageForm.channel" class="select select-bordered">
              <option value="全体广播">全体广播</option>
              <option value="主席团内线">主席团内线</option>
              <option value="指定会场">指定会场</option>
            </select>
          </label>
          <label class="form-control" v-if="messageForm.channel === '指定会场'">
            <span class="label-text">目标会场</span>
            <input v-model="messageForm.target" type="text" class="input input-bordered" placeholder="如：安理会" />
          </label>
          <label class="form-control">
            <span class="label-text">消息内容</span>
            <textarea v-model="messageForm.content" class="textarea textarea-bordered" rows="4"
              placeholder="输入需要推送的文本"></textarea>
          </label>
          <div class="flex gap-3">
            <button class="btn btn-primary" @click="sendMessage">立即发送</button>
            <button class="btn btn-outline">保存为草稿</button>
          </div>
        </div>

        <div class="border border-base-200 rounded-2xl p-4 space-y-3">
          <h3 class="font-semibold">历史记录</h3>
          <div class="space-y-3 max-h-[26rem] overflow-y-auto pr-2">
            <div v-for="msg in history" :key="msg.id" class="bg-base-200 rounded-xl p-3">
              <div class="flex justify-between text-sm">
                <span class="font-semibold">{{ msg.channel }}</span>
                <span>{{ msg.time }}</span>
              </div>
              <p class="text-xs text-base-content/60">{{ msg.sender }}</p>
              <p class="mt-2 text-sm">{{ msg.content }}</p>
            </div>
            <p v-if="history.length === 0" class="text-center text-base-content/60 text-sm">暂无消息</p>
          </div>
        </div>
      </div>

      <div class="border border-base-200 rounded-2xl p-4 space-y-3">
        <h3 class="font-semibold">消息模板</h3>
        <label class="form-control">
          <span class="label-text">模板标题</span>
          <input type="text" class="input input-bordered" placeholder="如：迟到提醒" />
        </label>
        <label class="form-control">
          <span class="label-text">模板内容</span>
          <textarea class="textarea textarea-bordered" rows="5" placeholder="在此编写模板"></textarea>
        </label>
        <button class="btn btn-outline w-full">保存模板</button>
        <div class="divider">或</div>
        <button class="btn btn-ghost w-full">导入历史模板</button>
      </div>
    </section>
  </div>
</template>
