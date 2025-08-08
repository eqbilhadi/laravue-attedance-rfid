<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { ref, defineExpose } from "vue";

const open = ref(false);
const itemLabel = ref("");
let onConfirmCallback: (() => void) | null = null;

function show(label: string, onConfirm: () => void) {
  itemLabel.value = label;
  onConfirmCallback = onConfirm;
  open.value = true;
}

function confirm() {
  onConfirmCallback?.();
  open.value = false;
}

defineExpose({ show });
</script>

<template>
  <AlertDialog :open="open" @update:open="(val) => (open = val)">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Are you sure you want to delete?</AlertDialogTitle>
        <AlertDialogDescription>
          This data will be permanently deleted. This action cannot be undone.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel @click="open = false">Cancel</AlertDialogCancel>
        <AlertDialogAction @click="confirm">Delete</AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
