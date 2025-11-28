<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'
import { api, type FileSubmission, type Crisis, buildFileUrl } from '@/services/api'

const documents = ref<FileSubmission[]>([])
const loading = ref(false)
const crisisLoading = ref(false)
const crises = ref<Crisis[]>([])

const showUploadModal = ref(false)

const uploadForm = reactive({
  title: '',
  type: 'position_paper',
  description: '',
  file: null as File | null,
})

const editingDocId = ref<number | null>(null)
const showEditModal = ref(false)
const editForm = reactive({
  title: '',
  type: 'position_paper',
  description: '',
  file: null as File | null,
  content_path: '',
  status: 'draft',
})

// 文件类型映射
const typeLabels: Record<string, string> = {
  position_paper: '立场文件',
  working_paper: '工作文件',
  draft_resolution: '决议草案',
  press_release: '新闻稿',
  other: '其他',
}

const FINALIZED_STATUSES = ['approved', 'published', 'rejected'] as const

const canEditDocument = (doc: FileSubmission): boolean => {
  if (typeof doc.can_edit === 'boolean') return doc.can_edit
  const isOwner = doc.is_owner ?? false
  if (!isOwner) return false
  return !FINALIZED_STATUSES.includes(doc.status as (typeof FINALIZED_STATUSES)[number])
}

const fetchDocuments = async () => {
  loading.value = true
  try {
    const response = await api.getMyDocuments()
    documents.value = response.items
  } catch (error) {
    console.error('Failed to fetch documents:', error)
  } finally {
    loading.value = false
  }
}

const fetchCrises = async () => {
  crisisLoading.value = true
  try {
    const response = await api.getCrises()
    crises.value = response.items
  } catch (error) {
    console.error('Failed to load crises:', error)
  } finally {
    crisisLoading.value = false
  }
}

const resetUploadForm = () => {
  uploadForm.title = ''
  uploadForm.type = 'position_paper'
  uploadForm.description = ''
  uploadForm.file = null
}

const closeUploadModal = () => {
  showUploadModal.value = false
  resetUploadForm()
}

const uploadDocument = async () => {
  if (!uploadForm.title || !uploadForm.file) return

  try {
    // First upload the file
    const uploadResult = await api.uploadFile(uploadForm.file)

    // Then submit the document
    await api.submitFile({
      title: uploadForm.title,
      type: uploadForm.type,
      description: uploadForm.description || undefined,
      content_path: uploadResult.fileUrl,
    })

    await fetchDocuments()

    // Reset form
    resetUploadForm()
    showUploadModal.value = false
  } catch (error) {
    console.error('Failed to upload document:', error)
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  uploadForm.file = target.files?.[0] || null
}

const startEditDocument = (doc: FileSubmission) => {
  editingDocId.value = doc.id
  editForm.title = doc.title
  editForm.type = doc.type
  editForm.description = doc.description || ''
  editForm.content_path = doc.content_path
  editForm.status = doc.status || 'draft'
  editForm.file = null
}

const openEditModal = (doc: FileSubmission) => {
  startEditDocument(doc)
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  cancelEditDocument()
}

const saveModal = async () => {
  await submitEditDocument()
  showEditModal.value = false
}

const onEditClick = (doc: FileSubmission) => {
  if (!canEditDocument(doc)) {
    window.alert('只能编辑自己且未定稿的文件')
    return
  }
  openEditModal(doc)
}

const onEditFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  editForm.file = target.files?.[0] || null
}

const cancelEditDocument = () => {
  editingDocId.value = null
  editForm.title = ''
  editForm.type = 'position_paper'
  editForm.description = ''
  editForm.file = null
  editForm.content_path = ''
  editForm.status = 'draft'
}

const submitEditDocument = async () => {
  if (editingDocId.value === null) return

  try {
    let content_path = editForm.content_path
    if (editForm.file) {
      const uploaded = await api.uploadFile(editForm.file)
      content_path = uploaded.fileUrl
    }

    const payload: any = {
      title: editForm.title,
      type: editForm.type,
      description: editForm.description || undefined,
      content_path,
      status: editForm.status,
    }

    await api.updateSubmission(editingDocId.value, payload)
    await fetchDocuments()
    cancelEditDocument()
  } catch (error) {
    console.error('Failed to update submission:', error)
  }
}
const showDiasFeedback = (doc: FileSubmission) => {
  window.alert('审批意见：\n' + (doc.dias_fb || '（无）'))
}

const targetSummary = (crisis: Crisis) => {
  if (!crisis.targetCommittees || crisis.targetCommittees.length === 0) return '面向：全部委员会'
  return `面向：${crisis.targetCommittees.length} 个委员会`
}

onMounted(() => {
  fetchDocuments()
  fetchCrises()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">危机与文件管理</h2>
      <p class="text-sm text-base-content/70">
        并排查看危机动态和文件状态，所有编辑操作通过弹窗完成。
      </p>
    </header>

    <section class="grid gap-6 lg:grid-cols-[35%_65%]">
      <!-- 左侧：危机速览 -->
      <article class="rounded-2xl border border-base-200 p-4 bg-base-100">
        <header class="flex items-center justify-between gap-3 mb-4">
          <div>
            <h3 class="text-xl font-semibold">危机速览</h3>
            <p class="text-sm text-base-content/60">与本委员会相关的危机、任务和提醒</p>
          </div>
          <button class="btn btn-sm" :disabled="crisisLoading" @click="fetchCrises">
            {{ crisisLoading ? '刷新中…' : '刷新' }}
          </button>
        </header>
        <div class="space-y-4 max-h-[600px] overflow-y-auto">
          <div v-if="crisisLoading" class="flex justify-center py-10">
            <span class="loading loading-spinner loading-lg"></span>
          </div>
          <p v-else-if="crises.length === 0" class="text-center text-base-content/60 py-6">
            暂无危机信息
          </p>
          <article v-for="crisis in crises" :key="crisis.id" class="rounded-xl border border-base-200 p-4 space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
              <h4 class="text-lg font-semibold">{{ crisis.title }}</h4>
              <span class="badge" :class="
                  crisis.status === 'active'
                    ? 'badge-info'
                    : crisis.status === 'resolved'
                      ? 'badge-success'
                      : 'badge-outline'
                ">
                {{
                crisis.status === 'active'
                ? '进行中'
                : crisis.status === 'resolved'
                ? '已结案'
                : '草稿'
                }}
              </span>
              <span class="badge badge-ghost">{{ targetSummary(crisis) }}</span>
            </div>
            <p class="text-sm text-base-content/70 whitespace-pre-line">{{ crisis.content }}</p>
            <div class="flex items-center gap-3 text-sm">
              <a v-if="crisis.filePath" :href="buildFileUrl(crisis.filePath)" class="link" target="_blank"
                rel="noopener">查看附件</a>
              <span class="text-base-content/60">更新：{{
                crisis.publishedAt ? new Date(crisis.publishedAt).toLocaleString() : '—'
              }}</span>
            </div>
          </article>
        </div>
      </article>

      <!-- 右侧：我的文件 -->
      <article class="rounded-2xl border border-base-200 p-4 bg-base-100">
        <header class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-xl font-semibold">我的文件</h3>
            <p class="text-sm text-base-content/60">查看文件进度、审批意见</p>
          </div>
          <button class="btn btn-primary btn-sm" @click="showUploadModal = true">上传文件</button>
        </header>
        <div class="overflow-x-auto rounded-xl border border-base-200">
          <table class="table table-sm">
            <thead>
              <tr>
                <th class="text-center">标题</th>
                <th class="text-center">类型</th>
                <th class="text-center">状态</th>
                <th class="text-center">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td>
                  <div class="flex flex-col">
                    <span class="font-medium">{{ doc.title }}</span>
                    <div class="text-xs text-base-content/60 flex flex-wrap gap-2">
                      <span>更新：{{
                        doc.updated_at ? new Date(doc.updated_at).toLocaleString() : '—'
                      }}</span>
                      <span v-if="!doc.is_owner && doc.submitted_by?.name" class="badge badge-ghost">
                        来自：{{ doc.submitted_by?.name }}
                      </span>
                      <button v-if="doc.dias_fb" class="btn btn-ghost btn-xs" @click.prevent="showDiasFeedback(doc)">
                        审批意见
                      </button>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-outline">{{ typeLabels[doc.type] || doc.type }}</span>
                </td>
                <td>
                  <span class="badge" :class="{
                    'badge-success': doc.status === 'approved',
                    'badge-primary': doc.status === 'published',
                    'badge-info': doc.status === 'submitted',
                    'badge-warning': doc.status === 'draft' || !doc.status,
                    'badge-error': doc.status === 'rejected',
                  }">
                    {{
                      doc.status === 'approved'
                        ? '已通过'
                        : doc.status === 'published'
                          ? '已发布'
                          : doc.status === 'submitted'
                            ? '待审批'
                            : doc.status === 'draft' || !doc.status
                              ? '草稿'
                              : '已驳回'
                    }}
                  </span>
                </td>
                <td class="space-x-1 whitespace-nowrap">
                  <a :href="buildFileUrl(doc.content_path)" target="_blank" class="btn btn-xs btn-outline">查看</a>
                  <button class="btn btn-xs btn-secondary" :disabled="!canEditDocument(doc)"
                    :class="{ 'opacity-60 cursor-not-allowed': !canEditDocument(doc) }"
                    :title="!canEditDocument(doc) ? '只能编辑自己且未定稿的文件' : '编辑'" @click.prevent="onEditClick(doc)">
                    编辑
                  </button>
                </td>
              </tr>
              <tr v-if="documents.length === 0 && !loading">
                <td colspan="4" class="text-center text-base-content/60 py-6">暂无文件</td>
              </tr>
              <tr v-if="loading">
                <td colspan="4" class="text-center py-6">
                  <span class="loading loading-spinner"></span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </section>

    <dialog v-if="showEditModal" class="modal" open>
      <form method="dialog" class="modal-box">
        <h3 class="font-semibold text-lg">编辑文件</h3>
        <div class="mt-3 space-y-3">
          <FormField legend="文件标题" label="请输入标题">
            <input v-model="editForm.title" type="text" class="input input-bordered" required />
          </FormField>
          <FormField legend="文件类型" label="选择类别">
            <select v-model="editForm.type" class="select select-bordered">
              <option value="position_paper">立场文件</option>
              <option value="working_paper">工作文件</option>
              <option value="draft_resolution">决议草案</option>
              <option value="press_release">新闻稿</option>
              <option value="other">其他</option>
            </select>
          </FormField>
          <FormField legend="更新附件" label="可选上传新附件">
            <input type="file" class="file-input file-input-bordered w-full" @change="onEditFileChange" />
          </FormField>
          <FormField legend="描述" label="补充说明">
            <textarea v-model="editForm.description" class="textarea textarea-bordered" rows="3"></textarea>
          </FormField>
          <FormField legend="提交状态" label="草稿或提交">
            <select v-model="editForm.status" class="select select-bordered">
              <option value="draft">草稿</option>
              <option value="submitted">提交</option>
            </select>
          </FormField>
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click.prevent="closeEditModal">取消</button>
          <button type="button" class="btn btn-primary" @click.prevent="saveModal">保存</button>
        </div>
      </form>
    </dialog>

    <dialog v-if="showUploadModal" class="modal" open>
      <form method="dialog" class="modal-box max-w-lg" @submit.prevent="uploadDocument">
        <h3 class="font-semibold text-lg mb-2">上传文件</h3>
        <p class="text-sm text-base-content/60 mb-4">
          填写文件信息并上传附件，提交后即可等待主席团审批。
        </p>
        <div class="space-y-3">
          <FormField legend="文件标题" label="请输入标题">
            <input v-model="uploadForm.title" type="text" class="input input-bordered" placeholder="文件标题" required />
          </FormField>
          <FormField legend="文件类型" label="选择类别">
            <select v-model="uploadForm.type" class="select select-bordered">
              <option value="position_paper">立场文件</option>
              <option value="working_paper">工作文件</option>
              <option value="draft_resolution">决议草案</option>
              <option value="press_release">新闻稿</option>
              <option value="other">其他</option>
            </select>
          </FormField>
          <FormField legend="上传附件" label="请选择文件">
            <input type="file" class="file-input file-input-bordered w-full" required @change="handleFileChange" />
          </FormField>
          <FormField legend="描述" label="可选补充说明">
            <textarea v-model="uploadForm.description" class="textarea textarea-bordered" rows="3"></textarea>
          </FormField>
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click.prevent="closeUploadModal">取消</button>
          <button type="submit" class="btn btn-primary">确认上传</button>
        </div>
      </form>
    </dialog>
  </div>
</template>
