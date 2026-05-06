import { onMounted, ref } from 'vue';
import { getCompanies } from '@/api/options';

export function useCompanies() {
    const companies = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    async function loadCompanies() {
        isLoading.value = true;
        error.value = null;

        try {
            companies.value = await getCompanies();
        } catch (exception) {
            error.value = exception;
        } finally {
            isLoading.value = false;
        }
    }

    onMounted(loadCompanies);

    return {
        companies,
        error,
        isLoading,
        loadCompanies,
    };
}
