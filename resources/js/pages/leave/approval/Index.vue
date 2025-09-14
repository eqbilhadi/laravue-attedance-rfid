<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { Pagination } from '@/types/pagination'
import type { LeaveRequest, BreadcrumbItem } from '@/types'
import { format, differenceInBusinessDays } from 'date-fns'
import 'vue-sonner/style.css'
import { useInitials } from '@/composables/useInitials'

import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import Button from '@/components/ui/button/Button.vue'
import PaginationWrapper from '@/components/Pagination.vue'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import Label from '@/components/ui/label/Label.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import InputError from '@/components/InputError.vue'
import { Check, X, LoaderCircle } from 'lucide-vue-next'

const props = defineProps<{
  data: Pagination<LeaveRequest>
}>()

const { getInitials } = useInitials()

// --- State untuk Dialog ---
const isDialogOpen = ref(false)
const selectedRequest = ref<LeaveRequest | null>(null)
const approvalAction = ref<'approve' | 'reject' | null>(null)

const form = useForm({
  action: '' as 'approve' | 'reject',
  rejection_reason: '',
})

// --- Fungsi ---
function openDialog(request: LeaveRequest, action: 'approve' | 'reject') {
  selectedRequest.value = request;
  approvalAction.value = action;
  form.reset();
  isDialogOpen.value = true;
}

function submitApproval() {
  if (!selectedRequest.value) return;
  form.action = approvalAction.value!;
  
  form.put(route('leave.approval.update', { leaveRequest: selectedRequest.value.id }), {
    onSuccess: () => {
      isDialogOpen.value = false;
    },
    preserveScroll: true,
  });
}

function getFilteredData(page?: number) {
  const query: Record<string, any> = {};
  if (page) query.page = page;

  router.get(route("leave.approval.index"), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const onPageChange = (page: number) => getFilteredData(page);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leave Management', href: '' },
  { title: 'Leave Approval', href: route('leave.approval.index') },
]
</script>

<template>
  <Head title="Leave Approval" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <Card class="gap-2">
        <CardHeader>
            <CardTitle>Leave Approval Queue</CardTitle>
            <CardDescription>
                Review and process pending leave requests from employees.
            </CardDescription>
        </CardHeader>
        <CardContent>
          <!-- Table -->
          <div
            class="overflow-hidden rounded-md border border-gray-200 mb-3 dark:border-zinc-800 mt-3"
          >
            <Table>
              <TableHeader class="bg-gray-100 text-left text-gray-700 dark:bg-zinc-800">
                <TableRow>
                  <TableHead class="dark:text-foreground">Employee</TableHead>
                  <TableHead class="dark:text-foreground">Leave Type</TableHead>
                  <TableHead class="dark:text-foreground">Date Range</TableHead>
                  <TableHead class="dark:text-foreground">Total Days</TableHead>
                  <TableHead class="dark:text-foreground">Reason</TableHead>
                  <TableHead class="text-right dark:text-foreground">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="data.data.length > 0">
                  <TableRow v-for="request in data.data" :key="request.id">
                    <TableCell>
                        <div class="flex items-center gap-3">
                            <Avatar class="h-9 w-9">
                                <AvatarImage :src="request.user.avatar_url ?? ''" :alt="request.user.name" />
                                <AvatarFallback>{{ getInitials(request.user.name) }}</AvatarFallback>
                            </Avatar>
                            <div>
                                <p class="font-medium text-sm">{{ request.user.name }}</p>
                            </div>
                        </div>
                    </TableCell>
                    <TableCell>{{ request.leave_type.name }}</TableCell>
                    <TableCell>
                        {{ format(new Date(request.start_date), 'dd MMM yyyy') }} - {{ format(new Date(request.end_date), 'dd MMM yyyy') }}
                    </TableCell>
                     <TableCell>
                        {{ differenceInBusinessDays(new Date(request.end_date), new Date(request.start_date)) + 1 }} day(s)
                    </TableCell>
                    <TableCell class="max-w-xs truncate">{{ request.reason }}</TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <Button variant="outline" size="icon" @click="openDialog(request, 'reject')" class="text-destructive hover:bg-destructive/10 hover:text-destructive">
                                <X class="w-4 h-4" />
                            </Button>
                            <Button size="icon" variant="outline" @click="openDialog(request, 'approve')">
                                <Check class="w-4 h-4" />
                            </Button>
                        </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="100%" class="text-center text-muted-foreground">
                      No pending leave requests.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
        <CardFooter v-if="data.data.length > 0">
           <PaginationWrapper :meta="data" @change="onPageChange" />
        </CardFooter>
      </Card>
    </div>

    <!-- Dialog untuk Aksi Approval/Reject -->
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="capitalize">{{ approvalAction }} Request</DialogTitle>
                <DialogDescription v-if="selectedRequest">
                    You are about to {{ approvalAction }} a leave request for <strong>{{ selectedRequest.user.name }}</strong> from <strong>{{ format(new Date(selectedRequest.start_date), 'dd MMM yyyy') }}</strong> to <strong>{{ format(new Date(selectedRequest.end_date), 'dd MMM yyyy') }}</strong>.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitApproval" class="space-y-4">
                <div v-if="approvalAction === 'reject'" class="grid gap-2">
                    <Label for="rejection_reason">Reason for Rejection (Required)</Label>
                    <Textarea
                        id="rejection_reason"
                        v-model="form.rejection_reason"
                        placeholder="Provide a clear reason for rejecting this request..."
                        :class="{ 'border-destructive': form.errors.rejection_reason }"
                    />
                    <InputError :message="form.errors.rejection_reason" />
                </div>
                <div v-else class="font-base text-sm">
                  <div class="relative w-full rounded-lg border px-4 py-3 text-sm [&amp;&gt;svg+div]:translate-y-[-3px] [&amp;&gt;svg]:absolute [&amp;&gt;svg]:left-4 [&amp;&gt;svg]:top-4 [&amp;&gt;svg]:text-foreground [&amp;&gt;svg~*]:pl-7 bg-background text-foreground" role="alert" bis_skin_checked="1">
                    <h5 class="mb-1 font-medium leading-none tracking-tight">Confirmation</h5>
                    <div class="text-sm [&amp;_p]:leading-relaxed" bis_skin_checked="1"> 
                      Please confirm to approve this request. This action cannot be undone.
                    </div>
                  </div>
                </div>
                 <DialogFooter class="!mt-6">
                    <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing" :variant="approvalAction === 'reject' ? 'destructive' : 'default'">
                        <LoaderCircle v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        Confirm {{ approvalAction === 'approve' ? 'Approval' : 'Rejection' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
  </AppLayout>
</template>
