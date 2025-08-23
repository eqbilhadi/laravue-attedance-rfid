<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { User, LeaveType, BreadcrumbItem, SharedData, LeaveRequest } from '@/types'
import { format } from 'date-fns'
import { useInitials } from '@/composables/useInitials'

import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'
import Button from '@/components/ui/button/Button.vue'
import BaseSelect from '@/components/BaseSelect.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import UserSearchCombobox from '@/components/SearchableCombobox.vue'
import { CircleArrowLeft, LoaderCircle } from 'lucide-vue-next'

const props = defineProps<{
  request?: LeaveRequest // Prop untuk data saat mode edit
  canCreateForOthers: boolean
  leaveTypes: LeaveType[]
}>()

const page = usePage<SharedData>()
const { getInitials } = useInitials()
const isEditMode = computed(() => !!props.request)

const selectedUserForDisplay = ref<User | null>(
    isEditMode.value ? props.request!.user : (props.canCreateForOthers ? null : page.props.auth.user as User)
)

const form = useForm({
    user_id: props.request?.user_id ?? (props.canCreateForOthers ? null : page.props.auth.user.id),
    leave_type_id: props.request?.leave_type_id ?? null,
    start_date: props.request?.start_date ? new Date(props.request.start_date) : null,
    end_date: props.request?.end_date ? new Date(props.request.end_date) : null,
    reason: props.request?.reason ?? '',
}).transform((data) => ({
    ...data,
    start_date: data.start_date ? format(data.start_date, 'yyyy-MM-dd') : null,
    end_date: data.end_date ? format(data.end_date, 'yyyy-MM-dd') : null,
}));

const dateRangeModel = computed({
  get() {
    return {
      start: form.start_date,
      end: form.end_date,
    };
  },
  set(val: { start: Date | null, end: Date | null }) {
    form.start_date = val.start;
    form.end_date = val.end;
  },
});

function submit() {
    if (isEditMode.value) {
        form.put(route('leave.request.update', { request: props.request!.id }));
    } else {
        form.post(route('leave.request.store'));
    }
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leave Management', href: '' },
  { title: 'Leave Request', href: route('leave.request.index') },
  { title: isEditMode.value ? 'Edit' : 'Create', href: '' }
]
</script>

<template>
  <Head :title="isEditMode ? 'Edit Leave Request' : 'New Leave Request'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <Card class="max-w-2xl mx-auto">
        <CardHeader>
            <div class="flex items-start justify-between">
                <div class="flex flex-col gap-1">
                    <CardTitle>{{ isEditMode ? 'Edit Leave Request' : 'New Leave Request' }}</CardTitle>
                    <CardDescription>
                        Fill out the form to submit or update a leave request.
                    </CardDescription>
                </div>
                 <Link
                    :href="route('leave.request.index')"
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 has-[>svg]:px-3"
                    >
                    <CircleArrowLeft class="mr-0.5" /> Back
                </Link>
            </div>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="submit" class="space-y-6 mt-4">
                <div class="grid gap-2">
                    <Label for="user">User</Label>
                    
                    <UserSearchCombobox
                        v-if="canCreateForOthers"
                        id="user"
                        v-model="form.user_id"
                        :initial-item="request?.user"
                        @update:selected-object="(user) => selectedUserForDisplay = user as User"
                        search-endpoint="/rbac/user-search"
                        placeholder="user"
                        :class="{ 'border-destructive': form.errors.user_id }"
                        :disabled="isEditMode"
                    >
                        <template #trigger="{ item }">
                            <span v-if="item" class="flex items-center gap-2">
                                <Avatar class="h-6 w-6"><AvatarImage :src="(item as User).avatar_url ?? ''" /><AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback></Avatar>
                                {{ item.name }}
                            </span>
                            <span v-else>Select user</span>
                        </template>
                        <template #item="{ item }">
                            <div class="flex items-center gap-2">
                                <Avatar class="h-6 w-6"><AvatarImage :src="(item as User).avatar_url ?? ''" /><AvatarFallback>{{ getInitials(item.name) }}</AvatarFallback></Avatar>
                                <span>{{ item.name }}</span>
                            </div>
                        </template>
                    </UserSearchCombobox>
                    
                    <div v-else-if="selectedUserForDisplay" class="p-3 flex items-center gap-4 border rounded-md bg-muted/50">
                        <Avatar class="h-12 w-12">
                            <AvatarImage :src="selectedUserForDisplay.avatar_url ?? ''" :alt="selectedUserForDisplay.name" />
                            <AvatarFallback>{{ getInitials(selectedUserForDisplay.name) }}</AvatarFallback>
                        </Avatar>
                        <div>
                            <p class="font-semibold text-card-foreground">{{ selectedUserForDisplay.name }}</p>
                            <p class="text-sm text-muted-foreground">Creating a request for yourself.</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.user_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="leave_type">Leave Type</Label>
                    <BaseSelect
                        id="leave_type"
                        v-model="form.leave_type_id"
                        :options="leaveTypes.map(lt => ({ label: lt.name, value: lt.id }))"
                        placeholder="Select leave type"
                        :class="{ 'border-destructive': form.errors.leave_type_id }"
                    />
                    <InputError :message="form.errors.leave_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="date_range">Date Range</Label>
                    <DateRangePicker id="date_range" v-model="dateRangeModel" />
                    <InputError :message="form.errors.start_date || form.errors.end_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="reason">Reason</Label>
                    <Textarea id="reason" v-model="form.reason" placeholder="Please provide a reason for your leave..." :class="{ 'border-destructive': form.errors.reason }" />
                    <InputError :message="form.errors.reason" />
                </div>

                 <CardFooter class="flex justify-end gap-2 px-0 !mt-8">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        {{ isEditMode ? 'Update Request' : 'Submit Request' }}
                    </Button>
                </CardFooter>
            </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
