import { test, expect } from '@playwright/test';

/** REQ-DEMO-013: the CRUD pages render on the UiKit shell; every delete goes through UiKit's confirm dialog (no native confirm). */
test.describe('Anonymize demo', () => {
  test('the aside lists the CRUD pages of both connections and the MongoDB collections', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByTestId('demo-platform')).toBeVisible();
    const links = page.locator('[data-nowo-ui-aside] a[data-testid^="demo-nav-"]');
    await expect(links).toHaveCount(44);
    await expect(page.getByTestId('demo-nav-user_index-default')).toBeVisible();
    await expect(page.getByTestId('demo-nav-user_index-postgres')).toBeVisible();
  });

  test('a list marks its aside entry current and a create form submits from the card footer', async ({ page }) => {
    await page.goto('/default/temp-data/');
    await expect(page.getByTestId('demo-nav-temp_data_index-default')).toHaveAttribute('aria-current', 'page');
    await expect(page.getByTestId('demo-nav-temp_data_index-postgres')).not.toHaveAttribute('aria-current', 'page');
    await page.getByRole('link', { name: /^Create New/ }).click();
    await expect(page.locator('form#demo-entity-form')).toHaveClass(/nowo-ui-form/);
    await expect(page.locator('button[form="demo-entity-form"][type="submit"]')).toHaveCount(1);
  });

  test('delete is confirmed in UiKit\'s dialog: Cancel keeps the row, Delete removes it', async ({ page }) => {
    const native: string[] = [];
    page.on('dialog', (dialog) => {
      native.push(dialog.message());
      void dialog.dismiss();
    });
    await page.goto('/default/temp-data/');
    const rows = page.locator('tbody tr');
    const before = await rows.count();
    expect(before).toBeGreaterThan(0);

    await rows.first().getByRole('button', { name: 'Delete' }).first().click();
    const dialog = page.locator('dialog[open]');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Cancel' })).toBeFocused();
    await dialog.getByRole('button', { name: 'Cancel' }).click();
    await expect(page.locator('dialog[open]')).toHaveCount(0);
    await expect(rows).toHaveCount(before);

    await rows.first().getByRole('button', { name: 'Delete' }).first().click();
    await page.locator('dialog[open]').getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByTestId('demo-platform-flashes')).toContainText('deleted successfully');
    await expect(rows).toHaveCount(before - 1);
    expect(native).toEqual([]);
  });
});
