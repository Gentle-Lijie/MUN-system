<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { ref, onMounted } from 'vue'

const navItems = [
  { label: '显示大屏', to: '/display' },
  { label: '会议管理', to: '/management' },
  { label: '会场小窗口', to: '/mini-window' },
]

const drawerOpen = ref(false)
const theme = ref('light')

const toggleTheme = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light'
  document.documentElement.setAttribute('data-theme', theme.value)
}

onMounted(() => {
  document.documentElement.setAttribute('data-theme', theme.value)
})
</script>

<template>
  <div class="drawer">
    <input id="drawer-toggle" type="checkbox" class="drawer-toggle" v-model="drawerOpen" />
    <div class="drawer-content">
      <button class="btn btn-ghost rounded-3xl fixed top-4 left-4 z-10" @click="drawerOpen = !drawerOpen">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
      <main class="flex-1 overflow-hidden">
        <div class="h-full">
          <RouterView />
        </div>
      </main>
    </div>
    <div class="drawer-side">
      <label for="drawer-toggle" class="drawer-overlay"></label>
      <div class="min-h-full w-80 bg-base-100 p-4 flex flex-col">
        <div class="mb-4">
          <span class="text-xl font-bold">MUN 控制中心</span>
        </div>
        <div class="flex-1 flex justify-center items-center">
          <div class="tabs tabs-vertical">
            <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="tab" active-class="tab-active">
              {{ item.label }}
            </RouterLink>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
              <div class="w-10 rounded-full">
                <img alt="User" src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" />
              </div>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
              <li><a>Profile</a></li>
              <li><a>Settings</a></li>
              <li><a>Logout</a></li>
            </ul>
          </div>
          <button class="btn btn-ghost btn-circle" @click="toggleTheme">
            <svg v-if="theme === 'light'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
              </path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
