import { expect, test } from '@playwright/test';

const today = new Date();
const todayDate = formatDate(today);
const todayDay = `${today.getDate()}`;
const nextDayDate = formatDate(addDays(today, 1));
const nextWeekDate = formatDate(addDays(today, 8));
const weekStartDate = formatDate(addDays(addDays(today, 8), -addDays(today, 8).getDay()));
const weekEndDate = formatDate(addDays(addDays(today, 8), 6 - addDays(today, 8).getDay()));
const nextMonthSameDayDate = formatDate(shiftMonth(parseDate(weekEndDate), 1));
const nextMonthSameDayDisplay = formatDisplayDate(parseDate(nextMonthSameDayDate));

test.beforeEach(async ({ page }) => {
    await page.goto('/entries/new');
    await expect(page.getByRole('heading', { name: 'New Entries' })).toBeVisible();
});

test('cell editor commits advance focus through company, date, and employee cells', async ({ page }) => {
    const firstRow = page.locator('tbody tr').first();

    await firstRow.getByRole('button', { name: 'Select company' }).focus();
    await page.keyboard.press('Enter');
    await page.getByRole('option', { name: 'Acme Operations' }).click();

    await expect(firstRow.getByRole('button', { name: 'Set date' })).toBeFocused();

    await page.keyboard.press('Enter');
    const selectedDay = page.locator(`[data-date="${todayDate}"]`).filter({ hasText: new RegExp(`^${todayDay}$`) });
    await expect(selectedDay).toBeFocused();

    await page.keyboard.press('ArrowRight');
    await expect(page.locator(`[data-date="${nextDayDate}"]`)).toBeFocused();

    await page.keyboard.press('ArrowDown');
    await expect(page.locator(`[data-date="${nextWeekDate}"]`)).toBeFocused();

    await page.keyboard.press('Home');
    await expect(page.locator(`[data-date="${weekStartDate}"]`)).toBeFocused();

    await page.keyboard.press('End');
    await expect(page.locator(`[data-date="${weekEndDate}"]`)).toBeFocused();

    await page.keyboard.press('PageDown');
    await expect(page.locator(`[data-date="${nextMonthSameDayDate}"]`)).toBeFocused();

    await page.keyboard.press('Enter');
    await expect(firstRow.getByRole('button', { name: nextMonthSameDayDisplay })).toBeVisible();
    await expect(firstRow.getByRole('button', { name: 'Select employee' })).toBeFocused();

    await page.keyboard.press('Enter');
    await page.getByRole('option', { name: 'Ava Chen' }).click();
    await expect(firstRow.getByRole('button', { name: 'Select project' })).toBeFocused();
});

test('tab exits an open picker into the next spreadsheet cell', async ({ page }) => {
    const firstRow = page.locator('tbody tr').first();

    await firstRow.getByRole('button', { name: 'Select company' }).focus();
    await page.keyboard.press('Enter');
    await page.getByRole('option', { name: 'Acme Operations' }).click();

    await page.keyboard.press('Enter');
    await expect(page.locator(`[data-date="${todayDate}"]`)).toBeFocused();

    await page.keyboard.press('Tab');
    await expect(firstRow.getByRole('button', { name: 'Select employee' })).toBeFocused();
});

test('spreadsheet actions are available through keyboard shortcuts', async ({ page }) => {
    const firstRow = page.locator('tbody tr').first();

    await selectSpreadsheetOption(page, firstRow, 'Select company', 'Acme Operations');
    await firstRow.getByRole('button', { name: 'Acme Operations' }).focus();

    await page.keyboard.press('Control+D');
    await expect(page.getByText('3 rows')).toBeVisible();
    await expect(page.locator('tbody tr').nth(1).getByPlaceholder('0.00')).toBeFocused();

    await page.keyboard.press('Control+Shift+Enter');
    await expect(page.getByText('4 rows')).toBeVisible();
    await expect(page.locator('tbody tr').nth(3).getByRole('button', { name: 'Select company' })).toBeFocused();
});

test('global shortcuts switch tabs and focus the spreadsheet', async ({ page }) => {
    const legend = page.getByLabel('Keyboard shortcuts');

    await expect(legend.getByText('Shortcuts')).toBeVisible();
    await expect(legend.getByText('New entries', { exact: true })).toBeVisible();
    await expect(legend.getByText('History', { exact: true })).toBeVisible();
    await expect(legend.getByText('Spreadsheet', { exact: true })).toBeVisible();

    await page.keyboard.press('Alt+H');
    await expect(page.getByRole('heading', { name: 'History' })).toBeVisible();

    await page.keyboard.press('Alt+N');
    await expect(page.getByRole('heading', { name: 'New Entries' })).toBeVisible();

    await page.keyboard.press('Alt+E');
    await expect(page.locator('tbody tr').first().getByRole('button', { name: 'Select company' })).toBeFocused();
});

test('batch submit shortcut keeps row-level validation readable', async ({ page }) => {
    const firstRow = page.locator('tbody tr').first();

    await selectSpreadsheetOption(page, firstRow, 'Select company', 'Acme Operations');
    await firstRow.getByRole('button', { name: 'Acme Operations' }).focus();

    await page.keyboard.press('Control+Enter');

    await expect(firstRow.getByText('Choose a date.')).toBeVisible();
    await expect(firstRow.getByText('Choose an employee.')).toBeVisible();
    await expect(firstRow.getByText('Choose a project.')).toBeVisible();
    await expect(firstRow.getByText('Choose a task.')).toBeVisible();
    await expect(firstRow.getByText('Enter hours.')).toBeVisible();
});

async function selectSpreadsheetOption(page, row, triggerName, optionName) {
    await row.getByRole('button', { name: triggerName }).click();
    await page.getByRole('option', { name: optionName }).click();
}

function parseDate(value) {
    const [year, month, day] = value.split('-').map((part) => Number.parseInt(part, 10));

    return new Date(year, month - 1, day);
}

function addDays(date, days) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

function shiftMonth(date, delta) {
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
