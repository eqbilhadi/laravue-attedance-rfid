<script setup lang="ts">
import { ref, computed } from "vue";
import { CheckIcon, Minus } from "lucide-vue-next";
import CheckboxGroup from "@/components/rbac/CheckboxGroup.vue";
import { ScrollArea } from "@/components/ui/scroll-area";
import Button from "../ui/button/Button.vue";
import {
  Table,
  TableHeader,
  TableHead,
  TableRow,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import InputError from "@/components/InputError.vue";

const props = defineProps<{
  permissions: { id: number; name: string; group: string }[];
  modelValue: number[];
  error?: string;
}>();

const emit = defineEmits(["update:modelValue"]);

const permissionSearch = ref("");

const groupedPermissions = computed(() => {
  const sorted = [...props.permissions].sort((a, b) => a.group.localeCompare(b.group));
  return sorted.reduce((acc, p) => {
    const group = p.group ?? "Ungrouped";
    if (!acc[group]) acc[group] = [];
    acc[group].push(p);
    return acc;
  }, {} as Record<string, typeof props.permissions>);
});

const filteredGroupedPermissions = computed(() => {
  const term = permissionSearch.value.toLowerCase();
  const result: Record<string, typeof props.permissions> = {};
  for (const [group, items] of Object.entries(groupedPermissions.value)) {
    const filtered = items.filter((p) => p.name.toLowerCase().includes(term));
    if (filtered.length) result[group] = filtered;
  }
  return result;
});

const isGroupChecked = (group: string) => {
  const groupItems = groupedPermissions.value[group] ?? [];
  return groupItems.every((p) => props.modelValue.includes(p.id));
};

const isGroupIndeterminate = (group: string) => {
  const groupItems = groupedPermissions.value[group] ?? [];
  const checkedCount = groupItems.filter((p) => props.modelValue.includes(p.id)).length;
  return checkedCount > 0 && checkedCount < groupItems.length;
};

const toggleGroup = (group: string, checked: boolean) => {
  const ids = (groupedPermissions.value[group] ?? []).map((p) => p.id);
  emit(
    "update:modelValue",
    checked
      ? [...new Set([...props.modelValue, ...ids])]
      : props.modelValue.filter((id) => !ids.includes(id))
  );
};

const togglePermission = (id: number) => {
  emit(
    "update:modelValue",
    props.modelValue.includes(id)
      ? props.modelValue.filter((i) => i !== id)
      : [...props.modelValue, id]
  );
};

const toggleAll = () => {
  const allIds = Object.values(filteredGroupedPermissions.value)
    .flat()
    .map((p) => p.id);

  const isAllSelected = allIds.every((id) => props.modelValue.includes(id));

  emit(
    "update:modelValue",
    isAllSelected
      ? props.modelValue.filter((id) => !allIds.includes(id)) // uncheck all
      : [...new Set([...props.modelValue, ...allIds])] // check all
  );
};

const allVisibleSelected = computed(() => {
  const allIds = Object.values(filteredGroupedPermissions.value)
    .flat()
    .map((p) => p.id);
  return allIds.every((id) => props.modelValue.includes(id));
});
</script>

<template>
  <div class="w-full rounded-md border p-4">
    <div class="mb-2">
      <h3 class="text-sm font-semibold">Role Permissions</h3>
      <p class="text-sm text-muted-foreground">
        Choose what this role is allowed to do in the system.
      </p>
    </div>
    <div class="flex justify-start mb-3 gap-2">
      <input
        v-model="permissionSearch"
        placeholder="Search permissions..."
        class="px-2 py-1 text-sm border rounded-md w-full"
      />
      <Button @click="toggleAll" class="text-sm h-8" type="button">
        {{ allVisibleSelected ? "Unselect All" : "Select All" }}
      </Button>
    </div>
    <ScrollArea class="h-[300px] mb-4">
      <div v-for="(items, group) in filteredGroupedPermissions" :key="group" class="mb-1">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>
                <label
                  class="flex items-center gap-2 cursor-pointer select-none justify-between"
                >
                  <span class="font-bold text-sm text-foreground">{{ group }}</span>
                  <input
                    type="checkbox"
                    class="peer hidden"
                    :checked="isGroupChecked(group)"
                    @change="e => toggleGroup(group, (e.target as HTMLInputElement).checked)"
                  />
                  <div
                    class="h-4 w-4 shrink-0 rounded-sm border border-primary shadow flex items-center justify-center transition-colors me-8"
                    :class="{
                      'bg-primary text-white':
                        isGroupChecked(group) || isGroupIndeterminate(group),
                    }"
                  >
                    <CheckIcon
                      v-if="isGroupChecked(group)"
                      class="h-3.5 w-3.5 text-white dark:text-black"
                    />
                    <Minus
                      v-else-if="isGroupIndeterminate(group)"
                      class="h-3.5 w-3.5 text-white dark:text-black"
                    />
                  </div>
                </label>
              </TableHead>
            </TableRow>
          </TableHeader>
        </Table>
        <Table>
          <TableBody>
            <TableRow
              v-for="perm in items"
              :key="perm.id"
              @click="togglePermission(perm.id)"
              class="cursor-pointer hover:bg-muted/40"
            >
              <TableCell class="ps-5">{{ perm.name }}</TableCell>
              <TableCell class="text-center w-16">
                <CheckboxGroup
                  :value="perm.id"
                  :model-value="modelValue"
                  @update:modelValue="(val) => emit('update:modelValue', val)"
                  @click.stop
                />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </ScrollArea>
    <InputError :message="error" />
  </div>
</template>
