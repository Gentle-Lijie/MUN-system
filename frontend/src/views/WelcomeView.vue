<script setup lang="ts">
// Welcome page component
</script>

<template>
    <div class="hero min-h-screen bg-base-200">
        <div class="hero-content text-center">
            <div class="max-w-md">
                <h1 class="text-5xl font-bold">欢迎来到 MUN 控制中心</h1>
                <p class="py-6">
                    这里是模拟联合国会议的后台管理系统。您可以在这里管理用户、会场、代表、文件、危机等各种功能。
                </p>
                <div class="flex gap-4 justify-center">
                    <router-link to="/backend" class="btn btn-primary">进入后台</router-link>
                    <router-link to="/display" class="btn btn-outline">查看大屏</router-link>
                </div>
            </div>
        </div>
    </div>
</template>