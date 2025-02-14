import {expect} from "@playwright/test";

export default class FusionPage {
  /**
   * @param {import('@playwright/test').Page} page
   * @param {info: import('@playwright/test').TestInfo} info
   */
  constructor(page, info) {
    this.page = page;
    this.info = info;

    return new Proxy(this, {
      get: (target, prop, receiver) => {
        if (prop in target) {
          return Reflect.get(target, prop, receiver);
        }

        if (prop in page) {
          const pageMethod = page[prop];
          if (typeof pageMethod === 'function') {
            return pageMethod.bind(page);
          }
          return pageMethod;
        }

        return undefined;
      }
    });
  }

  /**
   * Visit the page with optional query parameters
   * @param {Object} [query] - Optional query parameters
   * @returns {Promise<void>}
   */
  async visit(query = {}) {
    return this.page.goto(this.url(query));
  }

  async see(text) {
    return expect(this.page.locator('body')).toContainText(text);
  }

  async dontSee(text) {
    return expect(this.page.locator('body')).not.toContainText(text);
  }

  /**
   * Check if the current URL has a specific query parameter
   * @param {string} name - The name of the query parameter
   * @param {string|number} [value] - Optional value to check against
   * @returns {Promise<void>}
   */
  async hasQuery(name, value) {
    const currentUrl = await this.page.url();
    const url = new URL(currentUrl);
    const params = url.searchParams;

    if (value === undefined) {
      return expect(params.has(name), `Expected URL to have query parameter '${name}'`).toBeTruthy();
    }

    const actualValue = params.get(name);
    const expectedValue = value.toString();

    return expect(
      actualValue === expectedValue,
      `Expected URL query parameter '${name}' to be '${expectedValue}', but got '${actualValue}'`
    ).toBeTruthy();
  }

  /**
   * Generate the URL for the page
   * @param {Object} [query] - Optional query parameters
   * @returns {string}
   */
  url(query = {}) {
    const path = this.info.file;
    const locator = 'extracted';
    const locatorSegment = `/${locator}/`;

    if (!path.includes(locatorSegment)) {
      throw new Error(`The file path does not contain the locator "${locator}".`);
    }

    const relativePath = path.split(locatorSegment)[1];
    const segments = relativePath.split('/');
    segments[segments.length - 1] = segments[segments.length - 1].replace(/\.spec\.js$/, '');

    const baseUrl = '/' + segments.join('/').toLowerCase();

    // If no query parameters, return the base URL
    if (!query || Object.keys(query).length === 0) {
      return baseUrl;
    }

    // Add query parameters
    const searchParams = new URLSearchParams();
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== null) {
        searchParams.append(key, value.toString());
      }
    }

    const queryString = searchParams.toString();
    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
  }
}