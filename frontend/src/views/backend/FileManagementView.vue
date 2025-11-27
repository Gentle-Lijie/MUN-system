<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'
import { api, type FileSubmission, type Venue, type FileReference, API_BASE } from '@/services/api'
import PopupFileSelect from '@/components/PopupFileSelect.vue'

const publishedFiles = ref<FileSubmission[]>([])
const loading = ref(false)
const committeeFilter = ref('')
const searchQuery = ref('')
const venues = ref<Venue[]>([])
const showFileSelect = ref(false)
const selectedFile = ref<FileSubmission | null>(null)
const isEditingConfig = ref(false)
const configForm = reactive({
  type: '',
  status: '',
  visibility: '',
  committee_id: '',
})

// 选项常量
const typeOptions = [
  { value: 'position_paper', label: '立场文件' },
  { value: 'working_paper', label: '工作文件' },
  { value: 'draft_resolution', label: '决议草案' },
  { value: 'press_release', label: '新闻稿' },
  { value: 'other', label: '其他' },
]

const statusOptions = [
  { value: 'draft', label: '草稿' },
  { value: 'submitted', label: '待审批' },
  { value: 'approved', label: '已批准' },
  { value: 'published', label: '已发布' },
  { value: 'rejected', label: '已拒绝' },
]

const visibilityOptions = [
  { value: 'committee_only', label: '仅主席团' },
  { value: 'all_committees', label: '会场公开' },
  { value: 'public', label: '公开' },
]

const publishForm = reactive({
  committee_id: '',
  type: '',
  title: '',
  description: '',
  content_path: '',
  visibility: 'committee_only',
})

const editForm = reactive({
  title: '',
  description: '',
  visibility: 'committee_only',
  dias_fb: '',
})

const fetchPublishedFiles = async () => {
  console.log('fetchPublishedFiles called')
  loading.value = true
  try {
    const params: { committeeId?: string; search?: string } = {}
    if (committeeFilter.value) params.committeeId = committeeFilter.value
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
    console.log('API params:', params)
    const response = await api.getPublishedFiles(params)
    console.log('API response:', response)
    publishedFiles.value = response.items
  } catch (error) {
    console.error('Failed to fetch published files:', error)
  } finally {
    loading.value = false
  }
}

const fetchVenues = async () => {
  try {
    const response = await api.getVenues()
    venues.value = response.items
  } catch (error) {
    console.error('Failed to fetch venues:', error)
  }
}

const handleFileSelect = (file: FileReference) => {
  publishForm.content_path = file.title // 或者使用其他合适的字段
}

const selectFile = (file: FileSubmission) => {
  selectedFile.value = file
  // editingFile removed; unified editing handled by isEditingConfig
  isEditingConfig.value = false // 退出配置编辑模式

  // 初始化配置表单
  configForm.type = file.type
  configForm.status = file.status
  configForm.visibility = file.visibility
  configForm.committee_id = file.committee?.id.toString() || ''
}

const startEdit = (file: FileSubmission) => {
  // Enter unified edit/config mode
  selectedFile.value = file
  isEditingConfig.value = true
  // initialize the unified form including title/description
  configForm.type = file.type
  configForm.status = file.status
  configForm.visibility = file.visibility
  configForm.committee_id = file.committee?.id?.toString() || ''
  // reuse editForm fields for title/description when present
  editForm.title = file.title
  editForm.description = file.description || ''
  editForm.dias_fb = file.dias_fb || ''
}

const deleteFile = async (file: FileSubmission) => {
  if (!confirm(`确定要删除文件"${file.title}"吗？此操作不可撤销。`)) return

  try {
    await api.deletePublishedFile(file.id)
    await fetchPublishedFiles()
  } catch (error) {
    console.error('Failed to delete file:', error)
  }
}

const updateConfig = async () => {
  if (!selectedFile.value) return

  try {
    const updateData: any = {
      type: configForm.type,
      status: configForm.status,
      visibility: configForm.visibility,
      // include editable fields
      title: editForm.title || undefined,
      description: editForm.description || undefined,
      dias_fb: editForm.dias_fb || undefined,
    }

    if (configForm.committee_id) {
      updateData.committee_id = parseInt(configForm.committee_id)
    }

    const updated = await api.updatePublishedFile(selectedFile.value.id, updateData)
    // refresh local state with updated data
    selectedFile.value = updated
    await fetchPublishedFiles()
    isEditingConfig.value = false
  } catch (error) {
    console.error('Failed to update file config:', error)
  }
}

onMounted(() => {
  console.log('FileManagementView mounted')
  fetchPublishedFiles()
  fetchVenues()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">文件管理</h2>
      <p class="text-sm text-base-content/70">统一管理发布文件、分类与可见性设置。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
      <!-- 左侧：文件列表 -->
      <div class="space-y-4">
        <div class="flex gap-4 mb-4 flex-wrap">
          <FormField legend="委员会筛选" label="选择委员会" fieldsetClass="min-w-[12rem]">
            <select
              v-model="committeeFilter"
              class="select select-bordered"
              @change="fetchPublishedFiles"
            >
              <option value="">所有委员会</option>
              <option v-for="venue in venues" :key="venue.id" :value="venue.id.toString()">
                {{ venue.name }} ({{ venue.code }})
              </option>
            </select>
          </FormField>
          <FormField legend="搜索" label="按标题或描述" fieldsetClass="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="搜索文件标题或描述"
              class="input input-bordered w-full"
              @input="fetchPublishedFiles"
            />
          </FormField>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="publishedFiles.length === 0" class="text-center py-8 text-base-content/60">
          暂无文件
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="file in publishedFiles"
            :key="file.id"
            class="card bg-base-100 border border-base-200 cursor-pointer transition-all hover:shadow-md"
            :class="{ 'ring-2 ring-primary': selectedFile?.id === file.id }"
            @click="selectFile(file)"
          >
            <div class="card-body p-4">
              <h3 class="card-title text-sm font-medium line-clamp-2">{{ file.title }}</h3>
              <div class="flex items-center gap-2 mt-2">
                <span class="badge badge-primary badge-xs">{{ file.type }}</span>
                <span class="badge badge-outline badge-xs">{{
                  statusOptions.find((opt) => opt.value === file.status)?.label || file.status
                }}</span>
              </div>
              <div class="text-xs text-base-content/60 mt-2">
                {{ file.submitted_by.name }}
              </div>
              <div class="text-xs text-base-content/60">
                {{ file.updated_at ? new Date(file.updated_at).toLocaleDateString() : '' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧：配置面板 -->
      <div class="space-y-4">
        <div
          v-if="!selectedFile"
          class="border border-base-200 rounded-2xl p-8 text-center text-base-content/60"
        >
          选择左侧的文件进行配置
        </div>

        <div v-else class="border border-base-200 rounded-2xl p-4 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-lg">文件配置</h3>
            <!-- <div class="flex gap-2">
               <button v-if="!isEditingConfig" class="btn btn-sm btn-outline" @click="startEditConfig">修改配置</button> 
            <<button v-else class="btn btn-sm btn-outline" @click="cancelEditConfig">取消</button> 
          </div> -->
          </div>

          <!-- 只读模式 -->
          <div v-if="!isEditingConfig" class="space-y-3">
            <div>
              <label class="label-text font-medium">标题</label>
              <p class="text-sm text-base-content/70 mt-1">{{ selectedFile.title }}</p>
            </div>

            <div>
              <label class="label-text font-medium">分类</label>
              <p class="text-sm text-base-content/70 mt-1">
                {{
                  typeOptions.find((opt) => opt.value === selectedFile!.type)?.label ||
                  selectedFile!.type
                }}
              </p>
            </div>

            <div>
              <label class="label-text font-medium">状态</label>
              <p class="text-sm text-base-content/70 mt-1">
                {{
                  statusOptions.find((opt) => opt.value === selectedFile!.status)?.label ||
                  selectedFile!.status
                }}
              </p>
            </div>

            <div>
              <label class="label-text font-medium">可见性</label>
              <p class="text-sm text-base-content/70 mt-1">
                {{
                  visibilityOptions.find((opt) => opt.value === selectedFile!.visibility)?.label ||
                  selectedFile!.visibility
                }}
              </p>
            </div>

            <div>
              <label class="label-text font-medium">委员会</label>
              <p class="text-sm text-base-content/70 mt-1">
                {{
                  selectedFile.committee
                    ? `${selectedFile.committee.name} (${selectedFile.committee.code})`
                    : '无'
                }}
              </p>
            </div>

            <div>
              <label class="label-text font-medium">更新时间</label>
              <p class="text-sm text-base-content/70 mt-1">
                {{
                  selectedFile.updated_at ? new Date(selectedFile.updated_at).toLocaleString() : ''
                }}
              </p>
            </div>

            <div>
              <label class="label-text font-medium">上传人</label>
              <p class="text-sm text-base-content/70 mt-1">{{ selectedFile.submitted_by.name }}</p>
            </div>

            <div v-if="selectedFile.description">
              <label class="label-text font-medium">描述</label>
              <p class="text-sm text-base-content/70 mt-1">{{ selectedFile.description }}</p>
            </div>

            <div v-if="selectedFile.dias_fb">
              <label class="label-text font-medium">主席意见</label>
              <p class="text-sm text-base-content/70 mt-1 whitespace-pre-wrap">
                {{ selectedFile.dias_fb }}
              </p>
            </div>
          </div>

          <!-- 编辑模式 -->
          <form v-else class="space-y-3" @submit.prevent="updateConfig">
            <FormField legend="文件标题" label="输入标题">
              <input
                v-model="editForm.title"
                type="text"
                class="input input-bordered input-sm w-full"
                required
              />
            </FormField>
            <FormField legend="描述" label="可填写背景">
              <textarea
                v-model="editForm.description"
                class="textarea textarea-bordered textarea-sm w-full"
                rows="3"
              ></textarea>
            </FormField>
            <FormField legend="主席意见" label="Dias Feedback">
              <textarea
                v-model="editForm.dias_fb"
                class="textarea textarea-bordered textarea-sm w-full"
                rows="3"
                placeholder="主席团反馈（可选）"
              ></textarea>
            </FormField>

            <!-- 2x2 网格布局：分类、状态、可见性、委员会 -->
            <div class="grid grid-cols-2 gap-4">
              <FormField legend="分类">
                <select v-model="configForm.type" class="select select-bordered w-full" required>
                  <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </FormField>

              <FormField legend="状态">
                <select v-model="configForm.status" class="select select-bordered w-full" required>
                  <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </FormField>

              <FormField legend="可见性">
                <select
                  v-model="configForm.visibility"
                  class="select select-bordered w-full"
                  required
                >
                  <option
                    v-for="option in visibilityOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </FormField>

              <FormField legend="委员会">
                <select v-model="configForm.committee_id" class="select select-bordered w-full">
                  <option value="">无</option>
                  <option v-for="venue in venues" :key="venue.id" :value="venue.id.toString()">
                    {{ venue.name }} ({{ venue.code }})
                  </option>
                </select>
              </FormField>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-full">保存配置</button>
          </form>

          <div class="divider"></div>

          <div class="flex gap-2">
            <a
              :href="`${API_BASE}${selectedFile.content_path}`"
              target="_blank"
              class="btn btn-primary flex-1"
              >查看文件</a
            >
            <button class="btn btn-outline" @click="startEdit(selectedFile)">编辑</button>
            <button class="btn btn-error" @click="deleteFile(selectedFile)">删除</button>
          </div>
        </div>

        <!-- 编辑表单已合并到右侧配置面板 -->
      </div>
    </section>

    <PopupFileSelect v-model="showFileSelect" @select="handleFileSelect" />
  </div>
</template>
