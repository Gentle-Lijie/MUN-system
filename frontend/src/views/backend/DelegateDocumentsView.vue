<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { api, type FileSubmission, API_BASE } from '@/services/api'

const documents = ref<FileSubmission[]>([])
const loading = ref(false)

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

const fetchDocuments = async () => {
  loading.value = true
  try {
    // For delegate view, we might need to filter by current user
    // Assuming the API returns only user's submissions when called by delegate
    const response = await api.getFileSubmissions()
    documents.value = response.items
  } catch (error) {
    console.error('Failed to fetch documents:', error)
  } finally {
    loading.value = false
  }
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
    Object.assign(uploadForm, {
      title: '',
      type: 'position_paper',
      description: '',
      file: null,
    })
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
  const finalized = ['approved', 'published', 'rejected'].includes(doc.status)
  if (finalized) {
    window.alert('已定稿，无法编辑')
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

onMounted(fetchDocuments)
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">选手文件中心</h2>
      <p class="text-sm text-base-content/70">用于代表上传、查看文件审核状态。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
      <div class="space-y-0">
        <div v-if="loading" class="flex justify-center">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <div class="overflow-x-auto border border-base-200 rounded-2xl">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>标题</th>
                <th>类型</th>
                <th>操作</th>
                <th>状态</th>
                <th>更新时间</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td>
                  <div class="flex items-center gap-2">
                    <span>{{ doc.title }}</span>
                    <a :href="`${API_BASE}${doc.content_path}`" target="_blank" class="btn btn-xs btn-outline">查看</a>
                    <button v-if="doc.dias_fb" class="btn btn-xs btn-ghost"
                      @click.prevent="showDiasFeedback(doc)">查看审批意见</button>
                  </div>
                </td>
                <td><span class="badge badge-outline">{{ doc.type }}</span></td>
                <td class="w-24">
                  <button class="btn btn-xs btn-secondary"
                    :class="{ 'opacity-60': doc.status === 'approved' || doc.status === 'published' || doc.status === 'rejected' }"
                    :title="(doc.status === 'approved' || doc.status === 'published' || doc.status === 'rejected') ? '已定稿，不可编辑' : '编辑'"
                    @click.prevent="onEditClick(doc)">编辑</button>
                </td>
                <td>
                  <span class="badge" :class="{
                    'badge-success': doc.status === 'approved',
                    'badge-primary': doc.status === 'published',
                    'badge-info': doc.status === 'submitted',
                    'badge-warning': (doc.status === 'draft' || !doc.status),
                    'badge-error': doc.status === 'rejected'
                  }">
                    {{ doc.status === 'approved' ? '已通过' : doc.status === 'published' ? '已发布' : doc.status ===
                    'submitted' ? '待审批' : (doc.status === 'draft' || !doc.status) ? '草稿' : '已驳回' }}
                  </span>
                </td>
                <td>{{ doc.updated_at ? new Date(doc.updated_at).toLocaleString() : '' }}</td>
              </tr>
              <tr v-if="documents.length === 0 && !loading">
                <td colspan="5" class="text-center text-base-content/60">暂无文件</td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- 编辑弹窗 -->
        <dialog v-if="showEditModal" class="modal" open>
          <form method="dialog" class="modal-box">
            <h3 class="font-semibold text-lg">编辑文件</h3>
            <div class="mt-3 space-y-3">
              <label class="form-control">
                <span class="label-text">文件标题</span>
                <input v-model="editForm.title" type="text" class="input input-bordered" required />
              </label>
              <label class="form-control">
                <span class="label-text">类型</span>
                <select v-model="editForm.type" class="select select-bordered">
                  <option value="position_paper">立场文件</option>
                  <option value="working_paper">工作文件</option>
                  <option value="draft_resolution">决议草案</option>
                  <option value="press_release">新闻稿</option>
                  <option value="other">其他</option>
                </select>
              </label>
              <label class="form-control">
                <span class="label-text">上传新附件（可选）</span>
                <input type="file" class="file-input file-input-bordered w-full" @change="onEditFileChange" />
              </label>
              <label class="form-control">
                <span class="label-text">描述</span>
                <textarea v-model="editForm.description" class="textarea textarea-bordered" rows="3"></textarea>
              </label>
              <label class="form-control">
                <span class="label-text">状态</span>
                <select v-model="editForm.status" class="select select-bordered">
                  <option value="draft">草稿</option>
                  <option value="submitted">提交</option>
                </select>
              </label>
            </div>
            <div class="modal-action">
              <button type="button" class="btn" @click.prevent="closeEditModal">取消</button>
              <button type="button" class="btn btn-primary" @click.prevent="saveModal">保存</button>
            </div>
          </form>
        </dialog>
      </div>

      <!-- 编辑表单 -->
      <div v-if="editingDocId !== null && !showEditModal" class="border border-base-200 rounded-2xl p-4 space-y-3">
        <h3 class="font-semibold">编辑文件</h3>
        <label class="form-control">
          <span class="label-text">文件标题</span>
          <input v-model="editForm.title" type="text" class="input input-bordered" placeholder="文件标题" required />
        </label>
        <label class="form-control">
          <span class="label-text">类型</span>
          <select v-model="editForm.type" class="select select-bordered">
            <option value="position_paper">立场文件</option>
            <option value="working_paper">工作文件</option>
            <option value="draft_resolution">决议草案</option>
            <option value="press_release">新闻稿</option>
            <option value="other">其他</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">上传新附件（可选）</span>
          <input type="file" class="file-input file-input-bordered w-full" @change="onEditFileChange" />
        </label>
        <label class="form-control">
          <span class="label-text">描述</span>
          <textarea v-model="editForm.description" class="textarea textarea-bordered" rows="3"></textarea>
        </label>
        <label class="form-control">
          <span class="label-text">状态</span>
          <select v-model="editForm.status" class="select select-bordered">
            <option value="draft">草稿</option>
            <option value="submitted">提交</option>
          </select>
        </label>
        <div class="flex gap-2">
          <button class="btn btn-primary flex-1" @click.prevent="submitEditDocument">保存更改</button>
          <button class="btn btn-outline" @click.prevent="cancelEditDocument">取消</button>
        </div>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="uploadDocument">
        <h3 class="font-semibold">上传文件</h3>
        <label class="form-control">
          <span class="label-text">文件标题</span>
          <input v-model="uploadForm.title" type="text" class="input input-bordered" placeholder="文件标题" required />
        </label>
        <label class="form-control">
          <span class="label-text">类型</span>
          <select v-model="uploadForm.type" class="select select-bordered">
            <option value="position_paper">立场文件</option>
            <option value="working_paper">工作文件</option>
            <option value="draft_resolution">决议草案</option>
            <option value="press_release">新闻稿</option>
            <option value="other">其他</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">上传附件</span>
          <input type="file" class="file-input file-input-bordered w-full" @change="handleFileChange" required />
        </label>
        <label class="form-control">
          <span class="label-text">描述</span>
          <textarea v-model="uploadForm.description" class="textarea textarea-bordered" rows="3"></textarea>
        </label>
        <button type="submit" class="btn btn-primary w-full">上传文件</button>
      </form>
    </section>
  </div>
</template>
