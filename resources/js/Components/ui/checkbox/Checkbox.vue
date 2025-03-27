<script setup>
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui';
import { computed } from 'vue';

const props = defineProps({
  defaultValue: { type: [Boolean, String], required: false },
  modelValue: { type: [Boolean, String, null], required: false },
  disabled: { type: Boolean, required: false },
  value: { type: [String, Number, Object, null], required: false },
  id: { type: String, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  name: { type: String, required: false },
  required: { type: Boolean, required: false },
  class: { type: null, required: false },
});
const emits = defineEmits(['update:modelValue']);

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <CheckboxRoot
    v-bind="forwarded"
    :class="
      cn(
        'peer h-4 w-4 shrink-0 rounded-sm border border-neutral-200 border-neutral-900 shadow focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-neutral-950 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-neutral-900 data-[state=checked]:text-neutral-50 dark:border-neutral-800 dark:border-neutral-50 dark:focus-visible:ring-neutral-300 dark:data-[state=checked]:bg-neutral-50 dark:data-[state=checked]:text-neutral-900',
        props.class,
      )
    "
  >
    <CheckboxIndicator
      class="flex h-full w-full items-center justify-center text-current"
    >
      <slot>
        <Check class="h-4 w-4" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
