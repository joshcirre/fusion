import {test as baseTest} from '@playwright/test';
import FusionPage from '@pw/FusionPage.js';

const test = baseTest.extend({
  fusion: async ({page}, use, testInfo) => {
    const fusionPage = new FusionPage(page, testInfo);
    await use(fusionPage);
  },
});

export {test};