
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
  { label: '平均部署', value: '〈2h', detail: '一键脚本即可上线' },
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
    description: '危机推送、代表回应、主席团追踪形成闭环，附带模板和附件管理。',
    bullets: ['定向推送', '响应统计', '附件同步'],
  },
]

const workflowSteps = [
  {
    step: '01',
    title: '配置与筹备',
    desc: '导入会场、角色与议题模板，设置主席团权限与显示主题，一键生成演示环境。',
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
    icon: '🎤',
    title: '大型辩论赛事',
    desc: '实时投票 + 屏幕展示 + 数据归档，提升舞台表现力。',
  },
  {
    icon: '🏛️',
    title: '模拟联合国大会',
    desc: '主席团、代表、观摩席在同一系统内协作，减少沟通链路。',
  },
  {
    icon: '💼',
    title: '企业 / 高校决策会',
    desc: '结构化动议与投票，支持合规要求的日志留痕。',
  },
]

const comparisonPlaceholders = [
  {
    title: '竞品 A · 定位备注',
    hint: '可在此记录竞品定位、目标人群与主要功能缺口。',
  },
  {
    title: '竞品 B · 能力对比',
    hint: '补充 2-3 条 bullet，强调 MUN 控制中心的差异化优势。',
  },
  {
    title: '竞品 C · 市场反馈',
    hint: '记录客户对竞品的常见抱怨或竞对策略，方便销售复用。',
  },
]

const ctaStats = [
  { label: '首月上线大会', value: '12 场' },
  { label: '平均节省沟通', value: '40% 时间' },
  { label: '满意度', value: '4.9 / 5' },
  { label: '开源贡献者', value: '26 位+' },
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
          <button
            v-for="link in navLinks"
            :key="link.target"
            class="text-base-content/70 transition hover:text-primary"
            @click="scrollToSection(link.target)"
          >
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
            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-primary">welcome</p>
            <h1 class="text-4xl font-bold leading-tight md:text-5xl">
              更贴合会务流程的<span class="text-primary">实时协同操作系统</span>
            </h1>
            <p class="text-lg opacity-80">
              从主席团调度、代表互动到大屏展示，一站式完成。我们提炼 Motion Vote 式的清晰叙事结构，并加入 MUN 场景所需的后台矩阵与危机闭环。
            </p>
            <div class="flex flex-wrap gap-4">
              <router-link to="/backend" class="btn btn-primary btn-lg">立即开始</router-link>
              <button class="btn btn-outline btn-lg" @click="onDisplayClick">打开显示大屏</button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div v-for="item in heroStats" :key="item.label" class="card border border-base-200 bg-base-100/80 shadow-sm">
                <div class="card-body p-5">
                  <p class="text-sm uppercase tracking-widest text-base-content/60">{{ item.label }}</p>
                  <p class="text-3xl font-semibold">{{ item.value }}</p>
                  <p class="text-sm text-base-content/70">{{ item.detail }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="relative">
            <div class="pointer-events-none absolute inset-0 -translate-y-6 translate-x-6 bg-gradient-to-br from-primary/40 via-secondary/30 to-accent/20 opacity-60 blur-3xl"></div>
            <div class="relative space-y-4">
              <div class="card glass border border-primary/20 bg-base-100/80 shadow-xl">
                <div class="card-body">
                  <h3 class="card-title text-base">实时控制台示意</h3>
                  <p class="text-sm opacity-80">
                    结合动议、发言、危机与文书进展，向干系人直观展示。可自由替换为真实截图。
                  </p>
                  <div class="stats shadow">
                    <div class="stat">
                      <div class="stat-title">待审核动议</div>
                      <div class="stat-value text-primary">4</div>
                      <div class="stat-desc">平均 1m 内处理</div>
                    </div>
                    <div class="stat">
                      <div class="stat-title">危机响应率</div>
                      <div class="stat-value">96%</div>
                      <div class="stat-desc">含附件追踪</div>
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
                      <span>危机回执</span>
                      <span class="text-success">12/12</span>
                    </li>
                    <li class="flex items-center justify-between py-2">
                      <span>文件审批</span>
                      <span class="text-warning">待处理 3</span>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="card border border-dashed border-base-300 bg-base-100/90">
                <div class="card-body text-sm">
                  <p class="font-semibold">提示</p>
                  <p class="opacity-70">
                    可以将此区域替换为产品截图、视频或交互动效，呼应 Motion Vote 的视觉节奏。
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          v-if="showScrollHint"
          class="absolute bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2 text-sm text-base-content/70"
        >
          <span class="h-8 w-px bg-base-content/30"></span>
          向下滚动探索更多
          <span class="h-8 w-px bg-base-content/30"></span>
        </div>
      </section>

      <section id="features" class="px-6">
        <div class="mx-auto max-w-6xl space-y-8">
          <div class="text-center space-y-4">
            <p class="badge badge-outline badge-primary">FEATURES</p>
            <h2 class="text-3xl font-semibold md:text-4xl">核心能力矩阵</h2>
            <p class="text-base-content/70">
              延续 Motion Vote 的卡片分区语言，同时突显 MUN 专属的后台治理需求。
            </p>
          </div>
          <div class="grid gap-6 md:grid-cols-2">
            <article v-for="item in featureList" :key="item.title" class="card border border-base-200 bg-base-100 shadow">
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
            <p class="text-base-content/70">参考 Motion Vote 的“3 steps”叙事，结合 MUN 实际操作细节。</p>
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
            <p class="text-base-content/70">无论是辩论赛、模拟联合国还是企业内部决策会，都能快速适配。</p>
          </div>
          <div class="grid gap-6 lg:grid-cols-3">
            <article v-for="card in scenarioCards" :key="card.title" class="card border border-base-200 bg-base-100 shadow-sm">
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
            <h2 class="text-3xl font-semibold">竞品对照留白区</h2>
            <p class="text-base-content/70">模仿 Motion Vote 的“additional links”区块，预留给你描述竞品差异与优势的内容。</p>
          </div>
          <div class="grid gap-6 md:grid-cols-3">
            <article v-for="block in comparisonPlaceholders" :key="block.title" class="card border border-dashed border-primary/40 bg-base-100/90">
              <div class="card-body space-y-3">
                <div class="flex items-center gap-2">
                  <span class="badge badge-primary badge-outline">Reserve</span>
                  <h3 class="font-semibold">{{ block.title }}</h3>
                </div>
                <p class="text-sm opacity-70">{{ block.hint }}</p>
                <div class="textarea textarea-bordered min-h-[140px] text-sm opacity-60">
                  在这里补充具体内容……
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
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="item in ctaStats" :key="item.label" class="card bg-primary-content/10">
              <div class="card-body">
                <p class="text-2xl font-bold">{{ item.value }}</p>
                <p class="text-sm opacity-80">{{ item.label }}</p>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap justify-center gap-4">
            <router-link to="/backend" class="btn btn-secondary">开启后台巡航</router-link>
            <a href="mailto:organizer@mun.com" class="btn btn-outline">预约演示</a>
          </div>
        </div>
      </section>
    </main>

    <button
      v-show="showBackToTop"
      class="btn btn-primary btn-circle fixed bottom-6 right-6 shadow-lg"
      aria-label="返回顶部"
      @click="scrollToTop"
    >
      ↑
    </button>
  </div>
</template>
