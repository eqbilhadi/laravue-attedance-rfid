<script setup lang="ts">
import { ref, computed } from "vue";
import { CheckIcon, Minus } from "lucide-vue-next";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import ScrollArea from "@/components/ui/scroll-area/ScrollArea.vue";
import CheckboxGroup from "@/components/rbac/CheckboxGroup.vue";
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";

const props = defineProps<{
  menus: {
    id: number;
    label_name: string;
    link: string;
    children?: { id: number; label_name: string; link: string }[];
  }[];
  modelValue: number[];
  error?: string;
}>();

const emit = defineEmits(["update:modelValue"]);
const menuSearch = ref("");

const groupedMenus = computed(() => {
  const result: Record<string, typeof props.menus> = {};
  const searchTerm = menuSearch.value.toLowerCase();

  props.menus.forEach((parent) => {
    if (parent.children?.length) {
      const filteredChildren = parent.children.filter((child) =>
        child.label_name.toLowerCase().includes(searchTerm)
      );
      
      const parentMatch = parent.label_name.toLowerCase().includes(searchTerm);
      
      if (parentMatch || filteredChildren.length > 0) {
        result[parent.label_name] = parentMatch ? parent.children : filteredChildren;
      }
    } else if (parent.label_name.toLowerCase().includes(searchTerm)) {
      result[parent.label_name] = [parent];
    }
  });

  return result;
});

const allVisibleIds = computed(() => 
  Object.values(groupedMenus.value).flat().map(p => p.id)
);

const allVisibleSelected = computed(() => 
  allVisibleIds.value.every(id => props.modelValue.includes(id))
);

const getGroupStats = (group: string) => {
  const items = groupedMenus.value[group] ?? [];
  const checkedCount = items.filter(m => props.modelValue.includes(m.id)).length;
  return {
    hasAny: checkedCount > 0,
    isIndeterminate: checkedCount > 0 && checkedCount < items.length,
    isFullyChecked: checkedCount === items.length && items.length > 0
  };
};

const updateModelValue = (newValue: number[]) => {
  const result = [...newValue];
  
  props.menus.forEach(parent => {
    if (parent.children?.length) {
      const hasAnyChild = parent.children.some(child => result.includes(child.id));
      
      if (hasAnyChild && !result.includes(parent.id)) {
        result.push(parent.id);
      } else if (!hasAnyChild && result.includes(parent.id)) {
        result.splice(result.indexOf(parent.id), 1);
      }
    }
  });
  
  emit("update:modelValue", result);
};

const toggleMenu = (id: number) => {
  const newValue = props.modelValue.includes(id)
    ? props.modelValue.filter(i => i !== id)
    : [...props.modelValue, id];
  updateModelValue(newValue);
};

const toggleGroup = (group: string, checked: boolean) => {
  const ids = (groupedMenus.value[group] ?? []).map(m => m.id);
  const newValue = checked
    ? [...new Set([...props.modelValue, ...ids])]
    : props.modelValue.filter(id => !ids.includes(id));
  updateModelValue(newValue);
};

const toggleAll = () => {
  const newValue = allVisibleSelected.value
    ? props.modelValue.filter(id => !allVisibleIds.value.includes(id))
    : [...new Set([...props.modelValue, ...allVisibleIds.value])];
  updateModelValue(newValue);
};
</script>

<template>
  <div class="w-full rounded-md border p-4">
    <div class="mb-2">
      <h3 class="text-sm font-semibold">Accessible Menus</h3>
      <p class="text-sm text-muted-foreground">
        Determine which menus this role can access in the application.
      </p>
    </div>
    
    <div class="flex justify-start mb-3 gap-2">
      <input
        v-model="menuSearch"
        placeholder="Search menu..."
        class="px-2 py-1 text-sm border rounded-md w-full"
      />
      <Button @click="toggleAll" class="text-sm h-8" type="button">
        {{ allVisibleSelected ? "Unselect All" : "Select All" }}
      </Button>
    </div>
    
    <ScrollArea class="h-[300px] mb-4">
      <div v-for="(children, parentName) in groupedMenus" :key="parentName">
        <Table>
          <TableHeader v-if="children.length > 1 || children[0].label_name !== parentName">
            <TableRow>
              <th colspan="2" class="text-muted-foreground h-10 px-2 text-left align-middle font-medium whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]">
                <label class="flex items-center gap-2 cursor-pointer select-none justify-between">
                  <span class="font-bold text-sm text-foreground">{{ parentName }}</span>
                  <input
                    type="checkbox"
                    class="peer hidden"
                    :checked="getGroupStats(parentName).hasAny"
                    @change="e => toggleGroup(parentName, (e.target as HTMLInputElement).checked)"
                  />
                  <div
                    class="h-4 w-4 shrink-0 rounded-sm border border-primary shadow flex items-center justify-center transition-colors me-8"
                    :class="{ 'bg-primary text-primary-foreground': getGroupStats(parentName).hasAny }"
                  >
                    <CheckIcon
                      v-if="getGroupStats(parentName).isFullyChecked"
                      class="h-3.5 w-3.5 text-white dark:text-black"
                    />
                    <Minus
                      v-else-if="getGroupStats(parentName).isIndeterminate"
                      class="h-3.5 w-3.5 text-white dark:text-black"
                    />
                  </div>
                </label>
              </th>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="child in children"
              :key="child.id"
              @click="toggleMenu(child.id)"
              class="cursor-pointer hover:bg-muted/40 !border-b"
            >
              <TableCell :class="{ 'ps-5': children.length > 1 || children[0].label_name !== parentName }">
                {{ child.label_name }}
              </TableCell>
              <TableCell class="text-center w-16">
                <CheckboxGroup
                  :value="child.id"
                  :model-value="modelValue"
                  @update:modelValue="updateModelValue"
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
