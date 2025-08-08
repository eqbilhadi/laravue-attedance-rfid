<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm, Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Input from "@/components/ui/input/Input.vue";
import InputError from "@/components/InputError.vue";
import Label from "@/components/ui/label/Label.vue";
import Button from "@/components/ui/button/Button.vue";
import Switch from "@/components/ui/switch/Switch.vue";
import { BreadcrumbItem, SharedData } from "@/types";
import { CircleArrowLeft, LoaderCircle } from "lucide-vue-next";
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import BaseSelect from "@/components/BaseSelect.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import Textarea from "@/components/ui/textarea/Textarea.vue";

interface UserFormData {
  name: string;
  email: string;
  username: string;
  password?: string;
  is_active: boolean;
  birthplace: string;
  birthdate: string;
  gender: string;
  phone: string;
  address: string;
  avatar?: string;
  role: string | number;
  [key: string]: string | boolean | null | undefined | number;
}

const props = defineProps<{
  user?: Partial<UserFormData> & { id?: string };
  roles?: Array<{ label: string; value: number | string }>;
}>();
console.log(props.user);

const page = usePage<SharedData>();
const isEdit = computed(() => !!props.user?.id);

const form = useForm<UserFormData>({
  name: props.user?.name ?? "",
  email: props.user?.email ?? "",
  username: props.user?.username ?? "",
  password: "", // Only for create
  is_active: props.user?.is_active ?? true,
  birthplace: props.user?.birthplace ?? "",
  birthdate: props.user?.birthdate ?? "",
  gender: props.user?.gender ?? "",
  phone: props.user?.phone ?? "",
  address: props.user?.address ?? "",
  role: props.user?.role ?? ""
});

const handleSubmit = () => {
  if (isEdit.value) {
    form.put(route("rbac.user.update", { id: props.user?.id },));
  } else {
    form.post(route("rbac.user.store"));
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
  { title: "User Management", href: route("rbac.user.index") },
  { title: isEdit.value ? "Edit User" : "Add User", href: "#" },
];
</script>
<template>
  <Head :title="isEdit ? 'Edit User' : 'Add User'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <CardTitle>{{ isEdit ? "Edit User" : "Add User" }}</CardTitle>
              <CardDescription>Fill user details in sections below</CardDescription>
            </div>
            <Link
              :href="route('rbac.user.index')"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
            >
              <CircleArrowLeft class="text-primary-foreground mr-0.5" /> Back
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 py-6">
              <!-- Left: Account Info -->
              <div class="flex flex-col gap-4 lg:col-span-1">
                <div class="grid gap-2">
                  <Label for="email">Email</Label>
                  <Input id="email" v-model="form.email" placeholder="Email address of the user" tabindex="1" />
                  <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                  <Label for="username">Username</Label>
                  <Input id="username" v-model="form.username" placeholder="Username of the user" tabindex="2" />
                  <InputError :message="form.errors.username" />
                </div>
                <div class="grid gap-2">
                  <Label for="password">Password</Label>
                  <Input id="password" v-model="form.password" type="password" :placeholder="isEdit ? 'Password for the user (leave empty to keep current)' : 'Password for the user'" tabindex="3" />
                  <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-2">
                  <Label for="password">Role</Label>
                  <BaseSelect
                    class="w-full focus-visible:!ring-0"
                    v-model="form.role"
                    :options="roles ?? []"
                    placeholder="Select Role"
                    tabindex="4"
                  />
                  <InputError :message="form.errors.role" />
                </div>
                <div class="grid gap-2">
                  <Label for="is_active">User Active</Label>
                  <div class="flex items-center justify-between h-9 w-full rounded-md border px-3 py-1 text-sm dark:bg-input/30 shadow-xs bg-transparent border-input">
                    <div class="flex items-center space-x-2">
                      <Switch v-model="form.is_active" id="is_active" tabindex="5" />
                      <Label for="is_active"> </Label>
                    </div>
                    <span class="text-muted-foreground">
                      {{ form.is_active ? "Active" : "Inactive" }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-4 lg:col-span-2">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div class="grid gap-2 col-span-2">
                    <Label for="name">Full Name</Label>
                    <Input id="name" v-model="form.name" placeholder="Full name of the user" tabindex="6" />
                    <InputError :message="form.errors.name" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="birthplace">Birthplace</Label>
                    <Input id="birthplace" v-model="form.birthplace" placeholder="Birthplace of the user" tabindex="7" />
                    <InputError :message="form.errors.birthplace" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="birthdate">Birthdate</Label>
                    <Input id="birthdate" v-model="form.birthdate" type="date" tabindex="8" />
                    <InputError :message="form.errors.birthdate" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" placeholder="08..." tabindex="9" />
                    <InputError :message="form.errors.phone" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="gender">Gender</Label>
                    <RadioGroup v-model="form.gender" class="flex items-center h-9 gap-4" id="gender" >
                      <div class="flex items-center space-x-2">
                        <RadioGroupItem id="gender_l" value="l" tabindex="10"/>
                        <Label for="gender_l">Laki-laki</Label>
                      </div>
                      <div class="flex items-center space-x-2">
                        <RadioGroupItem id="gender_p" value="p" tabindex="11"/>
                        <Label for="gender_p">Perempuan</Label>
                      </div>
                    </RadioGroup>
                    <InputError :message="form.errors.gender" />
                  </div>
                  <div class="grid gap-2 col-span-2">
                    <Label for="address">Address</Label>
                    <Textarea class="min-h-28" id="address" v-model="form.address" placeholder="Address of the user" tabindex="12" />

                    <InputError :message="form.errors.address" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 mt-6">
              <Link
                :href="route('rbac.user.index')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm transition-all border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                tabindex="13"
              >
                Cancel
              </Link>
              <Button type="submit" :disabled="form.processing" tabindex="14">
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
