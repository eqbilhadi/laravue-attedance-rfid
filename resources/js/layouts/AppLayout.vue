<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3'
import { type SharedData } from '@/types'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'
import { defineProps, watch } from 'vue'

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();

watch(
  () => page.props.flash,
  flash => {
    setTimeout(() => {
      if (flash.success) {
        const success = flash.success
        toast.success(
          typeof success === "string" ? success : success.title,
          {
            description: typeof success === "string" ? undefined : success.description,
          }
        )
      }

      if (flash.error) {
        const error = flash.error
        toast.error(
          typeof error === "string" ? error : error.title,
          {
            description: typeof error === "string" ? undefined : error.description,
          }
        )
      }
    }, 5)
  },
  { immediate: true }
)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
    <Toaster position="top-right" />
  </AppLayout>
</template>
