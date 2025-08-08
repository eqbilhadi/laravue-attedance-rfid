import { supabase } from '@/supabase';
import { Ref, onMounted, onUnmounted } from 'vue';

export function useSupabaseTableSync<T extends { id: any }>(
  table: string, 
  targetRef: Ref<T[]>, 
  matchKey: keyof T = 'id'
) {
  let channel: ReturnType<typeof supabase.channel> | null = null;

  onMounted(() => {
    channel = supabase
      .channel(`public:${table}`)
      .on('postgres_changes', { event: '*', schema: 'public', table }, (payload) => {
        const data = payload.new as T;
        const oldData = payload.old as T;

        switch (payload.eventType) {
          case 'INSERT':
            targetRef.value.push(data);
            break;
          case 'UPDATE':
            const updateIndex = targetRef.value.findIndex((item) => item[matchKey] === data[matchKey]);
            if (updateIndex !== -1) {
              targetRef.value[updateIndex] = data;
            }
            break;
          case 'DELETE':
            const deleteIndex = targetRef.value.findIndex((item) => item[matchKey] === oldData[matchKey]);
            if (deleteIndex !== -1) {
              targetRef.value.splice(deleteIndex, 1);
            }
            break;
        }
      })
      .subscribe();
  });

  onUnmounted(() => {
    channel?.unsubscribe();
  });
}
