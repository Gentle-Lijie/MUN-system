<script setup lang="ts">
import { reactive } from 'vue'
import FormField from '@/components/common/FormField.vue'

const profile = reactive({
  name: '王雨',
  country: '中国',
  committee: '安理会',
  bloc: 'P5',
  email: 'delegates@mun.org',
  phone: '+86 139****7788',
  attendance: '已签到',
  notes: '关注网络安全议题，擅长即兴演讲。',
})

const updateProfile = () => {
  // 此处仅为占位，后续可接入 API
  window.alert('已保存至临时状态，接入后端后写入数据库。')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">选手个人面板</h2>
      <p class="text-sm text-base-content/70">查看并更新代表的个人信息与会场状态。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="updateProfile">
        <h3 class="font-semibold">基础信息</h3>
        <FormField legend="姓名" label="代表姓名">
          <input v-model="profile.name" type="text" class="input input-bordered" />
        </FormField>
        <FormField legend="代表国家" label="所属国家">
          <input v-model="profile.country" type="text" class="input input-bordered" />
        </FormField>
        <div class="grid md:grid-cols-2 gap-3">
          <FormField legend="所属会场" label="填写会场">
            <input v-model="profile.committee" type="text" class="input input-bordered" />
          </FormField>
          <FormField legend="集团" label="如 P5/G77">
            <input v-model="profile.bloc" type="text" class="input input-bordered" />
          </FormField>
        </div>
        <div class="grid md:grid-cols-2 gap-3">
          <FormField legend="邮箱" label="联系方式">
            <input v-model="profile.email" type="email" class="input input-bordered" />
          </FormField>
          <FormField legend="电话" label="手机或座机">
            <input v-model="profile.phone" type="text" class="input input-bordered" />
          </FormField>
        </div>
        <FormField legend="出勤状态" label="选择当前状态">
          <select v-model="profile.attendance" class="select select-bordered">
            <option value="已签到">已签到</option>
            <option value="迟到">迟到</option>
            <option value="缺席">缺席</option>
          </select>
        </FormField>
        <FormField legend="备注" label="可选备注">
          <textarea v-model="profile.notes" class="textarea textarea-bordered" rows="4"></textarea>
        </FormField>
        <button class="btn btn-primary w-full" type="submit">保存更改</button>
      </form>

      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">会场状态</h3>
          <div class="stats shadow-none">
            <div class="stat">
              <div class="stat-title">发言排队</div>
              <div class="stat-value text-primary">#3</div>
            </div>
            <div class="stat">
              <div class="stat-title">文件提交</div>
              <div class="stat-value text-secondary">2 份</div>
            </div>
            <div class="stat">
              <div class="stat-title">危机反馈</div>
              <div class="stat-value text-accent">1 次</div>
            </div>
          </div>
        </div>

        <div class="border border-base-200 rounded-2xl p-4 space-y-3">
          <h3 class="font-semibold">操作快捷键</h3>
          <div class="join join-vertical">
            <button class="btn btn-ghost join-item">推送会场通知</button>
            <button class="btn btn-ghost join-item">发送私聊消息</button>
            <button class="btn btn-ghost join-item">重置账户密码</button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
