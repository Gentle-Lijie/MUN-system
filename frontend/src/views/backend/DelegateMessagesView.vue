<script setup lang="ts">
import { reactive, ref } from 'vue'

type ChatMessage = {
  id: string
  sender: string
  content: string
  time: string
  direction: 'incoming' | 'outgoing'
}

const conversations = ref([
  {
    id: 'conv-chair',
    title: '主席团通知',
    unread: 1,
    messages: [
      { id: 'm1', sender: '主席团', content: '下午 2:00 进入危机环节，请做好准备。', time: '12:10', direction: 'incoming' },
      { id: 'm2', sender: '我', content: '收到，已准备好资料。', time: '12:12', direction: 'outgoing' },
    ] as ChatMessage[],
  },
  {
    id: 'conv-team',
    title: '国家伙伴群',
    unread: 0,
    messages: [
      { id: 'm3', sender: '队友A', content: '先讨论第二条 operative clause。', time: '11:50', direction: 'incoming' },
    ] as ChatMessage[],
  },
])

const selectedConversation = ref(conversations.value[0])
const messageInput = reactive({ content: '' })

const selectConversation = (convId: string) => {
  const found = conversations.value.find((conv) => conv.id === convId)
  if (found) {
    selectedConversation.value = found
    found.unread = 0
  }
}

const sendMessage = () => {
  if (!selectedConversation.value || !messageInput.content) return
  selectedConversation.value.messages.push({
    id: crypto.randomUUID(),
    sender: '我',
    content: messageInput.content,
    time: new Date().toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }),
    direction: 'outgoing',
  })
  messageInput.content = ''
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">消息中心</h2>
      <p class="text-sm text-base-content/70">展示代表私聊与群聊，并可直接回复。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[18rem,1fr]">
      <aside class="border border-base-200 rounded-2xl p-3 space-y-2">
        <button
          v-for="conversation in conversations"
          :key="conversation.id"
          class="btn btn-ghost w-full justify-between"
          :class="{ 'btn-active': selectedConversation && selectedConversation.id === conversation.id }"
          @click="selectConversation(conversation.id)"
        >
          <span>{{ conversation.title }}</span>
          <span v-if="conversation.unread" class="badge badge-error">{{ conversation.unread }}</span>
        </button>
      </aside>

      <div class="border border-base-200 rounded-2xl p-4 flex flex-col gap-4 min-h-[28rem]">
        <div class="flex-1 overflow-y-auto space-y-3 pr-2">
          <div v-for="message in selectedConversation?.messages" :key="message.id" class="chat"
            :class="message.direction === 'incoming' ? 'chat-start' : 'chat-end'">
            <div class="chat-header text-xs text-base-content/60">
              {{ message.sender }}
              <time class="ml-2">{{ message.time }}</time>
            </div>
            <div class="chat-bubble"
              :class="message.direction === 'incoming' ? 'chat-bubble-primary' : 'chat-bubble-secondary'">
              {{ message.content }}
            </div>
          </div>
          <p v-if="!selectedConversation?.messages.length" class="text-center text-base-content/60 text-sm">暂无消息</p>
        </div>
        <div class="join w-full">
          <textarea v-model="messageInput.content" class="textarea textarea-bordered join-item grow"
            placeholder="输入内容"></textarea>
          <button class="btn btn-primary join-item" @click="sendMessage">发送</button>
        </div>
      </div>
    </section>
  </div>
</template>
