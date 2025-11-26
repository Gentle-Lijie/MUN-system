const envApiBase = import.meta.env?.VITE_API_BASE_URL
const browserOrigin = typeof window !== 'undefined' ? window.location.origin : ''
const normalizedBase = (
  envApiBase && envApiBase.trim().length > 0 ? envApiBase : browserOrigin
).replace(/\/$/, '')
export const API_BASE = import.meta.env.DEV ? '' : normalizedBase

export interface ApiResponse<T> {
  data?: T
  error?: string
}

export interface FileSubmission {
  id: number
  committee?: {
    id: number
    name: string
    code: string
  }
  type: string
  title: string
  description?: string
  content_path: string
  submitted_by: {
    id: number
    name: string
    organization?: string
  }
  submitted_at?: string
  status: string
  visibility: string
  dias_fb?: string
  created_at: string
  updated_at: string
}

export interface FileReference {
  id: number
  title: string
  type: string
  committee?: {
    id: number
    name: string
    code: string
  }
}

export interface Venue {
  id: number
  code: string
  name: string
  venue?: string
  status: string
  capacity: number
}

export type MessageTarget = 'everyone' | 'delegate' | 'committee' | 'dias'

export interface MessageRecord {
  id: number
  target: MessageTarget
  targetId?: number | null
  channel: string
  committee?: {
    id: number
    name: string
    code: string
  } | null
  content: string
  time: string
  sender?: {
    id: number
    name: string
    role: string
  } | null
  targetMeta?: {
    label?: string
    committeeName?: string
    committeeCode?: string
    recipientName?: string
    [key: string]: unknown
  } | null
}

export interface MessageRecipientsPayload {
  committees: Array<{ id: number; name: string; code: string }>
  delegates: Array<{
    delegateId: number
    userId: number
    name: string
    committeeId: number
    committeeName?: string | null
    country?: string | null
  }>
}

export interface MessageListResponse {
  items: MessageRecord[]
  total: number
  page: number
  pageSize: number
  recipients: MessageRecipientsPayload
  allowedTargets: MessageTarget[]
}

export type CrisisStatus = 'draft' | 'active' | 'resolved' | 'archived'

export interface CrisisResponseContent {
  summary?: string | null
  actions?: string | null
  resources?: string | null
}

export interface CrisisResponseItem {
  id: number
  crisisId: number
  user?: {
    id: number
    name: string
    role: string
  } | null
  committee?: {
    id: number
    name: string
    code: string
  } | null
  country?: string | null
  content: CrisisResponseContent
  filePath?: string | null
  createdAt?: string | null
  updatedAt?: string | null
}

export interface Crisis {
  id: number
  title: string
  content: string
  filePath?: string | null
  status: CrisisStatus
  responsesAllowed: boolean
  targetCommittees: number[] | null
  publishedAt?: string | null
  publishedBy?: {
    id: number
    name: string
  } | null
  responsesCount: number
  canRespond?: boolean
  myResponse?: CrisisResponseItem
}

export interface UserSummary {
  id: number
  name: string
  email: string
  role: string
  organization?: string | null
  committee?: string | null
  phone?: string | null
  permissions?: string[]
}

export interface LogActor {
  id: number
  name: string
  role: string
  email: string
}

export interface LogRecord {
  id: number
  action: string
  targetTable?: string | null
  targetId?: number | null
  meta?: Record<string, unknown> | null
  timestamp?: string | null
  actor?: LogActor | null
}

export interface LogListResponse {
  items: LogRecord[]
  total: number
  page: number
  pageSize: number
}

class ApiService {
  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${API_BASE}${endpoint}`
    const response = await fetch(url, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    })

    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`)
    }

    return response.json()
  }

  // Auth API
  async getProfile(): Promise<{
    id: number
    name: string
    email: string
    role: string
    organization?: string
    phone?: string
    last_login?: string
    created_at: string
    permissions: string[]
  }> {
    return this.request('/api/auth/profile')
  }
  async getFileSubmissions(params?: {
    status?: string
    committeeId?: string
    search?: string
  }): Promise<{ items: FileSubmission[]; total: number }> {
    const query = new URLSearchParams()
    if (params?.status) query.set('status', params.status)
    if (params?.committeeId) query.set('committeeId', params.committeeId)
    if (params?.search) query.set('search', params.search)

    return this.request(`/api/files/submissions?${query}`)
  }

  async submitFile(data: {
    title: string
    type: string
    description?: string
    content_path: string
  }): Promise<FileSubmission> {
    return this.request('/api/files/submissions', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async decideSubmission(
    submissionId: number,
    data: { decision: 'approved' | 'rejected'; dias_fb?: string }
  ): Promise<FileSubmission> {
    return this.request(`/api/files/submissions/${submissionId}/decision`, {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async getPublishedFiles(params?: {
    committeeId?: string
    search?: string
  }): Promise<{ items: FileSubmission[]; total: number }> {
    const query = new URLSearchParams()
    if (params?.committeeId) query.set('committeeId', params.committeeId)
    if (params?.search) query.set('search', params.search)

    return this.request(`/api/files/published?${query}`)
  }

  async publishFile(data: {
    committee_id?: number
    type: string
    title: string
    description?: string
    content_path: string
    visibility: string
  }): Promise<FileSubmission> {
    return this.request('/api/files/published', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async updatePublishedFile(
    fileId: number,
    data: {
      title?: string
      description?: string
      visibility?: string
      type?: string
      status?: string
      committee_id?: number
      dias_fb?: string | null
    }
  ): Promise<FileSubmission> {
    return this.request(`/api/files/published/${fileId}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    })
  }

  // Delegate: update own submission before approval
  async updateSubmission(
    submissionId: number,
    data: {
      title?: string
      description?: string
      content_path?: string
      type?: string
      status?: string
    }
  ): Promise<FileSubmission> {
    return this.request(`/api/files/submissions/${submissionId}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    })
  }

  async deletePublishedFile(fileId: number): Promise<void> {
    return this.request(`/api/files/published/${fileId}`, {
      method: 'DELETE',
    })
  }

  async getFileReferences(): Promise<{ items: FileReference[] }> {
    return this.request('/api/files/reference')
  }

  async uploadFile(file: File): Promise<{ fileUrl: string; filename: string }> {
    const formData = new FormData()
    formData.append('file', file)

    const response = await fetch(`${API_BASE}/api/files/upload`, {
      method: 'POST',
      credentials: 'include',
      body: formData,
    })

    if (!response.ok) {
      throw new Error(`Upload Error: ${response.status} ${response.statusText}`)
    }

    return response.json()
  }

  // Crisis API
  async getCrises(params?: { status?: string }): Promise<{ items: Crisis[]; total: number }> {
    const query = new URLSearchParams()
    if (params?.status) query.set('status', params.status)
    const suffix = query.toString() ? `?${query.toString()}` : ''
    return this.request(`/api/crises${suffix}`)
  }

  async createCrisis(data: {
    title: string
    content: string
    file_path?: string | null
    responses_allowed?: boolean
    target_committees?: number[] | null
    status?: CrisisStatus
  }): Promise<Crisis> {
    return this.request('/api/crises', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async updateCrisis(
    crisisId: number,
    data: {
      title?: string
      content?: string
      file_path?: string | null
      responses_allowed?: boolean
      target_committees?: number[] | null
      status?: CrisisStatus
    }
  ): Promise<Crisis> {
    return this.request(`/api/crises/${crisisId}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    })
  }

  async getCrisisResponses(
    crisisId: number
  ): Promise<{ crisis: Crisis; items: CrisisResponseItem[]; total: number }> {
    return this.request(`/api/crises/${crisisId}/responses`)
  }

  async submitCrisisResponse(
    crisisId: number,
    data: {
      summary: string
      file_path?: string | null
    }
  ): Promise<CrisisResponseItem> {
    return this.request(`/api/crises/${crisisId}/responses`, {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  // Venues API
  async getVenues(): Promise<{ items: Venue[]; total: number }> {
    return this.request('/api/venues')
  }

  // Messages API
  async getMessages(params?: {
    page?: number
    pageSize?: number
    target?: MessageTarget | 'all'
    committeeId?: number
    search?: string
  }): Promise<MessageListResponse> {
    const query = new URLSearchParams()
    if (params?.page) query.set('page', params.page.toString())
    if (params?.pageSize) query.set('pageSize', params.pageSize.toString())
    if (params?.committeeId) query.set('committeeId', params.committeeId.toString())
    if (params?.target && params.target !== 'all') query.set('target', params.target)
    if (params?.target === 'all') query.set('target', 'all')
    if (params?.search) query.set('search', params.search)

    const suffix = query.toString() ? `?${query}` : ''
    return this.request(`/api/messages${suffix}`)
  }

  async sendMessage(data: {
    target: MessageTarget
    content: string
    targetId?: number | null
    committeeId?: number | null
  }): Promise<{ message: MessageRecord }> {
    return this.request('/api/messages', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async getUsers(params?: {
    role?: string
    search?: string
  }): Promise<{ items: UserSummary[]; total: number }> {
    const query = new URLSearchParams()
    if (params?.role) query.set('role', params.role)
    if (params?.search) query.set('search', params.search)
    const suffix = query.toString() ? `?${query}` : ''
    return this.request(`/api/users${suffix}`)
  }

  async getLogs(params?: {
    actorId?: number
    action?: string
    table?: string
    start?: string
    end?: string
    page?: number
    pageSize?: number
  }): Promise<LogListResponse> {
    const query = new URLSearchParams()
    if (params?.actorId) query.set('actorId', params.actorId.toString())
    if (params?.action) query.set('action', params.action)
    if (params?.table) query.set('table', params.table)
    if (params?.start) query.set('start', params.start)
    if (params?.end) query.set('end', params.end)
    if (params?.page) query.set('page', params.page.toString())
    if (params?.pageSize) query.set('pageSize', params.pageSize.toString())
    const suffix = query.toString() ? `?${query}` : ''
    return this.request(`/api/logs${suffix}`)
  }

  async clearLogs(): Promise<{ deleted: number }> {
    return this.request('/api/logs', {
      method: 'DELETE',
    })
  }
}

export const api = new ApiService()
