import { createRouter, createWebHistory } from 'vue-router'

const DisplayBoard = () => import('@/views/DisplayBoard.vue')
const ManagementConsole = () => import('@/views/ManagementConsole.vue')
const MiniWindow = () => import('@/views/MiniWindow.vue')
const MotionLauncher = () => import('@/views/MotionLauncher.vue')
const FileSelect = () => import('@/views/FileSelect.vue')

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/display',
    },
    {
      path: '/display',
      name: 'display',
      component: DisplayBoard,
      meta: { title: '会议显示大屏' },
    },
    {
      path: '/management',
      name: 'management',
      component: ManagementConsole,
      meta: { title: '会议管理面板' },
    },
    {
      path: '/mini-window',
      name: 'mini-window',
      component: MiniWindow,
      meta: { title: '会场小窗口' },
    },
    {
      path: '/motion-launcher',
      name: 'motion-launcher',
      component: MotionLauncher,
      meta: { title: '动议弹窗演示' },
    },
    {
      path: '/file-select',
      name: 'file-select',
      component: FileSelect,
      meta: { title: '文件选择' },
    },
    {
      path: '/popup_delegate',
      name: 'popup_delegate',
      component: () => import('@/views/popup_delegate.vue'),
      meta: { title: '选择发言者国家' },
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

router.afterEach((to) => {
  if (to.meta?.title) {
    document.title = `${to.meta.title} · MUN 控制中心`
  }
})

export default router
