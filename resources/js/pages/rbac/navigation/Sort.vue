<script setup lang="ts">
import { router, Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import * as icons from 'lucide-vue-next';
import { CircleArrowLeft, Grip, LoaderCircle } from 'lucide-vue-next'
import { resolveDynamicComponent, shallowRef, ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable'
import Button from '@/components/ui/button/Button.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import draggable from 'vuedraggable'


// Types
interface Lists {
  id: number
  name: string
  icon: keyof typeof icons
  sort: number
  is_divider: boolean
  children?: Lists[]
}

type MenuOrder = {
  id: number
  sort_num: number
  children?: MenuOrder[]
}

const props = defineProps<{
  lists: Lists[]
}>()

const menuList = ref(ensureChildrenProp(props.lists))

function toPascalCase(str: string): string {
  return str.replace(/(^\w|-\w)/g, s => s.replace('-', '').toUpperCase())
}

function getSafeIcon(name: string) {
  const pascal = toPascalCase(name) as keyof typeof icons;
  return resolveDynamicComponent(icons[pascal] ?? icons.FileLock);
}

function mapNestedOrder(items: Lists[]): MenuOrder[] {
  return items.map((item, index) => ({
    id: item.id,
    sort_num: index + 1,
    children: item.children ? mapNestedOrder(item.children) : [],
  }))
}

function ensureChildrenProp(items: Lists[]): Lists[] {
  return items.map(item => ({
    ...item,
    children: item.children ? ensureChildrenProp(item.children) : []
  }));
}

const form = useForm<{
  newOrder: MenuOrder[]
}>({
  newOrder: [],
})

const submitNewOrder = () => {
  form.newOrder = mapNestedOrder(menuList.value)
  form.post(route("rbac.nav.sort-update"))
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Access Settings', href: route('rbac.nav.sort') },
  { title: 'Navigation Management', href: route('rbac.nav.index') },
  { title: 'Sort Navigation', href: "" }
]
</script>

<template>
  <Head title="Sort Navigation" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>Sort Navigation</CardTitle>
              <CardDescription>
                Reorder the navigation items by dragging and dropping them.
              </CardDescription>
            </div>
            <Link
              :href="route('rbac.nav.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="text-primary-foreground mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <draggable
            v-model="menuList"
            item-key="id"
            group="nested"
            handle=".drag-handle"
            animation="200"
          >
            <template #item="{ element }">
              <div class="p-2 border rounded mb-2 bg-white text-sm font-medium items-center pb-0 dark:bg-neutral-950">
                <div class="flex items-center gap-2">
                  <span class="drag-handle mr-10 hover:text-accent-foreground dark:hover:bg-accent/50 text-muted-foreground hover:bg-transparent"><Grip class="w-5 h-5" /></span>
                  <span class="flex items-center gap-2">
                    <template v-if="!element.is_divider">
                      <component :is="getSafeIcon(element.icon)" class="w-4 h-4" />
                    </template>
                    {{ element.name }}
                  </span>
                </div>

                <!-- Nested level -->
                <draggable
                  v-if="element.children"
                  v-model="element.children"
                  item-key="id"
                  group="nested"
                  handle=".drag-handle"
                  animation="200"
                  class="ml-10 mt-2"
                >
                  <template #item="{ element: child }">
                    <div class="p-2 border rounded mb-2 bg-gray-100 flex items-center gap-2 text-sm font-medium dark:bg-neutral-900">
                      <span class="drag-handle mr-10 hover:text-accent-foreground dark:hover:bg-accent/50 text-muted-foreground hover:bg-transparent"><Grip class="w-5 h-5" /></span>
                      <span class="flex items-center gap-2">
                        <template v-if="!child.is_divider">
                          <component :is="getSafeIcon(child.icon)" class="w-4 h-4" />
                        </template>
                        {{ child.name }}
                      </span>
                    </div>
                  </template>
                </draggable>
              </div>
            </template>
          </draggable>
          <div class="flex items-center justify-end gap-3 mt-6">
            <Link
              :href="route('rbac.nav.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 h-9 px-4 py-2 has-[>svg]:px-3"
              tabindex="9"
            >
              Cancel
            </Link>
            <Button :disabled="form.processing" @click="submitNewOrder">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
              Save Order
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
