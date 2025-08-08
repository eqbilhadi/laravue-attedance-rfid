<script setup lang="ts">
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
} from "@/components/ui/pagination";
import { computed } from "vue";

const props = defineProps<{
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  };
}>();

const emit = defineEmits<{
  (e: "change", page: number): void;
}>();

const onPageChange = (page: number) => {
  emit("change", page);
};

// generate halaman untuk tampil, bisa kamu perbaiki logika ini untuk tampilkan page dinamis
const visiblePages = computed(() => {
  const { current_page, last_page } = props.meta;
  const pages: number[] = [];

  if (last_page <= 3) {
    for (let i = 1; i <= last_page; i++) pages.push(i);
    return pages;
  }

  // Jika current_page di akhir (misal 9 atau 10 dari 10), tetap tampil 3 terakhir
  if (current_page >= last_page - 1) {
    return [last_page - 2, last_page - 1, last_page];
  }

  // Kalau current_page di awal (1 atau 2), tampil 1 2 3
  if (current_page <= 2) {
    return [1, 2, 3];
  }

  // Selebihnya tampil current ±1
  return [current_page - 1, current_page, current_page + 1];
});

const pageInfo = computed(() => {
  const { current_page, per_page, total } = props.meta;
  const from = total === 0 ? 0 : (current_page - 1) * per_page + 1;
  const to = Math.min(current_page * per_page, total);

  return `Showing ${from} to ${to} of ${total} results`;
});
</script>

<template>
  <div class="flex items-center w-full justify-between mt-0 text-sm text-gray-600">
    <span class="dark:text-muted-foreground">{{ pageInfo }}</span>
    <Pagination
      class="justify-end w-auto mx-0"
      :items-per-page="meta.per_page"
      :total="meta.total"
    >
      <PaginationContent>
        <PaginationPrevious
          :disabled="meta.current_page === 1"
          @click="onPageChange(meta.current_page - 1)"
        />

        <!-- Halaman pertama -->
        <PaginationItem
          v-if="!visiblePages.includes(1)"
          :value="1"
          @click="onPageChange(1)"
        >
          1
        </PaginationItem>

        <!-- Ellipsis jika perlu -->
        <PaginationEllipsis v-if="visiblePages[0] > 2" />

        <!-- Halaman aktif dan sekitarnya -->
        <PaginationItem
          v-for="page in visiblePages"
          :key="page"
          :value="page"
          :is-active="page === meta.current_page"
          @click="onPageChange(page)"
        >
          {{ page }}
        </PaginationItem>

        <!-- Ellipsis di akhir -->
        <PaginationEllipsis
          v-if="visiblePages[visiblePages.length - 1] < meta.last_page - 1"
        />

        <!-- Halaman terakhir -->
        <PaginationItem
          v-if="!visiblePages.includes(meta.last_page)"
          :value="meta.last_page"
          @click="onPageChange(meta.last_page)"
        >
          {{ meta.last_page }}
        </PaginationItem>

        <PaginationNext
          :disabled="meta.current_page === meta.last_page"
          @click="onPageChange(meta.current_page + 1)"
        />
      </PaginationContent>
    </Pagination>
  </div>
</template>
