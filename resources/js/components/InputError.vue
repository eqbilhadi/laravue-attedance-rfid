<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-xs -mt-1 text-red-600 dark:text-red-500">
            {{ message }}
        </p>
    </div>
</template>
