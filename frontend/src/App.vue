<script setup lang="ts">
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { ref, onMounted, reactive, computed, watch } from 'vue'
import FormField from '@/components/common/FormField.vue'

type UserProfile = {
  username: string
  avatarUrl?: string
}

const navItems = [
  { label: '欢迎', to: '/welcome' },
  { label: '后台功能', to: '/backend' },
  { label: '会场小窗口', to: '/mini-window' },
]

const drawerOpen = ref(false)
const theme = ref<'light' | 'dark'>('light')
const user = ref<UserProfile | null>(null)
const isFetchingUser = ref(false)
const showLoginModal = ref(false)
const loginSubmitting = ref(false)
const loginError = ref('')
const showLoginRequiredModal = ref(false)
const showChangePasswordModal = ref(false)
const changePasswordSubmitting = ref(false)
const changePasswordError = ref('')

const route = useRoute()
const router = useRouter()
const loginForm = reactive({
  email: '',
  password: '',
})
const changePasswordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const isLoggedIn = computed(() => Boolean(user.value))
const userInitials = computed(() => user.value?.username?.slice(-2).toUpperCase() ?? '')

const getAvatarStyle = (name: string) => {
  const colors = [
    '#FF6B6B',
    '#4ECDC4',
    '#45B7D1',
    '#96CEB4',
    '#FFEAA7',
    '#DDA0DD',
    '#98D8C8',
    '#F7DC6F',
    '#BB8FCE',
    '#85C1E9',
  ]
  const hash = name.split('').reduce((a, b) => a + b.charCodeAt(0), 0)
  const colorIndex = hash % colors.length
  const backgroundColor = colors[colorIndex]
  const color = theme.value === 'light' ? '#FFFFFF' : '#000000'
  return { backgroundColor, color }
}
const needsLogin = computed(() => !isLoggedIn.value && route.path !== '/welcome')

const openLoginModal = () => {
  loginError.value = ''
  loginForm.email = ''
  loginForm.password = ''
  showLoginModal.value = true
}

const closeLoginModal = () => {
  showLoginModal.value = false
}

const openChangePasswordModal = () => {
  changePasswordError.value = ''
  changePasswordForm.currentPassword = ''
  changePasswordForm.newPassword = ''
  changePasswordForm.confirmPassword = ''
  showChangePasswordModal.value = true
}

const closeChangePasswordModal = () => {
  showChangePasswordModal.value = false
}

const fetchUserProfile = async () => {
  isFetchingUser.value = true
  try {
    const response = await fetch('/api/auth/profile', {
      credentials: 'include',
    })
    if (!response.ok) return
    const data = await response.json()
    user.value = {
      username: data?.username ?? '用户',
      avatarUrl: data?.avatarUrl,
    }
  } catch (error) {
    console.warn('无法获取用户信息：', error)
  } finally {
    isFetchingUser.value = false
  }
}

const handleLogin = async () => {
  if (!loginForm.email || !loginForm.password) {
    loginError.value = '请输入邮箱和密码'
    return
  }
  loginSubmitting.value = true
  loginError.value = ''
  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        email: loginForm.email,
        password: loginForm.password,
      }),
    })

    if (!response.ok) {
      const errData = await response.json().catch(() => ({}))
      throw new Error(errData?.message || '登录失败，请检查邮箱或密码')
    }
    const data = await response.json()
    user.value = {
      username: data?.username ?? loginForm.email,
      avatarUrl: data?.avatarUrl,
    }
    closeLoginModal()
    // 登录成功后刷新页面
    window.location.reload()
  } catch (error) {
    console.warn('登录失败：', error)
    loginError.value = error instanceof Error ? error.message : '登录失败，请稍后重试'
  } finally {
    loginSubmitting.value = false
  }
}

const handleLogout = async () => {
  try {
    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' })
  } catch (error) {
    console.warn('登出接口暂不可用：', error)
  } finally {
    user.value = null
    router.push('/welcome')
  }
}

const handleChangePassword = () => {
  openChangePasswordModal()
}

const handleChangePasswordSubmit = async () => {
  if (!changePasswordForm.currentPassword || !changePasswordForm.newPassword) {
    changePasswordError.value = '请输入当前密码和新密码'
    return
  }
  if (changePasswordForm.newPassword !== changePasswordForm.confirmPassword) {
    changePasswordError.value = '新密码和确认密码不匹配'
    return
  }
  if (changePasswordForm.newPassword.length < 6) {
    changePasswordError.value = '新密码至少需要6个字符'
    return
  }

  changePasswordSubmitting.value = true
  changePasswordError.value = ''
  try {
    const response = await fetch('/api/auth/password', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        currentPassword: changePasswordForm.currentPassword,
        newPassword: changePasswordForm.newPassword,
      }),
    })

    if (response.ok) {
      const data = await response.json()
      alert(data.message || '密码修改成功')
      closeChangePasswordModal()
    } else {
      const errorData = await response.json()
      throw new Error(errorData.message || '密码修改失败')
    }
  } catch (error) {
    console.warn('修改密码失败：', error)
    changePasswordError.value = error instanceof Error ? error.message : '修改密码失败，请重试'
  } finally {
    changePasswordSubmitting.value = false
  }
}

onMounted(() => {
  document.documentElement.setAttribute('data-theme', theme.value)
  fetchUserProfile()
})

watch(needsLogin, (newVal) => {
  showLoginRequiredModal.value = newVal
})
</script>

<template>
  <div class="drawer">
    <input id="drawer-toggle" v-model="drawerOpen" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <button
        class="btn btn-ghost rounded-3xl fixed top-4 left-4 z-10"
        @click="drawerOpen = !drawerOpen"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          ></path>
        </svg>
      </button>
      <main class="flex-1 overflow-hidden">
        <div class="h-full">
          <RouterView
            v-if="isLoggedIn || route.path.startsWith('/backend') || route.path === '/welcome'"
          />
        </div>
      </main>
    </div>
    <div class="drawer-side">
      <label for="drawer-toggle" class="drawer-overlay"></label>
      <div class="min-h-full w-80 bg-base-100 p-5 flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-6">
          <div>
            <p class="text-xl font-bold leading-tight">MUN 控制中心</p>
            <p class="text-sm text-base-content/60">模拟联合国会议后台</p>
          </div>
          <div class="flex items-center gap-2">
            <div v-if="isLoggedIn" class="dropdown dropdown-end">
              <div tabindex="0" role="button" class="avatar">
                <div
                  class="w-10 rounded-full"
                  :style="user?.avatarUrl ? {} : getAvatarStyle(user?.username || '')"
                >
                  <img v-if="user?.avatarUrl" :src="user.avatarUrl" alt="用户头像" />
                  <span
                    v-else
                    class="text-sm font-semibold flex items-center justify-center h-full w-full"
                    >{{ userInitials }}</span
                  >
                </div>
              </div>
              <ul
                tabindex="0"
                class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-48 space-y-1"
              >
                <li>
                  <button class="btn btn-ghost justify-start" @click="handleChangePassword">
                    修改密码
                  </button>
                </li>
                <li>
                  <button class="btn btn-ghost justify-start text-error" @click="handleLogout">
                    退出登录
                  </button>
                </li>
              </ul>
            </div>
            <button
              v-else
              class="btn btn-ghost btn-circle"
              aria-label="登录"
              @click="openLoginModal"
            >
              <div class="avatar placeholder">
                <div
                  class="w-10 rounded-full bg-base-200 text-base-content/60 flex justify-center items-center"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M4.5 20.25a8.25 8.25 0 0115 0"
                    />
                  </svg>
                </div>
              </div>
            </button>
          </div>
        </div>

        <div v-if="isFetchingUser" class="alert alert-info alert-soft text-sm mb-4">
          <span>正在获取用户信息...</span>
        </div>

        <nav class="flex-1">
          <ul class="menu menu-vertical gap-2">
            <li v-for="item in navItems" :key="item.to" @click="drawerOpen = false">
              <RouterLink :to="item.to" class="justify-start" active-class="active">
                {{ item.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <div class="modal" :class="{ 'modal-open': showLoginModal }">
    <div class="modal-box space-y-4">
      <h3 class="font-bold text-lg">账号登录</h3>
      <form class="space-y-4" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <FormField
            legend="登录邮箱"
            label="邮箱"
            description="请输入会议后台发放的邮箱和密码"
            fieldset-class="border border-base-300 rounded-box p-4"
          >
            <div class="input input-bordered flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              <input
                v-model="loginForm.email"
                type="email"
                class="grow bg-transparent focus:outline-none"
                required
              />
            </div>
          </FormField>
          <FormField
            legend="登录密码"
            label="密码"
            fieldset-class="border border-base-300 rounded-box p-4"
          >
            <div class="input input-bordered flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.5 10.125V6.75a4.5 4.5 0 10-9 0v3.375M18.75 10.125h-13.5A1.125 1.125 0 004.125 11.25v8.625c0 .621.504 1.125 1.125 1.125h13.5a1.125 1.125 0 001.125-1.125V11.25a1.125 1.125 0 00-1.125-1.125z"
                />
              </svg>
              <input
                v-model="loginForm.password"
                type="password"
                class="grow bg-transparent focus:outline-none"
                required
              />
            </div>
          </FormField>
        </div>
        <div v-if="loginError" class="alert alert-error alert-soft text-sm">
          <span>{{ loginError }}</span>
        </div>
        <div class="modal-action">
          <button type="button" class="btn btn-ghost min-w-[4rem]" @click="closeLoginModal">
            取消
          </button>
          <button type="submit" class="btn btn-primary min-w-[4rem]" :disabled="loginSubmitting">
            <span v-if="loginSubmitting" class="loading loading-spinner loading-sm"></span>
            <span>登录</span>
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="closeLoginModal">关闭</button>
    </form>
  </div>

  <div class="modal" :class="{ 'modal-open': showLoginRequiredModal }">
    <div class="modal-box space-y-4">
      <h3 class="font-bold text-lg">需要登录</h3>
      <p>访问此页面需要先登录您的账号。</p>
      <div class="modal-action">
        <button
          class="btn btn-primary"
          @click="
            () => {
              openLoginModal()
              showLoginRequiredModal = false
            }
          "
        >
          去登录
        </button>
      </div>
    </div>
  </div>

  <div class="modal" :class="{ 'modal-open': showChangePasswordModal }">
    <div class="modal-box space-y-4">
      <h3 class="font-bold text-lg">修改密码</h3>
      <form class="space-y-4" @submit.prevent="handleChangePasswordSubmit">
        <div class="space-y-4">
          <FormField
            legend="当前密码"
            label="输入当前密码"
            fieldset-class="border border-base-300 rounded-box p-4"
          >
            <div class="input input-bordered flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.5 10.125V6.75a4.5 4.5 0 10-9 0v3.375M18.75 10.125h-13.5A1.125 1.125 0 004.125 11.25v8.625c0 .621.504 1.125 1.125 1.125h13.5a1.125 1.125 0 001.125-1.125V11.25a1.125 1.125 0 00-1.125-1.125z"
                />
              </svg>
              <input
                v-model="changePasswordForm.currentPassword"
                type="password"
                class="grow bg-transparent focus:outline-none"
                required
              />
            </div>
          </FormField>
          <FormField
            legend="新密码"
            label="输入新密码"
            fieldset-class="border border-base-300 rounded-box p-4"
          >
            <div class="input input-bordered flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.5 10.125V6.75a4.5 4.5 0 10-9 0v3.375M18.75 10.125h-13.5A1.125 1.125 0 004.125 11.25v8.625c0 .621.504 1.125 1.125 1.125h13.5a1.125 1.125 0 001.125-1.125V11.25a1.125 1.125 0 00-1.125-1.125z"
                />
              </svg>
              <input
                v-model="changePasswordForm.newPassword"
                type="password"
                class="grow bg-transparent focus:outline-none"
                required
              />
            </div>
          </FormField>
          <FormField
            legend="确认新密码"
            label="再次输入新密码"
            description="新密码至少需要6个字符"
            fieldset-class="border border-base-300 rounded-box p-4"
          >
            <div class="input input-bordered flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.5 10.125V6.75a4.5 4.5 0 10-9 0v3.375M18.75 10.125h-13.5A1.125 1.125 0 004.125 11.25v8.625c0 .621.504 1.125 1.125 1.125h13.5a1.125 1.125 0 001.125-1.125V11.25a1.125 1.125 0 00-1.125-1.125z"
                />
              </svg>
              <input
                v-model="changePasswordForm.confirmPassword"
                type="password"
                class="grow bg-transparent focus:outline-none"
                required
              />
            </div>
          </FormField>
        </div>
        <div v-if="changePasswordError" class="alert alert-error alert-soft text-sm">
          <span>{{ changePasswordError }}</span>
        </div>
        <div class="modal-action">
          <button
            type="button"
            class="btn btn-ghost min-w-[4rem]"
            @click="closeChangePasswordModal"
          >
            取消
          </button>
          <button
            type="submit"
            class="btn btn-primary min-w-[4rem]"
            :disabled="changePasswordSubmitting"
          >
            <span v-if="changePasswordSubmitting" class="loading loading-spinner loading-sm"></span>
            <span>修改</span>
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="closeChangePasswordModal">关闭</button>
    </form>
  </div>
</template>
