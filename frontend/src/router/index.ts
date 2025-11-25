import { createRouter, createWebHistory } from 'vue-router'

const DisplayBoard = () => import('@/views/DisplayBoard.vue')
const MiniWindow = () => import('@/views/MiniWindow.vue')
const MotionLauncher = () => import('@/views/MotionLauncher.vue')
const FileSelect = () => import('@/views/FileSelect.vue')
const WelcomeView = () => import('@/views/WelcomeView.vue')
const BackendWorkspace = () => import('@/views/backend/BackendWorkspace.vue')
const UserManagementView = () => import('@/views/backend/UserManagementView.vue')
const VenueManagementView = () => import('@/views/backend/VenueManagementView.vue')
const DelegateManagementView = () => import('@/views/backend/DelegateManagementView.vue')
const LogManagementView = () => import('@/views/backend/LogManagementView.vue')
const FileApprovalView = () => import('@/views/backend/FileApprovalView.vue')
const FileManagementView = () => import('@/views/backend/FileManagementView.vue')
const CrisisManagementView = () => import('@/views/backend/CrisisManagementView.vue')
const TimelineManagementView = () => import('@/views/backend/TimelineManagementView.vue')
const MessageManagementView = () => import('@/views/backend/MessageManagementView.vue')
const DelegateProfileView = () => import('@/views/backend/DelegateProfileView.vue')
const DelegateDocumentsView = () => import('@/views/backend/DelegateDocumentsView.vue')
const DelegateMessagesView = () => import('@/views/backend/DelegateMessagesView.vue')
const DelegateCrisisResponseView = () => import('@/views/backend/DelegateCrisisResponseView.vue')
const TestPopupDelegate = () => import('@/views/TestPopupDelegate.vue')

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/welcome',
    },
    {
      path: '/welcome',
      name: 'welcome',
      component: WelcomeView,
      meta: { title: '欢迎' },
    },
    {
      path: '/display',
      redirect: '/welcome', // 或者重定向到选择committee的页面
    },
    {
      path: '/display/:committeeId',
      name: 'display',
      component: DisplayBoard,
      meta: { title: '会议显示大屏' },
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
      path: '/test-popup',
      name: 'test-popup',
      component: TestPopupDelegate,
      meta: { title: '测试 PopupDelegate' },
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
    {
      path: '/backend',
      component: BackendWorkspace,
      children: [
        {
          path: '',
          redirect: '/backend/users',
        },
        {
          path: 'users',
          name: 'backend-users',
          component: UserManagementView,
          meta: { title: '用户管理' },
        },
        {
          path: 'venues',
          name: 'backend-venues',
          component: VenueManagementView,
          meta: { title: '会场管理' },
        },
        {
          path: 'delegates',
          name: 'backend-delegates',
          component: DelegateManagementView,
          meta: { title: '代表管理' },
        },
        {
          path: 'logs',
          name: 'backend-logs',
          component: LogManagementView,
          meta: { title: '日志管理' },
        },
        {
          path: 'presidium/file-approval',
          name: 'backend-presidium-file-approval',
          component: FileApprovalView,
          meta: { title: '文件审批' },
        },
        {
          path: 'presidium/file-management',
          name: 'backend-presidium-file-management',
          component: FileManagementView,
          meta: { title: '文件管理' },
        },
        {
          path: 'presidium/crisis',
          name: 'backend-presidium-crisis',
          component: CrisisManagementView,
          meta: { title: '危机管理' },
        },
        {
          path: 'presidium/timeline',
          name: 'backend-presidium-timeline',
          component: TimelineManagementView,
          meta: { title: '时间轴管理' },
        },
        {
          path: 'presidium/messages',
          name: 'backend-presidium-messages',
          component: MessageManagementView,
          meta: { title: '消息管理' },
        },
        {
          path: 'delegate/profile',
          name: 'backend-delegate-profile',
          component: DelegateProfileView,
          meta: { title: '个人面板' },
        },
        {
          path: 'delegate/documents',
          name: 'backend-delegate-documents',
          component: DelegateDocumentsView,
          meta: { title: '文件中心' },
        },
        {
          path: 'delegate/messages',
          name: 'backend-delegate-messages',
          component: DelegateMessagesView,
          meta: { title: '消息中心' },
        },
        {
          path: 'delegate/crisis-response',
          name: 'backend-delegate-crisis-response',
          component: DelegateCrisisResponseView,
          meta: { title: '危机响应' },
        },
      ],
      meta: { title: '后台功能矩阵' },
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
