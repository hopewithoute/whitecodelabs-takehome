<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps({
    active: { type: Boolean, default: false },
    error: { type: [String, null], default: null },
    modelValue: { type: [String, null], default: null },
    placeholder: { type: String, required: true },
    closeSignal: { type: Number, default: 0 },
    triggerVariant: { type: String, default: 'cell' },
    triggerClass: { type: String, default: '' },
});

const emit = defineEmits(['commit', 'focus', 'keydown', 'navigate', 'select']);

const open = ref(false);
const trigger = ref(null);
const calendar = ref(null);
const visibleMonth = ref(startOfMonth(parseDate(props.modelValue) ?? new Date()));

const selectedDate = computed(() => parseDate(props.modelValue));
const selectedLabel = computed(() => selectedDate.value ? formatDisplayDate(selectedDate.value) : props.placeholder);
const monthLabel = computed(() => visibleMonth.value.toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric',
}));

const calendarDays = computed(() => {
    const firstDay = startOfMonth(visibleMonth.value);
    const gridStart = addDays(firstDay, -firstDay.getDay());

    return Array.from({ length: 42 }, (value, index) => {
        const date = addDays(gridStart, index);

        return {
            date,
            key: formatDate(date),
            inMonth: date.getMonth() === visibleMonth.value.getMonth(),
            isSelected: props.modelValue === formatDate(date),
            isToday: formatDate(date) === formatDate(new Date()),
        };
    });
});

function focus() {
    if (typeof trigger.value?.focus === 'function') {
        trigger.value.focus();
        return;
    }

    trigger.value?.$el?.focus();
}

async function openPicker() {
    open.value = true;
    visibleMonth.value = startOfMonth(selectedDate.value ?? new Date());

    await nextTick();
    focusSelectedDay();
}

function closePicker() {
    open.value = false;
    focus();
}

async function selectDate(date) {
    emit('select', formatDate(date));
    open.value = false;

    await nextTick();
    emit('commit');
}

async function moveMonth(delta, focusDate = null) {
    const targetDate = focusDate ? sameDayInShiftedMonth(focusDate, delta) : null;
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + delta, 1);

    await nextTick();
    targetDate ? focusDateButton(targetDate) : focusSelectedDay();
}

function handleTriggerKeydown(event) {
    if (!['Enter', ' ', 'F2'].includes(event.key)) {
        emit('keydown', event);
        return;
    }

    event.preventDefault();
    openPicker();
}

function handleCalendarKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        closePicker();
        return;
    }

    if (event.key === 'Tab') {
        event.preventDefault();
        open.value = false;
        emit('navigate', event.shiftKey ? -1 : 1);
        return;
    }

    const movements = {
        ArrowLeft: -1,
        ArrowRight: 1,
        ArrowUp: -7,
        ArrowDown: 7,
    };

    if (!(event.key in movements)) {
        if (event.key === 'Home') {
            event.preventDefault();
            focusDayButton(event.target, -getDayButtonIndex(event.target) % 7);
            return;
        }

        if (event.key === 'End') {
            event.preventDefault();
            focusDayButton(event.target, 6 - (getDayButtonIndex(event.target) % 7));
            return;
        }

        if (event.key === 'PageUp') {
            event.preventDefault();
            moveMonth(event.shiftKey ? -12 : -1, parseButtonDate(event.target));
            return;
        }

        if (event.key === 'PageDown') {
            event.preventDefault();
            moveMonth(event.shiftKey ? 12 : 1, parseButtonDate(event.target));
            return;
        }

        return;
    }

    event.preventDefault();
    focusDayButton(event.target, movements[event.key]);
}

function focusSelectedDay() {
    const selector = props.modelValue
        ? `[data-date="${props.modelValue}"]`
        : '[data-today="true"]';
    const target = calendar.value?.querySelector(selector)
        ?? calendar.value?.querySelector('[data-date]');

    target?.focus();
}

function focusDayButton(currentButton, delta) {
    const buttons = [...calendar.value.querySelectorAll('[data-date]')];
    const index = buttons.indexOf(currentButton);
    const nextButton = buttons[index + delta];

    if (nextButton) {
        nextButton.focus();
        return;
    }

    const currentDate = parseButtonDate(currentButton);

    if (!currentDate) {
        return;
    }

    const nextDate = addDays(currentDate, delta);
    visibleMonth.value = startOfMonth(nextDate);

    nextTick(() => focusDateButton(nextDate));
}

function focusDateButton(date) {
    calendar.value?.querySelector(`[data-date="${formatDate(date)}"]`)?.focus();
}

function getDayButtonIndex(button) {
    return [...calendar.value.querySelectorAll('[data-date]')].indexOf(button);
}

function parseButtonDate(button) {
    return parseDate(button?.getAttribute('data-date'));
}

function parseDate(value) {
    if (!value || !/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value)) {
        return null;
    }

    const [year, month, day] = value.split('-').map((part) => parseInt(part, 10));
    const date = new Date(year, month - 1, day);

    if (formatDate(date) !== value) {
        return null;
    }

    return date;
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addDays(date, days) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

function sameDayInShiftedMonth(date, delta) {
    const targetMonthStart = new Date(date.getFullYear(), date.getMonth() + delta, 1);
    const lastDay = new Date(targetMonthStart.getFullYear(), targetMonthStart.getMonth() + 1, 0).getDate();

    return new Date(targetMonthStart.getFullYear(), targetMonthStart.getMonth(), Math.min(date.getDate(), lastDay));
}

function formatDate(date) {
    const year = `${date.getFullYear()}`;
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatDisplayDate(date) {
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

watch(
    () => props.modelValue,
    (value) => {
        const date = parseDate(value);

        if (date) {
            visibleMonth.value = startOfMonth(date);
        }
    },
);

watch(
    () => props.closeSignal,
    () => {
        open.value = false;
    },
);

defineExpose({ focus });
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                ref="trigger"
                type="button"
                :variant="triggerVariant"
                size="cell"
                data-editor="trigger"
                :data-active="active"
                :aria-invalid="Boolean(error)"
                :aria-expanded="open"
                :class="cn('justify-between', triggerClass)"
                @focus="emit('focus')"
                @keydown="handleTriggerKeydown"
            >
                <span :class="cn('truncate', modelValue ? 'text-foreground' : 'text-muted-foreground')">
                    {{ selectedLabel }}
                </span>
                <CalendarDays class="text-muted-foreground" />
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-72 p-2" align="start" @close-auto-focus.prevent>
            <div class="mb-2 flex items-center justify-between">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="moveMonth(-1)"
                >
                    <ChevronLeft />
                </Button>
                <div class="text-sm font-medium">{{ monthLabel }}</div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="moveMonth(1)"
                >
                    <ChevronRight />
                </Button>
            </div>

            <div class="grid grid-cols-7 gap-1 px-1 pb-1 text-center text-xs text-muted-foreground">
                <span>Su</span>
                <span>Mo</span>
                <span>Tu</span>
                <span>We</span>
                <span>Th</span>
                <span>Fr</span>
                <span>Sa</span>
            </div>

            <div ref="calendar" class="grid grid-cols-7 gap-1" @keydown="handleCalendarKeydown">
                <button
                    v-for="day in calendarDays"
                    :key="day.key"
                    type="button"
                    :data-date="day.key"
                    :data-today="day.isToday"
                    :class="cn(
                        'flex size-8 items-center justify-center rounded-md border border-transparent text-sm outline-none transition focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
                        day.inMonth ? 'text-foreground hover:bg-accent' : 'text-muted-foreground/45 hover:bg-accent/60',
                        day.isSelected ? 'border-primary/60 bg-primary text-primary-foreground hover:bg-primary' : '',
                        day.isToday && !day.isSelected ? 'border-border text-[#c6cbff]' : '',
                    )"
                    @click="selectDate(day.date)"
                >
                    {{ day.date.getDate() }}
                </button>
            </div>
        </PopoverContent>
    </Popover>
</template>
