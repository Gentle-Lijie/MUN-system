<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

const navLinks = [
  { label: '功能亮点', target: 'features' },
  { label: '会议流程', target: 'workflow' },
  { label: '适用场景', target: 'scenarios' },
  { label: '竞品对照', target: 'comparison' },
]

const heroStats = [
  { label: '覆盖委员会', value: '32+', detail: '多会场并行调度' },
  { label: '实时在线', value: '2,100+', detail: '主席团与代表' },
  { label: '累计动议', value: '18k+', detail: '自动归档记录' },
  { label: '平均部署', value: '30s', detail: '一键脚本即可上线' },
]

const featureList = [
  {
    icon: '📡',
    title: '实时显示 · 屏幕协同',
    description: '动议、发言队列、投票结果与危机状态实时上传，支持主屏 + 小窗同步浏览。',
    bullets: ['WebSocket 推送', '多主题切换', '演示安全锁定'],
  },
  {
    icon: '🗂️',
    title: '后台矩阵 · 全角色治理',
    description: '用户、会场、代表、文件、危机六大模块集中式权限管理，CSV 批量导入导出。',
    bullets: ['粒度权限', '会场模板', '批处理工具'],
  },
  {
    icon: '📝',
    title: '文书流转 · 审批闭环',
    description: '代表端提交、主席团批注、发布/引用一次完成，保留多版本反馈。',
    bullets: ['富文本批注', '引用管理', '可追溯日志'],
  },
  {
    icon: '⚡',
    title: '危机广播 · 快速响应',
    description: '支持会场时间轴动态配置，按需调整模拟时间流速\n危机推送、代表回应、主席团追踪形成闭环，附带模板和附件管理。',
    bullets: ['定向推送', '响应统计', '附件同步'],
  },
]

const workflowSteps = [
  {
    step: '01',
    title: '配置与筹备',
    desc: '导入会场、角色与会期，设置主席团权限与显示主题，提供丰富的配置变量。',
  },
  {
    step: '02',
    title: '会中调度',
    desc: '实时管理动议、点名、危机与发言序列；显示端全自动同步，后台提供审批流。',
  },
  {
    step: '03',
    title: '复盘沉淀',
    desc: '导出日志、动议、文件与危机响应，快速生成复盘材料与对外宣传素材。',
  },
]

const scenarioCards = [
  {
    icon: '🌐',
    title: '网络模联赛事',
    desc: '支持多时区、多会场的线上模拟联合国大会，简化沟通与调度流程。',
  },
  {
    icon: '🏛️',
    title: '模拟联合国大会',
    desc: '主席团、代表、观摩席在同一系统内协作，减少沟通链路。',
  },
  {
    icon: '💼',
    title: '学校模联社团活动',
    desc: '提供更详细的会后复盘功能，为学校模联社团提供支持。',
  },
]

const comparisonPlaceholders = [
  {
    title: 'MUNCS (Express/Nyarlathotep) 系统',
    hint: '全部本地化处理，提供极弱的复盘与数据导出能力。',
    content: '数据云端化，同时本地进行备份，在断网时仍可以使用。\n联网状态下数据操作实时写入数据库，更有效的应对突发情况。\n提供 CSV 批量导入导出功能，方便大会前期配置与会后数据整理。\n',
  },
  {
    title: 'MAUAC Routine 系统',
    hint: '单网页设计，不支持数据导入导出，缺乏多会场支持。',
    content: '支持多会场、多角色的权限管理，提供 CSV 批量导入导出功能，方便大会前期配置与会后数据沉淀。\n支持多会场并行调度，主席团可在同一后台管理多个会场的动议、文件与危机。\n提供更丰富的配置选项，满足不同规模与需求的模拟联合国大会',
  },
  {
    title: 'MAUAC Linkage 系统',
    hint: '服务已不可用，根据公开信息推测功能仅包含针对线上开设的会议支持跨会场危机同步',
    content: '支持多会场并行调度，主席团可在同一后台管理多个会场的动议、文件与危机。\n危机模块支持跨会场同步与定向推送，主席团可根据需要灵活调整危机内容与接收对象。\n支持同一系统管理所有相关会议，提供单一系统入口的会议支持。',
  },
]


const showScrollHint = ref(true)
const showBackToTop = ref(false)

const handleScroll = () => {
  const y = window.scrollY
  showScrollHint.value = y < window.innerHeight * 0.35
  showBackToTop.value = y > window.innerHeight * 0.6
}

function scrollToSection(id: string) {
  const el = document.getElementById(id)
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function onDisplayClick() {
  window.alert('请主席团从后台打开大屏')
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="min-h-screen bg-base-100 text-base-content">
    <header class="sticky top-0 z-30 border-b border-base-200 bg-base-100/90 backdrop-blur">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-2 text-lg font-semibold">
          <span class="h-2 w-2 rounded-full bg-primary"></span>
          MUN 控制中心
        </div>
        <nav class="hidden gap-6 text-sm font-medium md:flex">
          <button v-for="link in navLinks" :key="link.target" class="text-base-content/70 transition hover:text-primary"
            @click="scrollToSection(link.target)">
            {{ link.label }}
          </button>
        </nav>
        <router-link to="/backend" class="btn btn-primary btn-sm">进入后台</router-link>
      </div>
    </header>

    <main class="flex flex-col gap-24 lg:gap-32">
      <section id="hero" class="relative overflow-hidden bg-gradient-to-b from-primary/15 via-base-100 to-base-100">
        <div class="absolute inset-0 bg-gradient-to-br from-base-100 via-primary/10 to-base-100 opacity-80"></div>
        <div class="relative mx-auto grid max-w-6xl gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
          <div class="space-y-8">
            <!-- <p class="text-xs font-semibold uppercase tracking-[0.4em] text-primary">welcome</p> -->
            <h1 class="text-4xl font-bold leading-15xl">
              更贴合会务流程的<br /><span class="text-primary">实时模拟联合国会场系统<br />MUN-System</span>
            </h1>
            <p class="text-lg opacity-80">
              从主席团调度、代表互动、文件反馈、危机联动到大屏展示，一站式完成。
            </p>
            <div class="flex flex-wrap gap-4">
              <router-link to="/backend" class="btn btn-primary btn-lg">进入后台</router-link>
              <button class="btn btn-outline btn-lg" @click="onDisplayClick">打开显示大屏</button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div v-for="item in heroStats" :key="item.label"
                class="card border border-base-200 bg-base-100/80 shadow-sm">
                <div class="card-body p-5">
                  <p class="text-sm uppercase tracking-widest text-base-content/60">{{ item.label }}</p>
                  <p class="text-3xl font-semibold">{{ item.value }}</p>
                  <p class="text-sm text-base-content/70">{{ item.detail }}</p>
                </div>
              </div>
            </div>
            <p>根据后台压力测试结果得出相关数据。</p>
          </div>
          <div class="relative">
            <div
              class="pointer-events-none absolute inset-0 -translate-y-6 translate-x-6 bg-gradient-to-br from-primary/40 via-secondary/30 to-accent/20 opacity-60 blur-3xl" />
            <div class="relative space-y-4">
              <div class="card glass border border-primary/20 bg-base-100/80 shadow-xl">
                <div class="card-body">
                  <h3 class="card-title text-base">实时控制台示意</h3>
                  <p class="text-sm opacity-80">
                    结合动议、发言、危机与文书进展，向干系人直观展示。可自由替换为真实截图。
                  </p>
                  <div class="stats shadow">
                    <div class="stat">
                      <div class="stat-title">待审核文件</div>
                      <div class="stat-value text-primary">4</div>
                      <div class="stat-desc"></div>
                    </div>
                    <div class="stat">
                      <div class="stat-title">近期未读page条</div>
                      <div class="stat-value text-secondary">13</div>
                      <div class="stat-desc">含附件</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card border border-base-200 bg-base-100/90 shadow-lg">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <h4 class="font-semibold">队列快照</h4>
                    <span class="badge badge-primary">LIVE</span>
                  </div>
                  <ul class="divide-y divide-base-200 text-sm">
                    <li class="flex items-center justify-between py-2">
                      <span>主发言名单</span>
                      <span class="text-primary">进行中</span>
                    </li>
                    <li class="flex items-center justify-between py-2">
                      <span>当前会期</span>
                      <span class="text-success">第一会期</span>
                    </li>
                    <li class="flex items-center justify-between py-2">
                      <span>文件审批</span>
                      <span class="text-warning">待处理 4</span>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="card border border-dashed border-base-300 bg-base-100/90">
                <div class="card-body text-sm">
                  <iframe
                    src="//player.bilibili.com/player.html?isOutside=true&aid=115641742792592&bvid=BV1Q9SzBpEDf&cid=34405420814&p=1"
                    scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"></iframe>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="showScrollHint"
          class="absolute bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2 text-sm text-base-content/70">
          <span class="h-8 w-px bg-base-content/30"></span>
          向下滚动探索更多
          <span class="h-8 w-px bg-base-content/30"></span>
        </div>
      </section>

      <section id="features" class="px-6">
        <div class="mx-auto max-w-6xl space-y-8">
          <div class="text-center space-y-4">
            <p class="badge badge-outline badge-primary">FEATURES</p>
            <h2 class="text-3xl font-semibold md:text-4xl">核心功能矩阵</h2>
            <p class="text-base-content/70">
              提供从会前配置、会中调度到会后复盘的全流程支持，覆盖主席团与代表端的多样需求。
            </p>
          </div>
          <div class="grid gap-6 md:grid-cols-2">
            <article v-for="item in featureList" :key="item.title"
              class="card border border-base-200 bg-base-100 shadow">
              <div class="card-body space-y-4">
                <div class="flex items-center gap-3">
                  <span class="text-3xl">{{ item.icon }}</span>
                  <h3 class="text-xl font-semibold">{{ item.title }}</h3>
                </div>
                <p class="text-sm opacity-80">{{ item.description }}</p>
                <div class="flex flex-wrap gap-2">
                  <span v-for="bullet in item.bullets" :key="bullet" class="badge badge-outline">{{ bullet }}</span>
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="workflow" class="bg-base-200 px-6">
        <div class="mx-auto flex max-w-6xl flex-col gap-10 py-16">
          <div class="text-center space-y-4">
            <p class="badge badge-outline">WORKFLOW</p>
            <h2 class="text-3xl font-semibold">三步完成一场 MUN 活动</h2>
            <!-- <p class="text-base-content/70">系统支持一键部署在个人服务器</p> -->
          </div>
          <div class="grid gap-6 lg:grid-cols-3">
            <article v-for="step in workflowSteps" :key="step.step" class="card bg-base-100 shadow">
              <div class="card-body space-y-3">
                <span class="text-sm font-mono text-primary">{{ step.step }}</span>
                <h3 class="text-xl font-semibold">{{ step.title }}</h3>
                <p class="text-sm opacity-80">{{ step.desc }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="scenarios" class="px-6">
        <div class="mx-auto flex max-w-6xl flex-col gap-8">
          <div class="text-center space-y-4">
            <p class="badge badge-outline">USE CASES</p>
            <h2 class="text-3xl font-semibold">适用场景</h2>
            <p class="text-base-content/70">支持不同规模不同形式的模拟联合国大会</p>
          </div>
          <div class="grid gap-6 lg:grid-cols-3">
            <article v-for="card in scenarioCards" :key="card.title"
              class="card border border-base-200 bg-base-100 shadow-sm">
              <div class="card-body space-y-3">
                <div class="text-3xl">{{ card.icon }}</div>
                <h3 class="text-xl font-semibold">{{ card.title }}</h3>
                <p class="text-sm opacity-80">{{ card.desc }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="comparison" class="bg-base-200 px-6">
        <div class="mx-auto max-w-5xl space-y-8 py-16">
          <div class="text-center space-y-4">
            <p class="badge badge-outline">BENCHMARK</p>
            <h2 class="text-3xl font-semibold">竞品对照</h2>
            <p class="text-base-content/70">市场上常用的模拟联合国系统与 MUN-System 相比……</p>
          </div>
          <div class="grid gap-6 md:grid-cols-3">
            <article v-for="block in comparisonPlaceholders" :key="block.title"
              class="card border border-dashed border-primary/40 bg-base-100/90">
              <div class="card-body space-y-3">
                <div class="flex items-center gap-2">
                  <span class="badge badge-primary badge-outline">Reserve</span>
                  <h3 class="font-semibold">{{ block.title }}</h3>
                </div>
                <p class="text-sm opacity-70">{{ block.hint }}</p>
                <div class="textarea textarea-bordered min-h-[200px] text-sm opacity-60 whitespace-pre-wrap">
                  {{ block.content }}
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="cta" class="relative overflow-hidden rounded-t-[2rem] bg-primary text-primary-content">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-secondary/60 to-accent/40 opacity-50"></div>
        <div class="relative mx-auto flex max-w-6xl flex-col items-center gap-8 px-6 py-20 text-center">
          <p class="badge badge-outline badge-primary-content">READY?</p>
          <h2 class="text-4xl font-bold">现在就把大会交给 MUN 控制中心</h2>
          <p class="max-w-3xl text-lg opacity-90">
            沿袭 Motion Vote 的现代视觉语言，加入更丰富的后台治理能力。下一步只需进入后台或联系我们，即可开始试用。
          </p>
          <!-- <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="item in ctaStats" :key="item.label" class="card bg-primary-content/10">
              <div class="card-body">
                <p class="text-2xl font-bold">{{ item.value }}</p>
                <p class="text-sm opacity-80">{{ item.label }}</p>
              </div>
            </div>
          </div> -->
          <div class="flex flex-wrap justify-center gap-4">
            <router-link to="/backend" class="btn btn-warning">进入后台</router-link>
            <a href="mailto:3442242644@qq.com" class="btn btn-warning">预约演示</a>
          </div>
        </div>
      </section>
    </main>

    <button v-show="showBackToTop" class="btn btn-primary btn-circle fixed bottom-6 right-6 shadow-lg" aria-label="返回顶部"
      @click="scrollToTop">
      ↑
    </button>
  </div>
</template>
