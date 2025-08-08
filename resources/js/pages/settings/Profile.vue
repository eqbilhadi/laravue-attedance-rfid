<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue'
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import Textarea from "@/components/ui/textarea/Textarea.vue";
import { Images, Trash } from 'lucide-vue-next';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from '@/components/ui/tooltip'

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Profile settings',
    href: '/settings/profile',
  },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const fallbackAvatar = '/avatar-fallback/blank-avatar.png'
console.log(user);

const avatarPreview = ref<string | null>(user.avatar_url ?? fallbackAvatar);

const form = useForm({
  name: user.name,
  email: user.email,
  username: user.username,
  birthplace: user.birthplace,
  birthdate: user.birthdate,
  phone: user.phone,
  gender: user.gender,
  address: user.address,
  avatar: user.avatar as File | string | null,
});

const handleAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
};

const deleteAvatar = () => {
  avatarPreview.value = fallbackAvatar
  if (form.avatar) form.avatar = null
}

const submit = () => {
  form.post(route('profile.update'), {
    preserveScroll: true,
    forceFormData: true,
  });
};

const fileInput = ref<HTMLInputElement | null>(null)

const openFilePicker = () => {
  fileInput.value?.click()
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Profile settings" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <HeadingSmall
          title="Profile information"
          description="Update your name and email address"
        />

        <form @submit.prevent="submit" class="space-y-6">
          <div class="flex items-center justify-center flex-col">
            <div v-if="avatarPreview" class="mb-4">
              <img
                :src="avatarPreview"
                alt="Avatar preview"
                class="w-24 h-24 rounded-full object-cover"
              />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger>
                      <Button
                        size="icon"
                        variant="secondary"
                        type="button"
                        @click.prevent="openFilePicker"
                        ><Images
                      /></Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Change Avatar</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger>
                      <Button
                        size="icon"
                        variant="secondary"
                        type="button"
                        @click.prevent="deleteAvatar"
                        ><Trash
                      /></Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Delete Avatar</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <input
                  ref="fileInput"
                  id="avatar"
                  type="file"
                  class="hidden"
                  accept="image/*"
                  @change="handleAvatarChange"
                />
              </div>
            </div>
            <InputError class="mt-2" :message="form.errors.avatar" />
          </div>
          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
              id="name"
              class="mt-1 block w-full"
              v-model="form.name"
              autocomplete="name"
              placeholder="Full name"
            />
            <InputError class="mt-2" :message="form.errors.name" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="email">Email address</Label>
              <Input
                id="email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.email"
                placeholder="Email address"
              />
              <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div class="grid gap-2">
              <Label for="username">Username</Label>
              <Input
                id="username"
                class="mt-1 block w-full"
                v-model="form.username"
                placeholder="Username"
              />
              <InputError class="mt-2" :message="form.errors.username" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="birthplace">Birthplace</Label>
              <Input
                id="birthplace"
                class="mt-1 block w-full"
                v-model="form.birthplace"
                placeholder="Birthplace"
              />
              <InputError class="mt-2" :message="form.errors.birthplace" />
            </div>
            <div class="grid gap-2">
              <Label for="birthdate">Birthdate</Label>
              <Input
                id="birthdate"
                type="date"
                class="mt-1 block w-full"
                v-model="form.birthdate"
                autocomplete="birthdate"
              />
              <InputError class="mt-2" :message="form.errors.birthdate" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="phone">Phone</Label>
              <Input id="phone" v-model="form.phone" placeholder="08..." tabindex="9" />
              <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-2">
              <Label for="gender">Gender</Label>
              <RadioGroup
                v-model="form.gender"
                class="flex items-center h-9 gap-4"
                id="gender"
              >
                <div class="flex items-center space-x-2">
                  <RadioGroupItem id="gender_l" value="l" tabindex="10" />
                  <Label for="gender_l">Laki-laki</Label>
                </div>
                <div class="flex items-center space-x-2">
                  <RadioGroupItem id="gender_p" value="p" tabindex="11" />
                  <Label for="gender_p">Perempuan</Label>
                </div>
              </RadioGroup>
              <InputError :message="form.errors.gender" />
            </div>
          </div>

          <div class="grid gap-2 col-span-2">
            <Label for="address">Address</Label>
            <Textarea id="address" v-model="form.address" placeholder="Address of the user" tabindex="12" />
            <InputError :message="form.errors.address" />
          </div>

          <div v-if="mustVerifyEmail && !user.email_verified_at">
            <p class="-mt-4 text-sm text-muted-foreground">
              Your email address is unverified.
              <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
              >
                Click here to resend the verification email.
              </Link>
            </p>

            <div
              v-if="status === 'verification-link-sent'"
              class="mt-2 text-sm font-medium text-green-600"
            >
              A new verification link has been sent to your email address.
            </div>
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="form.processing">Save</Button>

            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                Saved.
              </p>
            </Transition>
          </div>
        </form>
      </div>

      <DeleteUser />
    </SettingsLayout>
  </AppLayout>
</template>
