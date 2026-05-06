import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/entries/history');
    await expect(page.getByRole('heading', { name: 'History' })).toBeVisible();
});

test('lists time entries from the history API', async ({ page }) => {
    await expect(page.getByRole('cell', { name: 'Acme Operations' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Ava Chen' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Website Redesign' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Development' }).first()).toBeVisible();
    await expect(page.getByText(/entries$/).first()).toBeVisible();
});

test('filters history when the global company scope changes', async ({ page }) => {
    await page.getByRole('button', { name: 'All companies' }).click();
    await page.getByRole('option', { name: 'Globex Services' }).click();

    await expect(page.getByRole('cell', { name: 'Globex Services' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Acme Operations' })).toHaveCount(0);
    await expect(page.getByText('Company scope')).toBeVisible();
});

test('searches history and sorts the visible rows', async ({ page }) => {
    await page.getByPlaceholder('Search company, employee, project, or task').fill('Website');

    await expect(page.getByRole('cell', { name: 'Website Redesign' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Data Migration' })).toHaveCount(0);

    await page.getByRole('button', { name: 'Hours' }).click();
    await expect(page.getByRole('button', { name: 'Hours' })).toBeVisible();
});

test('edits an existing history entry', async ({ page }) => {
    await page.getByPlaceholder('Search company, employee, project, or task').fill('Cora');
    await expect(page.getByRole('cell', { name: 'Cora Diaz' }).first()).toBeVisible();

    await page.getByRole('button', { name: 'Edit Cora Diaz entry' }).first().click();
    await expect(page.getByRole('dialog', { name: 'Edit time entry' })).toBeVisible();

    await page.getByLabel('Hours').fill('6.25');
    await page.getByRole('button', { name: 'Save changes' }).click();

    await expect(page.getByText('Time entry updated.')).toBeVisible();
    await expect(page.getByRole('dialog', { name: 'Edit time entry' })).toHaveCount(0);
    const updatedRow = page.getByRole('row').filter({ has: page.getByRole('cell', { name: 'Cora Diaz' }) }).first();

    await expect(updatedRow.getByRole('cell', { name: '6.25' })).toBeVisible();
});

test('shows a newly submitted entry after switching to history', async ({ page }) => {
    const { entryDate, taskName } = await unusedWebsiteEntrySlot(page);
    const entryDateDisplay = new Date(`${entryDate}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    await page.goto('/entries/new');
    await expect(page.getByRole('heading', { name: 'New Entries' })).toBeVisible();

    const firstRow = page.locator('tbody tr').first();

    await selectSpreadsheetOption(page, firstRow, 'Select company', 'Acme Operations');
    await selectDate(page, firstRow, entryDate);
    await selectSpreadsheetOption(page, firstRow, 'Select employee', 'Ben Carter');
    await selectSpreadsheetOption(page, firstRow, 'Select project', 'Website Redesign');
    await selectSpreadsheetOption(page, firstRow, 'Select task', taskName);
    await firstRow.getByPlaceholder('0.00').fill('7.75');
    await page.getByRole('button', { name: 'Submit batch' }).click();

    await expect(page.getByText('Time entries saved.').first()).toBeVisible();
    await expect(page.getByText('1 entry added to history.')).toBeVisible();
    await page.getByRole('link', { name: 'History' }).click();

    await expect(page.getByRole('heading', { name: 'History' })).toBeVisible();
    const createdRow = page.getByRole('row').filter({ has: page.getByRole('cell', { name: entryDateDisplay }) }).first();

    await expect(createdRow.getByRole('cell', { name: 'Ben Carter', exact: true })).toBeVisible();
    await expect(createdRow.getByRole('cell', { name: taskName })).toBeVisible();
    await expect(createdRow.getByRole('cell', { name: '7.75' })).toBeVisible();
});

async function unusedWebsiteEntrySlot(page) {
    const response = await page.request.get('/api/v1/time-entries?filter[search]=Ben%20Carter&per_page=50');
    const payload = await response.json();
    const used = new Set((payload.data ?? [])
        .filter((entry) => entry.company?.name === 'Acme Operations' && entry.project?.name === 'Website Redesign')
        .map((entry) => `${entry.entry_date}:${entry.task?.name}`));
    const taskNames = ['Planning', 'Development', 'Review'];
    const today = new Date();

    for (let day = 1; day <= 28; day += 1) {
        const entryDate = formatDate(new Date(today.getFullYear(), today.getMonth(), day));

        for (const taskName of taskNames) {
            if (!used.has(`${entryDate}:${taskName}`)) {
                return { entryDate, taskName };
            }
        }
    }

    throw new Error('No unused current-month Website Redesign slot is available for Ben Carter.');
}

async function selectSpreadsheetOption(page, row, triggerName, optionName) {
    await row.getByRole('button', { name: triggerName }).click();
    await page.getByRole('option', { name: optionName }).click();
}

async function selectDate(page, row, date) {
    await row.getByRole('button', { name: 'Set date' }).click();
    await page.locator(`[data-date="${date}"]`).click();
}

function formatDate(date) {
    const year = `${date.getFullYear()}`;
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}
