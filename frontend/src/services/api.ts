export const API_BASE = 'http://localhost:8000' // Adjust to your backend URL

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

    // Files API
    async getFileSubmissions(params?: { status?: string; committeeId?: string; search?: string }): Promise<{ items: FileSubmission[]; total: number }> {
        const query = new URLSearchParams()
        if (params?.status) query.set('status', params.status)
        if (params?.committeeId) query.set('committeeId', params.committeeId)
        if (params?.search) query.set('search', params.search)

        return this.request(`/api/files/submissions?${query}`)
    }

    async submitFile(data: { title: string; type: string; description?: string; content_path: string }): Promise<FileSubmission> {
        return this.request('/api/files/submissions', {
            method: 'POST',
            body: JSON.stringify(data),
        })
    }

    async decideSubmission(submissionId: number, data: { decision: 'approved' | 'rejected'; dias_fb?: string }): Promise<FileSubmission> {
        return this.request(`/api/files/submissions/${submissionId}/decision`, {
            method: 'POST',
            body: JSON.stringify(data),
        })
    }

    async getPublishedFiles(params?: { committeeId?: string; search?: string }): Promise<{ items: FileSubmission[]; total: number }> {
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

    async updatePublishedFile(fileId: number, data: { title?: string; description?: string; visibility?: string; type?: string; status?: string; committee_id?: number; dias_fb?: string | null }): Promise<FileSubmission> {
        return this.request(`/api/files/published/${fileId}`, {
            method: 'PATCH',
            body: JSON.stringify(data),
        })
    }

    // Delegate: update own submission before approval
    async updateSubmission(submissionId: number, data: { title?: string; description?: string; content_path?: string; type?: string; status?: string }): Promise<FileSubmission> {
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

    async updateCrisis(crisisId: number, data: {
        title?: string
        content?: string
        file_path?: string | null
        responses_allowed?: boolean
        target_committees?: number[] | null
        status?: CrisisStatus
    }): Promise<Crisis> {
        return this.request(`/api/crises/${crisisId}`, {
            method: 'PATCH',
            body: JSON.stringify(data),
        })
    }

    async getCrisisResponses(crisisId: number): Promise<{ crisis: Crisis; items: CrisisResponseItem[]; total: number }> {
        return this.request(`/api/crises/${crisisId}/responses`)
    }

    async submitCrisisResponse(crisisId: number, data: {
        summary: string
        actions?: string
        resources?: string
        file_path?: string | null
    }): Promise<CrisisResponseItem> {
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
    async sendMessage(data: { channel: string; content: string }): Promise<{ success: boolean; message: string }> {
        return this.request('/api/messages/send', {
            method: 'POST',
            body: JSON.stringify(data),
        })
    }
}

export const api = new ApiService()