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
