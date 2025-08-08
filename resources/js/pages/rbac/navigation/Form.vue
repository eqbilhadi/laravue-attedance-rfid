<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, useForm, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Input from "@/components/ui/input/Input.vue";
import InputError from "@/components/InputError.vue";
import Label from "@/components/ui/label/Label.vue";
import Button from "@/components/ui/button/Button.vue";
import { BreadcrumbItem, SharedData } from "@/types";
import { CircleArrowLeft, LoaderCircle } from "lucide-vue-next";
import Switch from "@/components/ui/switch/Switch.vue";

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface MenuFormData {
  label_name: string;
  controller_name: string;
  route_name: string;
  url: string;
  icon: string;
  is_divider: boolean;
  is_active: boolean;
  [key: string]: string | boolean;
}

const props = defineProps<{
  menu?: Partial<MenuFormData> & { id: string };
}>();

const page = usePage<SharedData>();
const isEdit = computed(() => !!props.menu?.id);

const form = useForm<MenuFormData>({
  label_name: props.menu?.label_name ?? "",
  controller_name: props.menu?.controller_name ?? "",
  route_name: props.menu?.route_name ?? "",
  url: props.menu?.url ?? "",
  icon: props.menu?.icon ?? "",
  is_divider: props.menu?.is_divider ?? false,
  is_active: props.menu?.is_active ?? true,
});

const handleSubmit = () => {
  if (isEdit.value) {
    form.put(route("rbac.nav.update", { id: props.menu?.id }));
  } else {
    form.post(route("rbac.nav.store"));
  }
};

watch(
  () => page.props.flash,
  (flash) => {
    if (flash.success) form.reset();
  }
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Access Settings", href: "" },
  { title: "Navigation Management", href: route("rbac.nav.index") },
  { title: isEdit.value ? "Edit Navigation" : "Add Navigation", href: "#" },
];
</script>

<template>
  <Head :title="isEdit ? 'Edit Navigation' : 'Add Navigation'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>{{ isEdit ? "Edit Navigation" : "Add Navigation" }}</CardTitle>
              <CardDescription>{{
                isEdit ? "Form edit navigation" : "Form add navigation"
              }}</CardDescription>
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
          <form @submit.prevent="handleSubmit" class="space-y-4 max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 mt-4">
              <!-- Label name -->
              <div class="grid gap-2">
                <Label for="label_name">Label name</Label>
                <Input
                  id="label_name"
                  v-model="form.label_name"
                  placeholder="Label of the menu"
                  tabindex="1"
                  autofocus
                />
                <InputError :message="form.errors.label_name" />
              </div>

              <!-- Label name -->
              <div class="grid gap-2">
                <Label for="icon">Icon Menu</Label>
                <Input
                  id="icon"
                  v-model="form.icon"
                  placeholder="Use icon from lucide icons https://lucide.dev/icons/"
                  tabindex="2"
                />
                <InputError :message="form.errors.icon" />
              </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 mt-4">
              <!-- Controller name -->
              <div class="grid gap-2">
                <Label for="controller_name">Controller</Label>
                <Input
                  id="controller_name"
                  v-model="form.controller_name"
                  placeholder="Controller of the menu"
                  tabindex="3"
                />
                <InputError :message="form.errors.controller_name" />
              </div>

              <!-- Route name -->
              <div class="grid gap-2">
                <Label for="route_name">Route</Label>
                <Input
                  id="route_name"
                  v-model="form.route_name"
                  placeholder="Route name of the menu"
                  tabindex="4"
                />
                <InputError :message="form.errors.route_name" />
              </div>

              <!-- URL -->
              <div class="grid gap-2">
                <Label for="url">URL</Label>
                <Input id="url" v-model="form.url" placeholder="URL of the menu" tabindex="5" />
                <InputError :message="form.errors.url" />
              </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 mt-4">
              <div class="flex items-center justify-between border rounded-md px-4 dark:bg-input/30 py-2">
                <div class="flex items-center space-x-2">
                  <Switch v-model="form.is_divider" id="is_divider" tabindex="6" />
                  <Label for="is_divider" class="text-sm font-medium">Divider Menu</Label>
                </div>
                <span class="text-sm text-muted-foreground">
                  {{ form.is_divider ? "Enabled" : "Disabled" }}
                </span>
              </div>

              <div class="flex items-center justify-between border rounded-md px-4 dark:bg-input/30 py-2">
                <div class="flex items-center space-x-2">
                  <Switch v-model="form.is_active" id="is_active" tabindex="7" />
                  <Label for="is_active" class="text-sm font-medium">Active Menu</Label>
                </div>
                <span class="text-sm text-muted-foreground">
                  {{ form.is_active ? "Active" : "Inactive" }}
                </span>
              </div>
            </div>

            <!-- Button -->
            <div class="flex items-center justify-end gap-3 mt-6">
              <Link
                :href="route('rbac.nav.index')"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 h-9 px-4 py-2 has-[>svg]:px-3"
                tabindex="9"
              >
                Cancel
              </Link>
              <Button type="submit" :disabled="form.processing" tabindex="8">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ isEdit ? "Update" : "Save" }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
