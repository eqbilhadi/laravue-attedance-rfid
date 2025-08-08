<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Input from "@/components/ui/input/Input.vue";
import InputError from "@/components/InputError.vue";
import Label from "@/components/ui/label/Label.vue";
import Button from "@/components/ui/button/Button.vue";
import { BreadcrumbItem, SharedData } from "@/types";
import { CircleArrowLeft, LoaderCircle } from "lucide-vue-next";
import PermissionSelector from "@/components/rbac/PermissionSelector.vue";
import MenuSelector from "@/components/rbac/MenuSelector.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface RoleFormData {
  name: string;
  color: string;
  permissions: number[];
  menus: number[];
  [key: string]: string | boolean | string[] | number[];
}

interface NavigationItem {
  id: number;
  label_name: string;
  link: string;
  parent_id?: number | null;
  children?: NavigationItem[];
}

const props = defineProps<{
  role?: Partial<RoleFormData> & { id: string };
  color?: string;
  permissions?: { id: number; name: string; group: string }[];
  navigations?: NavigationItem[];
}>();

const page = usePage<SharedData>();
const isEdit = computed(() => !!props.role?.id);

const form = useForm<RoleFormData>({
  name: props.role?.name ?? "",
  color: props.role?.color ?? "",
  permissions: props.role?.permissions ?? [],
  menus: props.role?.menus ?? [],
});
console.log(form.menus);

const handleSubmit = () => {
  if (isEdit.value) {
    form.put(route("rbac.role.update", { id: props.role?.id }));
  } else {
    form.post(route("rbac.role.store"));
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
  { title: "Role Management", href: route("rbac.role.index") },
  { title: isEdit.value ? "Edit Role" : "Add Role", href: "#" },
];
</script>

<template>
  <Head :title="isEdit ? 'Edit Role' : 'Add Role'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>{{ isEdit ? "Edit Role" : "Add Role" }}</CardTitle>
              <CardDescription>{{
                isEdit ? "Form edit role" : "Form add role"
              }}</CardDescription>
            </div>
            <Link
              :href="route('rbac.role.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="text-primary-foreground mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 mt-4 max-w-4xl mx-auto">
              <!-- Role name -->
              <div class="grid gap-2">
                <Label for="name">Role name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="Label of the role"
                  tabindex="1"
                  autofocus
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Label name -->
              <div class="grid gap-2">
                <Label for="color">Color Role</Label>
                <Input
                  id="color"
                  v-model="form.color"
                  placeholder="Use color from tailwindcss color"
                  tabindex="2"
                />
                <InputError :message="form.errors.color" />
              </div>
            </div>
            <!-- Permissions & Menus Table -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mt-5">
              <PermissionSelector
                :permissions="props.permissions ?? []"
                v-model="form.permissions"
                :error="form.errors.permissions"
              />

              <MenuSelector
                :menus="props.navigations ?? []"
                v-model="form.menus"
                :error="form.errors.menus"
              />
            </div>

            <!-- Button -->
            <div class="flex items-center justify-end gap-3 mt-6">
              <Link
                :href="route('rbac.role.index')"
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
