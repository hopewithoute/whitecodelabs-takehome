import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { getTimeEntries } from '@/api/options';

const PER_PAGE = 10;

export function useHistoryEntries(selectedCompanyId, historyRefreshKey) {
    const entries = ref([]);
    const error = ref(null);
    const isLoading = ref(false);
    const loadToken = ref(0);
    const links = ref({});
    const meta = ref({});
    const page = ref(1);
    const search = ref('');
    const sortKey = ref('entry_date');
    const sortDirection = ref('desc');
    let searchTimeout = null;

    const sortOptions = [
        { key: 'entry_date', label: 'Date' },
        { key: 'employee', label: 'Employee' },
        { key: 'project', label: 'Project' },
        { key: 'task', label: 'Task' },
        { key: 'hours', label: 'Hours' },
    ];

    // ── Computed ──────────────────────────────────────────────────

    const sortedEntries = computed(() => [...entries.value].sort(compareEntries));

    const pageHours = computed(
        () => sortedEntries.value.reduce((total, entry) => total + Number.parseFloat(entry.hours ?? 0), 0),
    );

    const summary = computed(() => meta.value.summary ?? {});

    const scopeLabel = computed(() => (selectedCompanyId.value ? 'Company scope' : 'All companies'));

    const totalEntries = computed(() => meta.value.total ?? sortedEntries.value.length);

    const totalHoursDisplay = computed(() => summary.value.total_hours ?? pageHours.value.toFixed(2));

    const summarySections = computed(() => [
        { key: 'by_company', label: 'Company', rows: summary.value.by_company ?? [] },
        { key: 'by_employee', label: 'Employee', rows: summary.value.by_employee ?? [] },
        { key: 'by_project', label: 'Project', rows: summary.value.by_project ?? [] },
        { key: 'by_task', label: 'Task', rows: summary.value.by_task ?? [] },
    ]);

    const pageLabel = computed(() => {
        const currentPage = meta.value.current_page ?? page.value;
        const lastPage = meta.value.last_page ?? 1;

        return `Page ${currentPage} of ${lastPage}`;
    });

    // ── Sorting ───────────────────────────────────────────────────

    function relatedName(entry, relation) {
        return entry[relation]?.name ?? 'Unknown';
    }

    function sortValue(entry, key) {
        if (key === 'hours') {
            return Number.parseFloat(entry.hours ?? 0);
        }

        if (key === 'entry_date') {
            return entry.entry_date ?? '';
        }

        return relatedName(entry, key).toLowerCase();
    }

    function compareEntries(first, second) {
        const firstValue = sortValue(first, sortKey.value);
        const secondValue = sortValue(second, sortKey.value);
        const direction = sortDirection.value === 'asc' ? 1 : -1;

        if (firstValue > secondValue) {
            return direction;
        }

        if (firstValue < secondValue) {
            return -direction;
        }

        return 0;
    }

    function setSort(key) {
        if (sortKey.value === key) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
            return;
        }

        sortKey.value = key;
        sortDirection.value = key === 'entry_date' || key === 'hours' ? 'desc' : 'asc';
    }

    // ── Fetching ──────────────────────────────────────────────────

    function cancelDebouncedLoad() {
        window.clearTimeout(searchTimeout);
        searchTimeout = null;
    }

    async function loadEntries() {
        cancelDebouncedLoad();

        const token = loadToken.value + 1;
        loadToken.value = token;

        isLoading.value = true;
        error.value = null;

        try {
            const result = await getTimeEntries({
                companyId: selectedCompanyId.value,
                search: search.value.trim(),
                page: page.value,
                perPage: PER_PAGE,
            });

            if (token === loadToken.value) {
                entries.value = result.entries;
                links.value = result.links;
                meta.value = result.meta;
            }
        } catch (exception) {
            if (token === loadToken.value) {
                error.value = exception;
            }
        } finally {
            if (token === loadToken.value) {
                isLoading.value = false;
            }
        }
    }

    function resetAndLoad() {
        page.value = 1;
        loadEntries();
    }

    function debounceLoad() {
        cancelDebouncedLoad();
        searchTimeout = window.setTimeout(resetAndLoad, 250);
    }

    // ── Pagination ────────────────────────────────────────────────

    function goToPage(nextPage) {
        if (nextPage < 1 || nextPage === page.value || isLoading.value) {
            return;
        }

        page.value = nextPage;
        loadEntries();
    }

    function nextPage() {
        if (!links.value.next) {
            return;
        }

        goToPage(page.value + 1);
    }

    function previousPage() {
        if (!links.value.prev) {
            return;
        }

        goToPage(page.value - 1);
    }

    // ── Lifecycle ─────────────────────────────────────────────────

    onMounted(loadEntries);
    onBeforeUnmount(cancelDebouncedLoad);
    watch(selectedCompanyId, resetAndLoad);
    watch(historyRefreshKey, resetAndLoad);
    watch(search, debounceLoad);

    return {
        // State
        entries,
        error,
        isLoading,
        links,
        meta,
        page,
        search,
        sortKey,
        sortDirection,

        // Computed
        sortedEntries,
        summary,
        scopeLabel,
        totalEntries,
        totalHoursDisplay,
        summarySections,
        pageLabel,

        // Constants
        sortOptions,

        // Methods
        loadEntries,
        setSort,
        goToPage,
        nextPage,
        previousPage,
        relatedName,
    };
}
