<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PopupFileSelect from '@/components/PopupFileSelect.vue'
import { api, type FileReference } from '@/services/api'

const showFileSelect = ref(false)
const loading = ref(false)
const files = ref<FileReference[]>([])
const selectedFile = ref<FileReference | null>(null)
const search = ref('')

const filteredFiles = computed(() => {
  if (!search.value.trim()) return files.value
  const keyword = search.value.trim().toLowerCase()
  return files.value.filter((file) => {
    const nameMatch = file.committee?.name?.toLowerCase().includes(keyword) ?? false
    const codeMatch = file.committee?.code?.toLowerCase().includes(keyword) ?? false
    return (
      file.title.toLowerCase().includes(keyword) ||
      file.type.toLowerCase().includes(keyword) ||
      nameMatch ||
      codeMatch
    )
  })
})

const fetchReferences = async () => {
  loading.value = true
  try {
    const response = await api.getFileReferences()
    files.value = response.items
  } catch (error) {
    console.error('Failed to load file references', error)
  } finally {
    loading.value = false
  }
}

const handleFileSelect = (file: FileReference) => {
  selectedFile.value = file
  showFileSelect.value = false
}

onMounted(() => {
  fetchReferences()
  showFileSelect.value = true
})
</script>

<template>
  <section class="min-h-screen bg-base-200 p-6 lg:p-10">
    <div
      class="mx-auto flex max-w-6xl flex-col gap-6 rounded-3xl bg-base-100 p-6 lg:p-10 shadow-xl"
    >
      <header class="flex flex-wrap items-center gap-4 border-b border-base-200 pb-4">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-primary/70">文件引用</p>
          <h1 class="text-3xl lg:text-4xl font-bold">关联文件选择器</h1>
          <p class="text-base text-base-content/60">
            实时读取数据库中的参考文件，便于动议与文件互相绑定。
          </p>
        </div>
        <div class="ml-auto flex flex-wrap gap-3">
          <button class="btn btn-primary" @click="showFileSelect = true">打开文件弹窗</button>
          <button class="btn btn-ghost" :disabled="loading" @click="fetchReferences">
            {{ loading ? '加载中...' : '刷新列表' }}
          </button>
          <button class="btn" @click="$router.push('/backend')">返回后台</button>
        </div>
      </header>

      <section class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
        <article class="rounded-2xl border border-base-200 p-5 space-y-4">
          <h2 class="text-xl font-semibold flex items-center justify-between">
            已选文件
            <span class="badge badge-outline">{{ selectedFile ? '已关联' : '未选择' }}</span>
          </h2>
          <div v-if="selectedFile" class="space-y-2">
            <p class="text-2xl font-bold">{{ selectedFile.title }}</p>
            <div class="flex flex-wrap gap-2 text-sm text-base-content/70">
              <span class="badge badge-secondary">{{ selectedFile.type }}</span>
              <span v-if="selectedFile.committee" class="badge badge-outline">
                {{ selectedFile.committee.name }} · {{ selectedFile.committee.code }}
              </span>
            </div>
            <p class="text-sm text-base-content/60">可在动议弹窗中直接引用上述文件。</p>
          </div>
          <div v-else class="text-base-content/60 text-sm">
            暂未选择文件，点击右上角的按钮或下方列表即可绑定。
          </div>
        </article>

        <article class="rounded-2xl border border-base-200 p-5 space-y-4">
          <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">文件引用库</h2>
            <div class="grow">
              <label class="input input-bordered flex items-center gap-2">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5 opacity-50"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
                <input
                  v-model="search"
                  type="text"
                  placeholder="按标题/类型/会场搜索"
                  class="grow"
                />
              </label>
            </div>
          </div>
          <div
            class="rounded-2xl border border-dashed border-base-300 min-h-[260px] overflow-hidden"
          >
            <div v-if="loading" class="flex items-center justify-center py-16">
              <span class="loading loading-spinner loading-lg"></span>
            </div>
            <div
              v-else-if="filteredFiles.length === 0"
              class="text-center text-base-content/60 py-16"
            >
              暂无匹配的文件，请尝试取消筛选或等待管理员发布。
            </div>
            <div v-else class="overflow-y-auto max-h-[360px] divide-y divide-base-200">
              <button
                v-for="file in filteredFiles"
                :key="file.id"
                type="button"
                class="w-full px-4 py-3 text-left hover:bg-base-200 transition flex flex-col gap-1"
                @click="handleFileSelect(file)"
              >
                <div class="flex items-center gap-2">
                  <span class="text-lg font-semibold">{{ file.title }}</span>
                  <span class="badge badge-outline">{{ file.type }}</span>
                </div>
                <p class="text-sm text-base-content/60">
                  {{
                    file.committee ? `${file.committee.name} · ${file.committee.code}` : '全体可见'
                  }}
                </p>
              </button>
            </div>
          </div>
        </article>
      </section>
    </div>
    <PopupFileSelect v-model="showFileSelect" @select="handleFileSelect" />
  </section>
</template>
